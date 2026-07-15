<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Item;
use App\Services\MetadataFetcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class FetchBookmarkMetadata implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $backoff = 10;

    public function __construct(
        private readonly Item $item,
    ) {}

    public function handle(MetadataFetcher $fetcher): void
    {
        $metadata = $fetcher->fetch($this->item->url);

        $updateData = [];

        if ($this->item->title === null && $metadata['title'] !== null) {
            $updateData['title'] = $metadata['title'];
        }

        if ($this->item->content === null && $metadata['description'] !== null) {
            $updateData['content'] = $metadata['description'];
        }

        $existingMetadata = $this->item->metadata ?? [];
        $updateData['metadata'] = array_merge($existingMetadata, [
            'domain' => $metadata['domain'],
            'canonical' => $metadata['canonical'],
            'favicon' => $metadata['favicon'],
            'thumbnail' => $metadata['thumbnail'],
            'description' => $metadata['description'],
            'reading_time_minutes' => $metadata['reading_time_minutes'],
            'og' => $metadata['og'],
        ]);

        $this->item->update($updateData);

        GenerateBookmarkAI::dispatch($this->item);
    }

    public function failed(\Throwable $e): void
    {
        logger()->error('FetchBookmarkMetadata failed', [
            'item_id' => $this->item->id,
            'url' => $this->item->url,
            'error' => $e->getMessage(),
        ]);
    }
}
