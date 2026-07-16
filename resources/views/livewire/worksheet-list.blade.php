<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-[var(--text-primary)]">Worksheets</h1>
            <p class="text-sm text-[var(--text-tertiary)] mt-1">{{ $stats['total'] }} worksheets</p>
        </div>
        <button wire:click="openCreate" class="btn-primary">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Worksheet
        </button>
    </div>

    <div class="flex flex-wrap items-center gap-3 mb-5">
        <div class="relative flex-1 min-w-[200px] max-w-md">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--text-quaternary)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search worksheets..."
                class="wp-form-input !pl-9 !py-2">
        </div>
        <div class="flex gap-1 bg-[var(--color-bg)] p-1 rounded-lg border border-[var(--color-border)]">
            <button wire:click="$set('filter', 'all')" class="px-3 py-1.5 text-xs font-medium rounded-md transition {{ $filter === 'all' ? 'bg-white text-[var(--text-primary)] shadow-sm' : 'text-[var(--text-tertiary)] hover:text-[var(--text-secondary)]' }}">All</button>
            <button wire:click="$set('filter', 'favorites')" class="px-3 py-1.5 text-xs font-medium rounded-md transition {{ $filter === 'favorites' ? 'bg-white text-[var(--text-primary)] shadow-sm' : 'text-[var(--text-tertiary)] hover:text-[var(--text-secondary)]' }}">Favorites</button>
        </div>
    </div>

    <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
        @if($worksheets->isEmpty())
            <div class="empty-state">
                <div class="empty-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="9" y1="3" x2="9" y2="21"/><line x1="15" y1="3" x2="15" y2="21"/></svg>
                </div>
                <div class="empty-title">No worksheets yet</div>
                <div class="empty-desc">Create worksheets with tables and checklists to organize your data.</div>
                <div class="empty-action">
                    <button wire:click="openCreate" class="btn-primary">Create Your First Worksheet</button>
                </div>
            </div>
        @else
            <div class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @foreach($worksheets as $ws)
                    @php
                        $meta = $ws->metadata ?? [];
                        $rows = $meta['rows'] ?? [];
                        $columns = $meta['columns'] ?? [];
                        $columnTypes = $meta['columnTypes'] ?? [];
                        $checklist = $meta['checklist'] ?? [];
                        $progress = $this->getChecklistProgress($checklist);
                    @endphp
                    <div class="group border border-[var(--color-border)] rounded-xl p-4 hover:shadow-md hover:border-[var(--color-border-strong)] transition-all bg-[var(--color-surface)] flex flex-col">
                        <div class="flex items-start justify-between mb-2">
                            <div class="w-8 h-8 rounded-lg bg-[var(--emerald-50)] flex items-center justify-center text-[var(--emerald-600)]">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="3" x2="9" y2="21"/></svg>
                            </div>
                            <button wire:click="toggleFavorite({{ $ws->id }})" class="p-1 rounded hover:bg-[var(--color-bg)] transition">
                                @if($ws->favorite)
                                    <svg class="w-4 h-4 text-amber-500" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                @else
                                    <svg class="w-4 h-4 text-[var(--text-quaternary)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                @endif
                            </button>
                        </div>

                        <h3 class="font-medium text-sm text-[var(--text-primary)] line-clamp-2 mb-2">{{ $ws->title ?? 'Untitled Worksheet' }}</h3>

                        {{-- Table preview --}}
                        @if(!empty($rows) && ($meta['showTable'] ?? true))
                            <div class="text-xs text-[var(--text-tertiary)] mb-2 overflow-hidden">
                                <table class="w-full border-collapse">
                                    <thead>
                                        <tr>
                                            @foreach($columns as $col)
                                                <th class="text-left px-1.5 py-0.5 border-b border-[var(--color-border)] font-medium truncate max-w-[80px]">{{ $col }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach(array_slice($rows, 0, 3) as $row)
                                            <tr>
                                                @foreach($row as $i => $cell)
                                                    <td class="px-1.5 py-0.5 border-b border-[var(--color-border)] truncate max-w-[80px]">{{ $cell }}</td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                @if(count($rows) > 3)
                                    <p class="text-[var(--text-quaternary)] mt-1">+{{ count($rows) - 3 }} more rows</p>
                                @endif
                            </div>
                        @endif

                        {{-- Checklist preview --}}
                        @if(!empty($checklist) && ($meta['showChecklist'] ?? true))
                            <div class="mb-2">
                                <div class="flex items-center justify-between text-xs text-[var(--text-tertiary)] mb-1">
                                    <span>Checklist</span>
                                    <span>{{ count(array_filter($checklist, fn($c) => $c['checked'])) }}/{{ count($checklist) }}</span>
                                </div>
                                <div class="w-full bg-[var(--color-bg)] rounded-full h-1.5">
                                    <div class="bg-[var(--emerald-500)] h-1.5 rounded-full transition-all" style="width: {{ $progress }}%"></div>
                                </div>
                            </div>
                        @endif

                        <div class="flex items-center justify-between mt-auto pt-3 border-t border-[var(--color-border)]">
                            <span class="text-xs text-[var(--text-quaternary)]">{{ $ws->created_at->diffForHumans() }}</span>
                            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button wire:click="openEdit({{ $ws->id }})" class="p-1 rounded hover:bg-[var(--color-bg)] transition" title="Edit">
                                    <svg class="w-3.5 h-3.5 text-[var(--text-quaternary)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </button>
                                <button wire:click="trash({{ $ws->id }})" wire:confirm="Delete this worksheet?" class="p-1 rounded hover:bg-[var(--red-50)] transition" title="Delete">
                                    <svg class="w-3.5 h-3.5 text-[var(--text-quaternary)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if($worksheets->hasPages())
            <div class="px-4 py-3 border-t border-[var(--color-border)]">
                {{ $worksheets->links() }}
            </div>
        @endif
    </div>

    {{-- ─── Modal ─── --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-start justify-center p-4 pt-16 overflow-y-auto" x-data>
            <div class="fixed inset-0 bg-black/50" wire:click="closeModal"></div>
            <div class="relative bg-[var(--color-surface)] rounded-xl shadow-xl w-full max-w-2xl border border-[var(--color-border)] mb-8 max-h-[85vh] overflow-y-auto">
                <div class="flex items-center justify-between px-6 py-4 border-b border-[var(--color-border)]">
                    <h3 class="text-lg font-semibold text-[var(--text-primary)]">{{ $editMode ? 'Edit Worksheet' : 'Add Worksheet' }}</h3>
                    <button wire:click="closeModal" class="p-1 rounded hover:bg-[var(--color-bg)] transition text-[var(--text-quaternary)]">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                <form wire:submit="save" class="p-6 space-y-6">
                    {{-- Title --}}
                    <div>
                        <label class="wp-form-label">Title *</label>
                        <input type="text" wire:model="formTitle" class="wp-form-input" placeholder="My Worksheet">
                        @error('formTitle') <span class="text-xs text-[var(--red-500)] mt-1">{{ $message }}</span> @enderror
                    </div>

                    {{-- Toggle switches --}}
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 text-sm text-[var(--text-secondary)] cursor-pointer">
                            <input type="checkbox" wire:model.live="formShowTable" class="rounded border-[var(--color-border)]">
                            Show Table
                        </label>
                        <label class="flex items-center gap-2 text-sm text-[var(--text-secondary)] cursor-pointer">
                            <input type="checkbox" wire:model.live="formShowChecklist" class="rounded border-[var(--color-border)]">
                            Show Checklist
                        </label>
                    </div>

                    {{-- ─── Table Section ─── --}}
                    @if($formShowTable)
                        <div class="border border-[var(--color-border)] rounded-lg overflow-hidden">
                            <div class="flex items-center justify-between px-4 py-3 bg-[var(--color-bg)] border-b border-[var(--color-border)]">
                                <h4 class="text-sm font-semibold text-[var(--text-primary)]">Table</h4>
                                <button type="button" wire:click="addRow" class="text-xs text-[var(--indigo-600)] hover:text-[var(--indigo-700)] font-medium">
                                    + Add Row
                                </button>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="bg-[var(--color-bg)]">
                                            @foreach($formColumns as $i => $col)
                                                <th class="p-1 border border-[var(--color-border)]">
                                                    <div class="flex flex-col gap-1">
                                                        <input type="text" value="{{ $col }}" wire:change="updateColumnName({{ $i }}, $event.target.value)"
                                                            class="w-full px-2 py-1 text-xs font-semibold bg-transparent border-0 border-b border-[var(--color-border)] focus:border-[var(--indigo-500)] focus:outline-none"
                                                            placeholder="Column name">
                                                        <select wire:change="updateColumnType({{ $i }}, $event.target.value)"
                                                            class="w-full px-1 py-0.5 text-[10px] bg-[var(--color-surface)] border border-[var(--color-border)] rounded">
                                                            <option value="text" {{ $formColumnTypes[$i] === 'text' ? 'selected' : '' }}>Text</option>
                                                            <option value="number" {{ $formColumnTypes[$i] === 'number' ? 'selected' : '' }}>Number</option>
                                                        </select>
                                                        @if(count($formColumns) > 1)
                                                            <button type="button" wire:click="removeColumn({{ $i }})"
                                                                class="text-[10px] text-[var(--red-500)] hover:text-[var(--red-700)]">Remove</button>
                                                        @endif
                                                    </div>
                                                </th>
                                            @endforeach
                                            <th class="p-1 border border-[var(--color-border)] w-10">
                                                <button type="button" wire:click="addColumn" class="text-xs text-[var(--indigo-600)] hover:text-[var(--indigo-700)]" title="Add column">+</button>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($formRows as $rowIndex => $row)
                                            <tr>
                                                @foreach($formColumns as $colIndex => $col)
                                                    <td class="p-1 border border-[var(--color-border)]">
                                                        <input type="{{ $formColumnTypes[$colIndex] === 'number' ? 'number' : 'text' }}"
                                                            value="{{ $row[$colIndex] ?? '' }}"
                                                            wire:change="updateCell({{ $rowIndex }}, {{ $colIndex }}, $event.target.value)"
                                                            class="w-full px-2 py-1 text-xs bg-transparent border-0 focus:outline-none focus:ring-1 focus:ring-[var(--indigo-500)] rounded"
                                                            placeholder="{{ $formColumnTypes[$colIndex] === 'number' ? '0' : '...' }}">
                                                    </td>
                                                @endforeach
                                                <td class="p-1 border border-[var(--color-border)] text-center">
                                                    @if(count($formRows) > 1)
                                                        <button type="button" wire:click="removeRow({{ $rowIndex }})"
                                                            class="text-[var(--red-500)] hover:text-[var(--red-700)] text-xs">x</button>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    {{-- Totals row --}}
                                        @php $hasNumbers = in_array('number', $formColumnTypes); @endphp
                                        @if($hasNumbers)
                                            <tfoot>
                                                <tr class="bg-[var(--color-bg)] font-semibold">
                                                    @foreach($formColumns as $colIndex => $col)
                                                        <td class="px-2 py-1 border border-[var(--color-border)] text-xs">
                                                            @if($formColumnTypes[$colIndex] === 'number')
                                                                {{ number_format($this->getColumnTotal($formRows, $colIndex, $formColumnTypes[$colIndex]) ?? 0, 0, ',', '.') }}
                                                            @endif
                                                        </td>
                                                    @endforeach
                                                    <td class="border border-[var(--color-border)]"></td>
                                                </tr>
                                            </tfoot>
                                        @endif
                                </table>
                            </div>
                        </div>
                    @endif

                    {{-- ─── Checklist Section ─── --}}
                    @if($formShowChecklist)
                        <div class="border border-[var(--color-border)] rounded-lg overflow-hidden">
                            <div class="flex items-center justify-between px-4 py-3 bg-[var(--color-bg)] border-b border-[var(--color-border)]">
                                <h4 class="text-sm font-semibold text-[var(--text-primary)]">Checklist</h4>
                                <button type="button" wire:click="addChecklistItem" class="text-xs text-[var(--indigo-600)] hover:text-[var(--indigo-700)] font-medium">
                                    + Add Item
                                </button>
                            </div>
                            <div class="p-4 space-y-2">
                                @forelse($formChecklist as $i => $item)
                                    <div class="flex items-center gap-3 group">
                                        <input type="checkbox" {{ $item['checked'] ? 'checked' : '' }}
                                            x-on:click.prevent="$wire.toggleChecklistItem({{ $i }})"
                                            class="rounded border-[var(--color-border)]">
                                        <input type="text" wire:model.live="formChecklist.{{ $i }}.text"
                                            class="flex-1 px-2 py-1 text-sm bg-transparent border-0 border-b border-[var(--color-border)] focus:border-[var(--indigo-500)] focus:outline-none"
                                            placeholder="Task description...">
                                        <button type="button" wire:click="removeChecklistItem({{ $i }})"
                                            class="text-[var(--red-500)] hover:text-[var(--red-700)] text-xs opacity-0 group-hover:opacity-100 transition">Remove</button>
                                    </div>
                                @empty
                                    <p class="text-xs text-[var(--text-quaternary)]">No checklist items yet. Click "+ Add Item" to start.</p>
                                @endforelse
                            </div>
                        </div>
                    @endif

                    {{-- Favorite + Actions --}}
                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model="formFavorite" id="wsFavorite" class="rounded border-[var(--color-border)]">
                        <label for="wsFavorite" class="text-sm text-[var(--text-secondary)]">Mark as favorite</label>
                    </div>

                    <div class="flex justify-end gap-3 pt-2 border-t border-[var(--color-border)]">
                        <button type="button" wire:click="closeModal" class="px-4 py-2 text-sm font-medium text-[var(--text-secondary)] rounded-lg border border-[var(--color-border)] hover:bg-[var(--color-bg)] transition">Cancel</button>
                        <button type="submit" class="btn-primary">{{ $editMode ? 'Update' : 'Save' }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
