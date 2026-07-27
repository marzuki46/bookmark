<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Item;
use App\Models\Tag;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

final class TagList extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showModal = false;

    public bool $editMode = false;

    public ?int $editingId = null;

    public string $formName = '';

    public bool $showItemsModal = false;

    public ?int $viewingTagId = null;

    public string $viewingTagName = '';

    public string $assignSearch = '';

    public array $assignableItems = [];

    public string $statusMessage = '';

    public string $statusType = 'success';

    protected string $paginationTheme = 'tailwind';

    public function rules(): array
    {
        return [
            'formName' => 'required|string|max:255',
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
        $tag = Tag::where('user_id', auth()->id())->findOrFail($id);
        $this->editingId = $id;
        $this->editMode = true;
        $this->formName = $tag->name;
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
        ];

        if ($this->editMode && $this->editingId) {
            $tag = Tag::where('user_id', auth()->id())->findOrFail($this->editingId);
            $tag->update($data);
        } else {
            $exists = Tag::where('user_id', auth()->id())->where('slug', $data['slug'])->exists();
            if (! $exists) {
                Tag::create($data);
            }
        }

        $this->closeModal();
    }

    public function delete(int $id): void
    {
        $tag = Tag::where('user_id', auth()->id())->findOrFail($id);
        $tag->items()->detach();
        $tag->delete();
    }

    public function viewItems(int $tagId): void
    {
        $tag = Tag::where('user_id', auth()->id())->findOrFail($tagId);
        $this->viewingTagId = $tagId;
        $this->viewingTagName = $tag->name;
        $this->assignSearch = '';
        $this->loadAssignableItems();
        $this->showItemsModal = true;
    }

    public function closeItemsModal(): void
    {
        $this->showItemsModal = false;
        $this->viewingTagId = null;
        $this->viewingTagName = '';
        $this->assignableItems = [];
    }

    public function updatedAssignSearch(): void
    {
        $this->loadAssignableItems();
    }

    public function attachItem(int $itemId): void
    {
        if (! $this->viewingTagId) {
            return;
        }

        $tag = Tag::where('user_id', auth()->id())->findOrFail($this->viewingTagId);
        $item = Item::where('user_id', auth()->id())->findOrFail($itemId);

        if (! $tag->items()->where('item_id', $itemId)->exists()) {
            $tag->items()->attach($itemId);
            $this->statusMessage = "Item \"{$item->title}\" added to tag \"{$tag->name}\".";
            $this->statusType = 'success';
        }

        $this->loadAssignableItems();
    }

    public function detachItem(int $itemId): void
    {
        if (! $this->viewingTagId) {
            return;
        }

        $tag = Tag::where('user_id', auth()->id())->findOrFail($this->viewingTagId);
        $item = Item::where('user_id', auth()->id())->findOrFail($itemId);
        $tag->items()->detach($itemId);

        $this->statusMessage = "Item \"{$item->title}\" removed from tag \"{$tag->name}\".";
        $this->statusType = 'success';
        $this->loadAssignableItems();
    }

    public function clearStatusMessage(): void
    {
        $this->statusMessage = '';
    }

    private function loadAssignableItems(): void
    {
        if (! $this->viewingTagId) {
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
        $query = Tag::withCount('items')
            ->where('user_id', auth()->id());

        if ($this->search) {
            $query->where('name', 'like', "%{$this->search}%");
        }

        $tags = $query->latest()->paginate(20);

        $allTags = Tag::withCount('items')
            ->where('user_id', auth()->id())
            ->orderByDesc('items_count')
            ->get();

        $unusedCount = Tag::where('user_id', auth()->id())->doesntHave('items')->count();

        $viewingTagItems = [];
        if ($this->viewingTagId) {
            $tag = Tag::with('items')->where('user_id', auth()->id())->find($this->viewingTagId);
            if ($tag) {
                $viewingTagItems = $tag->items->toArray();
            }
        }

        return view('livewire.tag-list', compact('tags', 'allTags', 'unusedCount', 'viewingTagItems'));
    }

    private function resetForm(): void
    {
        $this->formName = '';
        $this->editingId = null;
        $this->editMode = false;
        $this->clearValidation();
    }
}
