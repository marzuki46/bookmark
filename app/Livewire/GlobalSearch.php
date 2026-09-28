<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Item;
use Livewire\Component;

final class GlobalSearch extends Component
{
    public string $query = '';

    public string $type = 'all';

    public $results = [];

    public function updatedQuery(): void
    {
        $this->search();
    }

    public function updatedType(): void
    {
        $this->search();
    }

    public function search(): void
    {
        if (strlen($this->query) < 2) {
            $this->results = [];

            return;
        }

        $q = Item::where('user_id', auth()->id())
            ->with(['tags']);

        if ($this->type !== 'all') {
            $q->where('type', $this->type);
        }

        $this->results = $q->where(function ($query) {
            $query->where('title', 'like', "%{$this->query}%")
                ->orWhere('url', 'like', "%{$this->query}%")
                ->orWhere('content', 'like', "%{$this->query}%")
                ->orWhere('metadata->username', 'like', "%{$this->query}%")
                ->orWhere('metadata->category', 'like', "%{$this->query}%");
        })
            ->latest()
            ->take(50)
            ->get()
            ->toArray();
    }

    public function render()
    {
        $userId = auth()->id();
        $typeCounts = Item::where('user_id', $userId)
            ->selectRaw('type, COUNT(*) as cnt')
            ->groupBy('type')
            ->pluck('cnt', 'type')
            ->toArray();

        $counts = array_merge(
            ['all' => array_sum($typeCounts)],
            $typeCounts
        );

        return view('livewire.global-search', compact('counts'));
    }
}
