<div class="space-y-6">
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold text-[var(--text-primary)]">Tabungan & Goals</h1>
            <p class="text-sm text-[var(--text-tertiary)] mt-1">Tabungan berjatah, dana darurat, dan alokasi otomatis dari surplus bulanan</p>
        </div>
        @if($family)
            <div class="flex items-center gap-2">
                <button wire:click="openCreateEmergency" class="btn-secondary">🛡️ Dana Darurat</button>
                <button wire:click="openCreate" class="btn-primary">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Goal Baru
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
            <div class="stat-card" style="--accent-start: #10b981; --accent-end: #34d399">
                <div class="stat-header">
                    <span class="stat-label">Dana Darurat</span>
                    <div class="stat-icon" style="background: #10b98115; color: #10b981;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    </div>
                </div>
                <div class="stat-value text-emerald-600">{{ number_format($emergencyFund['current'], 0, ',', '.') }}</div>
                <div class="stat-trend">
                    <span>Target {{ number_format($emergencyFund['target'], 0, ',', '.') }} · {{ $emergencyFund['percent'] }}%</span>
                </div>
            </div>
            <div class="stat-card" style="--accent-start: #6366f1; --accent-end: #8b5cf6">
                <div class="stat-header">
                    <span class="stat-label">Surplus Bulan Ini</span>
                    <div class="stat-icon" style="background: #6366f115; color: #6366f1;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg>
                    </div>
                </div>
                <div class="stat-value text-indigo-600">{{ number_format($surplus, 0, ',', '.') }}</div>
                <div class="stat-trend">
                    <span>Sisa setelah pengeluaran & cicilan wajib</span>
                </div>
            </div>
            <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-5 flex flex-col justify-center items-center text-center">
                <div class="text-sm font-semibold text-[var(--text-primary)] mb-1">Alokasi Jatah</div>
                <p class="text-xs text-[var(--text-tertiary)] mb-3">Bagikan surplus bulan ini ke tabungan berdasarkan prioritas.</p>
                <button wire:click="openAllocation" class="btn-primary">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    Alokasikan Sekarang
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @forelse($goals as $goal)
                <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-5">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="text-xl">{{ $goal->icon ?? '🎯' }}</span>
                            <div class="min-w-0">
                                <div class="font-medium text-[var(--text-primary)] truncate">{{ $goal->name }}</div>
                                @if($goal->type === 'emergency_fund')
                                    <span class="text-[10px] text-emerald-600 font-semibold uppercase tracking-wide">Dana Darurat</span>
                                @elseif($goal->deadline)
                                    <span class="text-[10px] text-[var(--text-quaternary)]">Deadline {{ $goal->deadline->format('d/m/Y') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-1">
                            <button wire:click="openEdit({{ $goal->id }})" class="btn-icon" title="Edit">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </button>
                            <button wire:click="archive({{ $goal->id }})" wire:confirm="Arsipkan goal ini?" class="btn-icon" title="Arsipkan">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="5" rx="1"/><path d="M4 8v11a2 2 0 002 2h12a2 2 0 002-2V8"/><path d="M10 12h4"/></svg>
                            </button>
                            <button wire:click="delete({{ $goal->id }})" wire:confirm="Hapus goal ini?" class="btn-icon text-red-500 hover:text-red-600" title="Hapus">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-baseline justify-between text-sm mb-2">
                        <span class="text-[var(--text-secondary)]">
                            {{ number_format($goal->current_amount, 0, ',', '.') }} / {{ number_format($goal->target_amount, 0, ',', '.') }}
                        </span>
                        <span class="text-xs text-[var(--text-tertiary)]">{{ $goal->progress }}%</span>
                    </div>

                    <div class="h-2.5 bg-[var(--color-bg)] rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all" style="width: {{ $goal->progress }}%; background: {{ $goal->color ?? '#6366f1' }}"></div>
                    </div>

                    @if((float) $goal->monthly_allocation > 0)
                        <div class="mt-2 text-xs text-[var(--text-tertiary)]">
                            Jatah bulanan: <span class="font-semibold text-[var(--text-primary)]">{{ number_format($goal->monthly_allocation, 0, ',', '.') }}</span>
                        </div>
                    @endif
                </div>
            @empty
                <div class="md:col-span-2 bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl">
                    <div class="empty-state !py-12">
                        <div class="empty-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="6" x2="12" y2="18"/><line x1="6" y1="12" x2="18" y2="12"/></svg>
                        </div>
                        <div class="empty-title">Belum ada tabungan</div>
                        <div class="empty-desc">Tentukan target tabungan keluarga, dari liburan hingga dana darurat.</div>
                        <button wire:click="openCreate" class="empty-action btn-primary">Buat Goal Pertama</button>
                    </div>
                </div>
            @endforelse
        </div>

        @if($completedGoals->isNotEmpty())
            <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-[var(--color-border)]">
                    <h3 class="text-sm font-semibold text-[var(--text-primary)]">🎉 Goal Tercapai</h3>
                </div>
                <ul class="divide-y divide-[var(--color-border)]">
                    @foreach($completedGoals as $goal)
                        <li class="px-5 py-3 flex items-center justify-between text-sm">
                            <span class="text-[var(--text-secondary)]">{{ $goal->icon ?? '🎯' }} {{ $goal->name }}</span>
                            <span class="text-xs font-semibold text-emerald-600">✓ {{ number_format($goal->target_amount, 0, ',', '.') }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    @endif

    {{-- Goal Modal --}}
    @if($showModal && $family)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" wire:click="closeModal"></div>
            <div class="relative bg-[var(--color-surface)] rounded-xl shadow-xl w-full max-w-lg border border-[var(--color-border)] max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between px-6 py-4 border-b border-[var(--color-border)]">
                    <h3 class="text-lg font-semibold text-[var(--text-primary)]">{{ $editingId ? 'Edit' : 'Tambah' }} Goal</h3>
                    <button wire:click="closeModal" class="p-1 rounded hover:bg-[var(--color-bg)] transition text-[var(--text-quaternary)]">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <form wire:submit="save" class="p-6 space-y-4">
                    <div>
                        <label class="wp-form-label">Nama Goal *</label>
                        <input type="text" wire:model="formName" class="wp-form-input" placeholder="Mis: Liburan ke Bali" required>
                        @error('formName') <span class="text-xs text-red-600 mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="wp-form-label">Target (Rp) *</label>
                            <input type="number" step="0.01" min="0" wire:model="formTargetAmount" class="wp-form-input" placeholder="0" required>
                            @error('formTargetAmount') <span class="text-xs text-red-600 mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="wp-form-label">Jatah Bulanan (Rp)</label>
                            <input type="number" step="0.01" min="0" wire:model="formMonthlyAllocation" class="wp-form-input" placeholder="0">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="wp-form-label">Deadline</label>
                            <input type="date" wire:model="formDeadline" class="wp-form-input">
                            @error('formDeadline') <span class="text-xs text-red-600 mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="wp-form-label">Icon</label>
                            <input type="text" wire:model="formIcon" class="wp-form-input" maxlength="5">
                        </div>
                    </div>
                    <div>
                        <label class="wp-form-label">Warna</label>
                        <input type="color" wire:model="formColor" class="wp-form-input h-10">
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" wire:click="closeModal" class="btn-secondary">Batal</button>
                        <button type="submit" class="btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Allocation Modal --}}
    @if($showAllocationModal && $family)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" wire:click="closeAllocation"></div>
            <div class="relative bg-[var(--color-surface)] rounded-xl shadow-xl w-full max-w-lg border border-[var(--color-border)]">
                <div class="flex items-center justify-between px-6 py-4 border-b border-[var(--color-border)]">
                    <h3 class="text-lg font-semibold text-[var(--text-primary)]">Alokasi Jatah Tabungan</h3>
                    <button wire:click="closeAllocation" class="p-1 rounded hover:bg-[var(--color-bg)] transition text-[var(--text-quaternary)]">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <p class="text-xs text-[var(--text-tertiary)]">
                        Surplus bulan ini <span class="font-semibold text-emerald-600">{{ number_format($surplus, 0, ',', '.') }}</span> akan dibagikan ke goal berikut sesuai prioritas & jatah bulanan.
                    </p>
                    @forelse($allocationDraft as $idx => $draft)
                        <div class="flex items-center gap-3">
                            <span class="text-lg">{{ $draft['icon'] }}</span>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-[var(--text-primary)] truncate">{{ $draft['name'] }}</div>
                            </div>
                            <input type="number" step="0.01" min="0" wire:model="allocationDraft.{{ $idx }}.amount"
                                class="wp-form-input !w-32 text-right" placeholder="0">
                        </div>
                    @empty
                        <div class="text-center text-xs text-[var(--text-quaternary)] py-6">Tidak ada goal aktif untuk dialokasikan.</div>
                    @endforelse
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" wire:click="closeAllocation" class="btn-secondary">Batal</button>
                        <button type="button" wire:click="confirmAllocation" class="btn-primary">Konfirmasi Alokasi</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
