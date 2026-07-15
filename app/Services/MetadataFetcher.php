<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;

final class MetadataFetcher
{
    public function fetch(string $url): array
    {
        $metadata = [
            'title' => null,
            'description' => null,
            'thumbnail' => null,
            'favicon' => null,
            'domain' => parse_url($url, PHP_URL_HOST),
            'canonical' => $url,
            'og' => [],
            'reading_time_minutes' => 0,
        ];

        try {
            $response = Http::timeout(10)
                ->withUserAgent('PersonalKnowledgeHub/1.0')
                ->get($url);

            if (! $response->successful()) {
                return $metadata;
            }

            $html = $response->body();
            $dom = new \DOMDocument;

            @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);

            $xpath = new \DOMXPath($dom);

            $metadata['title'] = $this->getTitle($dom, $xpath);
            $metadata['description'] = $this->getMetaDescription($xpath);
            $metadata['canonical'] = $this->getCanonical($xpath) ?? $url;
            $metadata['favicon'] = $this->getFavicon($dom, $xpath, $url);
            $metadata['thumbnail'] = $this->getThumbnail($xpath);
            $metadata['og'] = $this->getOpenGraph($xpath);

            $bodyText = $this->getBodyText($xpath);
            $metadata['reading_time_minutes'] = $this->calculateReadingTime($bodyText);

            if ($metadata['title'] === null && isset($metadata['og']['title'])) {
                $metadata['title'] = $metadata['og']['title'];
            }

            if ($metadata['description'] === null && isset($metadata['og']['description'])) {
                $metadata['description'] = $metadata['og']['description'];
            }
        } catch (\Exception $e) {
            logger()->warning('MetadataFetcher failed', ['url' => $url, 'error' => $e->getMessage()]);
        }

        return $metadata;
    }

    private function getTitle(\DOMDocument $dom, \DOMXPath $xpath): ?string
    {
        $ogTitle = $xpath->query('//meta[@property="og:title"]/@content');
        if ($ogTitle->length > 0) {
            return trim($ogTitle->item(0)->nodeValue);
        }

        $titleTags = $dom->getElementsByTagName('title');
        if ($titleTags->length > 0) {
            return trim($titleTags->item(0)->textContent);
        }

        return null;
    }

    private function getMetaDescription(\DOMXPath $xpath): ?string
    {
        $nodes = $xpath->query('//meta[@name="description"]/@content');
        if ($nodes->length > 0) {
            return trim($nodes->item(0)->nodeValue);
        }

        return null;
    }

    private function getCanonical(\DOMXPath $xpath): ?string
    {
        $nodes = $xpath->query('//link[@rel="canonical"]/@href');
        if ($nodes->length > 0) {
            return trim($nodes->item(0)->nodeValue);
        }

        return null;
    }

    private function getFavicon(\DOMDocument $dom, \DOMXPath $xpath, string $url): ?string
    {
        $nodes = $xpath->query('//link[@rel="icon"]/@href');
        if ($nodes->length > 0) {
            return $this->resolveUrl(trim($nodes->item(0)->nodeValue), $url);
        }

        $nodes = $xpath->query('//link[@rel="shortcut icon"]/@href');
        if ($nodes->length > 0) {
            return $this->resolveUrl(trim($nodes->item(0)->nodeValue), $url);
        }

        return rtrim($url, '/').'/favicon.ico';
    }

    private function getThumbnail(\DOMXPath $xpath): ?string
    {
        $nodes = $xpath->query('//meta[@property="og:image"]/@content');
        if ($nodes->length > 0) {
            return trim($nodes->item(0)->nodeValue);
        }

        $nodes = $xpath->query('//meta[@name="twitter:image"]/@content');
        if ($nodes->length > 0) {
            return trim($nodes->item(0)->nodeValue);
        }

        return null;
    }

    private function getOpenGraph(\DOMXPath $xpath): array
    {
        $og = [];
        $nodes = $xpath->query('//meta[starts-with(@property, "og:")]');

        foreach ($nodes as $node) {
            $property = $node->getAttribute('property');
            $content = $node->getAttribute('content');
            if ($property && $content) {
                $key = str_replace('og:', '', $property);
                $og[$key] = $content;
            }
        }

        return $og;
    }

    private function getBodyText(\DOMXPath $xpath): string
    {
        $texts = [];
        $nodes = $xpath->query('//body//p | //body//article | //body//main');

        foreach ($nodes as $node) {
            $texts[] = $node->textContent;
        }

        return implode(' ', $texts);
    }

    private function calculateReadingTime(string $text): int
    {
        $wordCount = str_word_count(strip_tags($text));
        $minutes = (int) ceil($wordCount / 200);

        return max(1, $minutes);
    }

    private function resolveUrl(string $path, string $baseUrl): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, '//')) {
            $scheme = parse_url($baseUrl, PHP_URL_SCHEME) ?? 'https';

            return $scheme.':'.$path;
        }

        $parts = parse_url($baseUrl);
        $base = ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? '');

        if (str_starts_with($path, '/')) {
            return $base.$path;
        }

        $dirPath = dirname($parts['path'] ?? '/');

        return rtrim($base.$dirPath, '/').'/'.$path;
    }
}
