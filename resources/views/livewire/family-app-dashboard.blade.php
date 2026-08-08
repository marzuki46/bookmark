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
            <div class="ka-onboard-desc">Buat keluarga dulu di Dashboard Keluarga (mode web) untuk mulai menggunakan app ini.</div>
        </div>
    @else
        <div class="ka-hero">
            <div class="ka-hero-greeting">Saldo Bulan Ini</div>
            <div class="ka-hero-amount {{ $stats['balance'] >= 0 ? '' : 'text-white' }}">
                {{ $stats['balance'] >= 0 ? '+' : '-' }}Rp {{ number_format(abs($stats['balance']), 0, ',', '.') }}
            </div>
            <div class="ka-hero-foot">
                Pemasukan Rp {{ number_format($stats['income'], 0, ',', '.') }} · Pengeluaran Rp {{ number_format($stats['expense'], 0, ',', '.') }}
            </div>
        </div>

        <div class="ka-section ka-stats">
            <div class="ka-stat">
                <div class="ka-stat-label">Pemasukan</div>
                <div class="ka-stat-value positive">Rp {{ number_format($stats['income'], 0, ',', '.') }}</div>
            </div>
            <div class="ka-stat">
                <div class="ka-stat-label">Pengeluaran</div>
                <div class="ka-stat-value negative">Rp {{ number_format($stats['expense'], 0, ',', '.') }}</div>
            </div>
            <div class="ka-stat">
                <div class="ka-stat-label">Skor</div>
                <div class="ka-stat-value">{{ $healthScore['score'] ?? 0 }}/100</div>
            </div>
        </div>

        @if($upcomingDebt)
            <div class="ka-section ka-card">
                <div class="ka-card-body flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="ka-row-icon">⏰</div>
                        <div class="min-w-0">
                            <div class="ka-row-title">{{ $upcomingDebt['name'] }}</div>
                            <div class="ka-row-sub">Jatuh tempo {{ $upcomingDebt['due_date']->format('d M Y') }} · {{ $upcomingDebt['days_left'] }} hari lagi</div>
                        </div>
                    </div>
                    <span class="text-sm font-semibold text-red-600 whitespace-nowrap">Rp {{ number_format($upcomingDebt['amount'], 0, ',', '.') }}</span>
                </div>
            </div>
        @endif

        <div class="ka-section ka-card">
            <div class="ka-card-header">
                <span class="ka-card-title">Tabungan & Dana Darurat</span>
                <a href="{{ route('keluarga.app.goals') }}" class="ka-card-link">Kelola →</a>
            </div>
            <div class="ka-card-body">
                @if($emergencyFund['target'] > 0)
                    <div class="mb-4">
                        <div class="flex items-center justify-between text-xs mb-1.5">
                            <span class="font-medium text-[var(--text-secondary)]">🛡️ Dana Darurat</span>
                            <span class="text-[var(--text-tertiary)]">
                                Rp {{ number_format($emergencyFund['current'], 0, ',', '.') }} / {{ number_format($emergencyFund['target'], 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="ka-progress">
                            <div class="ka-progress-bar bg-emerald-500" style="width: {{ $emergencyFund['percent'] }}%"></div>
                        </div>
                    </div>
                @endif

                @forelse($goals as $goal)
                    <div class="mb-3 last:mb-0">
                        <div class="flex items-center justify-between text-xs mb-1.5">
                            <span class="font-medium text-[var(--text-secondary)]">{{ $goal->icon ?? '🎯' }} {{ $goal->name }}</span>
                            <span class="text-[var(--text-tertiary)]">{{ $goal->progress }}%</span>
                        </div>
                        <div class="ka-progress">
                            <div class="ka-progress-bar" style="width: {{ $goal->progress }}%; background: {{ $goal->color ?? '#6366f1' }}"></div>
                        </div>
                    </div>
                @empty
                    @if($emergencyFund['target'] <= 0)
                        <div class="ka-empty">
                            <div class="ka-empty-icon">🎯</div>
                            <div class="ka-empty-title">Belum ada tabungan</div>
                            <div class="ka-empty-desc">Buat target tabungan dari menu Tabungan.</div>
                        </div>
                    @endif
                @endforelse
            </div>
        </div>

        <div class="ka-section ka-card">
            <div class="ka-card-header">
                <span class="ka-card-title">Transaksi Terbaru</span>
                <a href="{{ route('keluarga.app.add') }}" class="ka-card-link">Tambah →</a>
            </div>
            <div class="ka-card-body !py-2">
                @forelse($recentTransactions as $tx)
                    <div class="ka-row">
                        <div class="ka-row-icon">{{ $tx->category?->icon ?? '💳' }}</div>
                        <div class="ka-row-main">
                            <div class="ka-row-title">{{ $tx->description }}</div>
                            <div class="ka-row-sub">{{ $tx->date->format('d M') }} · {{ $tx->category?->name ?? 'Umum' }}</div>
                        </div>
                        <div class="ka-row-value {{ $tx->type === 'income' ? 'positive' : 'negative' }}">
                            {{ $tx->type === 'income' ? '+' : '-' }}Rp {{ number_format($tx->amount, 0, ',', '.') }}
                        </div>
                    </div>
                @empty
                    <div class="ka-empty">
                        <div class="ka-empty-icon">💸</div>
                        <div class="ka-empty-title">Belum ada transaksi</div>
                        <div class="ka-empty-desc">Catat pengeluaran atau pemasukan pertama.</div>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="ka-section">
            <button wire:click="$set('showAiModal', true)" class="ka-btn ka-btn-secondary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5a3 3 0 10-3 3.85A5 5 0 1112 5"/><path d="M15 12a1 1 0 01-1 1h-2a1 1 0 00-1 1v3a1 1 0 001 1h2a1 1 0 001-1v-3a1 1 0 011-1"/><path d="M9 12a1 1 0 011-1h2a1 1 0 011 1v3a1 1 0 01-1 1h-2a1 1 0 01-1-1v-3a1 1 0 00-1-1"/></svg>
                Analisis AI Keuangan
            </button>
        </div>
    @endif

    @if($showAiModal && $family)
        <div class="ka-modal">
            <div class="ka-modal-backdrop" wire:click="closeAiModal"></div>
            <div class="ka-modal-panel">
                <button class="ka-modal-close" wire:click="closeAiModal" aria-label="Tutup">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
                <div class="ka-modal-title">Analisis AI Keuangan</div>
                <div class="space-y-4">
                    <textarea wire:model="aiQuery" rows="3" class="ka-input" placeholder="Contoh: Bagaimana cara hemat bulan ini?"></textarea>
                    <button wire:click="askAi" wire:loading.attr="disabled" class="ka-btn ka-btn-primary">
                        <span wire:loading.remove wire:target="askAi">Analisis Sekarang</span>
                        <span wire:loading wire:target="askAi" class="flex items-center gap-2">
                            <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"/><path d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" fill="currentColor" class="opacity-75"/></svg>
                            Menganalisis...
                        </span>
                    </button>
                    @if($aiAnswer)
                        <div class="bg-[var(--color-bg)] border border-[var(--color-border)] rounded-lg p-4">
                            <div class="text-sm text-[var(--text-secondary)] whitespace-pre-wrap leading-relaxed">{!! nl2br(e($aiAnswer)) !!}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
