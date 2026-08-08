<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

final class AIService
{
    private string $apiUrl;

    private string $apiKey;

    private string $model;

    private int $userId = 0;

    public function __construct(?int $userId = null)
    {
        $this->userId = $userId ?? auth()->id() ?? 0;
        $settings = $this->loadUserSettings();

        $this->apiUrl = $settings['api_url'] ?? config('services.ai.api_url', 'https://api.openai.com/v1');
        $this->apiKey = $settings['api_key'] ?? config('services.ai.api_key', '');
        $this->model = $settings['model'] ?? config('services.ai.model', 'gpt-4o-mini');
    }

    public function initializeForUser(int $userId): void
    {
        $this->userId = $userId;
        $settings = $this->loadUserSettings();
        $this->apiUrl = $settings['api_url'] ?? config('services.ai.api_url', 'https://api.openai.com/v1');
        $this->apiKey = $settings['api_key'] ?? config('services.ai.api_key', '');
        $this->model = $settings['model'] ?? config('services.ai.model', 'gpt-4o-mini');
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    public function summarize(string $content): ?string
    {
        $prompt = "Anda adalah asisten pengetahuan ahli. Ringkas konten berikut dalam 2-3 kalimat.
Fokus pada poin utama yang paling berharga.
Gunakan Bahasa Indonesia yang ringkas dan jelas.
Jangan tambahkan informasi yang tidak ada di konten asli.";

        return $this->ask($prompt, $content, 500);
    }

    public function categorize(string $title, ?string $content): ?string
    {
        $text = "Judul: {$title}";
        if ($content) {
            $text .= "\n\nKonten:\n".mb_substr($content, 0, 3000);
        }

        $prompt = "Analisis konten berikut dan tentukan SATU kategori yang paling tepat.

Kategori yang tersedia: Technology, Science, Design, Business, Health, Education, Entertainment, News, Reference, Other.

Panduan memilih kategori:
- Technology: Pemrograman, software, hardware, AI, blockchain, gadgets
- Science: Penelitian, sains, kedokteran, lingkungan
- Design: UI/UX, grafis, arsitektur, branding
- Business: Marketing, startup, keuangan, manajemen
- Health: Kesehatan, olahraga, nutrisi, psikologi
- Education: Pembelajaran, kursus, tutorial, akademik
- Entertainment: Film, musik, game, hiburan
- News: Berita terkini, politik, sosial
- Reference: Dokumentasi, referensi, kamus, panduan

Balas HANYA nama kategori, tanpa penjelasan tambahan.";

        return $this->ask($prompt, $text, 50);
    }

    public function suggestTags(string $title, ?string $content): array
    {
        $text = "Judul: {$title}";
        if ($content) {
            $text .= "\n\nKonten:\n".mb_substr($content, 0, 3000);
        }

        $prompt = "Buatkan 3-5 tag yang relevan untuk konten ini.

Aturan:
- Tag harus spesifik dan deskriptif
- Gunakan lowercase
- Pisahkan dengan koma
- Jangan gunakan tag yang terlalu umum seperti 'internet' atau 'website'
- Contoh bagus: 'laravel', 'react', 'seo', 'machine-learning'

Balas HANYA tag yang dipisahkan koma, tanpa nomor atau bullet.";

        $result = $this->ask($prompt, $text, 150);

        if ($result === null) {
            return [];
        }

        return array_map(
            fn (string $tag) => trim(strtolower($tag)),
            array_filter(explode(',', $result))
        );
    }

    public function renderPage(string $title, string $url, ?string $content = null): ?string
    {
        $text = "Judul: {$title}\nURL: {$url}";
        if ($content) {
            $text .= "\n\nKonten:\n".mb_substr($content, 0, 8000);
        }

        $prompt = "Anda adalah asisten pengetahuan ahli. Analisis halaman web berikut dan buat catatan komprehensif.

Format output dalam Markdown yang bersih:

## Ringkasan
(2-3 kalimat tentang apa halaman ini)

## Poin-Poin Utama
- (poin 1)
- (poin 2)
- (poin 3)

## Detail Penting
- Nama, tanggal, angka, atau link yang disebutkan
- Konteks atau latar belakang yang relevan

## Evaluasi
- Seberapa berguna konten ini (1-5 bintang)
- Siapa yang cocok membaca ini
- Rekomendasi tindakan lanjutan

Jadilah detail namun ringkas. Gunakan Bahasa Indonesia.";

        return $this->ask($prompt, $text, 800);
    }

    public function organizeBookmarks(array $bookmarks): ?array
    {
        if (! $this->isConfigured() || empty($bookmarks)) {
            return null;
        }

        $list = '';
        foreach ($bookmarks as $i => $b) {
            $list .= ($i + 1).". [{$b['title']}] {$b['url']}\n";
        }

        $prompt = "Anda adalah bookmark organizer ahli. Analisis bookmark berikut dan untuk SETIAP satu (berdasarkan nomor), berikan:
- category: salah satu dari Technology, SEO, Business, Marketing, Design, Education, News, Entertainment, Reference, Other
- tags: 2-4 tag relevan dipisahkan koma
- action: keep jika bookmark berguna, remove jika spam/dead/low-quality
- summary: ringkasan singkat 1 kalimat

Balas sebagai JSON array valid saja, tanpa markdown. Setiap item: {\"id\": <number>, \"category\": \"...\", \"tags\": \"...\", \"action\": \"keep\"|\"remove\", \"summary\": \"...\"}";

        $result = $this->ask($prompt, $list, 1500);

        if ($result === null) {
            return null;
        }

        $result = trim($result);
        $result = preg_replace('/```json\s*/', '', $result);
        $result = preg_replace('/```\s*$/', '', $result);

        $decoded = json_decode($result, true);

        return is_array($decoded) ? $decoded : null;
    }

    public function batchOrganize(array $bookmarks, int $batchSize = 20): ?array
    {
        $all = [];
        $chunks = array_chunk($bookmarks, $batchSize);

        foreach ($chunks as $chunk) {
            $result = $this->organizeBookmarks($chunk);
            if ($result) {
                $all = array_merge($all, $result);
            }
        }

        return empty($all) ? null : $all;
    }

    public function getSettings(): array
    {
        return $this->loadUserSettings();
    }

    public function askRaw(string $systemPrompt, string $content, int $maxTokens = 300): ?string
    {
        return $this->ask($systemPrompt, $content, $maxTokens);
    }

    private function ask(string $systemPrompt, string $content, int $maxTokens = 300): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $url = rtrim($this->apiUrl, '/').'/chat/completions';

            $response = Http::timeout(30)
                ->connectTimeout(5)
                ->withToken($this->apiKey)
                ->post($url, [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $content],
                    ],
                    'max_tokens' => $maxTokens,
                    'temperature' => 0.3,
                    'stream' => false,
                ]);

            if (! $response->successful()) {
                logger()->warning('AI API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $text = $response->json('choices.0.message.content');

            return $text ? trim($text) : null;
        } catch (\Exception $e) {
            logger()->error('AI Service failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    private function loadUserSettings(): array
    {
        $cacheKey = "ai_settings_{$this->userId}";

        return Cache::remember($cacheKey, 300, function () {
            $path = storage_path("app/user-settings-{$this->userId}.json");

            if (file_exists($path)) {
                $all = json_decode(file_get_contents($path), true) ?? [];

                return $all['ai'] ?? [];
            }

            return [];
        });
    }
}
