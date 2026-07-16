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

        $this->apiUrl = $settings['api_url'] ?? '';
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

        $results = $this->searchViaTavily($query, $maxResults);

        if (! empty($results)) {
            return $results;
        }

        $results = $this->searchViaDuckDuckGo($query, $maxResults);

        if (! empty($results)) {
            return $results;
        }

        return $this->searchViaGoogleScrape($query, $maxResults);
    }

    private function searchViaTavily(string $query, int $maxResults): array
    {
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
            return [];
        }
    }

    private function searchViaDuckDuckGo(string $query, int $maxResults): array
    {
        try {
            $url = 'https://api.duckduckgo.com/?q='.urlencode($query).'&format=json&no_html=1&skip_disambig=1';

            $response = Http::timeout(10)->get($url);

            if (! $response->successful()) {
                return [];
            }

            $data = $response->json();
            $results = [];

            if (! empty($data['AbstractText'])) {
                $results[] = [
                    'title' => $data['Heading'] ?? $query,
                    'url' => $data['AbstractURL'] ?? '',
                    'snippet' => $data['AbstractText'] ?? '',
                ];
            }

            foreach (($data['RelatedTopics'] ?? []) as $topic) {
                if (count($results) >= $maxResults) {
                    break;
                }
                if (is_array($topic) && ! empty($topic['Text'])) {
                    $results[] = [
                        'title' => mb_substr($topic['Text'], 0, 100),
                        'url' => $topic['FirstURL'] ?? '',
                        'snippet' => $topic['Text'] ?? '',
                    ];
                }
            }

            return $results;
        } catch (\Exception $e) {
            return [];
        }
    }

    private function searchViaGoogleScrape(string $query, int $maxResults): array
    {
        try {
            $url = 'https://www.google.com/search?q='.urlencode($query).'&num='.$maxResults.'&hl=en';

            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept-Language' => 'en-US,en;q=0.9',
                ])
                ->get($url);

            if (! $response->successful()) {
                return [];
            }

            $html = $response->body();
            $results = [];

            preg_match_all('/<a href="\/url\?q=([^&"]+)/', $html, $matches);

            if (! empty($matches[1])) {
                $seen = [];
                foreach ($matches[1] as $rawUrl) {
                    $decoded = urldecode($rawUrl);
                    if (str_starts_with($decoded, 'http') && ! in_array($decoded, $seen, true)) {
                        $seen[] = $decoded;
                        $host = parse_url($decoded, PHP_URL_HOST);
                        if (! in_array($host, ['www.google.com', 'google.com', 'accounts.google.com', 'support.google.com'], true)) {
                            $results[] = [
                                'title' => $query,
                                'url' => $decoded,
                                'snippet' => '',
                            ];
                        }
                    }
                    if (count($results) >= $maxResults) {
                        break;
                    }
                }
            }

            return $results;
        } catch (\Exception $e) {
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
