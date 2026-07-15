<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Item;
use Livewire\Component;
use Livewire\WithPagination;

final class SnippetList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filter = 'all';

    public bool $showModal = false;

    public bool $editMode = false;

    public ?int $editingId = null;

    public string $formTitle = '';

    public string $formContent = '';

    public string $formLanguage = '';

    public string $formDescription = '';

    public bool $formFavorite = false;

    protected string $paginationTheme = 'tailwind';

    public function rules(): array
    {
        return [
            'formTitle' => 'required|string|max:255',
            'formContent' => 'required|string|max:50000',
            'formLanguage' => 'nullable|string|max:50',
            'formDescription' => 'nullable|string|max:500',
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
        $item = Item::where('user_id', auth()->id())->where('type', 'snippet')->findOrFail($id);
        $this->editingId = $id;
        $this->editMode = true;
        $this->formTitle = $item->title ?? '';
        $this->formContent = $item->content ?? '';
        $this->formLanguage = $item->metadata['language'] ?? '';
        $this->formDescription = $item->metadata['description'] ?? '';
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
            'type' => 'snippet',
            'title' => $this->formTitle,
            'content' => $this->formContent,
            'favorite' => $this->formFavorite,
            'metadata' => array_filter([
                'language' => $this->formLanguage ?: null,
                'description' => $this->formDescription ?: null,
            ]),
        ];

        if ($this->editMode && $this->editingId) {
            $item = Item::where('user_id', auth()->id())->where('type', 'snippet')->findOrFail($this->editingId);
            $item->update($data);
        } else {
            Item::create($data);
        }

        $this->closeModal();
    }

    public function toggleFavorite(int $id): void
    {
        $item = Item::where('user_id', auth()->id())->where('type', 'snippet')->findOrFail($id);
        $item->update(['favorite' => ! $item->favorite]);
    }

    public function trash(int $id): void
    {
        $item = Item::where('user_id', auth()->id())->where('type', 'snippet')->findOrFail($id);
        $item->delete();
    }

    public function render()
    {
        $query = Item::with(['tags'])
            ->where('user_id', auth()->id())
            ->where('type', 'snippet');

        match ($this->filter) {
            'favorites' => $query->where('favorite', true),
            default => null,
        };

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                    ->orWhere('content', 'like', "%{$this->search}%");
            });
        }

        $snippets = $query->latest()->paginate(12);

        $languages = Item::where('user_id', auth()->id())
            ->where('type', 'snippet')
            ->whereNotNull('metadata')
            ->get()
            ->pluck('metadata.language')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $stats = [
            'total' => Item::where('user_id', auth()->id())->where('type', 'snippet')->count(),
            'favorites' => Item::where('user_id', auth()->id())->where('type', 'snippet')->where('favorite', true)->count(),
        ];

        return view('livewire.snippet-list', compact('snippets', 'languages', 'stats'));
    }

    private function resetForm(): void
    {
        $this->formTitle = '';
        $this->formContent = '';
        $this->formLanguage = '';
        $this->formDescription = '';
        $this->formFavorite = false;
        $this->editingId = null;
        $this->editMode = false;
        $this->clearValidation();
    }
}
