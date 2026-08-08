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
        <div class="ka-section ka-card">
            <div class="ka-card-body">
                <div class="flex items-center gap-3">
                    <div class="ka-row-icon" style="background: var(--emerald-50); color: var(--emerald-600)">🛡️</div>
                    <div class="flex-1 min-w-0">
                        <div class="ka-row-title">Dana Darurat</div>
                        <div class="ka-row-sub">
                            Rp {{ number_format($emergencyFund['current'], 0, ',', '.') }} dari
                            Rp {{ number_format($emergencyFund['target'], 0, ',', '.') }}
                        </div>
                    </div>
                    <span class="text-sm font-semibold text-[var(--text-primary)]">{{ $emergencyFund['percent'] }}%</span>
                </div>
                @if($emergencyFund['target'] > 0)
                    <div class="ka-progress mt-3">
                        <div class="ka-progress-bar bg-emerald-500" style="width: {{ $emergencyFund['percent'] }}%"></div>
                    </div>
                @endif
                <button wire:click="openCreateEmergency" class="ka-btn ka-btn-secondary mt-4">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                    Buat / Atur Dana Darurat
                </button>
            </div>
        </div>

        <div class="ka-section ka-card">
            <div class="ka-card-header">
                <span class="ka-card-title">Target Tabungan</span>
                <button wire:click="openCreateCustom" class="ka-card-link">+ Baru</button>
            </div>
            <div class="ka-card-body !py-2">
                @forelse($goals as $goal)
                    <div class="ka-row">
                        <div class="ka-row-icon">{{ $goal->icon ?? '🎯' }}</div>
                        <div class="ka-row-main">
                            <div class="ka-row-title">{{ $goal->name }}</div>
                            <div class="ka-row-sub">Rp {{ number_format($goal->current_amount, 0, ',', '.') }} dari {{ number_format($goal->target_amount, 0, ',', '.') }}</div>
                            <div class="ka-progress mt-2">
                                <div class="ka-progress-bar" style="width: {{ $goal->progress }}%; background: {{ $goal->color ?? '#6366f1' }}"></div>
                            </div>
                        </div>
                        <div class="ka-row-value">{{ $goal->progress }}%</div>
                    </div>
                @empty
                    <div class="ka-empty">
                        <div class="ka-empty-icon">🎯</div>
                        <div class="ka-empty-title">Belum ada tabungan</div>
                        <div class="ka-empty-desc">Ketuk "+ Baru" untuk membuat target.</div>
                    </div>
                @endforelse
            </div>
        </div>
    @endif

    @if($showModal)
        <div class="ka-modal">
            <div class="ka-modal-backdrop" wire:click="closeModal"></div>
            <div class="ka-modal-panel">
                <button class="ka-modal-close" wire:click="closeModal" aria-label="Tutup">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
                <div class="ka-modal-title">{{ $formType === 'emergency_fund' ? 'Dana Darurat' : 'Target Tabungan Baru' }}</div>
                <div class="space-y-4">
                    <div>
                        <label class="ka-form-label">Nama</label>
                        <input type="text" wire:model="formName" class="ka-input" placeholder="Contoh: Liburan keluarga">
                        @error('formName') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="ka-form-label">Target (Rp)</label>
                        <input type="number" min="0" step="0.01" wire:model="formTargetAmount" class="ka-input" placeholder="0" inputmode="decimal">
                        @error('formTargetAmount') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="ka-form-label">Alokasi Bulanan (opsional)</label>
                        <input type="number" min="0" step="0.01" wire:model="formMonthlyAllocation" class="ka-input" placeholder="0" inputmode="decimal">
                        @error('formMonthlyAllocation') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="ka-form-label">Ikon</label>
                            <input type="text" wire:model="formIcon" class="ka-input text-center" maxlength="4">
                        </div>
                        <div>
                            <label class="ka-form-label">Warna</label>
                            <input type="color" wire:model="formColor" class="ka-input h-[46px] p-1">
                        </div>
                    </div>
                    <button wire:click="save" wire:loading.attr="disabled" class="ka-btn ka-btn-primary">
                        Simpan Tabungan
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
