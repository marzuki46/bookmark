<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Collection;
use App\Models\Item;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

final class CollectionList extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showModal = false;

    public bool $editMode = false;

    public ?int $editingId = null;

    public string $formName = '';

    public string $formDescription = '';

    public bool $showItemsModal = false;

    public ?int $viewingCollectionId = null;

    public string $viewingCollectionName = '';

    public string $assignSearch = '';

    public array $assignableItems = [];

    public string $statusMessage = '';

    public string $statusType = 'success';

    protected string $paginationTheme = 'tailwind';

    public function rules(): array
    {
        return [
            'formName' => 'required|string|max:255',
            'formDescription' => 'nullable|string|max:1000',
        ];
    }

    public function updatedSearch(): void
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
        $collection = Collection::where('user_id', auth()->id())->findOrFail($id);
        $this->editingId = $id;
        $this->editMode = true;
        $this->formName = $collection->name;
        $this->formDescription = $collection->description ?? '';
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
            'name' => $this->formName,
            'slug' => Str::slug($this->formName),
            'description' => $this->formDescription ?: null,
        ];

        if ($this->editMode && $this->editingId) {
            $collection = Collection::where('user_id', auth()->id())->findOrFail($this->editingId);
            $collection->update($data);
        } else {
            Collection::create($data);
        }

        $this->closeModal();
    }

    public function delete(int $id): void
    {
        $collection = Collection::where('user_id', auth()->id())->findOrFail($id);
        $collection->items()->detach();
        $collection->delete();
    }

    public function viewItems(int $collectionId): void
    {
        $collection = Collection::where('user_id', auth()->id())->findOrFail($collectionId);
        $this->viewingCollectionId = $collectionId;
        $this->viewingCollectionName = $collection->name;
        $this->assignSearch = '';
        $this->loadAssignableItems();
        $this->showItemsModal = true;
    }

    public function closeItemsModal(): void
    {
        $this->showItemsModal = false;
        $this->viewingCollectionId = null;
        $this->viewingCollectionName = '';
        $this->assignableItems = [];
    }

    public function updatedAssignSearch(): void
    {
        $this->loadAssignableItems();
    }

    public function attachItem(int $itemId): void
    {
        if (! $this->viewingCollectionId) {
            return;
        }

        $collection = Collection::where('user_id', auth()->id())->findOrFail($this->viewingCollectionId);
        $item = Item::where('user_id', auth()->id())->findOrFail($itemId);

        if (! $collection->items()->where('item_id', $itemId)->exists()) {
            $collection->items()->attach($itemId);
            $this->statusMessage = "Item \"{$item->title}\" added to \"{$collection->name}\".";
            $this->statusType = 'success';
        }

        $this->loadAssignableItems();
    }

    public function detachItem(int $itemId): void
    {
        if (! $this->viewingCollectionId) {
            return;
        }

        $collection = Collection::where('user_id', auth()->id())->findOrFail($this->viewingCollectionId);
        $item = Item::where('user_id', auth()->id())->findOrFail($itemId);
        $collection->items()->detach($itemId);

        $this->statusMessage = "Item \"{$item->title}\" removed from \"{$collection->name}\".";
        $this->statusType = 'success';
        $this->loadAssignableItems();
    }

    public function clearStatusMessage(): void
    {
        $this->statusMessage = '';
    }

    private function loadAssignableItems(): void
    {
        if (! $this->viewingCollectionId) {
            return;
        }

        $query = Item::where('user_id', auth()->id())
            ->where('type', '!=', 'file');

        if ($this->assignSearch !== '') {
            $query->where(function ($q) {
                $q->where('title', 'like', '%'.$this->assignSearch.'%')
                    ->orWhere('content', 'like', '%'.$this->assignSearch.'%');
            });
        }

        $this->assignableItems = $query->latest()->take(50)->get()->toArray();
    }

    public function render()
    {
        $query = Collection::withCount('items')
            ->where('user_id', auth()->id());

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%");
            });
        }

        $collections = $query->latest()->paginate(12);

        $stats = [
            'total' => Collection::where('user_id', auth()->id())->count(),
            'totalItems' => Item::where('user_id', auth()->id())->count(),
        ];

        $viewingCollectionItems = [];
        if ($this->viewingCollectionId) {
            $collection = Collection::with('items')->where('user_id', auth()->id())->find($this->viewingCollectionId);
            if ($collection) {
                $viewingCollectionItems = $collection->items->toArray();
            }
        }

        return view('livewire.collection-list', compact('collections', 'stats', 'viewingCollectionItems'));
    }

    private function resetForm(): void
    {
        $this->formName = '';
        $this->formDescription = '';
        $this->editingId = null;
        $this->editMode = false;
        $this->clearValidation();
    }
}
