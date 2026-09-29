<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-[var(--text-primary)]">Manajemen Keluarga</h1>
        <p class="text-sm text-[var(--text-tertiary)] mt-1">Pilih keluarga lalu kelola tiap sub-menu: transaksi, anggaran, tabungan, hutang, laporan, anggota &amp; lisensi</p>
        @if($statusMessage)
            <p class="text-sm text-emerald-600 mt-2">{{ $statusMessage }}</p>
        @endif
    </div>

    <div class="flex items-center justify-between gap-3 flex-wrap">
        <h2 class="text-base font-semibold text-[var(--text-primary)]">Daftar Keluarga</h2>
    </div>

    @if(! $familyId && ! $userId)
        <nav class="flex gap-2 flex-wrap border-b border-[var(--color-border)] pb-2" aria-label="Direktori manajemen keluarga">
            <button type="button" wire:click="setDirectoryTab('keluarga')"
                    class="px-4 py-2 rounded-full text-sm font-medium transition {{ $directoryTab === 'keluarga' ? 'bg-indigo-600 text-white' : 'text-[var(--text-secondary)] hover:bg-[var(--color-bg)]' }}">
                Keluarga
            </button>
            <button type="button" wire:click="setDirectoryTab('pengguna')"
                    class="px-4 py-2 rounded-full text-sm font-medium transition {{ $directoryTab === 'pengguna' ? 'bg-indigo-600 text-white' : 'text-[var(--text-secondary)] hover:bg-[var(--color-bg)]' }}">
                Semua Pengguna
            </button>
        </nav>

        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
            <div class="p-5 border-b border-[var(--color-border)]">
                <label class="wp-form-label">{{ $directoryTab === 'pengguna' ? 'Cari pengguna atau keluarga' : 'Cari keluarga' }}</label>
                <input type="search" wire:model.live.debounce.300ms="familySearch" class="wp-form-input" placeholder="{{ $directoryTab === 'pengguna' ? 'Cari nama atau email' : 'Cari nama keluarga' }}">
            </div>

            @if($directoryTab === 'keluarga')
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-[var(--text-tertiary)] border-b border-[var(--color-border)]">
                            <th class="px-5 py-3">Keluarga</th>
                            <th class="px-5 py-3">Email terdaftar</th>
                            <th class="px-5 py-3">Anggota</th>
                            <th class="px-5 py-3">Masa aktif</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Terakhir online</th>
                            <th class="px-5 py-3">AI</th>
                            <th class="px-5 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($families as $family)
                            @php
                                $subscription = $family->latest_subscription;
                                $isLicensed = $subscription?->isUsable() === true;
                            @endphp
                            <tr class="border-b border-[var(--color-border)] last:border-0 hover:bg-[var(--color-bg)]">
                                <td class="px-5 py-4">
                                    <a href="{{ route('keuangan.keluarga.detail', $family) }}" class="font-semibold text-[var(--text-primary)] hover:text-indigo-600">
                                        {{ $family->name }}
                                    </a>
                                    <p class="text-xs text-[var(--text-tertiary)] mt-1">{{ $family->owner?->name ?? '-' }}</p>
                                </td>
                                <td class="px-5 py-4 text-[var(--text-secondary)]">{{ $family->owner?->email ?? '-' }}</td>
                                <td class="px-5 py-4">{{ $family->members_count }}</td>
                                <td class="px-5 py-4">
                                    @if($subscription?->expires_at)
                                        <span class="text-[var(--text-primary)]">{{ $subscription->expires_at->format('d M Y') }}</span>
                                    @elseif($subscription)
                                        <span class="text-[var(--text-primary)]">Seumur hidup</span>
                                    @else
                                        <span class="text-[var(--text-tertiary)]">-</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <span class="text-xs px-2 py-1 rounded-full {{ $isLicensed ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                                        {{ $isLicensed ? 'Aktif' : 'Tidak aktif' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-[var(--text-secondary)] whitespace-nowrap">
                                    {{ $family->last_online_at?->format('d M Y H:i') ?? 'Belum ada data' }}
                                </td>
                                <td class="px-5 py-4">
                                    @if(! $aiConfigured)
                                        <span class="text-xs text-amber-600">Konfigurasi belum ada</span>
                                    @elseif(! $isLicensed || $subscription->plan?->ai_analysis_limit === 0)
                                        <span class="text-xs text-red-600">Tidak aktif</span>
                                    @else
                                        <span class="text-xs text-emerald-600">Berjalan</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <a href="{{ route('keuangan.keluarga.detail', $family) }}" class="btn-secondary !py-1 text-xs">Kelola</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-5 py-8 text-center text-[var(--text-tertiary)]">Tidak ada keluarga ditemukan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between gap-3 flex-wrap">
                <p class="text-xs text-[var(--text-tertiary)]">
                    Menampilkan {{ $families->firstItem() ?? 0 }}&ndash;{{ $families->lastItem() ?? 0 }} dari {{ $families->total() }} keluarga
                </p>
                @if($families->hasPages())
                    <nav class="flex items-center gap-1" aria-label="Paginasi keluarga">
                        @if($families->onFirstPage())
                            <span class="px-2 py-1 text-xs text-[var(--text-tertiary)]">&laquo;</span>
                        @else
                            <button type="button" wire:click="previousPage" class="px-2 py-1 text-xs rounded hover:bg-[var(--color-bg)]">&laquo;</button>
                        @endif
                        <span class="px-2 py-1 text-xs font-medium">Hal. {{ $families->currentPage() }} / {{ $families->lastPage() }}</span>
                        @if($families->hasMorePages())
                            <button type="button" wire:click="nextPage" class="px-2 py-1 text-xs rounded hover:bg-[var(--color-bg)]">&raquo;</button>
                        @else
                            <span class="px-2 py-1 text-xs text-[var(--text-tertiary)]">&raquo;</span>
                        @endif
                    </nav>
                @endif
            </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wide text-[var(--text-tertiary)] border-b border-[var(--color-border)]">
                                <th class="px-5 py-3">Pengguna</th>
                                <th class="px-5 py-3">Email</th>
                                <th class="px-5 py-3">Keluarga</th>
                                <th class="px-5 py-3">Peran</th>
                                <th class="px-5 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($directoryFamilies as $directoryFamily)
                                @php
                                    $owner = $directoryFamily->owner;
                                    $members = $directoryFamily->members->filter(fn ($member) => $member->user && $member->user_id !== $owner?->id);
                                @endphp
                                @if($owner)
                                    <tr class="border-b border-[var(--color-border)] bg-[var(--color-bg)]">
                                        <td class="px-5 py-3 font-semibold text-[var(--text-primary)]">{{ $owner->name }}</td>
                                        <td class="px-5 py-3 text-[var(--text-secondary)]">{{ $owner->email }}</td>
                                        <td class="px-5 py-3">{{ $directoryFamily->name }}</td>
                                        <td class="px-5 py-3"><span class="text-xs px-2 py-1 rounded-full bg-indigo-50 text-indigo-700">Kepala keluarga</span></td>
                                        <td class="px-5 py-3 text-right"><a href="{{ route('keuangan.keluarga.detail', $directoryFamily) }}" class="btn-secondary !py-1 text-xs">Kelola</a></td>
                                    </tr>
                                @endif
                                @foreach($members as $member)
                                    <tr class="border-b border-[var(--color-border)] last:border-0">
                                        <td class="px-5 py-3 pl-12 text-[var(--text-primary)]"><span class="mr-2 text-[var(--text-tertiary)]">&rarr;</span>{{ $member->user->name }}</td>
                                        <td class="px-5 py-3 text-[var(--text-secondary)]">{{ $member->user->email }}</td>
                                        <td class="px-5 py-3 text-[var(--text-secondary)]">{{ $directoryFamily->name }}</td>
                                        <td class="px-5 py-3"><span class="text-xs text-[var(--text-tertiary)]">{{ $member->relationship === 'child' ? 'Anak' : 'Anggota' }}</span></td>
                                        <td class="px-5 py-3 text-right"><a href="{{ route('keuangan.keluarga.detail', $directoryFamily) }}" class="text-xs text-indigo-600 hover:underline">Lihat keluarga</a></td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr><td colspan="5" class="px-5 py-8 text-center text-[var(--text-tertiary)]">Tidak ada keluarga atau pengguna ditemukan.</td></tr>
                            @endforelse
                            @foreach($unassignedUsers as $user)
                                <tr class="border-b border-[var(--color-border)] last:border-0">
                                    <td class="px-5 py-3 font-medium text-[var(--text-primary)]">{{ $user->name }}</td>
                                    <td class="px-5 py-3 text-[var(--text-secondary)]">{{ $user->email }}</td>
                                    <td class="px-5 py-3 text-[var(--text-tertiary)]">Belum ada keluarga</td>
                                    <td class="px-5 py-3"><span class="text-xs px-2 py-1 rounded-full bg-amber-50 text-amber-700">Belum ditautkan</span></td>
                                    <td class="px-5 py-3 text-right">-</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @else
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-5 flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-4">
                <a href="{{ route('keuangan.keluarga') }}" class="btn-secondary !py-1.5 text-xs">&larr; Kembali</a>
                <div>
                    <p class="text-xs uppercase tracking-wide text-[var(--text-tertiary)]">Keluarga terpilih</p>
                    <p class="text-lg font-bold text-[var(--text-primary)]">{{ $selectedFamily?->name }}</p>
                    <p class="text-sm text-[var(--text-tertiary)]">Kepala keluarga: {{ $selectedFamily?->owner?->name ?? '-' }}</p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-xs uppercase tracking-wide text-[var(--text-tertiary)]">Lisensi</p>
                @if($license)
                    <p class="font-semibold text-[var(--text-primary)]">{{ $license->plan?->name ?? 'Paket' }}</p>
                    <p class="text-sm {{ $license->isUsable() ? 'text-emerald-600' : 'text-red-500' }}">
                        {{ $license->expires_at?->format('d M Y') ?? 'Seumur hidup' }} Â· {{ $license->isUsable() ? 'aktif' : 'habis' }}
                    </p>
                @else
                    <p class="text-sm text-[var(--text-tertiary)]">Belum berlisensi</p>
                @endif
            </div>
        </div>

        <nav class="flex gap-2 flex-wrap border-b border-[var(--color-border)] pb-2" aria-label="Sub-menu keluarga" wire:ignore>
            @php
                $sections = [
                    'ringkasan' => 'Dashboard',
                    'transaksi' => 'Transaksi',
                    'anggaran' => 'Anggaran',
                    'tabungan' => 'Tabungan',
                    'hutang' => 'Hutang',
                    'laporan' => 'Laporan',
                    'anggota' => 'Anggota',
                    'lisensi' => 'Lisensi',
                ];
            @endphp
            @foreach($sections as $key => $label)
                <button type="button" wire:click="$set('section', '{{ $key }}')"
                        class="px-4 py-2 rounded-full text-sm font-medium transition
                            {{ $section === $key ? 'bg-indigo-600 text-white' : 'text-[var(--text-secondary)] hover:bg-[var(--color-bg)]' }}"
                        aria-current="{{ $section === $key ? 'page' : 'false' }}">
                    {{ $label }}
                </button>
            @endforeach
        </nav>

        @if($section === 'transaksi')
            <livewire:family-transactions :family-id="$familyId" :key="'admin-family-transactions-'.$familyId" />
        @elseif($section === 'anggaran')
            <livewire:family-budget :family-id="$familyId" :key="'admin-family-budget-'.$familyId" />
        @elseif($section === 'tabungan')
            <livewire:family-goals :family-id="$familyId" :key="'admin-family-goals-'.$familyId" />
        @elseif($section === 'hutang')
            <livewire:family-debts :family-id="$familyId" :key="'admin-family-debts-'.$familyId" />
        @elseif($section === 'laporan')
            <livewire:family-report :family-id="$familyId" :key="'admin-family-report-'.$familyId" />
        @else
        @if($section === 'ringkasan')
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

        @if($section === 'transaksi')
            <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-5">
                <label class="wp-form-label">Bulan</label>
                <input type="month" wire:model.live="month" class="wp-form-input">
            </div>
            <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-[var(--color-border)]">
                    <h3 class="text-sm font-semibold text-[var(--text-primary)]">Transaksi {{ \Carbon\Carbon::parse($month.'-01')->format('M Y') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wide text-[var(--text-tertiary)] border-b border-[var(--color-border)]">
                                <th class="px-5 py-3">Tanggal</th>
                                <th class="px-5 py-3">Kategori</th>
                                <th class="px-5 py-3">Deskripsi</th>
                                <th class="px-5 py-3">Oleh</th>
                                <th class="px-5 py-3 text-right">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transactions as $t)
                                <tr class="border-b border-[var(--color-border)] last:border-0">
                                    <td class="px-5 py-2.5 text-[var(--text-tertiary)]">{{ $t->date->format('d M Y') }}</td>
                                    <td class="px-5 py-2.5">{{ $t->category?->name ?? 'tanpa kategori' }}</td>
                                    <td class="px-5 py-2.5 text-[var(--text-tertiary)]">{{ $t->description ?: '-' }}</td>
                                    <td class="px-5 py-2.5 text-[var(--text-tertiary)]">{{ $t->user?->name ?? '-' }}</td>
                                    <td class="px-5 py-2.5 text-right font-medium {{ $t->type === 'income' ? 'text-emerald-600' : 'text-red-500' }}">
                                        {{ $t->type === 'income' ? '+' : '-' }}Rp{{ number_format($t->amount, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-5 py-8 text-center text-[var(--text-tertiary)]">Tidak ada transaksi pada bulan ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if($section === 'anggaran')
            <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-5">
                <label class="wp-form-label">Bulan</label>
                <input type="month" wire:model.live="month" class="wp-form-input">
            </div>
            <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-[var(--color-border)] flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-[var(--text-primary)]">Anggaran {{ \Carbon\Carbon::parse($month.'-01')->format('M Y') }}</h3>
                    @php $totalBudget = array_sum(array_column($budgetRows, 'amount')); $totalSpent = array_sum(array_column($budgetRows, 'spent')); @endphp
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $totalSpent > $totalBudget && $totalBudget > 0 ? 'bg-red-50 text-red-700' : 'bg-slate-100 text-slate-600' }}">
                        Rp{{ number_format($totalSpent, 0, ',', '.') }} dari Rp{{ number_format($totalBudget, 0, ',', '.') }}
                    </span>
                </div>
                <div class="p-5 space-y-3">
                    @if(empty($budgetRows))
                        <p class="text-sm text-[var(--text-tertiary)]">Belum ada kategori pengeluaran.</p>
                    @else
                        @foreach($budgetRows as $row)
                            <div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-[var(--text-primary)]">{{ $row['category'] }}</span>
                                    <span class="flex items-center gap-2">
                                        <span class="font-medium text-[var(--text-primary)]">Rp{{ number_format($row['spent'], 0, ',', '.') }}</span>
                                        @if($row['amount'] > 0)
                                            <span class="text-[var(--text-tertiary)]">/ Rp{{ number_format($row['amount'], 0, ',', '.') }}</span>
                                            <span class="text-xs px-2 py-0.5 rounded-full {{ $row['overspent'] ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700' }}">{{ $row['percent'] }}%</span>
                                        @else
                                            <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-500">tanpa anggaran</span>
                                        @endif
                                    </span>
                                </div>
                                <div class="mt-1 w-full h-2 rounded-full bg-[var(--color-bg)] overflow-hidden">
                                    <div class="h-full rounded-full {{ $row['overspent'] ? 'bg-red-500' : 'bg-emerald-500' }}" style="width: {{ $row['percent'] }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        @endif

        @if($section === 'tabungan')
            <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-[var(--color-border)]">
                    <h3 class="text-sm font-semibold text-[var(--text-primary)]">Tabungan &amp; Goals</h3>
                </div>
                <div class="p-5 space-y-4">
                    @if($goalRows->isEmpty())
                        <p class="text-sm text-[var(--text-tertiary)]">Belum ada goal tabungan.</p>
                    @else
                        @foreach($goalRows as $goal)
                            <div class="flex items-center gap-4 justify-between p-4 rounded-xl border border-[var(--color-border)]">
                                <div class="flex items-center gap-3">
                                    <span class="w-10 h-10 rounded-full flex items-center justify-center text-lg" style="background: {{ $goal->color }}22">{{ $goal->icon ?? 'ðŸŽ¯' }}</span>
                                    <div>
                                        <p class="font-medium text-[var(--text-primary)]">{{ $goal->name }}</p>
                                        <p class="text-xs text-[var(--text-tertiary)]">
                                            {{ $goal->status === 'completed' ? 'Selesai' : ($goal->deadline ? 'Target '.$goal->deadline->format('d M Y') : 'Tanpa tenggat') }}
                                            @if((float) $goal->monthly_allocation > 0) &middot; alokasi Rp{{ number_format($goal->monthly_allocation, 0, ',', '.') }}/bln @endif
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold text-[var(--text-primary)]">Rp{{ number_format((float) $goal->current_amount, 0, ',', '.') }}</p>
                                    <p class="text-xs text-[var(--text-tertiary)]">dari Rp{{ number_format((float) $goal->target_amount, 0, ',', '.') }}</p>
                                    <span class="text-xs px-2 py-0.5 rounded-full {{ $goal->status === 'completed' ? 'bg-emerald-50 text-emerald-700' : 'bg-indigo-50 text-indigo-700' }}">
                                        {{ number_format($goal->progress, 0) }}%
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        @endif

        @if($section === 'hutang')
            <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-[var(--color-border)]">
                    <h3 class="text-sm font-semibold text-[var(--text-primary)]">Hutang / Piutang</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wide text-[var(--text-tertiary)] border-b border-[var(--color-border)]">
                                <th class="px-5 py-3">Nama</th>
                                <th class="px-5 py-3">Tipe</th>
                                <th class="px-5 py-3">Total</th>
                                <th class="px-5 py-3 text-right">Terbayar</th>
                                <th class="px-5 py-3 text-right">Sisa</th>
                                <th class="px-5 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($debtRows as $debt)
                                @php
                                    $remaining = (float) $debt->amount - (float) $debt->paid_amount;
                                    $settled = $debt->status === 'settled';
                                @endphp
                                <tr class="border-b border-[var(--color-border)] last:border-0 {{ $settled ? 'opacity-60' : '' }}">
                                    <td class="px-5 py-2.5 font-medium text-[var(--text-primary)]">{{ $debt->name }}</td>
                                    <td class="px-5 py-2.5 text-[var(--text-tertiary)]">{{ $debt->type === 'payable' ? 'Hutang' : 'Piutang' }}</td>
                                    <td class="px-5 py-2.5">Rp{{ number_format((float) $debt->amount, 0, ',', '.') }}</td>
                                    <td class="px-5 py-2.5 text-right text-[var(--text-tertiary)]">Rp{{ number_format((float) $debt->paid_amount, 0, ',', '.') }}</td>
                                    <td class="px-5 py-2.5 text-right font-medium text-[var(--text-primary)]">Rp{{ number_format($remaining, 0, ',', '.') }}</td>
                                    <td class="px-5 py-2.5">
                                        <span class="text-xs px-2 py-0.5 rounded-full {{ $settled ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                            {{ $settled ? 'Lunas' : ($debt->status === 'partial' ? 'Dicicil' : 'Terbuka') }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-5 py-8 text-center text-[var(--text-tertiary)]">Belum ada data hutang.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if($section === 'laporan')
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
                    <div class="px-5 py-4 border-b border-[var(--color-border)]">
                        <h3 class="text-sm font-semibold text-[var(--text-primary)]">Pemasukan vs Pengeluaran &mdash; 12 Bulan</h3>
                    </div>
                    <div class="p-5">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-left text-xs uppercase tracking-wide text-[var(--text-tertiary)] border-b border-[var(--color-border)]">
                                        <th class="px-3 py-2">Bulan</th>
                                        <th class="px-3 py-2 text-right">Pemasukan</th>
                                        <th class="px-3 py-2 text-right">Pengeluaran</th>
                                        <th class="px-3 py-2 text-right">Saldo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($monthlySeries as $s)
                                        @php $saldo = $s['income'] - $s['expense']; @endphp
                                        <tr class="border-b border-[var(--color-border)] last:border-0">
                                            <td class="px-3 py-2 font-medium text-[var(--text-primary)]">{{ $s['label'] }}</td>
                                            <td class="px-3 py-2 text-right text-emerald-600">Rp{{ number_format($s['income'], 0, ',', '.') }}</td>
                                            <td class="px-3 py-2 text-right text-red-500">Rp{{ number_format($s['expense'], 0, ',', '.') }}</td>
                                            <td class="px-3 py-2 text-right font-medium {{ $saldo >= 0 ? 'text-emerald-700' : 'text-red-600' }}">Rp{{ number_format($saldo, 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
                    <div class="px-5 py-4 border-b border-[var(--color-border)]">
                        <h3 class="text-sm font-semibold text-[var(--text-primary)]">Sisa Saldo 12 Bulan</h3>
                    </div>
                    <div class="p-5">
                        <svg viewBox="0 0 300 140" class="w-full" role="img" aria-label="Grafik saldo 12 bulan">
                            @php
                                $values = array_map(fn($s) => $s['income'] - $s['expense'], $monthlySeries);
                                $min = min(0, min($values)); $max = max(1, max($values));
                                $range = $max - $min;
                                $plot = 100; $baseY = 115; $step = 300 / max(1, count($values) - 1);
                                $points = [];
                                foreach ($values as $i => $v) {
                                    $points[] = [(10 + $i * $step), $baseY - (($v - $min) / $range) * $plot];
                                }
                                $path = 'M '.implode(' L ', array_map(fn($p) => number_format($p[0], 1).','.number_format($p[1], 1), $points));
                            @endphp
                            <line x1="10" y1="{{ $baseY }}" x2="290" y2="{{ $baseY }}" stroke="var(--color-border-strong)" stroke-width="1"/>
                            <line x1="10" y1="{{ $baseY - $plot }}" x2="290" y2="{{ $baseY - $plot }}" stroke="var(--color-border)" stroke-width="1" stroke-dasharray="3 3"/>
                            <path d="{{ $path }}" fill="none" stroke="#6366f1" stroke-width="2"/>
                            <text x="12" y="{{ $baseY - $plot }}" font-size="9" fill="var(--text-tertiary)">Maks</text>
                            <text x="12" y="{{ $baseY }}" font-size="9" fill="var(--text-tertiary)">0 / Min</text>
                        </svg>
                    </div>
                </div>
            </div>
        @endif

        @if($section === 'anggota')
            <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-[var(--color-border)]">
                    <h3 class="text-sm font-semibold text-[var(--text-primary)]">Anggota Keluarga</h3>
                    <p class="text-xs text-[var(--text-tertiary)] mt-1">Semua akun di bawah ini menginduk pada kepala keluarga: {{ $selectedFamily?->owner?->name ?? '-' }}.</p>
                </div>
                <div class="divide-y divide-[var(--color-border)]">
                    @foreach($selectedFamily?->members ?? [] as $member)
                        <div class="px-5 py-4 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                            <div>
                                <p class="font-medium text-[var(--text-primary)]">{{ $member->user?->name ?? '-' }}</p>
                                <p class="text-xs text-[var(--text-secondary)]">{{ $member->user?->email ?? '-' }}</p>
                                <p class="text-xs text-[var(--text-tertiary)]">
                                    {{ $member->role === 'owner' ? 'Kepala keluarga' : ($member->relationship === 'child' ? 'Anak' : 'Dewasa') }}
                                    Â· Pemasukan {{ ($member->visibility['income'] ?? true) ? 'terlihat' : 'disembunyikan' }}
                                    Â· Pengeluaran {{ ($member->visibility['expense'] ?? true) ? 'terlihat' : 'disembunyikan' }}
                                    Â· Hutang {{ ($member->visibility['debts'] ?? true) ? 'terlihat' : 'disembunyikan' }}
                                </p>
                            </div>
                            @if($member->role !== 'owner')
                                <button type="button" wire:click="editMember({{ $member->user_id }})" class="btn-secondary !py-1.5 text-xs">Atur Permission</button>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            @if($editingMemberId)
                <form wire:submit="saveMemberSettings" class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-5 space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-[var(--text-primary)]">Permission Anggota</h3>
                        <button type="button" wire:click="$set('editingMemberId', null)" class="btn-secondary !py-1.5 text-xs">Batal</button>
                    </div>
                    <div>
                        <label class="wp-form-label">Hubungan</label>
                        <select wire:model="memberRelationship" class="wp-form-input">
                            <option value="adult">Dewasa</option>
                            <option value="child">Anak</option>
                        </select>
                    </div>
                    <div class="space-y-3">
                        <label class="flex items-center gap-3 text-sm text-[var(--text-primary)]">
                            <input type="checkbox" wire:model="memberCanViewIncome"> Dapat melihat pemasukan
                        </label>
                        <label class="flex items-center gap-3 text-sm text-[var(--text-primary)]">
                            <input type="checkbox" wire:model="memberCanViewExpense"> Dapat melihat pengeluaran
                        </label>
                        <label class="flex items-center gap-3 text-sm text-[var(--text-primary)]">
                            <input type="checkbox" wire:model="memberCanViewDebts"> Dapat melihat hutang
                        </label>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="btn-primary">Simpan Permission</button>
                    </div>
                </form>
            @endif
        @endif

        @if($section === 'lisensi')
            <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-[var(--color-border)]">
                    <h3 class="text-sm font-semibold text-[var(--text-primary)]">Lisensi Keluarga</h3>
                </div>
                <div class="p-5">
                    @if($license)
                        <div class="flex items-center justify-between gap-4 flex-wrap">
                            <div>
                                <p class="text-xl font-bold text-[var(--text-primary)]">{{ $license->plan?->name ?? 'Paket' }}</p>
                                <p class="text-sm {{ $license->isUsable() ? 'text-emerald-600' : 'text-red-500' }}">
                                    {{ $license->expires_at?->format('d M Y') ?? 'Seumur hidup' }}
                                    Â· {{ $license->isUsable() ? 'aktif' : 'habis' }}
                                </p>
                            </div>
                            @if($license->expires_at)
                                <div class="text-right">
                                    <p class="text-xs uppercase tracking-wide text-[var(--text-tertiary)]">Sisa</p>
                                    <p class="text-lg font-bold {{ $license->isUsable() ? 'text-emerald-600' : 'text-red-500' }}">
                                        {{ max(0, now()->diffInDays($license->expires_at)) }} hari
                                    </p>
                                </div>
                            @endif
                        </div>
                        <div class="mt-5 grid grid-cols-1 lg:grid-cols-3 gap-4 border-t border-[var(--color-border)] pt-5">
                            <div>
                                <label class="wp-form-label">Perpanjang dengan paket</label>
                                <select wire:model="licensePlanId" class="wp-form-input">
                                    <option value="">Pilih paket...</option>
                                    @foreach($plans as $plan)
                                        <option value="{{ $plan->id }}">{{ $plan->name }} &mdash; {{ $plan->durationLabel() }}</option>
                                    @endforeach
                                </select>
                                @error('licensePlanId') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="wp-form-label">Tanggal berakhir</label>
                                <input type="date" wire:model="licenseExpiry" class="wp-form-input">
                                @error('licenseExpiry') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </div>
                            <div class="flex items-end gap-2 flex-wrap">
                                <button type="button" wire:click="extendLicense" class="btn-primary">Perpanjang</button>
                                <button type="button" wire:click="saveLicenseExpiry" class="btn-secondary">Simpan Tanggal</button>
                                <button type="button" wire:click="revokeLicense" wire:confirm="Cabut lisensi keluarga ini?" class="btn-secondary">Cabut</button>
                            </div>
                        </div>
                    @else
                        <p class="text-sm text-[var(--text-tertiary)]">Keluarga ini belum memiliki lisensi aktif.</p>
                        <div class="mt-4 flex items-end gap-3 flex-wrap">
                            <div class="min-w-[260px] flex-1">
                                <label class="wp-form-label">Beri lisensi</label>
                                <select wire:model="licensePlanId" class="wp-form-input">
                                    <option value="">Pilih paket...</option>
                                    @foreach($plans as $plan)
                                        <option value="{{ $plan->id }}">{{ $plan->name }} &mdash; {{ $plan->durationLabel() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="button" wire:click="grantLicense" class="btn-primary">Aktifkan Lisensi</button>
                        </div>
                    @endif
                </div>
            </div>
        @endif
        @endif
    @endif
</div>
