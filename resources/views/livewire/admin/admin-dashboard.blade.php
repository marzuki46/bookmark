<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-[var(--text-primary)]">Dashboard Penjualan Kang Cuan</h1>
        <p class="text-sm text-[var(--text-tertiary)] mt-1">Pantau keluarga trial, subscriber berbayar, transaksi penjualan, dan daya tahan pelanggan.</p>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-5">
            <p class="text-xs uppercase tracking-wide text-[var(--text-tertiary)]">Total keluarga</p>
            <p class="text-3xl font-bold text-[var(--text-primary)] mt-1">{{ number_format($this->familiesCount) }}</p>
        </div>
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-5">
            <p class="text-xs uppercase tracking-wide text-[var(--text-tertiary)]">Subscriber aktif</p>
            <p class="text-3xl font-bold text-emerald-600 mt-1">{{ number_format($this->activeFamilies) }}</p>
        </div>
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-5">
            <p class="text-xs uppercase tracking-wide text-[var(--text-tertiary)]">Sedang trial</p>
            <p class="text-3xl font-bold text-amber-500 mt-1">{{ number_format($this->trialFamilies) }}</p>
        </div>
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-5">
            <p class="text-xs uppercase tracking-wide text-[var(--text-tertiary)]">Sudah bayar</p>
            <p class="text-3xl font-bold text-indigo-600 mt-1">{{ number_format($this->paidFamilies) }}</p>
        </div>
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-5">
            <p class="text-xs uppercase tracking-wide text-[var(--text-tertiary)]">Transaksi penjualan</p>
            <p class="text-3xl font-bold text-[var(--text-primary)] mt-1">{{ number_format($this->salesCount) }}</p>
            <p class="text-xs text-[var(--text-tertiary)] mt-1">Rp{{ number_format($this->revenue, 0, ',', '.') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
        <section class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-[var(--color-border)] flex items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold text-[var(--text-primary)]">Penjualan 12 Bulan</h2>
                    <p class="text-xs text-[var(--text-tertiary)] mt-1">Jumlah transaksi berbayar dan omzet per bulan</p>
                </div>
            </div>
            <div class="p-5">
                @php
                    $salesMax = max(1, max(array_column($this->salesTrend, 'count')));
                @endphp
                <div class="grid grid-cols-12 items-end gap-2 h-56">
                    @foreach($this->salesTrend as $month)
                        <button type="button" class="group h-full flex flex-col items-center justify-end gap-2" title="{{ $month['label'] }}: {{ $month['count'] }} transaksi"
                                onclick="this.querySelector('[data-sales-bar]').classList.toggle('opacity-70')">
                            <span class="text-[10px] text-[var(--text-tertiary)] opacity-0 group-hover:opacity-100">{{ $month['count'] }}</span>
                            <span data-sales-bar class="w-full max-w-8 rounded-t bg-indigo-500 transition-opacity" style="height: {{ max(4, round($month['count'] / $salesMax * 150)) }}px"></span>
                            <span class="text-[10px] text-[var(--text-tertiary)] truncate max-w-full">{{ $month['label'] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-[var(--color-border)] flex items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold text-[var(--text-primary)]">Rata-rata Lama Subscriber</h2>
                    <p class="text-xs text-[var(--text-tertiary)] mt-1">Klik batang grafik untuk melihat keluarga di rentang tersebut</p>
                </div>
                <p class="text-2xl font-bold text-indigo-600">{{ $this->durationAnalysis['average'] }} <span class="text-xs font-normal text-[var(--text-tertiary)]">bulan</span></p>
            </div>
            <div class="p-5">
                @php $durationMax = max(1, max(array_column($this->durationAnalysis['buckets'], 'count'))); @endphp
                <div class="grid grid-cols-5 items-end gap-3 h-56">
                    @foreach($this->durationAnalysis['buckets'] as $key => $bucket)
                        <button type="button" wire:click="selectDurationBucket('{{ $key }}')" class="group h-full flex flex-col items-center justify-end gap-2" title="{{ $bucket['label'] }}: {{ $bucket['count'] }} keluarga">
                            <span class="text-xs text-[var(--text-tertiary)]">{{ $bucket['count'] }}</span>
                            <span class="w-full rounded-t transition-colors {{ $selectedDurationBucket === $key ? 'bg-emerald-600' : 'bg-emerald-400 group-hover:bg-emerald-500' }}" style="height: {{ max(4, round($bucket['count'] / $durationMax * 150)) }}px"></span>
                            <span class="text-[10px] text-center text-[var(--text-tertiary)]">{{ $bucket['label'] }}</span>
                        </button>
                    @endforeach
                </div>

                @if($selectedDurationBucket !== '')
                    <div class="mt-5 border-t border-[var(--color-border)] pt-4">
                        <h3 class="text-sm font-semibold text-[var(--text-primary)]">Subscriber {{ $this->durationAnalysis['buckets'][$selectedDurationBucket]['label'] }}</h3>
                        <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-2">
                            @forelse(collect($this->durationAnalysis['details'])->where('bucket', $selectedDurationBucket) as $detail)
                                <div class="rounded-lg border border-[var(--color-border)] px-3 py-2 text-sm flex justify-between gap-3">
                                    <span>{{ $detail['family'] }} <span class="text-xs text-[var(--text-tertiary)]">{{ $detail['plan'] }}</span></span>
                                    <span class="font-medium">{{ $detail['months'] }} bln</span>
                                </div>
                            @empty
                                <p class="text-sm text-[var(--text-tertiary)]">Belum ada data.</p>
                            @endforelse
                        </div>
                    </div>
                @endif
            </div>
        </section>
    </div>

    <section class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-[var(--color-border)]">
            <h2 class="text-base font-semibold text-[var(--text-primary)]">Transaksi Penjualan Terbaru</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-[var(--text-tertiary)] border-b border-[var(--color-border)]">
                        <th class="px-5 py-3">Tanggal</th><th class="px-5 py-3">Keluarga</th><th class="px-5 py-3">Paket</th><th class="px-5 py-3">Order</th><th class="px-5 py-3 text-right">Nominal</th><th class="px-5 py-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->recentPayments as $payment)
                        <tr class="border-b border-[var(--color-border)] last:border-0">
                            <td class="px-5 py-3">{{ $payment->paid_at?->format('d M Y H:i') ?? $payment->created_at?->format('d M Y H:i') }}</td>
                            <td class="px-5 py-3">{{ $payment->family?->name ?? $payment->user?->name ?? '-' }}</td>
                            <td class="px-5 py-3">{{ $payment->plan?->name ?? '-' }}</td>
                            <td class="px-5 py-3 font-mono text-xs">{{ $payment->order_id }}</td>
                            <td class="px-5 py-3 text-right font-semibold">Rp{{ number_format($payment->gross_amount, 0, ',', '.') }}</td>
                            <td class="px-5 py-3"><span class="text-xs px-2 py-1 rounded-full {{ $payment->status === 'paid' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $payment->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-8 text-center text-[var(--text-tertiary)]">Belum ada transaksi penjualan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
