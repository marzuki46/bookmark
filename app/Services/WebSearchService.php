<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;

final class WebSearchService
{
    private string $apiUrl;

    private string $apiKey;

    public function __construct(?int $userId = null)
    {
        $settings = $this->loadUserSettings($userId);

        $this->apiUrl = $settings['api_url'] ?? config('services.ai.api_url', '');
        $this->apiKey = $settings['api_key'] ?? '';
    }

    public function isConfigured(): bool
    {
        return $this->apiUrl !== '' && $this->apiKey !== '';
    }

    public function search(string $query, int $maxResults = 5): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        try {
            $searchUrl = rtrim($this->apiUrl, '/').'/search';

            $response = Http::timeout(15)
                ->withToken($this->apiKey)
                ->post($searchUrl, [
                    'model' => 'tavily',
                    'query' => $query,
                    'max_results' => $maxResults,
                ]);

            if (! $response->successful()) {
                logger()->warning('Web search error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [];
            }

            $data = $response->json();
            $results = $data['results'] ?? [];

            return array_map(fn ($r) => [
                'title' => $r['title'] ?? '',
                'url' => $r['url'] ?? '',
                'snippet' => $r['snippet'] ?? '',
            ], $results);
        } catch (\Exception $e) {
            logger()->error('Web search failed', ['error' => $e->getMessage()]);

            return [];
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
