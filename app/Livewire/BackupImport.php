<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Collection;
use App\Models\Item;
use App\Models\Tag;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

final class BackupImport extends Component
{
    use WithFileUploads;

    public $importFile;

    public ?array $importPreview = null;

    public int $importCount = 0;

    public string $importType = '';

    public bool $showImportModal = false;

    public string $statusMessage = '';

    public string $statusType = 'success';

    public int $statsBookmarks = 0;

    public int $statsNotes = 0;

    public int $statsPrompts = 0;

    public int $statsSnippets = 0;

    public int $statsFiles = 0;

    public int $statsSecrets = 0;

    public int $statsWorksheets = 0;

    public int $statsTags = 0;

    public int $statsCollections = 0;

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->refreshStats();
    }

    public function refreshStats(): void
    {
        $userId = auth()->id();
        $this->statsBookmarks = Item::where('user_id', $userId)->where('type', 'bookmark')->count();
        $this->statsNotes = Item::where('user_id', $userId)->where('type', 'note')->count();
        $this->statsPrompts = Item::where('user_id', $userId)->where('type', 'prompt')->count();
        $this->statsSnippets = Item::where('user_id', $userId)->where('type', 'snippet')->count();
        $this->statsFiles = Item::where('user_id', $userId)->where('type', 'file')->count();
        $this->statsSecrets = Item::where('user_id', $userId)->where('type', 'secret')->count();
        $this->statsWorksheets = Item::where('user_id', $userId)->where('type', 'worksheet')->count();
        $this->statsTags = Tag::where('user_id', $userId)->count();
        $this->statsCollections = Collection::where('user_id', $userId)->count();
    }

    public function openImport(): void
    {
        $this->importFile = null;
        $this->importPreview = null;
        $this->importCount = 0;
        $this->importType = '';
        $this->showImportModal = true;
        $this->clearValidation();
    }

    public function closeImportModal(): void
    {
        $this->showImportModal = false;
        $this->importFile = null;
        $this->importPreview = null;
        $this->importCount = 0;
        $this->importType = '';
        $this->clearValidation();
    }

    public function updatedImportFile(): void
    {
        $this->importPreview = null;
        $this->importCount = 0;
        $this->importType = '';

        if (! $this->importFile) {
            return;
        }

        try {
            $json = file_get_contents($this->importFile->getRealPath());
            $data = json_decode($json, true);

            if (! is_array($data) || ! isset($data['items'])) {
                $this->importType = 'invalid';
                $this->statusMessage = 'Invalid backup file format.';
                $this->statusType = 'error';

                return;
            }

            $this->importType = 'valid';
            $this->importCount = count($data['items']);
            $this->importPreview = array_slice($data['items'], 0, 10);
        } catch (\Throwable $e) {
            $this->importType = 'invalid';
            $this->statusMessage = 'Failed to read backup file.';
            $this->statusType = 'error';
        }
    }

    public function doImport(): void
    {
        if (! $this->importFile || $this->importType !== 'valid') {
            return;
        }

        $json = file_get_contents($this->importFile->getRealPath());
        $data = json_decode($json, true);

        $userId = auth()->id();
        $imported = 0;

        // Import collections first
        $collectionMap = [];
        if (! empty($data['collections'])) {
            foreach ($data['collections'] as $colData) {
                $slug = Str::slug($colData['name']);
                $collection = Collection::firstOrCreate(
                    ['user_id' => $userId, 'slug' => $slug],
                    [
                        'name' => $colData['name'],
                        'description' => $colData['description'] ?? '',
                    ]
                );
                $collectionMap[$colData['name']] = $collection->id;
            }
        }

        // Import items
        foreach ($data['items'] as $itemData) {
            // Skip files (can't restore file content)
            if ($itemData['type'] === 'file') {
                continue;
            }

            // Check duplicate URL for bookmarks
            if ($itemData['type'] === 'bookmark' && ! empty($itemData['url'])) {
                $exists = Item::where('user_id', $userId)
                    ->where('type', 'bookmark')
                    ->where('url', $itemData['url'])
                    ->exists();

                if ($exists) {
                    continue;
                }
            }

            $item = Item::create([
                'user_id' => $userId,
                'type' => $itemData['type'] ?? 'bookmark',
                'title' => $itemData['title'] ?? '(untitled)',
                'url' => $itemData['url'] ?? null,
                'content' => $itemData['content'] ?? null,
                'metadata' => $itemData['metadata'] ?? null,
                'favorite' => $itemData['favorite'] ?? false,
            ]);

            // Sync tags
            if (! empty($itemData['tags']) && is_array($itemData['tags'])) {
                $tagIds = [];
                foreach ($itemData['tags'] as $tagName) {
                    $slug = Str::slug($tagName);
                    $tag = Tag::firstOrCreate(
                        ['user_id' => $userId, 'slug' => $slug],
                        ['name' => $tagName]
                    );
                    $tagIds[] = $tag->id;
                }
                $item->tags()->sync($tagIds);
            }

            // Sync collections
            if (! empty($itemData['collections']) && is_array($itemData['collections'])) {
                $colIds = [];
                foreach ($itemData['collections'] as $colName) {
                    if (isset($collectionMap[$colName])) {
                        $colIds[] = $collectionMap[$colName];
                    }
                }
                $item->collections()->sync($colIds);
            }

            $imported++;
        }

        $this->refreshStats();
        $this->closeImportModal();
        $this->statusMessage = "Successfully imported {$imported} items.";
        $this->statusType = 'success';
    }

    public function clearStatusMessage(): void
    {
        $this->statusMessage = '';
    }

    public function render()
    {
        return view('livewire.backup-import');
    }
}
