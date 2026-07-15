<div>
    @if(!$unlocked)
        <div class="flex items-center justify-center min-h-[60vh]">
            <div class="text-center max-w-sm">
                <div class="w-20 h-20 rounded-2xl bg-[var(--red-50)] flex items-center justify-center mx-auto mb-5">
                    <svg class="w-10 h-10 text-[var(--red-500)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                </div>
                <h2 class="text-xl font-bold text-[var(--text-primary)] mb-2">Secret Vault</h2>
                <p class="text-sm text-[var(--text-tertiary)] mb-6">Enter your PIN to unlock the vault. This PIN was set during initial setup.</p>

                @if($unlockError)
                    <div class="bg-red-50 border border-red-200 text-red-700 text-sm p-3 rounded-lg mb-4">
                        {{ $unlockError }}
                    </div>
                @endif

                <div class="space-y-3">
                    <input type="password" wire:model="masterPassword" class="wp-form-input w-full" placeholder="Enter PIN" wire:keydown.enter="unlock" inputmode="numeric" maxlength="6">
                    <button wire:click="unlock" class="btn-primary w-full justify-center">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                        Unlock Vault
                    </button>
                </div>
            </div>
        </div>
    @else
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-[var(--text-primary)]">Secret Vault</h1>
                <p class="text-sm text-[var(--text-tertiary)] mt-1">{{ $stats['total'] }} secrets stored securely</p>
            </div>
            <div class="flex items-center gap-2">
                <button wire:click="lock" class="px-3 py-2 text-xs font-medium rounded-lg border border-[var(--color-border)] hover:bg-[var(--color-bg)] transition flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                    Lock
                </button>
                <button wire:click="openCreate" class="btn-primary">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Add Secret
                </button>
            </div>
        </div>

        @if($statusMessage)
            <div class="px-4 py-3 rounded-lg text-sm font-medium flex items-center justify-between mb-4
                {{ $statusType === 'success' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200' }}">
                <span>{{ $statusMessage }}</span>
                <button wire:click="clearStatusMessage" class="p-1 rounded hover:bg-white/50">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
        @endif

        <div class="flex flex-wrap items-center gap-3 mb-5">
            <div class="relative flex-1 min-w-[200px] max-w-md">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--text-quaternary)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search secrets..."
                    class="wp-form-input !pl-9 !py-2">
            </div>
            <div class="flex gap-1 bg-[var(--color-bg)] p-1 rounded-lg border border-[var(--color-border)] flex-wrap">
                <button wire:click="$set('filter', 'all')" class="px-3 py-1.5 text-xs font-medium rounded-md transition {{ $filter === 'all' ? 'bg-white text-[var(--text-primary)] shadow-sm' : 'text-[var(--text-tertiary)] hover:text-[var(--text-secondary)]' }}">All</button>
                @foreach($categories as $cat)
                    <button wire:click="$set('filter', '{{ $cat }}')" class="px-3 py-1.5 text-xs font-medium rounded-md transition {{ $filter === $cat ? 'bg-white text-[var(--text-primary)] shadow-sm' : 'text-[var(--text-tertiary)] hover:text-[var(--text-secondary)]' }}">{{ $cat }}</button>
                @endforeach
            </div>
        </div>

        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
            @if($secrets->isEmpty())
                <div class="empty-state">
                    <div class="empty-icon" style="background: var(--red-50); color: var(--red-500);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                    </div>
                    <div class="empty-title">No secrets yet</div>
                    <div class="empty-desc">Store API keys, passwords, licenses, and other sensitive data securely.</div>
                    <div class="empty-action">
                        <button wire:click="openCreate" class="btn-primary">Add Your First Secret</button>
                    </div>
                </div>
            @else
                <div class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($secrets as $secret)
                        <div class="group border border-[var(--color-border)] rounded-xl p-4 hover:shadow-md hover:border-[var(--color-border-strong)] transition-all bg-[var(--color-surface)]">
                            <div class="flex items-start justify-between mb-3">
                                @if($secret->metadata['category'] ?? null)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wider bg-[var(--red-50)] text-[var(--red-500)]">{{ $secret->metadata['category'] }}</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wider bg-[var(--color-bg)] text-[var(--text-quaternary)]">Secret</span>
                                @endif
                                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button wire:click="openEdit({{ $secret->id }})" class="p-1 rounded hover:bg-[var(--color-bg)] transition" title="Edit">
                                        <svg class="w-3.5 h-3.5 text-[var(--text-quaternary)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    </button>
                                    <button wire:click="trash({{ $secret->id }})" wire:confirm="Delete this secret?" class="p-1 rounded hover:bg-[var(--red-50)] transition" title="Delete">
                                        <svg class="w-3.5 h-3.5 text-[var(--text-quaternary)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                                    </button>
                                </div>
                            </div>
                            <h3 class="font-medium text-sm text-[var(--text-primary)] mb-2">{{ $secret->title }}</h3>
                            @if($secret->metadata['username'] ?? null)
                                <div class="flex items-center gap-2 mb-1">
                                    <svg class="w-3 h-3 text-[var(--text-quaternary)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                    <span class="text-xs text-[var(--text-tertiary)] font-mono">{{ $secret->metadata['username'] }}</span>
                                </div>
                            @endif
                            @if($secret->metadata['url'] ?? null)
                                <div class="flex items-center gap-2 mb-1">
                                    <svg class="w-3 h-3 text-[var(--text-quaternary)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/></svg>
                                    <span class="text-xs text-[var(--text-tertiary)] font-mono truncate">{{ $secret->metadata['url'] }}</span>
                                </div>
                            @endif
                            <div class="flex items-center justify-between mt-3 pt-3 border-t border-[var(--color-border)]">
                                <span class="text-xs text-[var(--text-quaternary)]">{{ $secret->created_at->diffForHumans() }}</span>
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-3 h-3 text-[var(--emerald-500)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                    <span class="text-[10px] font-medium text-[var(--emerald-500)] uppercase">AES-256 Encrypted</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if($secrets->hasPages())
                <div class="px-4 py-3 border-t border-[var(--color-border)]">
                    {{ $secrets->links() }}
                </div>
            @endif
        </div>

        @if($showModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50" wire:click="closeModal"></div>
                <div class="relative bg-[var(--color-surface)] rounded-xl shadow-xl w-full max-w-2xl border border-[var(--color-border)]">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-[var(--color-border)]">
                        <h3 class="text-lg font-semibold text-[var(--text-primary)]">{{ $editMode ? 'Edit Secret' : 'Add Secret' }}</h3>
                        <button wire:click="closeModal" class="p-1 rounded hover:bg-[var(--color-bg)] transition text-[var(--text-quaternary)]">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>
                    <form wire:submit="save" class="p-6 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="wp-form-label">Title *</label>
                                <input type="text" wire:model="formTitle" class="wp-form-input" placeholder="API Key Name">
                                @error('formTitle') <span class="text-xs text-[var(--red-500)] mt-1">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="wp-form-label">Category</label>
                                <input type="text" wire:model="formCategory" class="wp-form-input" placeholder="API Keys" list="secretCatList">
                                <datalist id="secretCatList">
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat }}">
                                    @endforeach
                                </datalist>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="wp-form-label">Username / Email</label>
                                <input type="text" wire:model="formUsername" class="wp-form-input" placeholder="user@example.com">
                            </div>
                            <div>
                                <label class="wp-form-label">Password / Key</label>
                                <input type="password" wire:model="formPassword" class="wp-form-input" placeholder="sk-xxxxx">
                            </div>
                        </div>
                        <div>
                            <label class="wp-form-label">URL</label>
                            <input type="url" wire:model="formUrl" class="wp-form-input" placeholder="https://dashboard.example.com">
                        </div>
                        <div>
                            <label class="wp-form-label">Content / Key Value</label>
                            <textarea wire:model="formContent" class="wp-form-input" rows="4" placeholder="Paste your API key, license key, or other secret data..."></textarea>
                        </div>
                        <div>
                            <label class="wp-form-label">Notes</label>
                            <textarea wire:model="formNotes" class="wp-form-input" rows="2" placeholder="Additional notes..."></textarea>
                        </div>
                        <div class="flex items-center gap-2 p-3 rounded-lg bg-[var(--emerald-50)] border border-[var(--emerald-200)]">
                            <svg class="w-4 h-4 text-[var(--emerald-600)] flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            <span class="text-xs text-[var(--emerald-700)]">Username, password, and notes are encrypted with AES-256 before storage.</span>
                        </div>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" wire:click="closeModal" class="px-4 py-2 text-sm font-medium text-[var(--text-secondary)] rounded-lg border border-[var(--color-border)] hover:bg-[var(--color-bg)] transition">Cancel</button>
                            <button type="submit" class="btn-primary">{{ $editMode ? 'Update' : 'Save' }}</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endif
</div>
