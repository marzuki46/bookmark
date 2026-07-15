<?php

declare(strict_types=1);

namespace App\Livewire;

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

        return view('livewire.tag-list', compact('tags', 'allTags', 'unusedCount'));
    }

    private function resetForm(): void
    {
        $this->formName = '';
        $this->editingId = null;
        $this->editMode = false;
        $this->clearValidation();
    }
}
