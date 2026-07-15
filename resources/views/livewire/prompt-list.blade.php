<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-[var(--text-primary)]">AI Prompts</h1>
            <p class="text-sm text-[var(--text-tertiary)] mt-1">{{ $stats['total'] }} prompts saved</p>
        </div>
        <button wire:click="openCreate" class="btn-primary">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Prompt
        </button>
    </div>

    <div class="flex flex-wrap items-center gap-3 mb-5">
        <div class="relative flex-1 min-w-[200px] max-w-md">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--text-quaternary)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search prompts..."
                class="wp-form-input !pl-9 !py-2">
        </div>
        <div class="flex gap-1 bg-[var(--color-bg)] p-1 rounded-lg border border-[var(--color-border)]">
            <button wire:click="$set('filter', 'all')" class="px-3 py-1.5 text-xs font-medium rounded-md transition {{ $filter === 'all' ? 'bg-white text-[var(--text-primary)] shadow-sm' : 'text-[var(--text-tertiary)] hover:text-[var(--text-secondary)]' }}">All</button>
            <button wire:click="$set('filter', 'favorites')" class="px-3 py-1.5 text-xs font-medium rounded-md transition {{ $filter === 'favorites' ? 'bg-white text-[var(--text-primary)] shadow-sm' : 'text-[var(--text-tertiary)] hover:text-[var(--text-secondary)]' }}">Favorites</button>
        </div>
    </div>

    @if($categories->isNotEmpty())
        <div class="flex flex-wrap gap-2 mb-5">
            @foreach($categories as $cat)
                <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium bg-[var(--violet-50)] text-[var(--violet-600)] border border-[var(--violet-100)]">
                    <svg class="w-3 h-3 mr-1.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4"/><path d="M12 18v4"/><path d="M4.93 4.93l2.83 2.83"/><path d="M16.24 16.24l2.83 2.83"/></svg>
                    {{ $cat }}
                </span>
            @endforeach
        </div>
    @endif

    <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
        @if($prompts->isEmpty())
            <div class="empty-state">
                <div class="empty-icon" style="background: var(--violet-50); color: var(--violet-600);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4"/><path d="M12 18v4"/><path d="M4.93 4.93l2.83 2.83"/><path d="M16.24 16.24l2.83 2.83"/><path d="M2 12h4"/><path d="M18 12h4"/></svg>
                </div>
                <div class="empty-title">No prompts yet</div>
                <div class="empty-desc">Save reusable AI prompts for SEO, coding, marketing, and more.</div>
                <div class="empty-action">
                    <button wire:click="openCreate" class="btn-primary">Create Your First Prompt</button>
                </div>
            </div>
        @else
            <div class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($prompts as $prompt)
                    <div class="group border border-[var(--color-border)] rounded-xl p-4 hover:shadow-md hover:border-[var(--color-border-strong)] transition-all bg-[var(--color-surface)] flex flex-col">
                        <div class="flex items-start justify-between mb-2">
                            @if($prompt->metadata['category'] ?? null)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wider bg-[var(--violet-50)] text-[var(--violet-600)]">{{ $prompt->metadata['category'] }}</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wider bg-[var(--color-bg)] text-[var(--text-quaternary)]">General</span>
                            @endif
                            <button wire:click="toggleFavorite({{ $prompt->id }})" class="p-1 rounded hover:bg-[var(--color-bg)] transition">
                                @if($prompt->favorite)
                                    <svg class="w-4 h-4 text-amber-500" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                @else
                                    <svg class="w-4 h-4 text-[var(--text-quaternary)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                @endif
                            </button>
                        </div>
                        <h3 class="font-medium text-sm text-[var(--text-primary)] line-clamp-2 mb-2">{{ $prompt->title }}</h3>
                        @if($prompt->content)
                            <p class="text-xs text-[var(--text-tertiary)] line-clamp-4 flex-1 leading-relaxed font-mono bg-[var(--color-bg)] rounded-lg p-2">{{ Str::limit($prompt->content, 200) }}</p>
                        @endif
                        <div class="flex items-center justify-between mt-3 pt-3 border-t border-[var(--color-border)]">
                            <span class="text-xs text-[var(--text-quaternary)]">{{ $prompt->created_at->diffForHumans() }}</span>
                            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button wire:click="openEdit({{ $prompt->id }})" class="p-1 rounded hover:bg-[var(--color-bg)] transition" title="Edit">
                                    <svg class="w-3.5 h-3.5 text-[var(--text-quaternary)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </button>
                                <button wire:click="trash({{ $prompt->id }})" wire:confirm="Delete this prompt?" class="p-1 rounded hover:bg-[var(--red-50)] transition" title="Delete">
                                    <svg class="w-3.5 h-3.5 text-[var(--text-quaternary)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if($prompts->hasPages())
            <div class="px-4 py-3 border-t border-[var(--color-border)]">
                {{ $prompts->links() }}
            </div>
        @endif
    </div>

    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" wire:click="closeModal"></div>
            <div class="relative bg-[var(--color-surface)] rounded-xl shadow-xl w-full max-w-2xl border border-[var(--color-border)]">
                <div class="flex items-center justify-between px-6 py-4 border-b border-[var(--color-border)]">
                    <h3 class="text-lg font-semibold text-[var(--text-primary)]">{{ $editMode ? 'Edit Prompt' : 'Add Prompt' }}</h3>
                    <button wire:click="closeModal" class="p-1 rounded hover:bg-[var(--color-bg)] transition text-[var(--text-quaternary)]">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <form wire:submit="save" class="p-6 space-y-4">
                    <div class="grid grid-cols-3 gap-4">
                        <div class="col-span-2">
                            <label class="wp-form-label">Title *</label>
                            <input type="text" wire:model="formTitle" class="wp-form-input" placeholder="SEO Blog Post Prompt">
                            @error('formTitle') <span class="text-xs text-[var(--red-500)] mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="wp-form-label">Category</label>
                            <input type="text" wire:model="formCategory" class="wp-form-input" placeholder="SEO" list="categoryList">
                            <datalist id="categoryList">
                                @foreach($categories as $cat)
                                    <option value="{{ $cat }}">
                                @endforeach
                            </datalist>
                        </div>
                    </div>
                    <div>
                        <label class="wp-form-label">Prompt Content *</label>
                        <textarea wire:model="formContent" class="wp-form-input" rows="8" placeholder="Write your prompt here..." style="font-family: ui-monospace, SFMono-Regular, Menlo, monospace;"></textarea>
                        @error('formContent') <span class="text-xs text-[var(--red-500)] mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model="formFavorite" id="promptFavorite" class="rounded border-[var(--color-border)]">
                        <label for="promptFavorite" class="text-sm text-[var(--text-secondary)]">Mark as favorite</label>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" wire:click="closeModal" class="px-4 py-2 text-sm font-medium text-[var(--text-secondary)] rounded-lg border border-[var(--color-border)] hover:bg-[var(--color-bg)] transition">Cancel</button>
                        <button type="submit" class="btn-primary">{{ $editMode ? 'Update' : 'Save' }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
