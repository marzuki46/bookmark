<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Item;
use Livewire\Component;

final class QuickNotepad extends Component
{
    public string $search = '';

    public string $sortBy = 'recent';

    public array $editingNote = [];

    public bool $showColorPicker = false;

    public ?int $pickingColorFor = null;

    public string $newNoteTitle = '';

    public string $newNoteContent = '';

    public string $newNoteColor = '#fef3c7';

    public bool $showNewNote = false;

    public array $colors = [
        '#fef3c7' => 'Kuning',
        '#d1fae5' => 'Hijau',
        '#dbeafe' => 'Biru',
        '#ede9fe' => 'Ungu',
        '#fee2e2' => 'Merah',
        '#f3f4f6' => 'Abu',
    ];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function createNote(): void
    {
        $this->validate([
            'newNoteTitle' => 'required|string|max:255',
        ]);

        $item = Item::create([
            'user_id' => auth()->id(),
            'type' => 'note',
            'title' => $this->newNoteTitle,
            'content' => $this->newNoteContent,
            'metadata' => ['color' => $this->newNoteColor, 'pinned' => false],
        ]);

        $this->newNoteTitle = '';
        $this->newNoteContent = '';
        $this->newNoteColor = '#fef3c7';
        $this->showNewNote = false;

        $this->dispatch('noteCreated');
    }

    public function startEditing(int $noteId): void
    {
        $item = Item::where('id', $noteId)->where('user_id', auth()->id())->first();

        if ($item) {
            $this->editingNote = [
                'id' => $item->id,
                'title' => $item->title ?? '',
                'content' => $item->content ?? '',
                'color' => $item->metadata['color'] ?? '#fef3c7',
            ];
        }
    }

    public function saveEdit(): void
    {
        if (empty($this->editingNote['id'])) {
            return;
        }

        $item = Item::where('id', $this->editingNote['id'])->where('user_id', auth()->id())->first();

        if ($item) {
            $item->update([
                'title' => $this->editingNote['title'],
                'content' => $this->editingNote['content'],
                'metadata' => ['color' => $this->editingNote['color'], 'pinned' => $item->metadata['pinned'] ?? false],
            ]);
        }

        $this->editingNote = [];
    }

    public function cancelEdit(): void
    {
        $this->editingNote = [];
    }

    public function setColor(int $noteId, string $color): void
    {
        $item = Item::where('id', $noteId)->where('user_id', auth()->id())->first();

        if ($item) {
            $metadata = $item->metadata ?? [];
            $metadata['color'] = $color;
            $item->update(['metadata' => $metadata]);
        }

        if ($this->editingNote['id'] ?? null) {
            $this->editingNote['color'] = $color;
        }

        $this->showColorPicker = false;
        $this->pickingColorFor = null;
    }

    public function togglePin(int $noteId): void
    {
        $item = Item::where('id', $noteId)->where('user_id', auth()->id())->first();

        if ($item) {
            $metadata = $item->metadata ?? [];
            $metadata['pinned'] = ! ($metadata['pinned'] ?? false);
            $item->update(['metadata' => $metadata]);
        }
    }

    public function toggleFavorite(int $noteId): void
    {
        $item = Item::where('id', $noteId)->where('user_id', auth()->id())->first();

        if ($item) {
            $item->update(['favorite' => ! $item->favorite]);
        }
    }

    public function deleteNote(int $noteId): void
    {
        Item::where('id', $noteId)->where('user_id', auth()->id())->delete();
    }

    public function render()
    {
        $query = Item::where('user_id', auth()->id())
            ->where('type', 'note');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                    ->orWhere('content', 'like', "%{$this->search}%");
            });
        }

        $query->orderByRaw("COALESCE(JSON_EXTRACT(metadata, '$.pinned'), 0) DESC");

        if ($this->sortBy === 'recent') {
            $query->latest();
        } else {
            $query->orderBy('title');
        }

        $notes = $query->get();

        return view('livewire.quick-notepad', compact('notes'));
    }
}
