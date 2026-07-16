<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;

final class AIService
{
    private string $apiUrl;

    private string $apiKey;

    private string $model;

    public function __construct(?int $userId = null)
    {
        $settings = $this->loadUserSettings($userId);

        $this->apiUrl = $settings['api_url'] ?? config('services.ai.api_url', 'https://api.openai.com/v1');
        $this->apiKey = $settings['api_key'] ?? config('services.ai.api_key', '');
        $this->model = $settings['model'] ?? config('services.ai.model', 'gpt-4o-mini');
    }

    /**
     * Reinitialize with a specific user context (for webhook usage).
     */
    public function initializeForUser(int $userId): void
    {
        $settings = $this->loadUserSettings($userId);
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
        $prompt = 'Summarize the following content in 2-3 sentences. Be concise and focus on the main points:';

        return $this->ask($prompt, $content);
    }

    public function categorize(string $title, ?string $content): ?string
    {
        $text = $title.($content ? "\n\n".$content : '');
        $prompt = 'Categorize this content into exactly one category from: Technology, Science, Design, Business, Health, Education, Entertainment, News, Reference, Other. Reply with only the category name.';

        return $this->ask($prompt, $text);
    }

    public function suggestTags(string $title, ?string $content): array
    {
        $text = $title.($content ? "\n\n".$content : '');
        $prompt = 'Generate 3-5 relevant tags for this content. Reply with only the tags separated by commas, no numbers or bullet points.';
        $result = $this->ask($prompt, $text);

        if ($result === null) {
            return [];
        }

        return array_map(
            fn (string $tag) => trim($tag),
            array_filter(explode(',', $result))
        );
    }

    public function renderPage(string $title, string $url, ?string $content = null): ?string
    {
        $text = "Title: {$title}\nURL: {$url}";
        if ($content) {
            $text .= "\n\nContent:\n".mb_substr($content, 0, 8000);
        }

        $prompt = 'You are a knowledge assistant. Analyze the following web page and create a comprehensive note. Include:
1. A brief summary (2-3 sentences)
2. Key points (bullet list)
3. Important details (names, dates, numbers, links mentioned)
4. Your assessment of the page value

Format your response in clean markdown. Be thorough but concise.';

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

        $prompt = 'You are a bookmark organizer. Analyze these bookmarks and for EACH one (by number), suggest:
- category: one of Technology, SEO, Business, Marketing, Design, Education, News, Entertainment, Reference, Other
- tags: 2-4 relevant tags as comma-separated
- action: "keep" if the bookmark is useful, "remove" if it seems like spam/dead/low-quality

Reply as valid JSON array only, no markdown. Each item: {"id": <number>, "category": "...", "tags": "...", "action": "keep"|"remove"}';

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

    /**
     * Public wrapper for AI chat. Used by FinancialAIService and others.
     */
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

    private function loadUserSettings(?int $userId = null): array
    {
        $userId = $userId ?? auth()->id();

        if (! $userId) {
            return [];
        }

        $path = storage_path('app/user-settings-'.$userId.'.json');

        if (file_exists($path)) {
            $all = json_decode(file_get_contents($path), true) ?? [];

            return $all['ai'] ?? [];
        }

        return [];
    }
}
