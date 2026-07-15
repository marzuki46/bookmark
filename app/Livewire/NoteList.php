<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Item;
use Livewire\Component;
use Livewire\WithPagination;

final class NoteList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filter = 'all';

    public bool $showModal = false;

    public bool $editMode = false;

    public ?int $editingId = null;

    public string $formTitle = '';

    public string $formContent = '';

    public bool $formFavorite = false;

    protected string $paginationTheme = 'tailwind';

    public function rules(): array
    {
        return [
            'formTitle' => 'required|string|max:255',
            'formContent' => 'required|string|max:50000',
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->editMode = false;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $item = Item::where('user_id', auth()->id())->where('type', 'note')->findOrFail($id);
        $this->editingId = $id;
        $this->editMode = true;
        $this->formTitle = $item->title ?? '';
        $this->formContent = $item->content ?? '';
        $this->formFavorite = $item->favorite;
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'user_id' => auth()->id(),
            'type' => 'note',
            'title' => $this->formTitle,
            'content' => $this->formContent,
            'favorite' => $this->formFavorite,
        ];

        if ($this->editMode && $this->editingId) {
            $item = Item::where('user_id', auth()->id())->where('type', 'note')->findOrFail($this->editingId);
            $item->update($data);
        } else {
            Item::create($data);
        }

        $this->closeModal();
    }

    public function toggleFavorite(int $id): void
    {
        $item = Item::where('user_id', auth()->id())->where('type', 'note')->findOrFail($id);
        $item->update(['favorite' => ! $item->favorite]);
    }

    public function archive(int $id): void
    {
        $item = Item::where('user_id', auth()->id())->where('type', 'note')->findOrFail($id);
        $item->update(['archived_at' => $item->archived_at ? null : now()]);
    }

    public function trash(int $id): void
    {
        $item = Item::where('user_id', auth()->id())->where('type', 'note')->findOrFail($id);
        $item->delete();
    }

    public function render()
    {
        $query = Item::with(['tags'])
            ->where('user_id', auth()->id())
            ->where('type', 'note');

        match ($this->filter) {
            'favorites' => $query->where('favorite', true),
            'archived' => $query->whereNotNull('archived_at'),
            default => $query->whereNull('archived_at'),
        };

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                    ->orWhere('content', 'like', "%{$this->search}%");
            });
        }

        $notes = $query->latest()->paginate(12);

        $stats = [
            'total' => Item::where('user_id', auth()->id())->where('type', 'note')->whereNull('archived_at')->count(),
            'favorites' => Item::where('user_id', auth()->id())->where('type', 'note')->where('favorite', true)->count(),
            'archived' => Item::where('user_id', auth()->id())->where('type', 'note')->whereNotNull('archived_at')->count(),
        ];

        return view('livewire.note-list', compact('notes', 'stats'));
    }

    private function resetForm(): void
    {
        $this->formTitle = '';
        $this->formContent = '';
        $this->formFavorite = false;
        $this->editingId = null;
        $this->editMode = false;
        $this->clearValidation();
    }
}
