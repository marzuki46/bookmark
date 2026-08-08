<div class="space-y-6">
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold text-[var(--text-primary)]">Anggaran Bulanan</h1>
            <p class="text-sm text-[var(--text-tertiary)] mt-1">Batas pengeluaran per kategori untuk menjaga arus kas keluarga</p>
        </div>
        @if($family)
            <div class="flex items-center gap-2">
                <input type="month" wire:model.live="month" class="wp-form-input !w-auto !py-2">
                <button wire:click="openCreate" class="btn-primary">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Set Budget
                </button>
            </div>
        @endif
    </div>

    @if($statusMessage)
        <div class="px-4 py-3 rounded-lg text-sm font-medium flex items-center justify-between
            {{ $statusType === 'success' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200' }}">
            <span>{{ $statusMessage }}</span>
            <button wire:click="clearStatusMessage" class="p-1 rounded hover:bg-white/50">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
    @endif

    @if(! $family)
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-8 text-center">
            <div class="text-3xl mb-2">🏠</div>
            <p class="text-sm text-[var(--text-tertiary)]">Buat keluarga dulu di menu Dashboard Keluarga.</p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="stat-card" style="--accent-start: #6366f1; --accent-end: #8b5cf6">
                <div class="stat-header">
                    <span class="stat-label">Total Budget</span>
                    <div class="stat-icon" style="background: #6366f115; color: #6366f1;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12V7H5a2 2 0 010-4h14v4"/><path d="M3 5v14a2 2 0 002 2h16v-5"/><path d="M18 12a2 2 0 000 4h4v-4z"/></svg>
                    </div>
                </div>
                <div class="stat-value text-indigo-600">{{ number_format($totals['budget'], 0, ',', '.') }}</div>
            </div>
            <div class="stat-card" style="--accent-start: #ef4444; --accent-end: #f87171">
                <div class="stat-header">
                    <span class="stat-label">Terpakai</span>
                    <div class="stat-icon" style="background: #ef444415; color: #ef4444;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                    </div>
                </div>
                <div class="stat-value text-red-600">{{ number_format($totals['spent'], 0, ',', '.') }}</div>
            </div>
            <div class="stat-card" style="--accent-start: #10b981; --accent-end: #34d399">
                <div class="stat-header">
                    <span class="stat-label">Sisa</span>
                    <div class="stat-icon" style="background: #10b98115; color: #10b981;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                </div>
                <div class="stat-value {{ ($totals['budget'] - $totals['spent']) >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                    {{ number_format($totals['budget'] - $totals['spent'], 0, ',', '.') }}
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @forelse($budgets as $b)
                <div class="bg-[var(--color-surface)] border {{ $b['overspent'] ? 'border-red-200 bg-red-50/40' : 'border-[var(--color-border)]' }} rounded-xl p-5">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="text-xl">{{ $b['category']->icon }}</span>
                            <span class="font-medium text-[var(--text-primary)] truncate">{{ $b['category']->name }}</span>
                        </div>
                        <div class="flex items-center gap-1">
                            @if($b['budget_id'])
                                <button wire:click="openEdit({{ $b['budget_id'] }})" class="btn-icon" title="Edit">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </button>
                                <button wire:click="delete({{ $b['budget_id'] }})" wire:confirm="Hapus budget ini?" class="btn-icon text-red-500 hover:text-red-600" title="Hapus">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-baseline justify-between text-sm mb-2">
                        <span class="{{ $b['overspent'] ? 'text-red-600 font-semibold' : 'text-[var(--text-secondary)]' }}">
                            {{ number_format($b['spent'], 0, ',', '.') }} / {{ number_format($b['amount'], 0, ',', '.') }}
                        </span>
                        <span class="text-xs {{ $b['overspent'] ? 'text-red-600 font-semibold' : 'text-[var(--text-tertiary)]' }}">
                            {{ $b['percent'] }}%
                        </span>
                    </div>

                    <div class="h-2.5 bg-[var(--color-bg)] rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all {{ $b['overspent'] ? 'bg-red-500' : 'bg-indigo-500' }}"
                             style="width: {{ max(0, $b['percent']) }}%"></div>
                    </div>

                    <div class="mt-2 flex items-center justify-between">
                        @if($b['remaining'] < 0)
                            <span class="text-xs font-semibold text-red-600">Lebih {{ number_format(abs($b['remaining']), 0, ',', '.') }}</span>
                        @else
                            <span class="text-xs font-semibold text-emerald-600">Sisa {{ number_format($b['remaining'], 0, ',', '.') }}</span>
                        @endif
                        @if(! $b['budget_id'])
                            <button wire:click="openCreate" class="text-xs font-medium text-[var(--indigo-600)] hover:underline">Tentukan budget →</button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="md:col-span-2 bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl">
                    <div class="empty-state !py-12">
                        <div class="empty-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                        </div>
                        <div class="empty-title">Belum ada anggaran</div>
                        <div class="empty-desc">Tetapkan batas pengeluaran bulanan agar keuangan keluarga lebih terkontrol.</div>
                        <button wire:click="openCreate" class="empty-action btn-primary">Set Budget Pertama</button>
                    </div>
                </div>
            @endforelse
        </div>
    @endif

    @if($showModal && $family)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" wire:click="closeModal"></div>
            <div class="relative bg-[var(--color-surface)] rounded-xl shadow-xl w-full max-w-md border border-[var(--color-border)]">
                <div class="flex items-center justify-between px-6 py-4 border-b border-[var(--color-border)]">
                    <h3 class="text-lg font-semibold text-[var(--text-primary)]">Anggaran Bulan {{ \Carbon\Carbon::parse($month . '-01')->translatedFormat('F Y') }}</h3>
                    <button wire:click="closeModal" class="p-1 rounded hover:bg-[var(--color-bg)] transition text-[var(--text-quaternary)]">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <form wire:submit="save" class="p-6 space-y-4">
                    <div>
                        <label class="wp-form-label">Kategori</label>
                        <select wire:model="formCategoryId" class="wp-form-input" required>
                            <option value="">-- Pilih kategori --</option>
                            @foreach($budgets as $b)
                                <option value="{{ $b['category']->id }}">{{ $b['category']->icon }} {{ $b['category']->name }}</option>
                            @endforeach
                        </select>
                        @error('formCategoryId') <span class="text-xs text-red-600 mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="wp-form-label">Batas Anggaran (Rp)</label>
                        <input type="number" step="0.01" min="0" wire:model="formAmount" class="wp-form-input" placeholder="0" required>
                        @error('formAmount') <span class="text-xs text-red-600 mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" wire:click="closeModal" class="btn-secondary">Batal</button>
                        <button type="submit" class="btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
