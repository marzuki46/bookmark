<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold text-[var(--text-primary)]">Laporan Keuangan</h1>
            <p class="text-sm text-[var(--text-tertiary)] mt-1">Pantau pemasukan & pengeluaran dengan AI + WhatsApp</p>
        </div>
        <div class="flex items-center gap-2">
            <button wire:click="$set('showWaModal', true)" class="btn-ghost">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                WA Gateway
            </button>
            <button wire:click="$set('showAiModal', true)" class="btn-ghost">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5a3 3 0 10-3 3.85A5 5 0 1112 5"/><path d="M15 12a1 1 0 01-1 1h-2a1 1 0 00-1 1v3a1 1 0 001 1h2a1 1 0 001-1v-3a1 1 0 011-1"/><path d="M9 12a1 1 0 011-1h2a1 1 0 011 1v3a1 1 0 01-1 1h-2a1 1 0 01-1-1v-3a1 1 0 00-1-1"/></svg>
                Tanya AI
            </button>
            <button wire:click="openCreateTransaction('expense')" class="btn-primary">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Tambah
            </button>
        </div>
    </div>

    {{-- Status Message --}}
    @if($statusMessage)
        <div class="px-4 py-3 rounded-lg text-sm font-medium flex items-center justify-between
            {{ $statusType === 'success' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200' }}">
            <span>{{ $statusMessage }}</span>
            <button wire:click="clearStatusMessage" class="p-1 rounded hover:bg-white/50">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
    @endif

    {{-- First Time Welcome --}}
    @if($isFirstTime)
        <div class="bg-gradient-to-r from-indigo-50 to-violet-50 border border-indigo-200 rounded-xl p-6">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-xl bg-indigo-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-indigo-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5a3 3 0 10-3 3.85A5 5 0 1112 5"/><path d="M15 12a1 1 0 01-1 1h-2a1 1 0 00-1 1v3a1 1 0 001 1h2a1 1 0 001-1v-3a1 1 0 011-1"/><path d="M9 12a1 1 0 011-1h2a1 1 0 011 1v3a1 1 0 01-1 1h-2a1 1 0 01-1-1v-3a1 1 0 00-1-1"/></svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-indigo-900">Selamat Datang di Laporan Keuangan! 🎉</h3>
                    <p class="text-sm text-indigo-700 mt-1">
                        Anda bisa mencatat transaksi dengan 3 cara:
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-4">
                        <div class="bg-white rounded-lg p-3 border border-indigo-100">
                            <div class="text-lg mb-1">💻</div>
                            <div class="text-sm font-medium text-indigo-900">Manual</div>
                            <div class="text-xs text-indigo-600 mt-0.5">Input langsung dari dashboard ini</div>
                        </div>
                        <div class="bg-white rounded-lg p-3 border border-indigo-100">
                            <div class="text-lg mb-1">🤖</div>
                            <div class="text-sm font-medium text-indigo-900">AI Chat</div>
                            <div class="text-xs text-indigo-600 mt-0.5">Ketik "makan 30000", AI otomatis catat</div>
                        </div>
                        <div class="bg-white rounded-lg p-3 border border-indigo-100">
                            <div class="text-lg mb-1">📱</div>
                            <div class="text-sm font-medium text-indigo-900">WhatsApp</div>
                            <div class="text-xs text-indigo-600 mt-0.5">Chat via WA, bot AI catat otomatis</div>
                        </div>
                    </div>
                    <button wire:click="$set('showWaModal', true)" class="mt-4 inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 transition">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        Hubungkan WhatsApp Sekarang
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Period Filter --}}
    <div class="flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-1 bg-[var(--color-surface)] border border-[var(--color-border)] rounded-lg p-1">
            @php $periods = ['today' => 'Hari Ini', 'yesterday' => 'Kemarin', 'this_week' => 'Minggu Ini', 'this_month' => 'Bulan Ini', 'last_month' => 'Bulan Lalu', 'this_year' => 'Tahun Ini', 'custom' => 'Custom'] @endphp
            @foreach($periods as $key => $label)
                <button wire:click="$set('period', '{{ $key }}')"
                    class="px-3 py-1.5 text-xs font-medium rounded-md transition {{ $period === $key ? 'bg-[var(--indigo-600)] text-white' : 'text-[var(--text-tertiary)] hover:text-[var(--text-primary)] hover:bg-[var(--color-bg)]' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
        @if($period === 'custom')
            <input type="date" wire:model.live="dateFrom" class="wp-form-input !py-1.5 text-sm w-36">
            <span class="text-[var(--text-tertiary)]">-</span>
            <input type="date" wire:model.live="dateTo" class="wp-form-input !py-1.5 text-sm w-36">
        @endif
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="stat-card" style="--accent-start: #10b981; --accent-end: #34d399">
            <div class="stat-header">
                <span class="stat-label">Pemasukan</span>
                <div class="stat-icon" style="background: #10b98115; color: #10b981;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                </div>
            </div>
            <div class="stat-value text-emerald-600">{{ App\Livewire\FinancialReport::formatRupiah($stats['total_income']) }}</div>
            <div class="stat-trend positive">
                <span>{{ $stats['count'] }} transaksi</span>
            </div>
        </div>

        <div class="stat-card" style="--accent-start: #ef4444; --accent-end: #f87171">
            <div class="stat-header">
                <span class="stat-label">Pengeluaran</span>
                <div class="stat-icon" style="background: #ef444415; color: #ef4444;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                </div>
            </div>
            <div class="stat-value text-red-600">{{ App\Livewire\FinancialReport::formatRupiah($stats['total_expense']) }}</div>
            <div class="stat-trend" style="color: #ef4444;">
                <span>{{ $stats['count'] }} transaksi</span>
            </div>
        </div>

        <div class="stat-card" style="--accent-start: #6366f1; --accent-end: #8b5cf6">
            <div class="stat-header">
                <span class="stat-label">Saldo</span>
                <div class="stat-icon" style="background: #6366f115; color: #6366f1;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
            </div>
            <div class="stat-value {{ $stats['balance'] >= 0 ? 'text-indigo-600' : 'text-red-600' }}">
                {{ App\Livewire\FinancialReport::formatRupiah($stats['balance']) }}
            </div>
            <div class="stat-trend {{ $stats['balance'] >= 0 ? 'positive' : '' }}" style="{{ $stats['balance'] < 0 ? 'color: #ef4444;' : '' }}">
                <span>{{ $stats['balance'] >= 0 ? 'Sehat 👍' : 'Defisit ⚠️' }}</span>
            </div>
        </div>

        <div class="stat-card" style="--accent-start: #f59e0b; --accent-end: #fbbf24">
            <div class="stat-header">
                <span class="stat-label">Tahunan (Pemasukan)</span>
                <div class="stat-icon" style="background: #f59e0b15; color: #f59e0b;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </div>
            </div>
            <div class="stat-value text-amber-600">{{ App\Livewire\FinancialReport::formatRupiah($this->incomeTotal) }}</div>
            <div class="stat-trend">
                <span>Pengeluaran: {{ App\Livewire\FinancialReport::formatRupiah($this->expenseTotal) }}</span>
            </div>
        </div>
    </div>

    {{-- Main Grid: Chart + Categories --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        {{-- Monthly Chart --}}
        <div class="lg:col-span-2 bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-5">
            <h3 class="text-sm font-semibold text-[var(--text-primary)] mb-4">Grafik Bulanan</h3>
            <div class="relative" style="height: 200px;">
                @php
                    $maxVal = max(max(array_column($monthlyStats, 'income')), max(array_column($monthlyStats, 'expense')));
                    $maxVal = $maxVal > 0 ? $maxVal : 1;
                @endphp
                <div class="flex items-end justify-between gap-2 h-full">
                    @foreach($monthlyStats as $month)
                        <div class="flex-1 flex flex-col items-center gap-1 h-full justify-end">
                            <div class="w-full flex flex-col items-center gap-0.5 relative" style="height: 160px;">
                                <div class="w-full bg-emerald-500 rounded-t transition-all duration-500"
                                    style="height: {{ ($month['income'] / $maxVal) * 140 }}px; min-height: {{ $month['income'] > 0 ? '4px' : '0' }};"
                                    title="Pemasukan: {{ number_format($month['income'], 0, ',', '.') }}">
                                </div>
                                <div class="w-full bg-red-500 rounded-t transition-all duration-500"
                                    style="height: {{ ($month['expense'] / $maxVal) * 140 }}px; min-height: {{ $month['expense'] > 0 ? '4px' : '0' }};"
                                    title="Pengeluaran: {{ number_format($month['expense'], 0, ',', '.') }}">
                                </div>
                            </div>
                            <span class="text-[10px] text-[var(--text-quaternary)] font-medium">{{ $month['month'] }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="flex items-center gap-4 mt-4 text-xs text-[var(--text-tertiary)]">
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-emerald-500"></span> Pemasukan</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-red-500"></span> Pengeluaran</span>
                </div>
            </div>
        </div>

        {{-- Category Breakdown --}}
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-[var(--text-primary)]">Kategori Pengeluaran</h3>
                <button wire:click="openCreateCategory('expense')" class="text-xs text-[var(--indigo-600)] hover:text-[var(--indigo-700)] font-medium">+ Baru</button>
            </div>
            <div class="space-y-2">
                @forelse($categoryStats['expense'] as $cat)
                    @php
                        $totalExpense = $stats['total_expense'] > 0 ? $stats['total_expense'] : 1;
                        $pct = round(($cat->total / $totalExpense) * 100);
                    @endphp
                    <div class="flex items-center gap-3">
                        <span class="text-lg">{{ $cat->category?->icon ?? '💸' }}</span>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-medium text-[var(--text-secondary)] truncate">{{ $cat->category?->name ?? 'Tanpa Kategori' }}</span>
                                <span class="text-[var(--text-tertiary)]">{{ $pct }}%</span>
                            </div>
                            <div class="mt-1 h-1.5 bg-[var(--color-bg)] rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all" style="width: {{ $pct }}%; background: {{ $cat->category?->color ?? '#ef4444' }}"></div>
                            </div>
                            <div class="text-[10px] text-[var(--text-quaternary)] mt-0.5">{{ number_format($cat->total, 0, ',', '.') }}</div>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-[var(--text-quaternary)] text-center py-4">Belum ada pengeluaran</p>
                @endforelse
            </div>

            @if($categoryStats['income']->isNotEmpty())
                <hr class="my-4 border-[var(--color-border)]">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-[var(--text-primary)]">Kategori Pemasukan</h3>
                    <button wire:click="openCreateCategory('income')" class="text-xs text-[var(--indigo-600)] hover:text-[var(--indigo-700)] font-medium">+ Baru</button>
                </div>
                <div class="space-y-2">
                    @foreach($categoryStats['income'] as $cat)
                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-2 text-xs">
                                <span>{{ $cat->category?->icon ?? '💵' }}</span>
                                <span class="text-[var(--text-secondary)]">{{ $cat->category?->name ?? 'Tanpa Kategori' }}</span>
                            </span>
                            <span class="text-xs font-medium text-emerald-600">{{ number_format($cat->total, 0, ',', '.') }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Transaction List with Filters --}}
    <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-[var(--color-border)]">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 class="text-sm font-semibold text-[var(--text-primary)]">Daftar Transaksi</h3>
                <div class="flex items-center gap-2 flex-wrap">
                    <div class="relative">
                        <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-[var(--text-quaternary)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text" wire:model.live="search" class="wp-form-input !py-1.5 !pl-8 text-xs w-40" placeholder="Cari transaksi...">
                    </div>
                    <select wire:model.live="filterType" class="wp-form-input !py-1.5 text-xs w-28">
                        <option value="all">Semua</option>
                        <option value="income">Pemasukan</option>
                        <option value="expense">Pengeluaran</option>
                    </select>
                    <select wire:model.live="filterCategory" class="wp-form-input !py-1.5 text-xs w-36">
                        <option value="">Semua Kategori</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->icon }} {{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            @if($transactions->isEmpty())
                <div class="empty-state !py-12">
                    <div class="empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div class="empty-title">Belum ada transaksi</div>
                    <div class="empty-desc">Mulai catat pemasukan dan pengeluaran Anda.</div>
                    <button wire:click="openCreateTransaction('expense')" class="empty-action btn-primary">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Tambah Transaksi
                    </button>
                </div>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-[var(--color-border)] text-left text-[var(--text-tertiary)]">
                            <th class="px-4 py-3 font-medium">Tanggal</th>
                            <th class="px-4 py-3 font-medium">Deskripsi</th>
                            <th class="px-4 py-3 font-medium">Kategori</th>
                            <th class="px-4 py-3 font-medium text-right">Jumlah</th>
                            <th class="px-4 py-3 font-medium text-center">Sumber</th>
                            <th class="px-4 py-3 font-medium text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $tx)
                            <tr class="border-b border-[var(--color-border)] last:border-0 hover:bg-[var(--color-bg)] transition">
                                <td class="px-4 py-3 text-[var(--text-secondary)] whitespace-nowrap">
                                    {{ $tx->date->format('d/m/Y') }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-[var(--text-primary)]">{{ $tx->description }}</div>
                                    @if($tx->notes)
                                        <div class="text-[10px] text-[var(--text-quaternary)] mt-0.5">{{ $tx->notes }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($tx->category)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium"
                                            style="background: {{ $tx->category->color }}15; color: {{ $tx->category->color }}">
                                            {{ $tx->category->icon }} {{ $tx->category->name }}
                                        </span>
                                    @else
                                        <span class="text-[var(--text-quaternary)] text-xs">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right font-medium {{ $tx->type === 'income' ? 'text-emerald-600' : 'text-red-600' }}">
                                    {{ $tx->type === 'income' ? '+' : '-' }} {{ number_format($tx->amount, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($tx->source === 'wa_auto')
                                        <span class="inline-flex items-center gap-1 text-xs text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">
                                            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                            WA
                                        </span>
                                    @else
                                        <span class="text-xs text-[var(--text-quaternary)]">Manual</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <button wire:click="openEditTransaction({{ $tx->id }})" class="p-1 rounded hover:bg-[var(--color-bg)] transition text-[var(--text-tertiary)] hover:text-[var(--text-primary)]" title="Edit">
                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        </button>
                                        <button wire:click="deleteTransaction({{ $tx->id }})" wire:confirm="Hapus transaksi ini?" class="p-1 rounded hover:bg-red-50 transition text-[var(--text-tertiary)] hover:text-red-600" title="Hapus">
                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if($transactions->hasPages())
                    <div class="px-4 py-3 border-t border-[var(--color-border)]">
                        {{ $transactions->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════ MODALS ═══════════════════════════════════════════════════ --}}

    {{-- Transaction Modal --}}
    @if($showTransactionModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" wire:click="closeTransactionModal"></div>
            <div class="relative bg-[var(--color-surface)] rounded-xl shadow-xl w-full max-w-lg border border-[var(--color-border)] max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between px-6 py-4 border-b border-[var(--color-border)]">
                    <h3 class="text-lg font-semibold text-[var(--text-primary)]">{{ $editingTransactionId ? 'Edit' : 'Tambah' }} Transaksi</h3>
                    <button wire:click="closeTransactionModal" class="p-1 rounded hover:bg-[var(--color-bg)] transition text-[var(--text-quaternary)]">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <form wire:submit="saveTransaction" class="p-6 space-y-4">
                    <div class="flex gap-2 p-1 bg-[var(--color-bg)] rounded-lg">
                        <button type="button" wire:click="$set('formType', 'expense')"
                            class="flex-1 py-2 text-sm font-medium rounded-md transition {{ $formType === 'expense' ? 'bg-white shadow text-red-600' : 'text-[var(--text-tertiary)] hover:text-[var(--text-primary)]' }}">
                            💸 Pengeluaran
                        </button>
                        <button type="button" wire:click="$set('formType', 'income')"
                            class="flex-1 py-2 text-sm font-medium rounded-md transition {{ $formType === 'income' ? 'bg-white shadow text-emerald-600' : 'text-[var(--text-tertiary)] hover:text-[var(--text-primary)]' }}">
                            💵 Pemasukan
                        </button>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="wp-form-label">Jumlah *</label>
                            <input type="number" step="0.01" min="0" wire:model="formAmount" class="wp-form-input" placeholder="0" required>
                            @error('formAmount') <span class="text-xs text-red-600 mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="wp-form-label">Tanggal *</label>
                            <input type="date" wire:model="formDate" class="wp-form-input" required>
                        </div>
                    </div>

                    <div>
                        <label class="wp-form-label">Deskripsi *</label>
                        <input type="text" wire:model="formDescription" class="wp-form-input" placeholder="Mis: Makan siang, Gaji bulanan..." required>
                        @error('formDescription') <span class="text-xs text-red-600 mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="wp-form-label">Kategori</label>
                        <select wire:model="formCategoryId" class="wp-form-input">
                            <option value="">-- Pilih Kategori --</option>
                            @foreach($categories->where('type', $formType) as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->icon }} {{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="wp-form-label">Metode Pembayaran</label>
                            <select wire:model="formPaymentMethod" class="wp-form-input">
                                <option value="">-- Pilih --</option>
                                <option value="tunai">Tunai</option>
                                <option value="transfer">Transfer Bank</option>
                                <option value="qris">QRIS</option>
                                <option value="kartu_kredit">Kartu Kredit</option>
                                <option value="e_wallet">E-Wallet</option>
                                <option value="lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div>
                            <label class="wp-form-label">Catatan</label>
                            <input type="text" wire:model="formNotes" class="wp-form-input" placeholder="Catatan opsional">
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" wire:click="closeTransactionModal" class="btn-secondary">Batal</button>
                        <button type="submit" class="btn-primary">{{ $editingTransactionId ? 'Simpan' : 'Tambah' }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Category Modal --}}
    @if($showCategoryModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" wire:click="closeCategoryModal"></div>
            <div class="relative bg-[var(--color-surface)] rounded-xl shadow-xl w-full max-w-md border border-[var(--color-border)]">
                <div class="flex items-center justify-between px-6 py-4 border-b border-[var(--color-border)]">
                    <h3 class="text-lg font-semibold text-[var(--text-primary)]">{{ $editingCategoryId ? 'Edit' : 'Tambah' }} Kategori</h3>
                    <button wire:click="closeCategoryModal" class="p-1 rounded hover:bg-[var(--color-bg)] transition text-[var(--text-quaternary)]">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <form wire:submit="saveCategory" class="p-6 space-y-4">
                    <div class="flex gap-2 p-1 bg-[var(--color-bg)] rounded-lg">
                        <button type="button" wire:click="$set('catFormType', 'expense')"
                            class="flex-1 py-2 text-sm font-medium rounded-md transition {{ $catFormType === 'expense' ? 'bg-white shadow text-red-600' : 'text-[var(--text-tertiary)]' }}">
                            💸 Pengeluaran
                        </button>
                        <button type="button" wire:click="$set('catFormType', 'income')"
                            class="flex-1 py-2 text-sm font-medium rounded-md transition {{ $catFormType === 'income' ? 'bg-white shadow text-emerald-600' : 'text-[var(--text-tertiary)]' }}">
                            💵 Pemasukan
                        </button>
                    </div>

                    <div>
                        <label class="wp-form-label">Nama Kategori *</label>
                        <input type="text" wire:model="catFormName" class="wp-form-input" placeholder="Nama kategori" required>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="wp-form-label">Icon (Emoji)</label>
                            <input type="text" wire:model="catFormIcon" class="wp-form-input" placeholder="💳" maxlength="5">
                        </div>
                        <div>
                            <label class="wp-form-label">Warna</label>
                            <input type="color" wire:model="catFormColor" class="wp-form-input h-10">
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" wire:click="closeCategoryModal" class="btn-secondary">Batal</button>
                        <button type="submit" class="btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- WA Gateway Modal (Cloud API) --}}
    @if($showWaModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" wire:click="$set('showWaModal', false)"></div>
            <div class="relative bg-[var(--color-surface)] rounded-xl shadow-xl w-full max-w-lg border border-[var(--color-border)] max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between px-6 py-4 border-b border-[var(--color-border)]">
                    <h3 class="text-lg font-semibold text-[var(--text-primary)]">
                        <span class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-500" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            WhatsApp Cloud API
                        </span>
                    </h3>
                    <button wire:click="$set('showWaModal', false)" class="p-1 rounded hover:bg-[var(--color-bg)] transition text-[var(--text-quaternary)]">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    {{-- Status --}}
                    <div class="flex items-center gap-3 p-3 rounded-lg {{ $waConnected ? 'bg-emerald-50 border border-emerald-200' : 'bg-amber-50 border border-amber-200' }}">
                        <div class="w-8 h-8 rounded-lg {{ $waConnected ? 'bg-emerald-100 text-emerald-600' : 'bg-amber-100 text-amber-600' }} flex items-center justify-center">
                            @if($waConnected)
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                            @else
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            @endif
                        </div>
                        <div>
                            <div class="text-sm font-medium {{ $waConnected ? 'text-emerald-700' : 'text-amber-700' }}">
                                {{ $waConnected ? 'Cloud API Terhubung' : 'Belum Terhubung' }}
                            </div>
                            @if($waStatus)
                                <div class="text-xs {{ $waConnected ? 'text-emerald-600' : 'text-amber-600' }} mt-0.5">{{ $waStatus }}</div>
                            @endif
                        </div>
                    </div>

                    {{-- Access Token --}}
                    <div>
                        <label class="wp-form-label">Access Token</label>
                        <input type="password" wire:model="waAccessToken" class="wp-form-input font-mono text-xs" placeholder="EAAG...">
                        <p class="text-xs text-[var(--text-quaternary)] mt-1">Permanent token dari Meta Business > System Users</p>
                    </div>

                    {{-- Phone Number ID --}}
                    <div>
                        <label class="wp-form-label">Phone Number ID</label>
                        <input type="text" wire:model="waPhoneNumberId" class="wp-form-input font-mono text-xs" placeholder="1216895661504974">
                        <p class="text-xs text-[var(--text-quaternary)] mt-1">ID dari WhatsApp > Getting Started > Phone Numbers</p>
                    </div>

                    {{-- Webhook URL for Meta --}}
                    <div class="bg-[var(--color-bg)] rounded-lg p-3 border border-[var(--color-border)]">
                        <label class="wp-form-label">Callback URL (untuk Meta Webhook)</label>
                        <div class="flex items-center gap-2 mt-1">
                            <input type="text" value="{{ url('/api/webhook/wa-finance') }}" class="wp-form-input text-xs font-mono flex-1" readonly onclick="this.select()">
                            <button onclick="navigator.clipboard.writeText('{{ url('/api/webhook/wa-finance') }}'); this.textContent='Copied!'; setTimeout(()=>this.textContent='Copy',1500)"
                                class="px-2 py-1 text-xs font-medium rounded border border-[var(--color-border)] hover:bg-[var(--color-bg)] transition whitespace-nowrap">Copy</button>
                        </div>
                        <p class="text-xs text-[var(--text-quaternary)] mt-1">Paste di Meta for Developers > WhatsApp > Configuration > Webhook</p>
                    </div>

                    {{-- Verify Token --}}
                    <div class="bg-[var(--color-bg)] rounded-lg p-3 border border-[var(--color-border)]">
                        <label class="wp-form-label">Verify Token</label>
                        <div class="flex items-center gap-2 mt-1">
                            <input type="text" value="knowledge-hub-webhook" class="wp-form-input text-xs font-mono flex-1" readonly onclick="this.select()">
                            <button onclick="navigator.clipboard.writeText('knowledge-hub-webhook'); this.textContent='Copied!'; setTimeout(()=>this.textContent='Copy',1500)"
                                class="px-2 py-1 text-xs font-medium rounded border border-[var(--color-border)] hover:bg-[var(--color-bg)] transition whitespace-nowrap">Copy</button>
                        </div>
                        <p class="text-xs text-[var(--text-quaternary)] mt-1">Masukkan di Meta Webhook > Verify Token</p>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button wire:click="saveWaSettings" class="btn-primary flex-1">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                            Simpan & Cek Koneksi
                        </button>
                        <button wire:click="$set('showWaModal', false)" class="btn-ghost">Tutup</button>
                    </div>

                    {{-- Setup Guide --}}
                    <div class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-lg p-4 border border-indigo-200">
                        <h4 class="text-xs font-semibold text-indigo-800 mb-2">🚀 Cara Setup WhatsApp Cloud API</h4>
                        <div class="space-y-2 text-xs text-indigo-700">
                            <div class="flex items-start gap-2">
                                <span class="flex-shrink-0 w-5 h-5 rounded-full bg-indigo-200 flex items-center justify-center text-[10px] font-bold text-indigo-700">1</span>
                                <span>Buka <strong>developers.facebook.com</strong> > My Apps > Create App (Business)</span>
                            </div>
                            <div class="flex items-start gap-2">
                                <span class="flex-shrink-0 w-5 h-5 rounded-full bg-indigo-200 flex items-center justify-center text-[10px] font-bold text-indigo-700">2</span>
                                <span>Tambah product <strong>WhatsApp</strong> > Setup</span>
                            </div>
                            <div class="flex items-start gap-2">
                                <span class="flex-shrink-0 w-5 h-5 rounded-full bg-indigo-200 flex items-center justify-center text-[10px] font-bold text-indigo-700">3</span>
                                <span>Generate <strong>Permanent Token</strong> di Business > System Users</span>
                            </div>
                            <div class="flex items-start gap-2">
                                <span class="flex-shrink-0 w-5 h-5 rounded-full bg-indigo-200 flex items-center justify-center text-[10px] font-bold text-indigo-700">4</span>
                                <span>Copy <strong>Phone Number ID</strong> dari WhatsApp > Getting Started</span>
                            </div>
                            <div class="flex items-start gap-2">
                                <span class="flex-shrink-0 w-5 h-5 rounded-full bg-indigo-200 flex items-center justify-center text-[10px] font-bold text-indigo-700">5</span>
                                <span>Isi data di atas, lalu <strong>Complete Business Verification</strong> untuk kirim pesan ke nomor Indonesia</span>
                            </div>
                        </div>
                    </div>

                    {{-- Command Guide --}}
                    <div class="bg-[var(--color-bg)] rounded-lg p-4 border border-[var(--color-border)]">
                        <h4 class="text-xs font-semibold text-[var(--text-primary)] mb-2">💬 Contoh Chat WhatsApp</h4>
                        <div class="space-y-1.5 text-xs text-[var(--text-secondary)]">
                            <p><code class="px-1 py-0.5 bg-[var(--color-surface)] rounded text-[var(--text-primary)]">makan 30000</code> → Catat pengeluaran</p>
                            <p><code class="px-1 py-0.5 bg-[var(--color-surface)] rounded text-[var(--text-primary)]">gaji 5jt</code> → Catat pemasukan</p>
                            <p><code class="px-1 py-0.5 bg-[var(--color-surface)] rounded text-[var(--text-primary)]">laporan</code> → Ringkasan bulan ini</p>
                            <p><code class="px-1 py-0.5 bg-[var(--color-surface)] rounded text-[var(--text-primary)]">tanya total belanja bulan ini</code> → AI menjawab</p>
                            <p><code class="px-1 py-0.5 bg-[var(--color-surface)] rounded text-[var(--text-primary)]">beli domain 200rb</code> → AI auto-kategorikan</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- AI Query Modal --}}
    @if($showAiModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" wire:click="closeAiModal"></div>
            <div class="relative bg-[var(--color-surface)] rounded-xl shadow-xl w-full max-w-lg border border-[var(--color-border)]">
                <div class="flex items-center justify-between px-6 py-4 border-b border-[var(--color-border)]">
                    <h3 class="text-lg font-semibold text-[var(--text-primary)]">
                        <span class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-[var(--indigo-600)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5a3 3 0 10-3 3.85A5 5 0 1112 5"/><path d="M15 12a1 1 0 01-1 1h-2a1 1 0 00-1 1v3a1 1 0 001 1h2a1 1 0 001-1v-3a1 1 0 011-1"/><path d="M9 12a1 1 0 011-1h2a1 1 0 011 1v3a1 1 0 01-1 1h-2a1 1 0 01-1-1v-3a1 1 0 00-1-1"/></svg>
                            Tanya AI Keuangan
                        </span>
                    </h3>
                    <button wire:click="closeAiModal" class="p-1 rounded hover:bg-[var(--color-bg)] transition text-[var(--text-quaternary)]">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="wp-form-label">Pertanyaan Anda</label>
                        <textarea wire:model="aiQuery" rows="2" class="wp-form-input" placeholder="Contoh: Total pengeluaran bulan ini berapa?&#10;Kategori apa yang paling banyak?&#10;Berapa rata-rata pengeluaran per hari?"></textarea>
                    </div>
                    <button wire:click="askAi" wire:loading.attr="disabled" class="btn-primary w-full justify-center">
                        <span wire:loading.remove wire:target="askAi">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            Tanya AI
                        </span>
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

    {{-- Webhook Debug --}}
    @if($webhookLogs->isNotEmpty())
    <div class="mt-4 bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
        <div class="flex items-center justify-between px-5 py-3 border-b border-[var(--color-border)]">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-[var(--amber-600)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                <span class="text-sm font-semibold text-[var(--text-primary)]">Webhook Debug ({{ $webhookLogs->count() }} log)</span>
            </div>
            <button wire:click="clearWebhookLogs" class="text-xs px-2 py-1 rounded border border-[var(--color-border)] hover:bg-[var(--color-bg)] transition text-[var(--text-tertiary)]">Clear</button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="border-b border-[var(--color-border)] text-left text-[var(--text-tertiary)]">
                        <th class="px-4 py-2 font-medium">Time</th>
                        <th class="px-4 py-2 font-medium">From</th>
                        <th class="px-4 py-2 font-medium">Message</th>
                        <th class="px-4 py-2 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($webhookLogs as $log)
                    <tr class="border-b border-[var(--color-border)] last:border-0 hover:bg-[var(--color-bg)]">
                        <td class="px-4 py-2 text-[var(--text-tertiary)] whitespace-nowrap">{{ $log->created_at->format('H:i:s d/m') }}</td>
                        <td class="px-4 py-2">
                            <span class="font-mono text-[var(--text-primary)]">{{ $log->sender ?: '-' }}</span>
                        </td>
                        <td class="px-4 py-2 max-w-[200px] truncate text-[var(--text-primary)]">{{ $log->message ?: '-' }}</td>
                        <td class="px-4 py-2">
                            @if($log->response_status === 200)
                                <span class="px-1.5 py-0.5 rounded-full bg-[var(--emerald-50)] text-[var(--emerald-700)] text-[10px] font-medium">OK</span>
                            @elseif($log->method === 'GET')
                                <span class="px-1.5 py-0.5 rounded-full bg-[var(--blue-50)] text-[var(--blue-700)] text-[10px] font-medium">Verify</span>
                            @else
                                <span class="px-1.5 py-0.5 rounded-full bg-[var(--red-50)] text-[var(--red-700)] text-[10px] font-medium">Error</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
