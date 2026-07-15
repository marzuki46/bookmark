<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-[var(--text-primary)]">Bookmarks</h1>
            <p class="text-sm text-[var(--text-tertiary)] mt-1">{{ $stats['total'] }} bookmarks saved</p>
        </div>
        <div class="flex items-center gap-2">
            <button wire:click="openImport" class="btn-secondary">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Import Chrome
            </button>
            <button wire:click="openCreate" class="btn-primary">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Bookmark
            </button>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-3 mb-5">
        <div class="relative flex-1 min-w-[200px] max-w-md">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--text-quaternary)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search bookmarks..."
                class="wp-form-input !pl-9 !py-2">
        </div>
        <div class="flex gap-1 bg-[var(--color-bg)] p-1 rounded-lg border border-[var(--color-border)]">
            <button wire:click="$set('filter', 'all')" class="px-3 py-1.5 text-xs font-medium rounded-md transition {{ $filter === 'all' ? 'bg-white text-[var(--text-primary)] shadow-sm' : 'text-[var(--text-tertiary)] hover:text-[var(--text-secondary)]' }}">All</button>
            <button wire:click="$set('filter', 'favorites')" class="px-3 py-1.5 text-xs font-medium rounded-md transition {{ $filter === 'favorites' ? 'bg-white text-[var(--text-primary)] shadow-sm' : 'text-[var(--text-tertiary)] hover:text-[var(--text-secondary)]' }}">Favorites</button>
            <button wire:click="$set('filter', 'archived')" class="px-3 py-1.5 text-xs font-medium rounded-md transition {{ $filter === 'archived' ? 'bg-white text-[var(--text-primary)] shadow-sm' : 'text-[var(--text-tertiary)] hover:text-[var(--text-secondary)]' }}">Archived</button>
        </div>
        <button wire:click="toggleView" class="p-2 rounded-lg border border-[var(--color-border)] text-[var(--text-tertiary)] hover:text-[var(--text-secondary)] hover:bg-[var(--color-surface-hover)] transition" title="Toggle view">
            @if($viewMode === 'list')
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
            @else
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
            @endif
        </button>
    </div>

    <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
        @if($bookmarks->isEmpty())
            <div class="empty-state">
                <div class="empty-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"/></svg>
                </div>
                <div class="empty-title">No bookmarks yet</div>
                <div class="empty-desc">Start saving your favorite links and organize them with tags.</div>
                <div class="empty-action">
                    <button wire:click="openCreate" class="btn-primary">Add Your First Bookmark</button>
                </div>
            </div>
        @elseif($viewMode === 'list')
            <div class="bookmark-list">
                @foreach($bookmarks as $bookmark)
                    <div class="bookmark-item group">
                        @if($bookmark->metadata['favicon'] ?? null)
                            <img src="{{ $bookmark->metadata['favicon'] }}" alt="" class="bookmark-favicon">
                        @else
                            <div class="bookmark-favicon-placeholder">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"/></svg>
                            </div>
                        @endif
                        <div class="bookmark-main">
                            <div class="bookmark-header">
                                <a href="{{ $bookmark->url }}" target="_blank" class="bookmark-title">{{ $bookmark->title ?? $bookmark->url }}</a>
                                <span class="bookmark-type">bookmark</span>
                            </div>
                            @if($bookmark->url)
                                <div class="bookmark-url">{{ $bookmark->url }}</div>
                            @endif
                            @if($bookmark->tags->isNotEmpty())
                                <div class="bookmark-tags">
                                    @foreach($bookmark->tags->take(3) as $tag)
                                        <span class="bookmark-tag">{{ $tag->name }}</span>
                                    @endforeach
                                    @if($bookmark->tags->count() > 3)
                                        <span class="bookmark-tag-more">+{{ $bookmark->tags->count() - 3 }}</span>
                                    @endif
                                </div>
                            @endif
                            @if($bookmark->content)
                                <div class="bookmark-ai-summary">{{ Str::limit($bookmark->content, 150) }}</div>
                            @endif
                        </div>
                        <div class="bookmark-meta">
                            <span class="bookmark-time">{{ $bookmark->created_at->diffForHumans() }}</span>
                            <div class="flex items-center gap-1 mt-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button wire:click="toggleFavorite({{ $bookmark->id }})" class="p-1 rounded hover:bg-[var(--color-bg)] transition" title="{{ $bookmark->favorite ? 'Unfavorite' : 'Favorite' }}">
                                    @if($bookmark->favorite)
                                        <svg class="w-4 h-4 text-amber-500" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                    @else
                                        <svg class="w-4 h-4 text-[var(--text-quaternary)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                    @endif
                                </button>
                                <button wire:click="openEdit({{ $bookmark->id }})" class="p-1 rounded hover:bg-[var(--color-bg)] transition" title="Edit">
                                    <svg class="w-4 h-4 text-[var(--text-quaternary)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </button>
                                <button wire:click="archive({{ $bookmark->id }})" class="p-1 rounded hover:bg-[var(--color-bg)] transition" title="{{ $bookmark->archived_at ? 'Unarchive' : 'Archive' }}">
                                    <svg class="w-4 h-4 text-[var(--text-quaternary)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>
                                </button>
                                <button wire:click="trash({{ $bookmark->id }})" wire:confirm="Delete this bookmark?" class="p-1 rounded hover:bg-[var(--red-50)] transition" title="Delete">
                                    <svg class="w-4 h-4 text-[var(--text-quaternary)] hover:text-[var(--red-500)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @foreach($bookmarks as $bookmark)
                    <div class="group border border-[var(--color-border)] rounded-xl p-4 hover:shadow-md hover:border-[var(--color-border-strong)] transition-all bg-[var(--color-surface)]">
                        <div class="flex items-start justify-between mb-3">
                            @if($bookmark->metadata['favicon'] ?? null)
                                <img src="{{ $bookmark->metadata['favicon'] }}" alt="" class="w-8 h-8 rounded-lg border border-[var(--color-border)]">
                            @else
                                <div class="w-8 h-8 rounded-lg bg-[var(--color-bg)] border border-[var(--color-border)] flex items-center justify-center text-[var(--text-quaternary)]">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"/></svg>
                                </div>
                            @endif
                            <button wire:click="toggleFavorite({{ $bookmark->id }})" class="p-1 rounded hover:bg-[var(--color-bg)] transition">
                                @if($bookmark->favorite)
                                    <svg class="w-4 h-4 text-amber-500" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                @else
                                    <svg class="w-4 h-4 text-[var(--text-quaternary)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                @endif
                            </button>
                        </div>
                        <a href="{{ $bookmark->url }}" target="_blank" class="font-medium text-sm text-[var(--text-primary)] hover:text-[var(--indigo-600)] transition line-clamp-2 mb-1">{{ $bookmark->title ?? $bookmark->url }}</a>
                        @if($bookmark->url)
                            <div class="text-xs text-[var(--text-quaternary)] truncate font-mono mb-2">{{ parse_url($bookmark->url, PHP_URL_HOST) ?? $bookmark->url }}</div>
                        @endif
                        @if($bookmark->tags->isNotEmpty())
                            <div class="flex flex-wrap gap-1 mb-3">
                                @foreach($bookmark->tags->take(2) as $tag)
                                    <span class="bookmark-tag">{{ $tag->name }}</span>
                                @endforeach
                            </div>
                        @endif
                        <div class="flex items-center justify-between pt-3 border-t border-[var(--color-border)]">
                            <span class="text-xs text-[var(--text-quaternary)]">{{ $bookmark->created_at->diffForHumans() }}</span>
                            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button wire:click="openEdit({{ $bookmark->id }})" class="p-1 rounded hover:bg-[var(--color-bg)] transition" title="Edit">
                                    <svg class="w-3.5 h-3.5 text-[var(--text-quaternary)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </button>
                                <button wire:click="trash({{ $bookmark->id }})" wire:confirm="Delete?" class="p-1 rounded hover:bg-[var(--red-50)] transition" title="Delete">
                                    <svg class="w-3.5 h-3.5 text-[var(--text-quaternary)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if($bookmarks->hasPages())
            <div class="px-4 py-3 border-t border-[var(--color-border)]">
                {{ $bookmarks->links() }}
            </div>
        @endif
    </div>

    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data>
            <div class="fixed inset-0 bg-black/50" wire:click="closeModal"></div>
            <div class="relative bg-[var(--color-surface)] rounded-xl shadow-xl w-full max-w-lg border border-[var(--color-border)]" @click.away="false">
                <div class="flex items-center justify-between px-6 py-4 border-b border-[var(--color-border)]">
                    <h3 class="text-lg font-semibold text-[var(--text-primary)]">{{ $editMode ? 'Edit Bookmark' : 'Add Bookmark' }}</h3>
                    <button wire:click="closeModal" class="p-1 rounded hover:bg-[var(--color-bg)] transition text-[var(--text-quaternary)]">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <form wire:submit="save" class="p-6 space-y-4">
                    <div>
                        <label class="wp-form-label">Title *</label>
                        <input type="text" wire:model="formTitle" class="wp-form-input" placeholder="My Bookmark">
                        @error('formTitle') <span class="text-xs text-[var(--red-500)] mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="wp-form-label">URL</label>
                        <input type="url" wire:model="formUrl" class="wp-form-input" placeholder="https://example.com">
                        @error('formUrl') <span class="text-xs text-[var(--red-500)] mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="wp-form-label">Notes</label>
                        <textarea wire:model="formContent" class="wp-form-input" rows="3" placeholder="Add some notes..."></textarea>
                    </div>
                    <div>
                        <label class="wp-form-label">Tags (comma separated)</label>
                        <input type="text" wire:model="formTags" class="wp-form-input" placeholder="laravel, php, tutorial">
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model="formFavorite" id="formFavorite" class="rounded border-[var(--color-border)]">
                        <label for="formFavorite" class="text-sm text-[var(--text-secondary)]">Mark as favorite</label>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" wire:click="closeModal" class="px-4 py-2 text-sm font-medium text-[var(--text-secondary)] rounded-lg border border-[var(--color-border)] hover:bg-[var(--color-bg)] transition">Cancel</button>
                        <button type="submit" class="btn-primary">{{ $editMode ? 'Update' : 'Save' }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if($showImportModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data>
            <div class="fixed inset-0 bg-black/50" wire:click="closeImportModal"></div>
            <div class="relative bg-[var(--color-surface)] rounded-xl shadow-xl w-full max-w-lg border border-[var(--color-border)]">
                <div class="flex items-center justify-between px-6 py-4 border-b border-[var(--color-border)]">
                    <h3 class="text-lg font-semibold text-[var(--text-primary)]">Import Chrome Bookmarks</h3>
                    <button wire:click="closeImportModal" class="p-1 rounded hover:bg-[var(--color-bg)] transition text-[var(--text-quaternary)]">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    @if(!$importFile)
                        <div class="text-center py-8 border-2 border-dashed border-[var(--color-border)] rounded-xl">
                            <svg class="w-12 h-12 mx-auto text-[var(--text-quaternary)] mb-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            <p class="text-sm text-[var(--text-secondary)] mb-1">Select your Chrome bookmarks HTML file</p>
                            <p class="text-xs text-[var(--text-quaternary)] mb-4">Go to <strong>chrome://bookmarks</strong> → ⋮ → <strong>Export bookmarks</strong></p>
                            <label class="btn-primary cursor-pointer inline-flex items-center gap-2">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                Choose File
                                <input type="file" wire:model="importFile" accept=".html,.htm,.txt" class="hidden">
                            </label>
                            @error('importFile') <p class="text-xs text-[var(--red-500)] mt-2">{{ $message }}</p> @enderror
                        </div>
                    @else
                        <div class="bg-[var(--color-bg)] rounded-lg p-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-[var(--indigo-50)] flex items-center justify-center">
                                    <svg class="w-5 h-5 text-[var(--indigo-500)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-[var(--text-primary)] truncate">{{ $importFile->getClientOriginalName() }}</p>
                                    <p class="text-xs text-[var(--text-quaternary)]">{{ number_format($importFile->getSize() / 1024, 1) }} KB</p>
                                </div>
                                <button wire:click="$set('importFile', null)" class="p-1 rounded hover:bg-[var(--color-surface-hover)] transition text-[var(--text-quaternary)]">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                </button>
                            </div>
                        </div>

                        @if($importPreview !== null)
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <p class="text-sm font-medium text-[var(--text-primary)]">{{ $importCount }} bookmarks found</p>
                                    @if($importCount > 20)
                                        <p class="text-xs text-[var(--text-quaternary)]">Showing first 20</p>
                                    @endif
                                </div>
                                <div class="max-h-60 overflow-y-auto border border-[var(--color-border)] rounded-lg">
                                    @foreach($importPreview as $item)
                                        <div class="px-3 py-2 border-b border-[var(--color-border)] last:border-0 text-sm">
                                            <div class="font-medium text-[var(--text-primary)] truncate">{{ $item['title'] ?? '(no title)' }}</div>
                                            <div class="text-xs text-[var(--text-quaternary)] truncate font-mono">{{ $item['url'] }}</div>
                                            @if(!empty($item['folder']))
                                                <div class="text-xs text-[var(--indigo-500)] mt-0.5">📁 {{ $item['folder'] }}</div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" wire:click="closeImportModal" class="px-4 py-2 text-sm font-medium text-[var(--text-secondary)] rounded-lg border border-[var(--color-border)] hover:bg-[var(--color-bg)] transition">Cancel</button>
                            <button type="button" wire:click="doImport" wire:loading.attr="disabled" class="btn-primary" {{ $importCount === 0 ? 'disabled' : '' }}>
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                Import {{ $importCount }} Bookmarks
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
