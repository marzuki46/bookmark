<div class="space-y-6">
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold text-[var(--text-primary)]">Laporan Keuangan Keluarga</h1>
            <p class="text-sm text-[var(--text-tertiary)] mt-1">Ringkasan, tren bulanan, kategori, dan skor kesehatan keuangan</p>
        </div>
        @if($family)
            <div class="flex items-center gap-2">
                <button wire:click="$set('showAiModal', true)" class="btn-secondary">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5a3 3 0 10-3 3.85A5 5 0 1112 5"/><path d="M15 12a1 1 0 01-1 1h-2a1 1 0 00-1 1v3a1 1 0 001 1h2a1 1 0 001-1v-3a1 1 0 011-1"/><path d="M9 12a1 1 0 011-1h2a1 1 0 011 1v3a1 1 0 01-1 1h-2a1 1 0 01-1-1v-3a1 1 0 00-1-1"/></svg>
                    Analisis AI
                </button>
                <button wire:click="exportCsv" class="btn-primary">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Export CSV
                </button>
            </div>
        @endif
    </div>

    @if(! $family)
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-8 text-center">
            <div class="text-3xl mb-2">🏠</div>
            <p class="text-sm text-[var(--text-tertiary)]">Buat keluarga dulu di menu Dashboard Keluarga.</p>
        </div>
    @else
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-4">
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="text-xs font-medium text-[var(--text-secondary)] mb-1 block">Periode</label>
                    <select wire:model.live="period" class="wp-form-input">
                        <option value="today">Hari Ini</option>
                        <option value="this_week">Minggu Ini</option>
                        <option value="this_month">Bulan Ini</option>
                        <option value="last_month">Bulan Lalu</option>
                        <option value="this_year">Tahun Ini</option>
                        <option value="custom">Kustom</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-medium text-[var(--text-secondary)] mb-1 block">Dari</label>
                    <input type="date" wire:model.live="dateFrom" class="wp-form-input" {{ $period === 'custom' ? '' : 'disabled' }}>
                </div>
                <div>
                    <label class="text-xs font-medium text-[var(--text-secondary)] mb-1 block">Sampai</label>
                    <input type="date" wire:model.live="dateTo" class="wp-form-input" {{ $period === 'custom' ? '' : 'disabled' }}>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="stat-card" style="--accent-start: #10b981; --accent-end: #34d399">
                <div class="stat-header">
                    <span class="stat-label">Pemasukan</span>
                    <div class="stat-icon" style="background: #10b98115; color: #10b981;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                    </div>
                </div>
                <div class="stat-value text-emerald-600">{{ number_format($stats['income'], 0, ',', '.') }}</div>
            </div>
            <div class="stat-card" style="--accent-start: #ef4444; --accent-end: #f87171">
                <div class="stat-header">
                    <span class="stat-label">Pengeluaran</span>
                    <div class="stat-icon" style="background: #ef444415; color: #ef4444;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                    </div>
                </div>
                <div class="stat-value text-red-600">{{ number_format($stats['expense'], 0, ',', '.') }}</div>
            </div>
            <div class="stat-card" style="--accent-start: #6366f1; --accent-end: #8b5cf6">
                <div class="stat-header">
                    <span class="stat-label">Saldo</span>
                    <div class="stat-icon" style="background: #6366f115; color: #6366f1;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                </div>
                <div class="stat-value {{ $stats['balance'] >= 0 ? 'text-indigo-600' : 'text-red-600' }}">
                    {{ number_format($stats['balance'], 0, ',', '.') }}
                </div>
            </div>
            <div class="stat-card" style="--accent-start: #f59e0b; --accent-end: #fbbf24">
                <div class="stat-header">
                    <span class="stat-label">Transaksi</span>
                    <div class="stat-icon" style="background: #f59e0b15; color: #f59e0b;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    </div>
                </div>
                <div class="stat-value text-amber-600">{{ $stats['count'] }}</div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <div class="lg:col-span-2 bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-5">
                <h3 class="text-sm font-semibold text-[var(--text-primary)] mb-4">Tren 6 Bulan Terakhir</h3>
                <div class="flex items-end justify-between gap-2 h-44">
                    @foreach($monthlyStats as $m)
                        <div class="flex-1 flex flex-col items-center gap-1 min-w-0">
                            <div class="w-full flex flex-col items-center justify-end gap-0.5" style="height: 120px;">
                                @php
                                    $max = max(1, max($m['income'], $m['expense']));
                                    $incomeH = max(4, round($m['income'] / $max * 120));
                                    $expenseH = max(4, round($m['expense'] / $max * 120));
                                @endphp
                                <div class="w-3/4 rounded-t bg-emerald-500/80" style="height: {{ $incomeH }}px;" title="Pemasukan {{ number_format($m['income'], 0, ',', '.') }}"></div>
                                <div class="w-3/4 rounded-b bg-red-400/80" style="height: {{ $expenseH }}px;" title="Pengeluaran {{ number_format($m['expense'], 0, ',', '.') }}"></div>
                            </div>
                            <span class="text-[10px] text-[var(--text-tertiary)]">{{ $m['month'] }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="flex items-center gap-4 mt-3 text-xs text-[var(--text-tertiary)]">
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-emerald-500/80 inline-block"></span> Pemasukan</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-red-400/80 inline-block"></span> Pengeluaran</span>
                </div>
            </div>

            <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-5">
                <h3 class="text-sm font-semibold text-[var(--text-primary)] mb-4">Skor Kesehatan</h3>
                <div class="text-center">
                    <div class="w-32 h-32 mx-auto rounded-full flex flex-col items-center justify-center border-8
                        {{ ($healthScore['insufficient_data'] ?? false)
                            ? 'border-slate-300 text-slate-500'
                            : ($healthScore['score'] >= 80 ? 'border-emerald-400 text-emerald-600' : ($healthScore['score'] >= 50 ? 'border-amber-400 text-amber-600' : 'border-red-400 text-red-600')) }}">
                        <span class="text-3xl font-bold">{{ $healthScore['score'] }}</span>
                        <span class="text-xs font-semibold">{{ $healthScore['grade'] }}</span>
                    </div>
                    <div class="mt-4 space-y-2 text-left">
                        @forelse($healthScore['recommendations'] as $rec)
                            <div class="flex items-start gap-2 text-xs text-[var(--text-secondary)]">
                                <span class="text-[var(--indigo-600)] mt-0.5">•</span>
                                <span>{{ $rec }}</span>
                            </div>
                        @empty
                            <p class="text-xs text-[var(--text-quaternary)] text-center">Tidak ada rekomendasi.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <div class="lg:col-span-2 bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-[var(--color-border)]">
                    <h3 class="text-sm font-semibold text-[var(--text-primary)]">Pengeluaran per Kategori</h3>
                </div>
                <div class="divide-y divide-[var(--color-border)]">
                    @forelse($categoryStats['expense'] as $row)
                        @php
                            $totalExpense = $stats['expense'];
                            $pct = $totalExpense > 0 ? round($row->total / $totalExpense * 100) : 0;
                        @endphp
                        <div class="px-5 py-3">
                            <div class="flex items-center justify-between text-sm mb-1.5">
                                <span class="text-[var(--text-primary)] font-medium">
                                    {{ $row->category?->icon ?? '•' }} {{ $row->category?->name ?? 'Tanpa kategori' }}
                                </span>
                                <span class="text-xs text-[var(--text-tertiary)]">
                                    {{ number_format($row->total, 0, ',', '.') }} · {{ $pct }}%
                                </span>
                            </div>
                            <div class="h-1.5 bg-[var(--color-bg)] rounded-full overflow-hidden">
                                <div class="h-full rounded-full" style="width: {{ $pct }}%; background: {{ $row->category?->color ?? '#6366f1' }}"></div>
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-8 text-center text-xs text-[var(--text-quaternary)]">Tidak ada pengeluaran pada periode ini.</div>
                    @endforelse
                </div>
            </div>

            <div class="space-y-5">
                <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
                    <div class="px-5 py-4 border-b border-[var(--color-border)]">
                        <h3 class="text-sm font-semibold text-[var(--text-primary)]">Pengeluaran per Payer</h3>
                    </div>
                    <div class="divide-y divide-[var(--color-border)]">
                        @php
                            $payerTotal = array_sum($payerStats);
                            $payers = [
                                'husband' => ['label' => '👨 Suami', 'color' => '#6366f1'],
                                'wife' => ['label' => '👩 Istri', 'color' => '#ec4899'],
                                'shared' => ['label' => '🤝 Bersama', 'color' => '#10b981'],
                            ];
                        @endphp
                        @foreach($payers as $key => $p)
                            <div class="px-5 py-3 flex items-center justify-between text-sm">
                                <span class="text-[var(--text-primary)] font-medium">{{ $p['label'] }}</span>
                                <span class="text-xs text-[var(--text-secondary)]">
                                    {{ number_format($payerStats[$key], 0, ',', '.') }}
                                    <span class="text-[var(--text-quaternary)]">({{ $payerTotal > 0 ? round($payerStats[$key] / $payerTotal * 100) : 0 }}%)</span>
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
                    <div class="px-5 py-4 border-b border-[var(--color-border)]">
                        <h3 class="text-sm font-semibold text-[var(--text-primary)]">Pemasukan per Kategori</h3>
                    </div>
                    <div class="divide-y divide-[var(--color-border)]">
                        @forelse($categoryStats['income'] as $row)
                            <div class="px-5 py-3 flex items-center justify-between text-sm">
                                <span class="text-[var(--text-primary)] font-medium">
                                    {{ $row->category?->icon ?? '•' }} {{ $row->category?->name ?? 'Tanpa kategori' }}
                                </span>
                                <span class="text-xs text-emerald-600 font-medium">{{ number_format($row->total, 0, ',', '.') }}</span>
                            </div>
                        @empty
                            <div class="px-5 py-8 text-center text-xs text-[var(--text-quaternary)]">Tidak ada pemasukan pada periode ini.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- AI Modal --}}
    @if($showAiModal && $family)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" wire:click="closeAiModal"></div>
            <div class="relative bg-[var(--color-surface)] rounded-xl shadow-xl w-full max-w-lg border border-[var(--color-border)]">
                <div class="flex items-center justify-between px-6 py-4 border-b border-[var(--color-border)]">
                    <h3 class="text-lg font-semibold text-[var(--text-primary)]">
                        <span class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-[var(--indigo-600)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5a3 3 0 10-3 3.85A5 5 0 1112 5"/><path d="M15 12a1 1 0 01-1 1h-2a1 1 0 00-1 1v3a1 1 0 001 1h2a1 1 0 001-1v-3a1 1 0 011-1"/><path d="M9 12a1 1 0 011-1h2a1 1 0 011 1v3a1 1 0 01-1 1h-2a1 1 0 01-1-1v-3a1 1 0 00-1-1"/></svg>
                            Analisis AI Keuangan
                        </span>
                    </h3>
                    <button wire:click="closeAiModal" class="p-1 rounded hover:bg-[var(--color-bg)] transition text-[var(--text-quaternary)]">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <textarea wire:model="aiQuery" rows="3" class="wp-form-input" placeholder="Contoh: Bagaimana cara hemat bulan ini?"></textarea>
                    <button wire:click="askAi" wire:loading.attr="disabled" class="btn-primary w-full justify-center">
                        <span wire:loading.remove wire:target="askAi">Analisis Sekarang</span>
                        <span wire:loading wire:target="askAi" class="flex items-center gap-2">
                            <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"/><path d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" fill="currentColor" class="opacity-75"/></svg>
                            Menganalisis...
                        </span>
                    </button>
                    @if($aiAnswer)
                        <div class="bg-[var(--color-bg)] rounded-lg p-4 border border-[var(--color-border)]">
                            <div class="text-sm text-[var(--text-secondary)] whitespace-pre-wrap leading-relaxed">{!! nl2br(e($aiAnswer)) !!}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
