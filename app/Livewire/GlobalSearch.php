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
        $counts = [
            'all' => Item::where('user_id', auth()->id())->count(),
            'bookmark' => Item::where('user_id', auth()->id())->where('type', 'bookmark')->count(),
            'note' => Item::where('user_id', auth()->id())->where('type', 'note')->count(),
            'prompt' => Item::where('user_id', auth()->id())->where('type', 'prompt')->count(),
            'snippet' => Item::where('user_id', auth()->id())->where('type', 'snippet')->count(),
            'file' => Item::where('user_id', auth()->id())->where('type', 'file')->count(),
            'secret' => Item::where('user_id', auth()->id())->where('type', 'secret')->count(),
            'worksheet' => Item::where('user_id', auth()->id())->where('type', 'worksheet')->count(),
            'todo' => Item::where('user_id', auth()->id())->where('type', 'todo')->count(),
        ];

        return view('livewire.global-search', compact('counts'));
    }
}
