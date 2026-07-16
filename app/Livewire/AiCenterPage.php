<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Item;
use App\Services\AIService;
use Livewire\Component;

final class AiCenterPage extends Component
{
    public string $selectedItemId = '';

    public string $aiResult = '';

    public bool $processing = false;

    public string $statusMessage = '';

    public string $statusType = 'success';

    public string $activeFeature = '';

    public string $batchInput = '';

    public int $timeout = 120;

    public function mount(): void
    {
    }

    public function summarizeItem(string $itemId): void
    {
        $item = Item::where('id', $itemId)->where('user_id', auth()->id())->first();

        if (! $item) {
            return;
        }

        $this->processing = true;
        $this->activeFeature = 'summarize';

        try {
            $ai = new AIService(auth()->id());

            if (! $ai->isConfigured()) {
                $this->statusMessage = 'AI belum dikonfigurasi. Atur API key di Settings.';
                $this->statusType = 'error';
                $this->processing = false;

                return;
            }

            $text = ($item->title ?? '')."\n\n".($item->content ?? '');
            $this->aiResult = $ai->askRaw(
                'Summarize this content in 2-3 sentences in Indonesian. Be concise and focus on main points:',
                $text,
                500
            ) ?? 'Gagal generate summary.';
        } catch (\Exception $e) {
            $this->aiResult = 'Error: '.$e->getMessage();
        }

        $this->processing = false;
    }

    public function categorizeItem(string $itemId): void
    {
        $item = Item::where('id', $itemId)->where('user_id', auth()->id())->first();

        if (! $item) {
            return;
        }

        $this->processing = true;
        $this->activeFeature = 'categorize';

        try {
            $ai = new AIService(auth()->id());

            if (! $ai->isConfigured()) {
                $this->statusMessage = 'AI belum dikonfigurasi.';
                $this->statusType = 'error';
                $this->processing = false;

                return;
            }

            $text = ($item->title ?? '')."\n\n".($item->content ?? '');
            $this->aiResult = $ai->askRaw(
                'Categorize this content into exactly one category: Technology, Science, Design, Business, Health, Education, Entertainment, News, Reference, Other. Reply with only the category name.',
                $text,
                50
            ) ?? 'Gagal generate category.';
        } catch (\Exception $e) {
            $this->aiResult = 'Error: '.$e->getMessage();
        }

        $this->processing = false;
    }

    public function suggestTagsForItem(string $itemId): void
    {
        $item = Item::where('id', $itemId)->where('user_id', auth()->id())->first();

        if (! $item) {
            return;
        }

        $this->processing = true;
        $this->activeFeature = 'tags';

        try {
            $ai = new AIService(auth()->id());

            if (! $ai->isConfigured()) {
                $this->statusMessage = 'AI belum dikonfigurasi.';
                $this->statusType = 'error';
                $this->processing = false;

                return;
            }

            $text = ($item->title ?? '')."\n\n".($item->content ?? '');
            $this->aiResult = $ai->askRaw(
                'Generate 3-5 relevant tags for this content. Reply with only the tags separated by commas, no numbers.',
                $text,
                100
            ) ?? 'Gagal generate tags.';
        } catch (\Exception $e) {
            $this->aiResult = 'Error: '.$e->getMessage();
        }

        $this->processing = false;
    }

    public function generateFromInput(): void
    {
        $text = trim($this->batchInput);

        if ($text === '') {
            return;
        }

        $this->processing = true;
        $this->activeFeature = 'custom';

        try {
            $chatService = new AiChatService(auth()->id());
            $this->aiResult = $chatService->chat($text);
        } catch (\Exception $e) {
            $this->aiResult = 'Error: '.$e->getMessage();
        }

        $this->processing = false;
    }

    public function clearResult(): void
    {
        $this->aiResult = '';
        $this->activeFeature = '';
    }

    public function render()
    {
        $userId = auth()->id();

        $stats = [
            'totalItems' => Item::where('user_id', $userId)->count(),
            'bookmarks' => Item::where('user_id', $userId)->where('type', 'bookmark')->count(),
            'notes' => Item::where('user_id', $userId)->where('type', 'note')->count(),
            'prompts' => Item::where('user_id', $userId)->where('type', 'prompt')->count(),
            'snippets' => Item::where('user_id', $userId)->where('type', 'snippet')->count(),
            'worksheets' => Item::where('user_id', $userId)->where('type', 'worksheet')->count(),
            'todos' => Item::where('user_id', $userId)->where('type', 'todo')->count(),
        ];

        $recentItems = Item::where('user_id', $userId)
            ->where('type', '!=', 'file')
            ->latest()
            ->take(10)
            ->get();

        $ai = new AIService($userId);

        return view('livewire.ai-center-page', [
            'stats' => $stats,
            'recentItems' => $recentItems,
            'isAiConfigured' => $ai->isConfigured(),
        ]);
    }
}
