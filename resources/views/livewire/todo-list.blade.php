<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-[var(--text-primary)]">To-Do List</h1>
            <p class="text-sm text-[var(--text-tertiary)] mt-1">Manage your tasks and track progress</p>
        </div>
        <button wire:click="openModal" class="btn-primary">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            New Todo
        </button>
    </div>

    @if($statusMessage)
        <div class="mb-4 px-4 py-3 rounded-lg text-sm font-medium {{ $statusType === 'success' ? 'bg-[var(--emerald-50)] text-[var(--emerald-700)] border border-[var(--emerald-200)]' : 'bg-red-50 text-red-700 border border-red-200' }}"
            x-data x-init="setTimeout(() => $wire.clearStatusMessage(), 3000)">
            {{ $statusMessage }}
        </div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-4">
            <div class="text-2xl font-bold text-[var(--text-primary)]">{{ $totalTodos }}</div>
            <div class="text-xs text-[var(--text-tertiary)]">Total</div>
        </div>
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-4">
            <div class="text-2xl font-bold text-[var(--indigo-600)]">{{ $pendingTodos }}</div>
            <div class="text-xs text-[var(--text-tertiary)]">Pending</div>
        </div>
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-4">
            <div class="text-2xl font-bold text-[var(--emerald-600)]">{{ $completedTodos }}</div>
            <div class="text-xs text-[var(--text-tertiary)]">Completed</div>
        </div>
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-4">
            <div class="text-2xl font-bold text-[var(--red-600)]">{{ $overdueTodos }}</div>
            <div class="text-xs text-[var(--text-tertiary)]">Overdue</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex items-center gap-3 mb-4 flex-wrap">
        <div class="flex items-center bg-[var(--color-surface)] border border-[var(--color-border)] rounded-lg overflow-hidden text-sm">
            @foreach(['all' => 'All', 'pending' => 'Pending', 'completed' => 'Completed', 'overdue' => 'Overdue'] as $key => $label)
                <button wire:click="$set('filterStatus', '{{ $key }}')"
                    class="px-3 py-1.5 {{ $filterStatus === $key ? 'bg-[var(--indigo-600)] text-white' : 'text-[var(--text-secondary)] hover:bg-[var(--color-bg)]' }} transition">
                    {{ $label }}
                </button>
            @endforeach
        </div>
        <input type="text" wire:model.live.debounce.300ms="search" class="px-3 py-1.5 text-sm bg-[var(--color-surface)] border border-[var(--color-border)] rounded-lg focus:outline-none focus:border-[var(--indigo-500)]" placeholder="Search todos...">
    </div>

    {{-- Todo List --}}
    <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
        @if($todos->isEmpty())
            <div class="flex flex-col items-center justify-center py-16">
                <div class="w-12 h-12 rounded-full bg-[var(--indigo-50)] flex items-center justify-center mb-3">
                    <svg class="w-6 h-6 text-[var(--indigo-500)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>
                    </svg>
                </div>
                <p class="text-sm text-[var(--text-tertiary)]">No todos found</p>
            </div>
        @else
            @php
                $priorityColors = ['high' => 'bg-red-50 text-red-600 border-red-200', 'medium' => 'bg-amber-50 text-amber-600 border-amber-200', 'low' => 'bg-emerald-50 text-emerald-600 border-emerald-200'];
            @endphp
            @foreach($todos as $todo)
                @php $meta = $todo->metadata ?? []; @endphp
                <div class="flex items-start gap-3 px-5 py-4 border-b border-[var(--color-border)] last:border-b-0 hover:bg-[var(--color-bg)] transition {{ ($meta['completed'] ?? false) ? 'opacity-60' : '' }}">
                    {{-- Checkbox --}}
                    <button wire:click="toggleComplete({{ $todo->id }})" class="mt-0.5 flex-shrink-0 w-5 h-5 rounded border-2 {{ ($meta['completed'] ?? false) ? 'bg-[var(--indigo-600)] border-[var(--indigo-600)]' : 'border-[var(--color-border)] hover:border-[var(--indigo-500)]' }} flex items-center justify-center transition">
                        @if($meta['completed'] ?? false)
                            <svg class="w-3 h-3 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                        @endif
                    </button>

                    {{-- Content --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-[var(--text-primary)] {{ ($meta['completed'] ?? false) ? 'line-through' : '' }}">{{ $todo->title }}</span>
                            <span class="text-[10px] px-1.5 py-0.5 rounded border {{ $priorityColors[$meta['priority'] ?? 'medium'] }}">{{ $meta['priority'] ?? 'medium' }}</span>
                        </div>
                        @if($todo->content)
                            <p class="text-xs text-[var(--text-tertiary)] mt-1 line-clamp-2">{{ $todo->content }}</p>
                        @endif
                        @if($meta['due_date'] ?? null)
                            @php
                                $isOverdue = !$meta['completed'] && $meta['due_date'] < now()->toDateString();
                            @endphp
                            <span class="inline-flex items-center gap-1 text-[10px] mt-1 {{ $isOverdue ? 'text-[var(--red-600)]' : 'text-[var(--text-quaternary)]' }}">
                                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                {{ $meta['due_date'] }}{{ $isOverdue ? ' (overdue)' : '' }}
                            </span>
                        @endif
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-1 flex-shrink-0">
                        <button wire:click="editTodo({{ $todo->id }})" class="p-1.5 rounded hover:bg-[var(--color-bg)] text-[var(--text-quaternary)] hover:text-[var(--text-primary)] transition" title="Edit">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        </button>
                        <button wire:click="deleteTodo({{ $todo->id }})" wire:confirm="Delete this todo?" class="p-1.5 rounded hover:bg-red-50 text-[var(--text-quaternary)] hover:text-[var(--red-600)] transition" title="Delete">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                        </button>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    @if($todos->hasPages())
        <div class="mt-4">{{ $todos->links() }}</div>
    @endif

    {{-- Modal --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" x-data x-on:keydown.escape.window="$wire.closeModal()">
            <div class="bg-[var(--color-surface)] rounded-xl shadow-2xl border border-[var(--color-border)] w-full max-w-lg mx-4" x-on:click.outside="$wire.closeModal()">
                <div class="px-5 py-4 border-b border-[var(--color-border)] flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-[var(--text-primary)]">{{ $editId ? 'Edit Todo' : 'New Todo' }}</h2>
                    <button wire:click="closeModal" class="p-1 rounded hover:bg-[var(--color-bg)] text-[var(--text-quaternary)]">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <div class="p-5 space-y-4">
                    <div>
                        <label class="wp-form-label">Title *</label>
                        <input type="text" wire:model="formTitle" class="wp-form-input" placeholder="What needs to be done?" autofocus>
                        @error('formTitle') <p class="text-xs text-[var(--red-600)] mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="wp-form-label">Description</label>
                        <textarea wire:model="formDescription" class="wp-form-input" rows="3" placeholder="Details or notes..."></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="wp-form-label">Priority</label>
                            <select wire:model="formPriority" class="wp-form-input">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                        <div>
                            <label class="wp-form-label">Due Date</label>
                            <input type="date" wire:model="formDueDate" class="wp-form-input">
                        </div>
                    </div>
                </div>
                <div class="px-5 py-3 border-t border-[var(--color-border)] flex justify-end gap-2">
                    <button wire:click="closeModal" class="px-4 py-2 text-sm font-medium text-[var(--text-secondary)] rounded-lg border border-[var(--color-border)] hover:bg-[var(--color-bg)] transition">Cancel</button>
                    <button wire:click="save" class="btn-primary">{{ $editId ? 'Update' : 'Create' }} Todo</button>
                </div>
            </div>
        </div>
    @endif
</div>
