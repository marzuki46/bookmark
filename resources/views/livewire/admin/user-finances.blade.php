<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-[var(--text-primary)]">Keuangan per Pengguna</h1>
        <p class="text-sm text-[var(--text-tertiary)] mt-1">Pantau transaksi keluarga, kesehatan finansial, dan keganjilan data</p>
    </div>

    <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-5 max-w-lg">
        <label class="wp-form-label">Pilih pengguna</label>
        <select wire:model.live="userId" class="wp-form-input">
            <option value="">Pilih pengguna...</option>
            @foreach($users as $u)
                <option value="{{ $u['id'] }}">{{ $u['name'] }} ({{ $u['email'] }}){{ $u['has_family'] ? '' : ' - belum punya keluarga' }}</option>
            @endforeach
        </select>
    </div>

    @if(! $userId)
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-8 text-center text-sm text-[var(--text-tertiary)]">
            Pilih pengguna untuk melihat keuangannya.
        </div>
    @else
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-5">
                <p class="text-xs uppercase tracking-wide text-[var(--text-tertiary)]">Pemasukan Bulan Ini</p>
                <p class="text-2xl font-bold text-emerald-600 mt-1">{{ $health ? 'Rp'.number_format($health['income'], 0, ',', '.') : '-' }}</p>
            </div>
            <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-5">
                <p class="text-xs uppercase tracking-wide text-[var(--text-tertiary)]">Pengeluaran Bulan Ini</p>
                <p class="text-2xl font-bold text-red-500 mt-1">{{ $health ? 'Rp'.number_format($health['expense'], 0, ',', '.') : '-' }}</p>
            </div>
            <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-5">
                <p class="text-xs uppercase tracking-wide text-[var(--text-tertiary)]">Total Hutang</p>
                <p class="text-2xl font-bold text-[var(--text-primary)] mt-1">{{ $debts['count'] ? 'Rp'.number_format($debts['total'], 0, ',', '.') : '-' }}</p>
                <p class="text-xs text-[var(--text-tertiary)]">{{ $debts['count'] }} aktif &middot; cicilan Rp{{ number_format($debts['installment'], 0, ',', '.') }}</p>
            </div>
            <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-5">
                <p class="text-xs uppercase tracking-wide text-[var(--text-tertiary)]">Skor Kesehatan</p>
                <p class="text-2xl font-bold mt-1
                    {{ ($health['score'] ?? 60) >= 80 ? 'text-emerald-600' : (($health['score'] ?? 60) >= 60 ? 'text-amber-500' : 'text-red-500') }}">
                    {{ $health['score'] ?? '-' }}/100
                </p>
                <p class="text-xs text-[var(--text-tertiary)]">{{ $health['grade'] ?? '-' }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-[var(--color-border)]">
                    <h3 class="text-sm font-semibold text-[var(--text-primary)]">Pemasukan vs Pengeluaran &mdash; 12 Bulan</h3>
                </div>
                <div class="p-5">
                    @php
                        $max = max(1, max(array_map(fn($s) => max($s['income'], $s['expense']), $monthlySeries)));
                        $barW = 12; $gap = 6; $block = $barW * 2 + $gap;
                        $chartW = count($monthlySeries) * $block + 20;
                        $baseline = 130; $plot = $baseline - 20;
                    @endphp
                    @if(count($monthlySeries))
                        <svg viewBox="0 0 {{ $chartW }} 160" class="w-full" role="img" aria-label="Grafik pemasukan dan pengeluaran 12 bulan">
                            <line x1="10" y1="{{ $baseline }}" x2="{{ $chartW }}" y2="{{ $baseline }}" stroke="var(--color-border-strong)" stroke-width="1"/>
                            <line x1="10" y1="{{ $baseline - ($plot / 2) }}" x2="{{ $chartW }}" y2="{{ $baseline - ($plot / 2) }}" stroke="var(--color-border)" stroke-width="1" stroke-dasharray="3 3"/>
                            @foreach($monthlySeries as $i => $s)
                                @php
                                    $x = 10 + $i * $block;
                                    $hi = max(1, $plot * $s['income'] / $max);
                                    $he = max(1, $plot * $s['expense'] / $max);
                                    $bi = $baseline - $hi;
                                    $be = $baseline - $he;
                                    $show = $s['income'] > 0 || $s['expense'] > 0;
                                @endphp
                                @if($show)
                                    <rect x="{{ $x }}" y="{{ $bi }}" width="{{ $barW }}" height="{{ $hi }}" rx="2" fill="#10b981"/>
                                    <rect x="{{ $x + $barW + $gap }}" y="{{ $be }}" width="{{ $barW }}" height="{{ $he }}" rx="2" fill="#ef4444"/>
                                @endif
                                <text x="{{ $x + $barW }}" y="150" text-anchor="middle" font-size="9" fill="var(--text-tertiary)">{{ $s['label'] }}</text>
                            @endforeach
                        </svg>
                        <div class="flex items-center gap-4 mt-3 text-xs text-[var(--text-tertiary)]">
                            <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded-sm bg-emerald-500"></span> Pemasukan</span>
                            <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded-sm bg-red-500"></span> Pengeluaran</span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-[var(--color-border)]">
                    <h3 class="text-sm font-semibold text-[var(--text-primary)]">Pengeluaran per Kategori &mdash; Bulan Ini</h3>
                </div>
                <div class="p-5 space-y-3">
                    @if(empty($categoryBreakdown))
                        <p class="text-sm text-[var(--text-tertiary)]">Belum ada transaksi bulan ini.</p>
                    @else
                        @php $catMax = max(1, max(array_column($categoryBreakdown, 'total'))); @endphp
                        @foreach($categoryBreakdown as $cat)
                            <div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-[var(--text-primary)]">{{ $cat['name'] }}</span>
                                    <span class="font-medium text-[var(--text-primary)]">Rp{{ number_format($cat['total'], 0, ',', '.') }}</span>
                                </div>
                                <div class="mt-1 w-full h-2 rounded-full bg-[var(--color-bg)] overflow-hidden">
                                    <div class="h-full rounded-full bg-indigo-500" style="width: {{ round($cat['total'] / $catMax * 100) }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-[var(--color-border)] flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-[var(--text-primary)]">Keganjilan Data</h3>
                    <span class="text-xs px-2 py-0.5 rounded-full {{ count($anomalies) ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700' }}">
                        {{ count($anomalies) ? count($anomalies).' temuan' : 'bersih' }}
                    </span>
                </div>
                <div class="p-5">
                    @if(empty($anomalies))
                        <p class="text-sm text-[var(--text-tertiary)]">Tidak ada transaksi mencurigakan bulan ini.</p>
                    @else
                        <ul class="space-y-2 text-sm">
                            @foreach($anomalies as $a)
                                <li class="flex items-center justify-between gap-2">
                                    <span class="text-[var(--text-primary)] truncate">{{ $a['date'] }} &middot; {{ $a['user'] ?? '?' }} &middot; {{ $a['description'] ?: '-' }}</span>
                                    <span class="flex items-center gap-2 shrink-0">
                                        <span class="text-xs px-2 py-0.5 rounded-full bg-red-50 text-red-700">{{ $a['problem'] }}</span>
                                        <span class="font-medium">{{ $a['type'] === 'income' ? '+' : '-' }}Rp{{ number_format($a['amount'], 0, ',', '.') }}</span>
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-[var(--color-border)]">
                    <h3 class="text-sm font-semibold text-[var(--text-primary)]">Transaksi Terbaru</h3>
                </div>
                <div class="p-5">
                    @if($recentTransactions->isEmpty())
                        <p class="text-sm text-[var(--text-tertiary)]">Belum ada transaksi.</p>
                    @else
                        <ul class="space-y-2 text-sm">
                            @foreach($recentTransactions as $t)
                                <li class="flex items-center justify-between gap-2">
                                    <span class="text-[var(--text-primary)] truncate">{{ $t->date->format('d M') }} &middot; {{ $t->category?->name ?? 'tanpa kategori' }}</span>
                                    <span class="font-medium {{ $t->type === 'income' ? 'text-emerald-600' : 'text-red-500' }} shrink-0">
                                        {{ $t->type === 'income' ? '+' : '-' }}Rp{{ number_format($t->amount, 0, ',', '.') }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>