<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AiSuggestion;
use App\Models\AiSummary;
use App\Models\Item;
use App\Services\AIService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class GenerateBookmarkAI implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $backoff = 30;

    public function __construct(
        private readonly Item $item,
    ) {}

    public function handle(AIService $ai): void
    {
        if (! $ai->isConfigured()) {
            return;
        }

        $content = $this->item->content ?? '';
        $title = $this->item->title ?? '';
        $metadataContent = $this->item->metadata['description'] ?? '';

        $textContent = implode("\n\n", array_filter([$title, $content, $metadataContent]));

        if (empty(trim($textContent))) {
            $textContent = $title ?: $this->item->url;
        }

        $summary = $ai->summarize($textContent);
        if ($summary !== null) {
            AiSummary::updateOrCreate(
                ['item_id' => $this->item->id],
                ['summary' => $summary, 'model' => config('services.ai.model', 'gpt-4o-mini')]
            );
        }

        $category = $ai->categorize($title, $textContent);
        if ($category !== null) {
            AiSuggestion::updateOrCreate(
                ['item_id' => $this->item->id, 'type' => 'category'],
                ['suggestion' => $category, 'applied' => false]
            );
        }

        $tags = $ai->suggestTags($title, $textContent);
        if (! empty($tags)) {
            AiSuggestion::updateOrCreate(
                ['item_id' => $this->item->id, 'type' => 'tag'],
                ['suggestion' => implode(', ', $tags), 'applied' => false]
            );
        }

        $this->item->update([
            'metadata' => array_merge($this->item->metadata ?? [], [
                'ai_summary' => $summary,
                'ai_category' => $category,
                'ai_tags' => $tags,
            ]),
        ]);
    }

    public function failed(\Throwable $e): void
    {
        logger()->error('GenerateBookmarkAI failed', [
            'item_id' => $this->item->id,
            'error' => $e->getMessage(),
        ]);
    }
}
