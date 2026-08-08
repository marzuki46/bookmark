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
                <div class="ka-stat-label">Anggaran</div>
                <div class="ka-stat-value">Rp {{ number_format($totals['budget'], 0, ',', '.') }}</div>
            </div>
            <div class="ka-stat">
                <div class="ka-stat-label">Terpakai</div>
                <div class="ka-stat-value negative">Rp {{ number_format($totals['spent'], 0, ',', '.') }}</div>
            </div>
            <div class="ka-stat">
                <div class="ka-stat-label">Sisa</div>
                <div class="ka-stat-value {{ $totals['budget'] - $totals['spent'] >= 0 ? 'positive' : 'negative' }}">
                    Rp {{ number_format($totals['budget'] - $totals['spent'], 0, ',', '.') }}
                </div>
            </div>
        </div>

        <div class="ka-section ka-card">
            <div class="ka-card-header">
                <span class="ka-card-title">Anggaran Bulan Ini</span>
                <span class="ka-badge ka-badge-indigo">{{ now()->translatedFormat('F Y') }}</span>
            </div>
            <div class="ka-card-body">
                @forelse($budgets as $item)
                    <div class="mb-4 last:mb-0">
                        <div class="flex items-center justify-between text-xs mb-1.5">
                            <span class="font-medium text-[var(--text-secondary)]">
                                {{ $item['category']?->icon ?? '🏷️' }} {{ $item['category']?->name ?? 'Umum' }}
                                @if($item['overspent'])
                                    <span class="ka-badge ka-badge-red ml-1">Lebih</span>
                                @endif
                            </span>
                            <span class="text-[var(--text-tertiary)]">
                                Rp {{ number_format($item['spent'], 0, ',', '.') }}
                                @if($item['amount'] > 0) / {{ number_format($item['amount'], 0, ',', '.') }} @endif
                            </span>
                        </div>
                        <div class="ka-progress">
                            <div class="ka-progress-bar {{ $item['overspent'] ? 'bg-red-500' : 'bg-indigo-500' }}" style="width: {{ $item['percent'] }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="ka-empty">
                        <div class="ka-empty-icon">📊</div>
                        <div class="ka-empty-title">Belum ada anggaran</div>
                        <div class="ka-empty-desc">Atur anggaran kategori dari halaman Anggaran (mode web).</div>
                    </div>
                @endforelse
            </div>
        </div>
    @endif
</div>
