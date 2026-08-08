<div>
    @if($statusMessage)
        <div class="ka-status {{ $statusType === 'success' ? 'ka-status-success' : 'ka-status-error' }}">
            <span>{{ $statusMessage }}</span>
            <button wire:click="clearStatusMessage" aria-label="Tutup">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
    @endif

    @if(! $family)
        <div class="ka-onboard">
            <div class="ka-onboard-icon">👨‍👩‍👧</div>
            <div class="ka-onboard-title">Belum Ada Keluarga</div>
            <div class="ka-onboard-desc">Buat keluarga dulu di Dashboard Keluarga (mode web).</div>
        </div>
    @else
        <div class="ka-stats">
            <div class="ka-stat">
                <div class="ka-stat-label">Sisa Hutang</div>
                <div class="ka-stat-value negative">Rp {{ number_format($debts->sum('remaining'), 0, ',', '.') }}</div>
            </div>
            <div class="ka-stat">
                <div class="ka-stat-label">Cicilan/bulan</div>
                <div class="ka-stat-value">Rp {{ number_format($debts->sum('installment'), 0, ',', '.') }}</div>
            </div>
            <div class="ka-stat">
                <div class="ka-stat-label">Bebas</div>
                <div class="ka-stat-value">{{ $projection ? $projection['months'].' bln' : '-' }}</div>
            </div>
        </div>

        <div class="ka-section ka-card">
            <div class="ka-card-header">
                <span class="ka-card-title">Daftar Hutang</span>
                <button wire:click="openCreate" class="ka-card-link">+ Catat</button>
            </div>
            <div class="ka-card-body !py-2">
                @forelse($debts as $debt)
                    <div class="ka-row items-start">
                        <div class="ka-row-icon">💸</div>
                        <div class="ka-row-main">
                            <div class="ka-row-title">{{ $debt->name }}</div>
                            <div class="ka-row-sub">
                                Sisa Rp {{ number_format($debt->remaining, 0, ',', '.') }}
                                @if($debt->due_date) · Jatuh tempo {{ $debt->due_date->format('d M Y') }} @endif
                                @if($debt->interest_rate) · {{ $debt->interest_rate }}% @endif
                            </div>
                            <div class="ka-progress mt-2">
                                <div class="ka-progress-bar bg-indigo-500" style="width: {{ $debt->amount > 0 ? min(100, round($debt->paid_amount / $debt->amount * 100)) : 0 }}%"></div>
                            </div>
                            <form wire:submit.prevent="recordPayment({{ $debt->id }})" class="flex gap-2 mt-2">
                                <input type="number" min="0" step="0.01" wire:model="payAmount.{{ $debt->id }}"
                                       class="ka-input !py-2 !text-sm" placeholder="Bayar Rp" inputmode="decimal">
                                <button type="submit" class="ka-btn ka-btn-secondary !w-auto !py-2 !px-3 !text-sm">
                                    Bayar
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="ka-empty">
                        <div class="ka-empty-icon">🎉</div>
                        <div class="ka-empty-title">Tidak ada hutang aktif</div>
                        <div class="ka-empty-desc">Ketuk "+ Catat" untuk menambahkan.</div>
                    </div>
                @endforelse
            </div>
        </div>

        @if($projection)
            <div class="ka-section ka-card">
                <div class="ka-card-header">
                    <span class="ka-card-title">Proyeksi Bebas Hutang</span>
                </div>
                <div class="ka-card-body">
                    <div class="flex items-center gap-3">
                        <div class="ka-row-icon" style="background: var(--emerald-50); color: var(--emerald-600)">📅</div>
                        <div>
                            <div class="ka-row-title">Bebas hutang sekitar {{ $projection['free_at'] }}</div>
                            <div class="ka-row-sub">~{{ $projection['months'] }} bulan · cicilan {{ number_format($projection['total_installment'], 0, ',', '.') }}/bulan</div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif

    @if($showModal)
        <div class="ka-modal">
            <div class="ka-modal-backdrop" wire:click="closeModal"></div>
            <div class="ka-modal-panel">
                <button class="ka-modal-close" wire:click="closeModal" aria-label="Tutup">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
                <div class="ka-modal-title">Catat Hutang Baru</div>
                <div class="space-y-4">
                    <div>
                        <label class="ka-form-label">Nama</label>
                        <input type="text" wire:model="formName" class="ka-input" placeholder="Contoh: Kredit motor">
                        @error('formName') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="ka-form-label">Total Hutang (Rp)</label>
                        <input type="number" min="0" step="0.01" wire:model="formAmount" class="ka-input" placeholder="0" inputmode="decimal">
                        @error('formAmount') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="ka-form-label">Sudah Dibayar (Rp)</label>
                        <input type="number" min="0" step="0.01" wire:model="formPaidAmount" class="ka-input" placeholder="0" inputmode="decimal">
                        @error('formPaidAmount') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="ka-form-label">Bunga (%)</label>
                            <input type="number" min="0" step="0.01" wire:model="formInterestRate" class="ka-input" placeholder="0">
                        </div>
                        <div>
                            <label class="ka-form-label">Cicilan/bulan</label>
                            <input type="number" min="0" step="0.01" wire:model="formInstallment" class="ka-input" placeholder="0">
                        </div>
                    </div>
                    <div>
                        <label class="ka-form-label">Jatuh Tempo</label>
                        <input type="date" wire:model="formDueDate" class="ka-input">
                    </div>
                    <div>
                        <label class="ka-form-label">Catatan</label>
                        <input type="text" wire:model="formNotes" class="ka-input" placeholder="Opsional">
                    </div>
                    <button wire:click="save" wire:loading.attr="disabled" class="ka-btn ka-btn-primary">
                        Simpan Hutang
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
