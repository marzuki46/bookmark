<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-[var(--text-primary)]">File Manager</h1>
            <p class="text-sm text-[var(--text-tertiary)] mt-1">{{ $stats['total'] }} files &middot; {{ \App\Livewire\FileManager::formatSize((int) $stats['totalSize']) }} used</p>
        </div>
        <button wire:click="openUpload" class="btn-primary">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            Upload File
        </button>
    </div>

    <div class="flex flex-wrap items-center gap-3 mb-5">
        <div class="relative flex-1 min-w-[200px] max-w-md">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--text-quaternary)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search files..."
                class="wp-form-input !pl-9 !py-2">
        </div>
        <div class="flex gap-1 bg-[var(--color-bg)] p-1 rounded-lg border border-[var(--color-border)] flex-wrap">
            <button wire:click="$set('filter', 'all')" class="px-3 py-1.5 text-xs font-medium rounded-md transition {{ $filter === 'all' ? 'bg-white text-[var(--text-primary)] shadow-sm' : 'text-[var(--text-tertiary)] hover:text-[var(--text-secondary)]' }}">All</button>
            <button wire:click="$set('filter', 'favorites')" class="px-3 py-1.5 text-xs font-medium rounded-md transition {{ $filter === 'favorites' ? 'bg-white text-[var(--text-primary)] shadow-sm' : 'text-[var(--text-tertiary)] hover:text-[var(--text-secondary)]' }}">Favorites</button>
            <button wire:click="$set('filter', 'pdf')" class="px-3 py-1.5 text-xs font-medium rounded-md transition {{ $filter === 'pdf' ? 'bg-white text-[var(--text-primary)] shadow-sm' : 'text-[var(--text-tertiary)] hover:text-[var(--text-secondary)]' }}">PDF</button>
            <button wire:click="$set('filter', 'image')" class="px-3 py-1.5 text-xs font-medium rounded-md transition {{ $filter === 'image' ? 'bg-white text-[var(--text-primary)] shadow-sm' : 'text-[var(--text-tertiary)] hover:text-[var(--text-secondary)]' }}">Images</button>
            <button wire:click="$set('filter', 'video')" class="px-3 py-1.5 text-xs font-medium rounded-md transition {{ $filter === 'video' ? 'bg-white text-[var(--text-primary)] shadow-sm' : 'text-[var(--text-tertiary)] hover:text-[var(--text-secondary)]' }}">Video</button>
            <button wire:click="$set('filter', 'zip')" class="px-3 py-1.5 text-xs font-medium rounded-md transition {{ $filter === 'zip' ? 'bg-white text-[var(--text-primary)] shadow-sm' : 'text-[var(--text-tertiary)] hover:text-[var(--text-secondary)]' }}">ZIP</button>
        </div>
    </div>

    <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
        @if($files->isEmpty())
            <div class="empty-state">
                <div class="empty-icon" style="background: var(--amber-50); color: var(--amber-600);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>
                </div>
                <div class="empty-title">No files yet</div>
                <div class="empty-desc">Upload PDFs, images, documents, and more.</div>
                <div class="empty-action">
                    <button wire:click="openUpload" class="btn-primary">Upload Your First File</button>
                </div>
            </div>
        @else
            <div class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @foreach($files as $file)
                    @php
                        $mime = $file->metadata['mime_type'] ?? '';
                        $isImage = str_starts_with($mime, 'image/');
                        $isPdf = str_contains($mime, 'pdf');
                        $isVideo = str_starts_with($mime, 'video/');
                        $isAudio = str_starts_with($mime, 'audio/');
                        $isZip = str_contains($mime, 'zip');
                    @endphp
                    <div class="group border border-[var(--color-border)] rounded-xl overflow-hidden hover:shadow-md hover:border-[var(--color-border-strong)] transition-all bg-[var(--color-surface)]">
                        <div class="h-32 flex items-center justify-center @if($isPdf) bg-red-50 @elseif($isImage) bg-blue-50 @elseif($isVideo) bg-purple-50 @elseif($isAudio) bg-green-50 @elseif($isZip) bg-amber-50 @else bg-[var(--color-bg)] @endif">
                            @if($isImage && $file->metadata['path'] ?? null)
                                <img src="{{ Storage::url($file->metadata['path']) }}" alt="" class="w-full h-full object-cover">
                            @elseif($isPdf)
                                <svg class="w-10 h-10 text-red-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>
                            @elseif($isVideo)
                                <svg class="w-10 h-10 text-purple-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
                            @elseif($isAudio)
                                <svg class="w-10 h-10 text-green-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
                            @elseif($isZip)
                                <svg class="w-10 h-10 text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 8v13H3V3h13"/><path d="M16 3v5h5"/></svg>
                            @else
                                <svg class="w-10 h-10 text-[var(--text-quaternary)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>
                            @endif
                        </div>
                        <div class="p-4">
                            <div class="flex items-start justify-between mb-1">
                                <h3 class="font-medium text-sm text-[var(--text-primary)] line-clamp-1 flex-1">{{ $file->title }}</h3>
                                <button wire:click="toggleFavorite({{ $file->id }})" class="p-1 rounded hover:bg-[var(--color-bg)] transition shrink-0">
                                    @if($file->favorite)
                                        <svg class="w-3.5 h-3.5 text-amber-500" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                    @else
                                        <svg class="w-3.5 h-3.5 text-[var(--text-quaternary)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                    @endif
                                </button>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-[var(--text-quaternary)]">{{ \App\Livewire\FileManager::formatSize((int) ($file->metadata['size'] ?? 0)) }}</span>
                                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <a href="{{ route('files.download', $file->id) }}" class="p-1 rounded hover:bg-[var(--color-bg)] transition" title="Download">
                                        <svg class="w-3.5 h-3.5 text-[var(--text-quaternary)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                    </a>
                                    <button wire:click="trash({{ $file->id }})" wire:confirm="Delete this file?" class="p-1 rounded hover:bg-[var(--red-50)] transition" title="Delete">
                                        <svg class="w-3.5 h-3.5 text-[var(--text-quaternary)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if($files->hasPages())
            <div class="px-4 py-3 border-t border-[var(--color-border)]">
                {{ $files->links() }}
            </div>
        @endif
    </div>

    @if($showUploadModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" wire:click="closeModal"></div>
            <div class="relative bg-[var(--color-surface)] rounded-xl shadow-xl w-full max-w-lg border border-[var(--color-border)]">
                <div class="flex items-center justify-between px-6 py-4 border-b border-[var(--color-border)]">
                    <h3 class="text-lg font-semibold text-[var(--text-primary)]">Upload File</h3>
                    <button wire:click="closeModal" class="p-1 rounded hover:bg-[var(--color-bg)] transition text-[var(--text-quaternary)]">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <form wire:submit="upload" class="p-6 space-y-4">
                    <div>
                        <label class="wp-form-label">File *</label>
                        <input type="file" wire:model="uploadFile" class="wp-form-input" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar,.jpg,.jpeg,.png,.gif,.svg,.mp4,.mp3,.wav,.txt,.csv">
                        @error('uploadFile') <span class="text-xs text-[var(--red-500)] mt-1">{{ $message }}</span> @enderror
                        @if($uploadFile)
                            <p class="text-xs text-[var(--text-tertiary)] mt-1">Selected: {{ $uploadFile->getClientOriginalName() }} ({{ round($uploadFile->getSize() / 1024) }} KB)</p>
                        @endif
                    </div>
                    <div>
                        <label class="wp-form-label">Title (optional)</label>
                        <input type="text" wire:model="uploadTitle" class="wp-form-input" placeholder="File name">
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model="uploadFavorite" id="uploadFavorite" class="rounded border-[var(--color-border)]">
                        <label for="uploadFavorite" class="text-sm text-[var(--text-secondary)]">Mark as favorite</label>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" wire:click="closeModal" class="px-4 py-2 text-sm font-medium text-[var(--text-secondary)] rounded-lg border border-[var(--color-border)] hover:bg-[var(--color-bg)] transition">Cancel</button>
                        <button type="submit" class="btn-primary" wire:loading.attr="disabled>Upload</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
