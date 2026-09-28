<div class="space-y-6">
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold text-[var(--text-primary)]">Hutang & Piutang</h1>
            <p class="text-sm text-[var(--text-tertiary)] mt-1">Pantau hutang, strategi lunas, dan proyeksi bebas hutang</p>
        </div>
        @if($family)
            <button wire:click="openCreate" class="btn-primary">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Tambah Hutang
            </button>
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
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="stat-card" style="--accent-start: #ef4444; --accent-end: #f87171">
                <div class="stat-header">
                    <span class="stat-label">Sisa Hutang</span>
                    <div class="stat-icon" style="background: #ef444415; color: #ef4444;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                    </div>
                </div>
                <div class="stat-value text-red-600">{{ number_format($projection['total_remaining'] ?? 0, 0, ',', '.') }}</div>
            </div>
            <div class="stat-card" style="--accent-start: #f59e0b; --accent-end: #fbbf24">
                <div class="stat-header">
                    <span class="stat-label">Total Cicilan / Bln</span>
                    <div class="stat-icon" style="background: #f59e0b15; color: #f59e0b;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                    </div>
                </div>
                <div class="stat-value text-amber-600">{{ number_format($projection['total_installment'] ?? 0, 0, ',', '.') }}</div>
            </div>
            <div class="stat-card" style="--accent-start: #6366f1; --accent-end: #8b5cf6">
                <div class="stat-header">
                    <span class="stat-label">Bebas Hutang</span>
                    <div class="stat-icon" style="background: #6366f115; color: #6366f1;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                </div>
                <div class="stat-value text-indigo-600">{{ $projection['free_at'] ?? '-' }}</div>
            </div>
            <div class="stat-card" style="--accent-start: #10b981; --accent-end: #34d399">
                <div class="stat-header">
                    <span class="stat-label">Tagihan Terdekat</span>
                    <div class="stat-icon" style="background: #10b98115; color: #10b981;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                </div>
                <div class="stat-value text-emerald-600">{{ data_get($projection, 'next_due')?->format('d M') ?? '-' }}</div>
            </div>
        </div>

        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-[var(--color-border)] flex items-center justify-between flex-wrap gap-3">
                <h3 class="text-sm font-semibold text-[var(--text-primary)]">Daftar Hutang Aktif</h3>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-[var(--text-tertiary)]">Strategi:</span>
                    <select wire:model.live="strategy" class="wp-form-input !py-1.5 text-xs w-40">
                        <option value="avalanche">Avalanche (bunga tinggi)</option>
                        <option value="snowball">Snowball (saldo kecil)</option>
                    </select>
                </div>
            </div>

            @if($debts->isEmpty())
                <div class="empty-state !py-12">
                    <div class="empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                    </div>
                    <div class="empty-title">Tidak ada hutang aktif</div>
                    <div class="empty-desc">Catat hutang keluarga untuk strategi pelunasan yang lebih terarah.</div>
                    <button wire:click="openCreate" class="empty-action btn-primary">Tambah Hutang</button>
                </div>
            @else
                <div class="divide-y divide-[var(--color-border)]">
                    @foreach($debts as $debt)
                        <div class="px-5 py-4">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-medium text-[var(--text-primary)]">{{ $debt->name }}</span>
                                        <span class="text-[10px] px-2 py-0.5 rounded-full font-semibold uppercase tracking-wide
                                            {{ $debt->status === 'open' ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-600' }}">
                                            {{ $debt->status === 'open' ? 'Belum dibayar' : 'Cicilan' }}
                                        </span>
                                        @if((float) $debt->interest_rate > 0)
                                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-[var(--color-bg)] text-[var(--text-tertiary)]">Bunga {{ $debt->interest_rate }}%/th</span>
                                        @endif
                                    </div>
                                    @if($debt->notes)
                                        <div class="text-xs text-[var(--text-quaternary)] mt-0.5">{{ $debt->notes }}</div>
                                    @endif
                                    <div class="text-xs text-[var(--text-tertiary)] mt-1">
                                        {{ $debt->type === 'payable' ? 'Hutang' : 'Piutang' }}
                                        @if($debt->due_date) · Jatuh tempo {{ $debt->due_date->format('d/m/Y') }} @endif
                                        @if((float) $debt->installment > 0) · Cicilan {{ number_format($debt->installment, 0, ',', '.') }}/bln @endif
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <div class="font-semibold text-[var(--text-primary)]">{{ number_format($debt->remaining, 0, ',', '.') }}</div>
                                    <div class="text-xs text-[var(--text-quaternary)]">
                                        dari {{ number_format($debt->amount, 0, ',', '.') }} · {{ $debt->amount > 0 ? round($debt->paid_amount / $debt->amount * 100) : 0 }}%
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 flex items-center gap-4">
                                <div class="flex-1 h-2 bg-[var(--color-bg)] rounded-full overflow-hidden">
                                    <div class="h-full rounded-full {{ $debt->type === 'payable' ? 'bg-red-500' : 'bg-emerald-500' }}"
                                         style="width: {{ min(100, $debt->paid_amount / max(1, $debt->amount) * 100) }}%"></div>
                                </div>
                                <input wire:model="payAmount.{{ $debt->id }}" type="number" step="0.01" min="0"
                                    class="wp-form-input !py-1.5 !w-36 text-xs text-right" placeholder="Bayar (Rp)">
                                <button wire:click="recordPayment({{ $debt->id }}, {{ $payAmount[$debt->id] ?? 0 }})" class="btn-secondary !py-1.5 text-xs whitespace-nowrap">
                                    Bayar
                                </button>
                                <button wire:click="openEdit({{ $debt->id }})" class="btn-icon" title="Edit">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </button>
                                <button wire:click="delete({{ $debt->id }})" wire:confirm="Hapus hutang ini?" class="btn-icon text-red-500 hover:text-red-600" title="Hapus">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        @if($payoffOrder)
            <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-[var(--color-border)]">
                    <h3 class="text-sm font-semibold text-[var(--text-primary)]">Urutan Pelunasan yang Disarankan</h3>
                </div>
                <ol class="divide-y divide-[var(--color-border)]">
                    @foreach($payoffOrder as $i => $item)
                        <li class="px-5 py-3 flex items-center justify-between text-sm">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="w-6 h-6 shrink-0 rounded-full bg-indigo-50 text-indigo-600 text-xs font-bold flex items-center justify-center">{{ $i + 1 }}</span>
                                <span class="text-[var(--text-primary)] truncate">{{ $item['debt']->name }}</span>
                            </div>
                            <span class="text-xs font-medium text-[var(--text-secondary)] whitespace-nowrap">{{ number_format($item['remaining'], 0, ',', '.') }}</span>
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif

        @if($settledDebts->isNotEmpty())
            <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-[var(--color-border)]">
                    <h3 class="text-sm font-semibold text-[var(--text-primary)]">✅ Lunas</h3>
                </div>
                <ul class="divide-y divide-[var(--color-border)]">
                    @foreach($settledDebts as $debt)
                        <li class="px-5 py-3 flex items-center justify-between text-sm">
                            <span class="text-[var(--text-secondary)]">{{ $debt->name }}</span>
                            <span class="text-xs font-semibold text-emerald-600">✓ {{ number_format($debt->amount, 0, ',', '.') }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    @endif

    @if($showModal && $family)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" wire:click="closeModal"></div>
            <div class="relative bg-[var(--color-surface)] rounded-xl shadow-xl w-full max-w-lg border border-[var(--color-border)] max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between px-6 py-4 border-b border-[var(--color-border)]">
                    <h3 class="text-lg font-semibold text-[var(--text-primary)]">{{ $editingId ? 'Edit' : 'Tambah' }} Hutang / Piutang</h3>
                    <button wire:click="closeModal" class="p-1 rounded hover:bg-[var(--color-bg)] transition text-[var(--text-quaternary)]">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <form wire:submit="save" class="p-6 space-y-4">
                    <div class="flex gap-2 p-1 bg-[var(--color-bg)] rounded-lg">
                        <button type="button" wire:click="$set('formType', 'payable')"
                            class="flex-1 py-2 text-sm font-medium rounded-md transition {{ $formType === 'payable' ? 'bg-white shadow text-red-600' : 'text-[var(--text-tertiary)]' }}">💳 Hutang</button>
                        <button type="button" wire:click="$set('formType', 'receivable')"
                            class="flex-1 py-2 text-sm font-medium rounded-md transition {{ $formType === 'receivable' ? 'bg-white shadow text-emerald-600' : 'text-[var(--text-tertiary)]' }}">💰 Piutang</button>
                    </div>
                    <div>
                        <label class="wp-form-label">Nama / Pihak *</label>
                        <input type="text" wire:model="formName" class="wp-form-input" placeholder="Mis: KPR, Pinjaman Bank, Diterima dari Rina" required>
                        @error('formName') <span class="text-xs text-red-600 mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="wp-form-label">Jumlah (Rp) *</label>
                            <input type="number" step="0.01" min="0" wire:model="formAmount" class="wp-form-input" placeholder="0" required>
                            @error('formAmount') <span class="text-xs text-red-600 mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="wp-form-label">Sudah Dibayar (Rp)</label>
                            <input type="number" step="0.01" min="0" wire:model="formPaidAmount" class="wp-form-input" placeholder="0">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="wp-form-label">Bunga (%/tahun)</label>
                            <input type="number" step="0.01" min="0" max="100" wire:model="formInterestRate" class="wp-form-input" placeholder="Opsional">
                        </div>
                        <div>
                            <label class="wp-form-label">Cicilan (Rp/bulan)</label>
                            <input type="number" step="0.01" min="0" wire:model="formInstallment" class="wp-form-input" placeholder="Opsional">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="wp-form-label">Jatuh Tempo</label>
                            <input type="date" wire:model="formDueDate" class="wp-form-input">
                        </div>
                        <div>
                            <label class="wp-form-label">Catatan</label>
                            <input type="text" wire:model="formNotes" class="wp-form-input" placeholder="Opsional">
                        </div>
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
