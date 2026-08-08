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
        <div class="ka-hero">
            <div class="ka-hero-greeting">Sisa Bulan Ini</div>
            <div class="ka-hero-amount">Rp {{ number_format((float) $surplus, 0, ',', '.') }}</div>
            <div class="ka-hero-foot">Bagikan ke tabungan sesuai rekomendasi.</div>
        </div>

        <div class="ka-section ka-card">
            <div class="ka-card-header">
                <span class="ka-card-title">Rekomendasi Alokasi</span>
                <button wire:click="loadSuggestions" class="ka-card-link">Perbarui</button>
            </div>
            <div class="ka-card-body">
                @forelse($draft as $i => $item)
                    <div class="mb-4 last:mb-0">
                        <div class="flex items-center justify-between text-sm mb-1.5">
                            <span class="font-medium text-[var(--text-primary)]">{{ $item['icon'] }} {{ $item['name'] }}</span>
                        </div>
                        <input type="number" min="0" step="0.01" wire:model="draft.{{ $i }}.amount"
                               class="ka-input !py-2.5" placeholder="0" inputmode="decimal">
                        <div class="mt-1 text-xs text-[var(--text-tertiary)]">
                            Progres: Rp {{ number_format((float) $item['current'], 0, ',', '.') }} dari
                            Rp {{ number_format((float) $item['target'], 0, ',', '.') }}
                        </div>
                    </div>
                @empty
                    <div class="ka-empty">
                        <div class="ka-empty-icon">🎯</div>
                        <div class="ka-empty-title">Belum ada rekomendasi</div>
                        <div class="ka-empty-desc">Buat target tabungan dulu dari menu Tabungan.</div>
                    </div>
                @endforelse

                @if(count($draft) > 0)
                    <button wire:click="confirm" wire:loading.attr="disabled" class="ka-btn ka-btn-primary mt-4">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
                        Konfirmasi Alokasi
                    </button>
                @endif
            </div>
        </div>
    @endif
</div>
