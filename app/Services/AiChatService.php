<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Item;
use Illuminate\Support\Facades\Cache;

final class AiChatService
{
    private AIService $ai;

    private WebSearchService $search;

    private int $userId;

    public function __construct(int $userId)
    {
        $this->userId = $userId;
        $this->ai = new AIService($userId);
        $this->search = new WebSearchService($userId);
    }

    public function isConfigured(): bool
    {
        return $this->ai->isConfigured();
    }

    public function chat(string $message, array $history = []): string
    {
        if (! $this->ai->isConfigured()) {
            return 'AI belum dikonfigurasi. Silakan atur API key di Settings.';
        }

        $lower = strtolower(trim($message));

        if (str_starts_with($lower, 'search ') || str_starts_with($lower, 'cari ') || str_starts_with($lower, 'google ') || str_starts_with($lower, '?')) {
            $query = preg_replace('/^(search |cari |google |\?)/i', '', $message);

            return $this->searchAndAnswer($query);
        }

        if (in_array($lower, ['ringkasan', 'summary', 'overview', 'semua data', 'all data'])) {
            return $this->generateOverview();
        }

        if (str_starts_with($lower, 'notulensi') || str_starts_with($lower, 'meeting') || str_starts_with($lower, 'rapat')) {
            $text = preg_replace('/^(notulensi |meeting |rapat )/i', '', $message);

            return $this->generateNotulensi($text);
        }

        $context = $this->buildContext();

        $systemPrompt = 'Anda adalah Clips AI, asisten ahli manajemen pengetahuan pribadi.

IDENTITAS:
- Nama: Clips AI
- Peran: Asisten pribadi yang membantu mengelola knowledge base pengguna
- Bahasa: Selalu jawab dalam bahasa yang sama dengan yang digunakan pengguna (Indonesia/English)

KEMAMPUAN:
1. Menganalisis bookmark, catatan, snippet, worksheet, todo, dan prompt pengguna
2. Menjawab pertanyaan tentang data yang tersimpan
3. Memberikan sorganisasi dan produktivitas
4. Mencari informasi di web jika diminta (prefix search atau cari)
5. Membuat ringkasan meeting/notulensi

ATURAN:
- Jawab dengan ringkas, langsung ke inti
- Rujuk item spesifik dari data pengguna jika relevan (sebutkan ID atau judul)
- Jika tidak tahu, katakan jangan mengarang
- Gunakan bullet points untuk daftar
- Format markdown untuk respons panjang

CONTOH RESPONS YANG BAIK:
User: Apa bookmark tentang programming?
AI: Anda memiliki 5 bookmark programming:
1. [ID 12] Laravel Documentation - url
2. [ID 15] React Hooks Guide - url
...
Mau saya bantu filter lebih spesifik?

User: Ringkas semua data saya
AI: 📊 Ringkasan Knowledge Base Anda:
• 45 Bookmarks (12 favorites)
• 23 Notes
• 8 Snippets
• 12 Todos (5 selesai)

Topik utama: Web Development, AI/ML, Design
Saran: Pertimbangkan untuk menambah tag pada 15 bookmark yang belum punya tag.';

        $historyText = '';
        if (! empty($history)) {
            $historyText = "\n\nRiwayat percakapan:\n";
            foreach (array_slice($history, -8) as $msg) {
                $role = $msg['role'] === 'user' ? 'Pengguna' : 'AI';
                $historyText .= "{$role}: {$msg['content']}\n";
            }
        }

        $fullMessage = "Konteks knowledge base pengguna:\n{$context}{$historyText}\n\nPertanyaan pengguna: {$message}";

        try {
            $reply = $this->ai->askRaw($systemPrompt, $fullMessage, 1200);

            return $reply ?? 'Tidak ada response dari AI. Coba lagi atau cek Settings.';
        } catch (\Exception $e) {
            return 'Error: '.$e->getMessage();
        }
    }

    private function searchAndAnswer(string $query): string
    {
        $results = $this->search->search($query, 5);

        $kbContext = $this->buildContext();

        if (! empty($results)) {
            $context = "Hasil pencarian web untuk: {$query}\n\n";
            foreach ($results as $i => $r) {
                $context .= ($i + 1).". {$r['title']}\n   URL: {$r['url']}\n   Ringkasan: {$r['snippet']}\n\n";
            }

            $prompt = "Berdasarkan hasil pencarian web DAN knowledge base pengguna, jawab pertanyaan ini: {$query}

ATURAN:
1. Gabungkan informasi dari web dan knowledge base
2. Prioritaskan informasi terbaru dan relevan
3. Sebutkan sumber (web atau knowledge base)
4. Jawab dalam bahasa yang sama dengan pertanyaan
5. Format dengan markdown yang rapi

Hasil pencarian web:
{$context}

Knowledge base pengguna:
{$kbContext}

Buatlah jawaban komprehensif yang menggabungkan kedua sumber. Gunakan heading untuk struktur yang jelas.";

            return $this->ai->askRaw($prompt, $query, 1000) ?? 'Gagal generate jawaban.';
        }

        $prompt = "Pengguna bertanya: {$query}

Berdasarkan knowledge base pengguna di bawah, berikan jawaban terbaik yang bisa Anda berikan.
Jika knowledge base tidak mengandung informasi yang relevan, katakan dengan jujur.

ATURAN:
1. Jawab dalam bahasa yang sama dengan pertanyaan
2. Rujuk item spesifik jika ada yang relevan
3. Berikan saran action items jika memungkinkan
4. Gunakan markdown untuk struktur yang jelas

Knowledge base pengguna:
{$kbContext}

Berdasarkan data di atas, berikan jawaban yang paling membantu.";

        return $this->ai->askRaw($prompt, $query, 1000) ?? 'Gagal generate jawaban. Pastikan AI sudah dikonfigurasi di Settings.';
    }

    private function generateOverview(): string
    {
        $context = $this->buildContext();

        $prompt = 'Anda adalah asisten manajemen pengetahuan. Analisis knowledge base pengguna dan berikan:

📊 **Ringkasan Data**
- Jumlah item per kategori
- Pertumbuhan/aktifitas terkini

🎯 **Topik Utama**
- Tema-tema yang paling banyak dibahas
- Pola content yang menarik

💡 **Saran Organisasi**
- Bagaimana cara mengorganisir lebih baik
- Tag atau kategori yang perlu ditambahkan

🔍 **Temuan Menarik**
- Pattern atau insight yang ditemukan
- Item yang mungkin perlu di-update

Gunakan emoji untuk visual yang menarik. Format markdown. Jawab dalam Bahasa Indonesia. Bersifat actionable dan spesifik.';

        return $this->ai->askRaw($prompt, $context, 800) ?? 'Gagal generate overview.';
    }

    private function generateNotulensi(string $meetingText): string
    {
        if (empty($meetingText)) {
            return 'Mohon masukkan teks notulensi/rapat. Contoh: notulensi [paste teks rapat]';
        }

        $prompt = 'Anda adalah asisten notulensi rapat ahli. Konversi teks mentah rapat berikut menjadi notulensi terstruktur.

Format wajib:

📋 **NOTULENSI RAPAT**

**Tanggal:** (ekstrak tanggal jika disebut, atau gunakan hari ini)
**Peserta:** (ekstrak nama jika disebutkan)
**Topik Utama:** (topik utama rapat)

---

**Ringkasan Eksekutif**
(1-2 kalimat tentang hasil rapat)

**Poin Pembahasan:**
1. **[Judul Topik 1]**
   - Detail: ...
   - Poin penting: ...

2. **[Judul Topik 2]**
   - Detail: ...
   - Poin penting: ...

**Keputusan yang Diambil:**
- ✅ (keputusan 1)
- ✅ (keputusan 2)

**Action Items:**
- [ ] (tugas 1) — Responsible: (nama) — Deadline: (tanggal)
- [ ] (tugas 2) — Responsible: (nama) — Deadline: (tanggal)

**Catatan Tambahan:**
(poin tambahan jika ada)

---
*Dicatat oleh: Clips AI*

Buatlah notulensi yang terstruktur, mudah dibaca, dan actionable. Gunakan Bahasa Indonesia.';

        return $this->ai->askRaw($prompt, $meetingText, 1500) ?? 'Gagal generate notulensi.';
    }

    private function buildContext(): string
    {
        $userId = $this->userId;

        $cacheKey = "ai_context_{$userId}";

        return Cache::remember($cacheKey, 300, function () use ($userId) {
            $items = Item::where('user_id', $userId)
                ->select('id', 'type', 'title', 'url', 'content', 'metadata')
                ->latest()
                ->get()
                ->groupBy('type');

            $ctx = "=== KNOWLEDGE BASE SUMMARY ===\n";
            $counts = [];
            foreach ($items as $type => $typeItems) {
                $counts[$type] = $typeItems->count();
            }
            $ctx .= 'Total: '.array_sum($counts)." items\n";
            foreach ($counts as $type => $count) {
                $ctx .= ucfirst($type).": {$count} items\n";
            }
            $ctx .= "\n";

            if (isset($items['bookmark']) && $items['bookmark']->isNotEmpty()) {
                $ctx .= "--- CLIPS ---\n";
                foreach ($items['bookmark']->take(30) as $b) {
                    $ctx .= "[ID {$b->id}] {$b->title}";
                    if ($b->url) {
                        $ctx .= " ({$b->url})";
                    }
                    if ($b->content) {
                        $ctx .= ' — '.mb_substr($b->content, 0, 100);
                    }
                    $ctx .= "\n";
                }
                $ctx .= "\n";
            }

            if (isset($items['note']) && $items['note']->isNotEmpty()) {
                $ctx .= "--- NOTES ---\n";
                foreach ($items['note']->take(20) as $n) {
                    $ctx .= "[ID {$n->id}] {$n->title}";
                    if ($n->content) {
                        $ctx .= ' — '.mb_substr($n->content, 0, 150);
                    }
                    $ctx .= "\n";
                }
                $ctx .= "\n";
            }

            if (isset($items['snippet']) && $items['snippet']->isNotEmpty()) {
                $ctx .= "--- CODE SNIPPETS ---\n";
                foreach ($items['snippet']->take(10) as $s) {
                    $ctx .= "[ID {$s->id}] {$s->title}";
                    if ($s->content) {
                        $ctx .= ' — '.mb_substr($s->content, 0, 100);
                    }
                    $ctx .= "\n";
                }
                $ctx .= "\n";
            }

            if (isset($items['worksheet']) && $items['worksheet']->isNotEmpty()) {
                $ctx .= "--- WORKSHEETS ---\n";
                foreach ($items['worksheet']->take(10) as $w) {
                    $meta = $w->metadata ?? [];
                    $rows = count($meta['rows'] ?? []);
                    $checklist = count($meta['checklist'] ?? []);
                    $ctx .= "[ID {$w->id}] {$w->title} ({$rows} rows, {$checklist} checklist items)\n";
                }
                $ctx .= "\n";
            }

            if (isset($items['prompt']) && $items['prompt']->isNotEmpty()) {
                $ctx .= "--- AI PROMPTS ---\n";
                foreach ($items['prompt']->take(10) as $p) {
                    $ctx .= "[ID {$p->id}] {$p->title}";
                    if ($p->content) {
                        $ctx .= ' — '.mb_substr($p->content, 0, 100);
                    }
                    $ctx .= "\n";
                }
            }

            if (isset($items['todo']) && $items['todo']->isNotEmpty()) {
                $ctx .= "\n--- TODOS ---\n";
                foreach ($items['todo']->take(20) as $t) {
                    $meta = $t->metadata ?? [];
                    $status = ($meta['completed'] ?? false) ? 'DONE' : 'PENDING';
                    $priority = $meta['priority'] ?? 'medium';
                    $due = $meta['due_date'] ?? 'no due date';
                    $ctx .= "[ID {$t->id}] [{$status}] [{$priority}] {$t->title} (due: {$due})";
                    if ($t->content) {
                        $ctx .= ' — '.mb_substr($t->content, 0, 80);
                    }
                    $ctx .= "\n";
                }
            }

            return $ctx;
        });
    }
}
