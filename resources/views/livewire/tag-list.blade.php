<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-[var(--text-primary)]">Tags</h1>
            <p class="text-sm text-[var(--text-tertiary)] mt-1">{{ count($allTags) }} tags total @if($unusedCount > 0) &middot; {{ $unusedCount }} unused @endif</p>
        </div>
        <button wire:click="openCreate" class="btn-primary">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Tag
        </button>
    </div>

    @if($statusMessage)
        <div class="mb-4 px-4 py-3 rounded-lg text-sm font-medium {{ $statusType === 'success' ? 'bg-[var(--emerald-50)] text-[var(--emerald-700)] border border-[var(--emerald-200)]' : 'bg-red-50 text-red-700 border border-red-200' }}"
            x-data x-init="setTimeout(() => $wire.clearStatusMessage(), 3000)">
            {{ $statusMessage }}
        </div>
    @endif

    <div class="flex flex-wrap items-center gap-3 mb-5">
        <div class="relative flex-1 min-w-[200px] max-w-md">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--text-quaternary)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search tags..."
                class="wp-form-input !pl-9 !py-2">
        </div>
    </div>

    @if($allTags->isNotEmpty())
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-5 mb-5">
            <h2 class="text-sm font-semibold text-[var(--text-primary)] mb-3">Tag Cloud</h2>
            <div class="flex flex-wrap gap-2">
                @php
                    $maxCount = max($allTags->pluck('items_count')->max(), 1);
                @endphp
                @foreach($allTags as $tag)
                    @php
                        $ratio = $tag->items_count / $maxCount;
                        $size = 0.75 + ($ratio * 0.5);
                    @endphp
                    <button wire:click="viewItems({{ $tag->id }})"
                        class="inline-flex items-center gap-1 px-3 py-1 rounded-full border border-[var(--color-border)] hover:border-[var(--indigo-500)] hover:bg-[var(--indigo-50)] transition cursor-pointer" style="font-size: {{ $size }}rem;">
                        {{ $tag->name }}
                        <span class="text-[var(--text-quaternary)] text-xs">({{ $tag->items_count }})</span>
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
        @if($tags->isEmpty())
            <div class="empty-state">
                <div class="empty-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7.01"/></svg>
                </div>
                <div class="empty-title">No tags yet</div>
                <div class="empty-desc">Tags help you categorize and find your content easily.</div>
                <div class="empty-action">
                    <button wire:click="openCreate" class="btn-primary">Create Your First Tag</button>
                </div>
            </div>
        @else
            <table class="w-full">
                <thead>
                    <tr class="border-b border-[var(--color-border)]">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-[var(--text-tertiary)] uppercase tracking-wider">Tag</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-[var(--text-tertiary)] uppercase tracking-wider">Items</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-[var(--text-tertiary)] uppercase tracking-wider">Created</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-[var(--text-tertiary)] uppercase tracking-wider w-32">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tags as $tag)
                        <tr class="border-b border-[var(--color-border)] last:border-b-0 hover:bg-[var(--color-surface-hover)] transition group">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="w-8 h-8 rounded-lg bg-[var(--indigo-50)] flex items-center justify-center text-[var(--indigo-600)] text-xs font-bold">{{ strtoupper(substr($tag->name, 0, 2)) }}</span>
                                    <span class="font-medium text-sm text-[var(--text-primary)]">{{ $tag->name }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[var(--color-bg)] text-[var(--text-secondary)]">{{ $tag->items_count ?? 0 }}</span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <span class="text-xs text-[var(--text-quaternary)]">{{ $tag->created_at->diffForHumans() }}</span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button wire:click="viewItems({{ $tag->id }})" class="px-2 py-1 text-[10px] font-medium rounded bg-[var(--indigo-50)] text-[var(--indigo-600)] hover:bg-[var(--indigo-100)] transition" title="Manage Items">
                                        Items
                                    </button>
                                    <button wire:click="openEdit({{ $tag->id }})" class="p-1.5 rounded-lg hover:bg-[var(--color-bg)] transition" title="Edit">
                                        <svg class="w-4 h-4 text-[var(--text-quaternary)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    </button>
                                    <button wire:click="delete({{ $tag->id }})" wire:confirm="Delete this tag?" class="p-1.5 rounded-lg hover:bg-[var(--red-50)] transition" title="Delete">
                                        <svg class="w-4 h-4 text-[var(--text-quaternary)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if($tags->hasPages())
            <div class="px-4 py-3 border-t border-[var(--color-border)]">
                {{ $tags->links() }}
            </div>
        @endif
    </div>

    {{-- Create/Edit Modal --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" wire:click="closeModal"></div>
            <div class="relative bg-[var(--color-surface)] rounded-xl shadow-xl w-full max-w-md border border-[var(--color-border)]">
                <div class="flex items-center justify-between px-6 py-4 border-b border-[var(--color-border)]">
                    <h3 class="text-lg font-semibold text-[var(--text-primary)]">{{ $editMode ? 'Edit Tag' : 'Add Tag' }}</h3>
                    <button wire:click="closeModal" class="p-1 rounded hover:bg-[var(--color-bg)] transition text-[var(--text-quaternary)]">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <form wire:submit="save" class="p-6 space-y-4">
                    <div>
                        <label class="wp-form-label">Tag Name *</label>
                        <input type="text" wire:model="formName" class="wp-form-input" placeholder="laravel">
                        @error('formName') <span class="text-xs text-[var(--red-500)] mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" wire:click="closeModal" class="px-4 py-2 text-sm font-medium text-[var(--text-secondary)] rounded-lg border border-[var(--color-border)] hover:bg-[var(--color-bg)] transition">Cancel</button>
                        <button type="submit" class="btn-primary">{{ $editMode ? 'Update' : 'Save' }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Manage Items Modal --}}
    @if($showItemsModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" wire:click="closeItemsModal"></div>
            <div class="relative bg-[var(--color-surface)] rounded-xl shadow-xl w-full max-w-3xl max-h-[85vh] border border-[var(--color-border)] flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-[var(--color-border)]">
                    <div>
                        <h3 class="text-lg font-semibold text-[var(--text-primary)]">Items in tag: {{ $viewingTagName }}</h3>
                        <p class="text-xs text-[var(--text-tertiary)]">Click items to attach/detach from this tag</p>
                    </div>
                    <button wire:click="closeItemsModal" class="p-1 rounded hover:bg-[var(--color-bg)] transition text-[var(--text-quaternary)]">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                <div class="px-6 py-3 border-b border-[var(--color-border)]">
                    <input type="text" wire:model.live.debounce.300ms="assignSearch" placeholder="Search items to add..."
                        class="wp-form-input text-sm">
                </div>

                <div class="flex-1 overflow-y-auto p-6 space-y-2">
                    @php
                        $attachedIds = collect($viewingTagItems)->pluck('id')->toArray();
                    @endphp

                    @if(!empty($viewingTagItems))
                        <h4 class="text-xs font-semibold text-[var(--text-tertiary)] uppercase tracking-wider mb-2">Currently Attached ({{ count($viewingTagItems) }})</h4>
                        @foreach($viewingTagItems as $item)
                            <div class="flex items-center gap-3 p-3 rounded-lg bg-[var(--indigo-50)] border border-[var(--indigo-200)]">
                                <span class="w-2 h-2 rounded-full flex-shrink-0 @if($item['type'] === 'bookmark') bg-[var(--indigo-500)] @elseif($item['type'] === 'note') bg-[var(--emerald-500)] @elseif($item['type'] === 'snippet') bg-[var(--amber-500)] @else bg-[var(--text-quaternary)] @endif"></span>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-[var(--text-primary)] truncate">{{ $item['title'] ?? 'Untitled' }}</div>
                                    <div class="text-[10px] text-[var(--text-quaternary)]">{{ $item['type'] }}</div>
                                </div>
                                <button wire:click="detachItem({{ $item['id'] }})" class="px-2 py-1 text-[10px] font-medium rounded bg-red-50 text-red-600 hover:bg-red-100 transition">Remove</button>
                            </div>
                        @endforeach
                    @endif

                    <h4 class="text-xs font-semibold text-[var(--text-tertiary)] uppercase tracking-wider mt-4 mb-2">Available Items</h4>
                    @forelse($assignableItems as $item)
                        @if(!in_array($item['id'], $attachedIds))
                            <div class="flex items-center gap-3 p-3 rounded-lg border border-[var(--color-border)] hover:bg-[var(--color-bg)] transition">
                                <span class="w-2 h-2 rounded-full flex-shrink-0 @if($item['type'] === 'bookmark') bg-[var(--indigo-500)] @elseif($item['type'] === 'note') bg-[var(--emerald-500)] @elseif($item['type'] === 'snippet') bg-[var(--amber-500)] @else bg-[var(--text-quaternary)] @endif"></span>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm text-[var(--text-primary)] truncate">{{ $item['title'] ?? 'Untitled' }}</div>
                                    <div class="text-[10px] text-[var(--text-quaternary)]">{{ $item['type'] }} &middot; {{ \Carbon\Carbon::parse($item['created_at'])->diffForHumans() }}</div>
                                </div>
                                <button wire:click="attachItem({{ $item['id'] }})" class="px-2 py-1 text-[10px] font-medium rounded bg-[var(--indigo-50)] text-[var(--indigo-600)] hover:bg-[var(--indigo-100)] transition">Add</button>
                            </div>
                        @endif
                    @empty
                        <p class="text-sm text-[var(--text-tertiary)] text-center py-4">No items found</p>
                    @endforelse
                </div>

                <div class="px-6 py-3 border-t border-[var(--color-border)] flex justify-end">
                    <button wire:click="closeItemsModal" class="px-4 py-2 text-sm font-medium rounded-lg border border-[var(--color-border)] hover:bg-[var(--color-bg)] transition">Close</button>
                </div>
            </div>
        </div>
    @endif
</div>
