<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\AIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class AITest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_service_returns_null_when_not_configured(): void
    {
        $ai = new AIService;
        $this->assertNull($ai->summarize('test'));
        $this->assertNull($ai->categorize('test', null));
        $this->assertEmpty($ai->suggestTags('test', null));
    }

    public function test_ai_service_summarize(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'This is a summary of the content.']],
                ],
            ]),
        ]);

        config(['services.ai.api_key' => 'test-key']);

        $ai = new AIService;
        $result = $ai->summarize('Long content here');

        $this->assertEquals('This is a summary of the content.', $result);
    }

    public function test_ai_service_categorize(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Technology']],
                ],
            ]),
        ]);

        config(['services.ai.api_key' => 'test-key']);

        $ai = new AIService;
        $result = $ai->categorize('New PHP framework released', null);

        $this->assertEquals('Technology', $result);
    }

    public function test_ai_service_suggest_tags(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'php, laravel, programming, web']],
                ],
            ]),
        ]);

        config(['services.ai.api_key' => 'test-key']);

        $ai = new AIService;
        $result = $ai->suggestTags('Laravel 13 released', null);

        $this->assertContains('php', $result);
        $this->assertContains('laravel', $result);
    }
}
