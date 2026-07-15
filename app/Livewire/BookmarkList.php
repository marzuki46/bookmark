<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Item;
use App\Models\Tag;
use App\Services\ChromeBookmarkParser;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

final class BookmarkList extends Component
{
    use WithFileUploads, WithPagination;

    public string $search = '';

    public string $filter = 'all';

    public bool $showModal = false;

    public bool $showImportModal = false;

    public bool $editMode = false;

    public ?int $editingId = null;

    public string $viewMode = 'list';

    public string $formTitle = '';

    public string $formUrl = '';

    public string $formContent = '';

    public string $formTags = '';

    public bool $formFavorite = false;

    public $importFile;

    public ?array $importPreview = null;

    public int $importCount = 0;

    protected string $paginationTheme = 'tailwind';

    public function rules(): array
    {
        return [
            'formTitle' => 'required|string|max:255',
            'formUrl' => 'nullable|url|max:2048',
            'formContent' => 'nullable|string|max:10000',
            'formTags' => 'nullable|string|max:500',
            'importFile' => 'required|file|mimes:html,htm,txt',
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

    public function toggleView(): void
    {
        $this->viewMode = $this->viewMode === 'list' ? 'grid' : 'list';
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->editMode = false;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $item = Item::where('user_id', auth()->id())->findOrFail($id);
        $this->editingId = $id;
        $this->editMode = true;
        $this->formTitle = $item->title ?? '';
        $this->formUrl = $item->url ?? '';
        $this->formContent = $item->content ?? '';
        $this->formTags = $item->tags->pluck('name')->implode(', ');
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
            'type' => 'bookmark',
            'title' => $this->formTitle,
            'url' => $this->formUrl ?: null,
            'content' => $this->formContent ?: null,
            'favorite' => $this->formFavorite,
        ];

        if ($this->editMode && $this->editingId) {
            $item = Item::where('user_id', auth()->id())->findOrFail($this->editingId);
            $item->update($data);
        } else {
            $item = Item::create($data);
        }

        $this->syncTags($item);
        $this->closeModal();
        $this->dispatch('bookmarkSaved');
    }

    public function toggleFavorite(int $id): void
    {
        $item = Item::where('user_id', auth()->id())->findOrFail($id);
        $item->update(['favorite' => ! $item->favorite]);
    }

    public function archive(int $id): void
    {
        $item = Item::where('user_id', auth()->id())->findOrFail($id);
        $item->update(['archived_at' => $item->archived_at ? null : now()]);
    }

    public function trash(int $id): void
    {
        $item = Item::where('user_id', auth()->id())->findOrFail($id);
        $item->delete();
    }

    public function openImport(): void
    {
        $this->importFile = null;
        $this->importPreview = null;
        $this->importCount = 0;
        $this->showImportModal = true;
        $this->clearValidation();
    }

    public function closeImportModal(): void
    {
        $this->showImportModal = false;
        $this->importFile = null;
        $this->importPreview = null;
        $this->importCount = 0;
        $this->clearValidation();
    }

    public function updatedImportFile(): void
    {
        $this->importPreview = null;
        $this->importCount = 0;

        if (! $this->importFile) {
            return;
        }

        try {
            $html = file_get_contents($this->importFile->getRealPath());
            $parser = new ChromeBookmarkParser;
            $bookmarks = $parser->parse($html);

            $this->importPreview = array_slice($bookmarks, 0, 20);
            $this->importCount = count($bookmarks);
        } catch (\Throwable $e) {
            $this->importPreview = null;
            $this->importCount = 0;
        }
    }

    public function doImport(): void
    {
        if (! $this->importFile) {
            return;
        }

        $html = file_get_contents($this->importFile->getRealPath());
        $parser = new ChromeBookmarkParser;
        $bookmarks = $parser->parse($html);

        $imported = 0;
        $userId = auth()->id();

        foreach ($bookmarks as $bookmark) {
            // Skip empty URLs
            if (empty($bookmark['url'])) {
                continue;
            }

            // Check duplicate URL
            $exists = Item::where('user_id', $userId)
                ->where('type', 'bookmark')
                ->where('url', $bookmark['url'])
                ->exists();

            if ($exists) {
                continue;
            }

            $item = Item::create([
                'user_id' => $userId,
                'type' => 'bookmark',
                'title' => Str::limit($bookmark['title'] ?: $bookmark['url'], 500, ''),
                'url' => $bookmark['url'],
            ]);

            // Auto-tag with folder name
            if (! empty($bookmark['folder'])) {
                $tagName = $bookmark['folder'];
                $slug = Str::slug($tagName);
                $tag = Tag::firstOrCreate(
                    ['user_id' => $userId, 'slug' => $slug],
                    ['name' => $tagName]
                );
                $item->tags()->sync([$tag->id]);
            }

            $imported++;
        }

        $this->closeImportModal();
        $this->dispatch('bookmarkSaved');
    }

    public function render()
    {
        $query = Item::with(['tags'])
            ->where('user_id', auth()->id())
            ->where('type', 'bookmark');

        match ($this->filter) {
            'favorites' => $query->where('favorite', true),
            'archived' => $query->whereNotNull('archived_at'),
            default => $query->whereNull('archived_at'),
        };

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                    ->orWhere('url', 'like', "%{$this->search}%")
                    ->orWhere('content', 'like', "%{$this->search}%");
            });
        }

        $bookmarks = $query->latest()->paginate(12);

        $stats = [
            'total' => Item::where('user_id', auth()->id())->where('type', 'bookmark')->whereNull('archived_at')->count(),
            'favorites' => Item::where('user_id', auth()->id())->where('type', 'bookmark')->where('favorite', true)->count(),
            'archived' => Item::where('user_id', auth()->id())->where('type', 'bookmark')->whereNotNull('archived_at')->count(),
        ];

        return view('livewire.bookmark-list', compact('bookmarks', 'stats'));
    }

    private function syncTags(Item $item): void
    {
        $tagNames = array_map('trim', explode(',', $this->formTags));
        $tagNames = array_filter($tagNames);

        $tagIds = [];
        foreach ($tagNames as $name) {
            $slug = Str::slug($name);
            $tag = Tag::firstOrCreate(
                ['user_id' => auth()->id(), 'slug' => $slug],
                ['name' => $name]
            );
            $tagIds[] = $tag->id;
        }

        $item->tags()->sync($tagIds);
    }

    private function resetForm(): void
    {
        $this->formTitle = '';
        $this->formUrl = '';
        $this->formContent = '';
        $this->formTags = '';
        $this->formFavorite = false;
        $this->editingId = null;
        $this->editMode = false;
        $this->clearValidation();
    }
}
