<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class BookmarkTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_bookmark(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/items', [
            'type' => 'bookmark',
            'url' => 'https://example.com',
            'title' => 'Example',
            'tags' => ['laravel', 'php'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'bookmark')
            ->assertJsonPath('data.url', 'https://example.com')
            ->assertJsonPath('data.title', 'Example')
            ->assertJsonCount(2, 'data.tags');
    }

    public function test_can_create_bookmark_with_only_url(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/items', [
            'type' => 'bookmark',
            'url' => 'https://laravel.com',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'bookmark')
            ->assertJsonPath('data.url', 'https://laravel.com');
    }

    public function test_requires_authentication(): void
    {
        $response = $this->postJson('/api/items', [
            'type' => 'bookmark',
            'url' => 'https://example.com',
        ]);

        $response->assertUnauthorized();
    }

    public function test_validates_type_field(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/items', [
            'type' => 'invalid-type',
            'url' => 'https://example.com',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('type');
    }

    public function test_can_list_bookmarks(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/items', ['type' => 'bookmark', 'url' => 'https://example.com']);
        $this->postJson('/api/items', ['type' => 'bookmark', 'url' => 'https://laravel.com']);

        $response = $this->getJson('/api/items');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_shows_only_own_bookmarks(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Sanctum::actingAs($user1);
        $this->postJson('/api/items', ['type' => 'bookmark', 'url' => 'https://user1.com']);

        Sanctum::actingAs($user2);
        $response = $this->getJson('/api/items');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_auto_fetches_metadata_when_title_empty(): void
    {
        Http::fake([
            'https://example.com/article' => Http::response('
                <html>
                <head>
                    <title>Example Title</title>
                    <meta name="description" content="Example description">
                    <link rel="canonical" href="https://example.com/canonical">
                    <link rel="icon" href="/favicon.ico">
                    <meta property="og:title" content="OG Title">
                    <meta property="og:description" content="OG Description">
                    <meta property="og:image" content="https://example.com/image.jpg">
                </head>
                <body>
                    <p>Hello world this is a test article with enough words to calculate reading time.</p>
                </body>
                </html>
            '),
        ]);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/items', [
            'type' => 'bookmark',
            'url' => 'https://example.com/article',
        ])->assertCreated();

        $item = Item::first();

        $this->assertEquals('OG Title', $item->title);
        $this->assertEquals('example.com', $item->metadata['domain']);
        $this->assertEquals('https://example.com/canonical', $item->metadata['canonical']);
        $this->assertEquals('https://example.com/favicon.ico', $item->metadata['favicon']);
        $this->assertEquals('https://example.com/image.jpg', $item->metadata['thumbnail']);
        $this->assertEquals(1, $item->metadata['reading_time_minutes']);
    }
}
