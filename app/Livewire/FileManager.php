<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Attachment;
use App\Models\Item;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

final class FileManager extends Component
{
    use WithFileUploads, WithPagination;

    public string $search = '';

    public string $filter = 'all';

    public bool $showUploadModal = false;

    public $uploadFile;

    public string $uploadTitle = '';

    public bool $uploadFavorite = false;

    protected string $paginationTheme = 'tailwind';

    public function rules(): array
    {
        return [
            'uploadFile' => 'required|file|max:10240',
            'uploadTitle' => 'nullable|string|max:255',
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

    public function openUpload(): void
    {
        $this->resetForm();
        $this->showUploadModal = true;
    }

    public function closeModal(): void
    {
        $this->showUploadModal = false;
        $this->resetForm();
    }

    public function upload(): void
    {
        $this->validate();

        $file = $this->uploadFile;
        $originalName = $file->getClientOriginalName();
        $filename = time().'_'.Str::slug(pathinfo($originalName, PATHINFO_FILENAME)).'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs('uploads', $filename, 'public');

        $item = Item::create([
            'user_id' => auth()->id(),
            'type' => 'file',
            'title' => $this->uploadTitle ?: $originalName,
            'content' => $file->getClientMimeType(),
            'favorite' => $this->uploadFavorite,
            'metadata' => [
                'original_name' => $originalName,
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'path' => $path,
            ],
        ]);

        Attachment::create([
            'item_id' => $item->id,
            'filename' => $filename,
            'original_name' => $originalName,
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'collection_name' => 'default',
        ]);

        $this->closeModal();
    }

    public function toggleFavorite(int $id): void
    {
        $item = Item::where('user_id', auth()->id())->where('type', 'file')->findOrFail($id);
        $item->update(['favorite' => ! $item->favorite]);
    }

    public function download(int $id): void
    {
        $item = Item::where('user_id', auth()->id())->where('type', 'file')->findOrFail($id);
        $path = $item->metadata['path'] ?? null;
        if ($path && \Storage::disk('public')->exists($path)) {
            $this->dispatch('downloadFile', path: \Storage::disk('public')->path($path), name: $item->metadata['original_name'] ?? 'download');
        }
    }

    public function trash(int $id): void
    {
        $item = Item::where('user_id', auth()->id())->where('type', 'file')->findOrFail($id);
        $path = $item->metadata['path'] ?? null;
        if ($path && \Storage::disk('public')->exists($path)) {
            \Storage::disk('public')->delete($path);
        }
        $item->attachments()->delete();
        $item->delete();
    }

    public function render()
    {
        $query = Item::with(['attachments'])
            ->where('user_id', auth()->id())
            ->where('type', 'file');

        if ($this->filter === 'favorites') {
            $query->where('favorite', true);
        } elseif ($this->filter !== 'all' && $this->filter !== '') {
            $query->where('metadata->mime_type', 'like', $this->getMimeFilter($this->filter));
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                    ->orWhere('metadata->original_name', 'like', "%{$this->search}%");
            });
        }

        $files = $query->latest()->paginate(12);

        $stats = [
            'total' => Item::where('user_id', auth()->id())->where('type', 'file')->count(),
            'favorites' => Item::where('user_id', auth()->id())->where('type', 'file')->where('favorite', true)->count(),
            'totalSize' => Item::where('user_id', auth()->id())->where('type', 'file')->sum('metadata->size'),
        ];

        return view('livewire.file-manager', compact('files', 'stats'));
    }

    private function getMimeFilter(string $filter): string
    {
        return match ($filter) {
            'pdf' => 'application/pdf%',
            'image' => 'image/%',
            'video' => 'video/%',
            'audio' => 'audio/%',
            'zip' => 'application/zip%',
            default => '%',
        };
    }

    private function resetForm(): void
    {
        $this->uploadFile = null;
        $this->uploadTitle = '';
        $this->uploadFavorite = false;
        $this->clearValidation();
    }

    public static function formatSize(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2).' GB';
        }
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2).' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 2).' KB';
        }

        return $bytes.' B';
    }
}
