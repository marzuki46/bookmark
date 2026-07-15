<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Item;
use App\Models\LinkScanSession;
use App\Services\LinkCheckerService;
use Livewire\Component;

final class DeadLinkChecker extends Component
{
    public bool $scanning = false;

    public int $progress = 0;

    public int $total = 0;

    public int $processed = 0;

    public array $results = [];

    public int $deadCount = 0;

    public int $aliveCount = 0;

    public int $timeoutCount = 0;

    public bool $scanComplete = false;

    public bool $showDeadOnly = false;

    public int $batchSize = 5;

    public int $currentOffset = 0;

    public ?int $sessionId = null;

    /** @var array<int> */
    public array $selectedIds = [];

    public function boot(): void
    {
        $this->loadSession();
    }

    public function startScan(): void
    {
        $userId = auth()->id();

        LinkScanSession::where('user_id', $userId)->update(['is_scanning' => false, 'is_complete' => true]);

        $this->scanning = true;
        $this->scanComplete = false;
        $this->progress = 0;
        $this->total = 0;
        $this->processed = 0;
        $this->results = [];
        $this->deadCount = 0;
        $this->aliveCount = 0;
        $this->timeoutCount = 0;
        $this->currentOffset = 0;
        $this->selectedIds = [];

        $this->total = Item::where('user_id', $userId)
            ->where('type', 'bookmark')
            ->whereNotNull('url')
            ->where('url', '!=', '')
            ->count();

        if ($this->total === 0) {
            $this->scanning = false;
            $this->scanComplete = true;
            $this->saveSession();

            return;
        }

        $session = LinkScanSession::create([
            'user_id' => $userId,
            'is_scanning' => true,
            'is_complete' => false,
            'total' => $this->total,
            'processed' => 0,
            'progress' => 0,
            'alive_count' => 0,
            'dead_count' => 0,
            'timeout_count' => 0,
            'current_offset' => 0,
            'batch_size' => $this->batchSize,
            'results' => [],
        ]);

        $this->sessionId = $session->id;

        $this->processBatch();
    }

    public function stopScan(): void
    {
        $this->scanning = false;
        $this->scanComplete = true;
        $this->saveSession();
    }

    public function processBatch(): void
    {
        if (! $this->scanning) {
            return;
        }

        $userId = auth()->id();

        $items = Item::where('user_id', $userId)
            ->where('type', 'bookmark')
            ->whereNotNull('url')
            ->where('url', '!=', '')
            ->orderBy('id')
            ->offset($this->currentOffset)
            ->limit($this->batchSize)
            ->get();

        if ($items->isEmpty()) {
            $this->scanning = false;
            $this->scanComplete = true;
            $this->saveSession();

            return;
        }

        $checker = new LinkCheckerService;

        foreach ($items as $item) {
            $result = $checker->check($item->url);

            $this->results[] = [
                'id' => $item->id,
                'title' => $item->title ?? $item->url,
                'url' => $item->url,
                'status' => $result['status'],
                'code' => $result['code'],
                'message' => $result['message'],
            ];

            match ($result['status']) {
                'alive', 'auth_required' => $this->aliveCount++,
                'dead' => $this->deadCount++,
                'timeout' => $this->timeoutCount++,
                default => null,
            };

            $this->processed++;
        }

        $this->currentOffset += $this->batchSize;
        $this->progress = $this->total > 0 ? (int) round(($this->processed / $this->total) * 100) : 0;

        if ($this->processed >= $this->total) {
            $this->scanning = false;
            $this->scanComplete = true;
        }

        $this->saveSession();
    }

    public function removeDead(): void
    {
        $deadIds = array_column(
            array_filter($this->results, fn ($r) => $r['status'] === 'dead'),
            'id'
        );

        if (empty($deadIds)) {
            return;
        }

        Item::where('user_id', auth()->id())
            ->whereIn('id', $deadIds)
            ->delete();

        $this->results = array_values(array_filter($this->results, fn ($r) => $r['status'] !== 'dead'));
        $this->deadCount = 0;
        $this->selectedIds = [];

        $this->saveSession();
        $this->dispatch('bookmarkSaved');
    }

    public function removeSelected(): void
    {
        if (empty($this->selectedIds)) {
            return;
        }

        Item::where('user_id', auth()->id())
            ->whereIn('id', $this->selectedIds)
            ->delete();

        $removedIds = $this->selectedIds;
        $this->results = array_values(array_filter($this->results, fn ($r) => ! in_array($r['id'], $removedIds)));
        $this->deadCount = count(array_filter($this->results, fn ($r) => $r['status'] === 'dead'));
        $this->selectedIds = [];

        $this->saveSession();
        $this->dispatch('bookmarkSaved');
    }

    public function toggleSelectAll(): void
    {
        $deadIds = array_column(
            array_filter($this->results, fn ($r) => $r['status'] === 'dead'),
            'id'
        );

        if (count($this->selectedIds) === count($deadIds)) {
            $this->selectedIds = [];
        } else {
            $this->selectedIds = $deadIds;
        }
    }

    public function toggleSelect(int $id): void
    {
        $index = array_search($id, $this->selectedIds);

        if ($index !== false) {
            array_splice($this->selectedIds, $index, 1);
        } else {
            $this->selectedIds[] = $id;
        }
    }

    public function removeItem(int $id): void
    {
        Item::where('user_id', auth()->id())->where('id', $id)->delete();
        $this->results = array_values(array_filter($this->results, fn ($r) => $r['id'] !== $id));

        $this->deadCount = max(0, $this->deadCount - 1);

        $this->saveSession();
        $this->dispatch('bookmarkSaved');
    }

    public function resetScanState(): void
    {
        $userId = auth()->id();
        LinkScanSession::where('user_id', $userId)->update(['is_scanning' => false, 'is_complete' => true]);

        $this->scanning = false;
        $this->scanComplete = false;
        $this->progress = 0;
        $this->total = 0;
        $this->processed = 0;
        $this->results = [];
        $this->deadCount = 0;
        $this->aliveCount = 0;
        $this->timeoutCount = 0;
        $this->currentOffset = 0;
        $this->sessionId = null;
    }

    private function loadSession(): void
    {
        $session = LinkScanSession::where('user_id', auth()->id())
            ->where('is_scanning', true)
            ->latest()
            ->first();

        if (! $session) {
            $lastSession = LinkScanSession::where('user_id', auth()->id())
                ->latest()
                ->first();

            if ($lastSession && $lastSession->is_complete) {
                $this->scanComplete = true;
                $this->total = $lastSession->total;
                $this->processed = $lastSession->processed;
                $this->progress = $lastSession->progress;
                $this->aliveCount = $lastSession->alive_count;
                $this->deadCount = $lastSession->dead_count;
                $this->timeoutCount = $lastSession->timeout_count;
                $this->results = $lastSession->results ?? [];
                $this->selectedIds = $lastSession->selected_ids ?? [];
                $this->sessionId = $lastSession->id;
            }

            return;
        }

        $this->sessionId = $session->id;
        $this->scanning = $session->is_scanning;
        $this->scanComplete = $session->is_complete;
        $this->total = $session->total;
        $this->processed = $session->processed;
        $this->progress = $session->progress;
        $this->aliveCount = $session->alive_count;
        $this->deadCount = $session->dead_count;
        $this->timeoutCount = $session->timeout_count;
        $this->currentOffset = $session->current_offset;
        $this->batchSize = $session->batch_size;
        $this->results = $session->results ?? [];
    }

    private function saveSession(): void
    {
        if ($this->sessionId) {
            LinkScanSession::where('id', $this->sessionId)->update([
                'is_scanning' => $this->scanning,
                'is_complete' => $this->scanComplete,
                'total' => $this->total,
                'processed' => $this->processed,
                'progress' => $this->progress,
                'alive_count' => $this->aliveCount,
                'dead_count' => $this->deadCount,
                'timeout_count' => $this->timeoutCount,
                'current_offset' => $this->currentOffset,
                'results' => $this->results,
                'selected_ids' => $this->selectedIds,
            ]);
        }
    }

    public function render()
    {
        return view('livewire.dead-link-checker');
    }
}
