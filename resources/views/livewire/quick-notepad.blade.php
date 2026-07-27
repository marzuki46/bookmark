<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-[var(--text-primary)]">Quick Notepad</h1>
            <p class="text-sm text-[var(--text-tertiary)] mt-1">Catatan cepat seperti Google Keep</p>
        </div>
        <button wire:click="$set('showNewNote', true)" class="btn-primary">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            New Note
        </button>
    </div>

    <div class="flex flex-wrap items-center gap-3 mb-5">
        <div class="relative flex-1 min-w-[200px] max-w-md">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--text-quaternary)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search notes..."
                class="wp-form-input !pl-9 !py-2">
        </div>
        <div class="flex gap-1 bg-[var(--color-bg)] p-1 rounded-lg border border-[var(--color-border)]">
            <button wire:click="$set('sortBy', 'recent')" class="px-3 py-1.5 text-xs font-medium rounded-md transition {{ $sortBy === 'recent' ? 'bg-white text-[var(--text-primary)] shadow-sm' : 'text-[var(--text-tertiary)] hover:text-[var(--text-secondary)]' }}">Recent</button>
            <button wire:click="$set('sortBy', 'alpha')" class="px-3 py-1.5 text-xs font-medium rounded-md transition {{ $sortBy === 'alpha' ? 'bg-white text-[var(--text-primary)] shadow-sm' : 'text-[var(--text-tertiary)] hover:text-[var(--text-secondary)]' }}">A-Z</button>
        </div>
    </div>

    @if($showNewNote)
        <div class="mb-5 p-4 rounded-xl border-2 border-dashed border-[var(--indigo-300)] bg-[var(--indigo-50)]">
            <div class="flex items-center gap-2 mb-3">
                <input type="text" wire:model="newNoteTitle" placeholder="Note title..."
                    class="flex-1 px-3 py-2 text-sm bg-white border border-[var(--color-border)] rounded-lg focus:outline-none focus:border-[var(--indigo-500)]"
                    autofocus>
                <div class="flex gap-1">
                    @foreach($colors as $color => $name)
                        <button wire:click="$set('newNoteColor', '{{ $color }}')"
                            class="w-6 h-6 rounded-full border-2 {{ $newNoteColor === $color ? 'border-[var(--indigo-500)] scale-110' : 'border-transparent' }}"
                            style="background-color: {{ $color }}" title="{{ $name }}"></button>
                    @endforeach
                </div>
            </div>
            <textarea wire:model="newNoteContent" placeholder="Write your note..." rows="3"
                class="w-full px-3 py-2 text-sm bg-white border border-[var(--color-border)] rounded-lg focus:outline-none focus:border-[var(--indigo-500)] resize-none"></textarea>
            <div class="flex justify-end gap-2 mt-3">
                <button wire:click="$set('showNewNote', false)" class="px-3 py-1.5 text-xs font-medium text-[var(--text-secondary)] rounded-lg border border-[var(--color-border)] hover:bg-[var(--color-bg)] transition">Cancel</button>
                <button wire:click="createNote" class="px-3 py-1.5 text-xs font-medium text-white rounded-lg bg-[var(--indigo-500)] hover:bg-[var(--indigo-600)] transition">Save</button>
            </div>
        </div>
    @endif

    @if($notes->isEmpty())
        <div class="empty-state">
            <div class="empty-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>
            </div>
            <div class="empty-title">No notes yet</div>
            <div class="empty-desc">Create your first quick note!</div>
            <div class="empty-action">
                <button wire:click="$set('showNewNote', true)" class="btn-primary">Create Note</button>
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @foreach($notes as $note)
                @if(!empty($editingNote['id']) && $editingNote['id'] === $note->id)
                    <div class="rounded-xl p-4 border-2 border-[var(--indigo-300)] shadow-lg" style="background-color: {{ $editingNote['color'] }}">
                        <input type="text" wire:model="editingNote.title" placeholder="Title..."
                            class="w-full px-2 py-1 text-sm font-semibold bg-transparent border-none focus:outline-none focus:ring-0 mb-2">
                        <textarea wire:model="editingNote.content" placeholder="Write..." rows="4"
                            class="w-full px-2 py-1 text-sm bg-transparent border-none focus:outline-none focus:ring-0 resize-none"></textarea>
                        <div class="flex items-center justify-between mt-2 pt-2 border-t border-black/10">
                            <div class="flex gap-1">
                                @foreach($colors as $color => $name)
                                    <button wire:click="setColor({{ $note->id }}, '{{ $color }}')"
                                        class="w-5 h-5 rounded-full border {{ ($editingNote['color'] ?? '') === $color ? 'border-[var(--indigo-500)] scale-110' : 'border-black/20' }}"
                                        style="background-color: {{ $color }}" title="{{ $name }}"></button>
                                @endforeach
                            </div>
                            <div class="flex gap-1">
                                <button wire:click="saveEdit" class="px-2 py-1 text-xs font-medium text-white bg-[var(--indigo-500)] rounded hover:bg-[var(--indigo-600)]">Save</button>
                                <button wire:click="cancelEdit" class="px-2 py-1 text-xs font-medium text-[var(--text-secondary)] rounded hover:bg-black/5">Cancel</button>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="group rounded-xl p-4 hover:shadow-md transition-all cursor-pointer relative"
                         style="background-color: {{ $note->metadata['color'] ?? '#fef3c7' }}"
                         wire:click="startEditing({{ $note->id }})">
                        @if($note->metadata['pinned'] ?? false)
                            <div class="absolute top-2 right-2">
                                <svg class="w-4 h-4 text-[var(--amber-600)]" viewBox="0 0 24 24" fill="currentColor"><path d="M16 12V4h1V2H7v2h1v8l-2 2v2h5.2v6h1.6v-6H18v-2l-2-2z"/></svg>
                            </div>
                        @endif

                        <h3 class="font-semibold text-sm text-[var(--text-primary)] line-clamp-2 mb-1 pr-6">{{ $note->title ?? 'Untitled' }}</h3>

                        @if($note->content)
                            <p class="text-xs text-[var(--text-secondary)] line-clamp-6 leading-relaxed">{{ Str::limit($note->content, 300) }}</p>
                        @endif

                        <div class="flex items-center justify-between mt-3 pt-2 border-t border-black/5">
                            <span class="text-[10px] text-[var(--text-quaternary)]">{{ $note->created_at->diffForHumans() }}</span>
                            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity" onclick="event.stopPropagation()">
                                <button wire:click.stop="togglePin({{ $note->id }})" class="p-1 rounded hover:bg-black/5 transition" title="Pin">
                                    <svg class="w-3 h-3 {{ ($note->metadata['pinned'] ?? false) ? 'text-[var(--amber-600)]' : 'text-[var(--text-quaternary)]' }}" viewBox="0 0 24 24" fill="currentColor"><path d="M16 12V4h1V2H7v2h1v8l-2 2v2h5.2v6h1.6v-6H18v-2l-2-2z"/></svg>
                                </button>
                                <button wire:click.stop="toggleFavorite({{ $note->id }})" class="p-1 rounded hover:bg-black/5 transition" title="Favorite">
                                    @if($note->favorite)
                                        <svg class="w-3 h-3 text-amber-500" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                    @else
                                        <svg class="w-3 h-3 text-[var(--text-quaternary)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                    @endif
                                </button>
                                <button wire:click.stop="$set('pickingColorFor', {{ $note->id }}); $set('showColorPicker', true)" class="p-1 rounded hover:bg-black/5 transition" title="Color">
                                    <svg class="w-3 h-3 text-[var(--text-quaternary)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                                <button wire:click.stop="deleteNote({{ $note->id }})" wire:confirm="Delete this note?" class="p-1 rounded hover:bg-[var(--red-50)] transition" title="Delete">
                                    <svg class="w-3 h-3 text-[var(--text-quaternary)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    @endif

    @if($showColorPicker && $pickingColorFor)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" wire:click="$set('showColorPicker', false)"></div>
            <div class="relative bg-[var(--color-surface)] rounded-xl shadow-xl p-4 border border-[var(--color-border)]">
                <div class="text-sm font-medium text-[var(--text-primary)] mb-3">Pilih Warna</div>
                <div class="flex gap-2">
                    @foreach($colors as $color => $name)
                        <button wire:click="setColor({{ $pickingColorFor }}, '{{ $color }}')"
                            class="w-10 h-10 rounded-full border-2 border-[var(--color-border)] hover:scale-110 transition"
                            style="background-color: {{ $color }}" title="{{ $name }}"></button>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>
