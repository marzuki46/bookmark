<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\FetchBookmarkMetadata;
use App\Models\Item;
use App\Models\User;
use App\Services\MetadataFetcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class MetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_metadata_fetcher_parses_html(): void
    {
        Http::fake([
            'https://example.com/article' => Http::response('
                <html>
                <head>
                    <title>Page Title</title>
                    <meta name="description" content="Meta desc">
                    <meta property="og:title" content="OG Title">
                    <meta property="og:image" content="https://example.com/img.jpg">
                </head>
                <body><p>Hello world test article content here.</p></body>
                </html>
            '),
        ]);

        $fetcher = new MetadataFetcher;
        $result = $fetcher->fetch('https://example.com/article');

        $this->assertEquals('OG Title', $result['title']);
        $this->assertEquals('example.com', $result['domain']);
        $this->assertEquals('https://example.com/img.jpg', $result['thumbnail']);
    }

    public function test_job_updates_item_metadata(): void
    {
        Http::fake([
            'https://example.com/article' => Http::response('
                <html>
                <head>
                    <title>Page Title</title>
                    <meta property="og:title" content="OG Title">
                </head>
                <body><p>Hello world.</p></body>
                </html>
            '),
        ]);

        $user = User::factory()->create();
        $item = Item::create([
            'user_id' => $user->id,
            'type' => 'bookmark',
            'url' => 'https://example.com/article',
        ]);

        FetchBookmarkMetadata::dispatchSync($item);

        $item->refresh();

        $this->assertEquals('OG Title', $item->title);
        $this->assertEquals('example.com', $item->metadata['domain']);
    }
}
