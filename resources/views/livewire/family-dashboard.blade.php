<div class="space-y-6">
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold text-[var(--text-primary)]">Keuangan Keluarga</h1>
            <p class="text-sm text-[var(--text-tertiary)] mt-1">
                Pantau jatah tabungan, hutang & goals dengan analisis AI
            </p>
        </div>
        @if($family)
            <div class="flex items-center gap-2">
                <button wire:click="$set('showAiModal', true)" class="btn-ghost">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5a3 3 0 10-3 3.85A5 5 0 1112 5"/><path d="M15 12a1 1 0 01-1 1h-2a1 1 0 00-1 1v3a1 1 0 001 1h2a1 1 0 001-1v-3a1 1 0 011-1"/><path d="M9 12a1 1 0 011-1h2a1 1 0 011 1v3a1 1 0 01-1 1h-2a1 1 0 01-1-1v-3a1 1 0 00-1-1"/></svg>
                    Analisis AI
                </button>
                <a href="{{ route('keluarga.transaksi') }}" class="btn-primary">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Tambah
                </a>
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
        <div class="bg-gradient-to-r from-indigo-50 to-violet-50 border border-indigo-200 rounded-xl p-8 max-w-xl">
            <div class="text-4xl mb-3">👨‍👩‍👧</div>
            <h3 class="text-lg font-semibold text-indigo-900">Buat Keluarga Anda</h3>
            <p class="text-sm text-indigo-700 mt-1">Mulai kelola keuangan keluarga: transaksi, anggaran, tabungan berjatah, dan hutang bersama pasangan.</p>
            <form wire:submit="createFamily" class="mt-4 flex gap-2">
                <input type="text" wire:model="familyName" class="wp-form-input flex-1" placeholder="Nama keluarga (mis: Keluarga Marzuki)">
                <button type="submit" class="btn-primary whitespace-nowrap">Buat Keluarga</button>
            </form>
            @error('familyName') <span class="text-xs text-red-600 mt-1">{{ $message }}</span> @enderror
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="stat-card" style="--accent-start: #10b981; --accent-end: #34d399">
                <div class="stat-header">
                    <span class="stat-label">Pemasukan Bulan Ini</span>
                    <div class="stat-icon" style="background: #10b98115; color: #10b981;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                    </div>
                </div>
                <div class="stat-value text-emerald-600">{{ App\Livewire\FamilyDashboard::formatRupiah($stats['income']) }}</div>
            </div>
            <div class="stat-card" style="--accent-start: #ef4444; --accent-end: #f87171">
                <div class="stat-header">
                    <span class="stat-label">Pengeluaran Bulan Ini</span>
                    <div class="stat-icon" style="background: #ef444415; color: #ef4444;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                    </div>
                </div>
                <div class="stat-value text-red-600">{{ App\Livewire\FamilyDashboard::formatRupiah($stats['expense']) }}</div>
            </div>
            <div class="stat-card" style="--accent-start: #6366f1; --accent-end: #8b5cf6">
                <div class="stat-header">
                    <span class="stat-label">Saldo</span>
                    <div class="stat-icon" style="background: #6366f115; color: #6366f1;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                </div>
                <div class="stat-value {{ $stats['balance'] >= 0 ? 'text-indigo-600' : 'text-red-600' }}">
                    {{ App\Livewire\FamilyDashboard::formatRupiah($stats['balance']) }}
                </div>
            </div>
            <div class="stat-card" style="--accent-start: #f59e0b; --accent-end: #fbbf24">
                <div class="stat-header">
                    <span class="stat-label">Skor Kesehatan</span>
                    <div class="stat-icon" style="background: #f59e0b15; color: #f59e0b;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                    </div>
                </div>
                <div class="stat-value text-amber-600">{{ $healthScore['score'] }}/100</div>
                <div class="stat-trend">
                    <span>{{ $healthScore['grade'] }}</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <div class="lg:col-span-2 bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-[var(--text-primary)]">Tabungan & Goals</h3>
                    <a href="{{ route('keluarga.tabungan') }}" class="text-xs text-[var(--indigo-600)] font-medium">Kelola →</a>
                </div>
                <div class="space-y-4">
                    @forelse($goals as $goal)
                        <div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-medium text-[var(--text-secondary)]">{{ $goal->icon ?? '🎯' }} {{ $goal->name }}</span>
                                <span class="text-xs text-[var(--text-tertiary)]">
                                    {{ App\Livewire\FamilyDashboard::formatRupiah($goal->current_amount) }} / {{ App\Livewire\FamilyDashboard::formatRupiah($goal->target_amount) }}
                                </span>
                            </div>
                            <div class="mt-1 h-2 bg-[var(--color-bg)] rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all" style="width: {{ $goal->progress }}%; background: {{ $goal->color ?? '#6366f1' }}"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-[var(--text-quaternary)] text-center py-4">
                            Belum ada tabungan. <a href="{{ route('keluarga.tabungan') }}" class="text-[var(--indigo-600)]">Buat goal pertama →</a>
                        </p>
                    @endforelse
                </div>
            </div>

            <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-5">
                <h3 class="text-sm font-semibold text-[var(--text-primary)] mb-4">Transaksi Terbaru</h3>
                <div class="space-y-3">
                    @forelse($recentTransactions as $tx)
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <div class="text-sm font-medium text-[var(--text-primary)] truncate">{{ $tx->description }}</div>
                                <div class="text-[10px] text-[var(--text-quaternary)]">{{ $tx->date->format('d/m/Y') }} · {{ $tx->category?->name ?? 'Umum' }}</div>
                            </div>
                            <span class="text-sm font-semibold whitespace-nowrap {{ $tx->type === 'income' ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ $tx->type === 'income' ? '+' : '-' }}{{ number_format($tx->amount, 0, ',', '.') }}
                            </span>
                        </div>
                    @empty
                        <p class="text-xs text-[var(--text-quaternary)] text-center py-4">Belum ada transaksi.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    @if($showAiModal && $family)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" wire:click="closeAiModal"></div>
            <div class="relative bg-[var(--color-surface)] rounded-xl shadow-xl w-full max-w-lg border border-[var(--color-border)]">
                <div class="flex items-center justify-between px-6 py-4 border-b border-[var(--color-border)]">
                    <h3 class="text-lg font-semibold text-[var(--text-primary)]">
                        <span class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-[var(--indigo-600)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5a3 3 0 10-3 3.85A5 5 0 1112 5"/><path d="M15 12a1 1 0 01-1 1h-2a1 1 0 00-1 1v3a1 1 0 001 1h2a1 1 0 001-1v-3a1 1 0 011-1"/><path d="M9 12a1 1 0 011-1h2a1 1 0 011 1v3a1 1 0 01-1 1h-2a1 1 0 01-1-1v-3a1 1 0 00-1-1"/></svg>
                            Analisis AI Keuangan Keluarga
                        </span>
                    </h3>
                    <button wire:click="closeAiModal" class="p-1 rounded hover:bg-[var(--color-bg)] transition text-[var(--text-quaternary)]">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <div class="p-6">
                    <button wire:click="askAi" wire:loading.attr="disabled" class="btn-primary w-full justify-center">
                        <span wire:loading.remove wire:target="askAi">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5a3 3 0 10-3 3.85A5 5 0 1112 5"/><path d="M15 12a1 1 0 01-1 1h-2a1 1 0 00-1 1v3a1 1 0 001 1h2a1 1 0 001-1v-3a1 1 0 011-1"/><path d="M9 12a1 1 0 011-1h2a1 1 0 011 1v3a1 1 0 01-1 1h-2a1 1 0 01-1-1v-3a1 1 0 00-1-1"/></svg>
                            Analisis Sekarang
                        </span>
                        <span wire:loading wire:target="askAi" class="flex items-center gap-2">
                            <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"/><path d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" fill="currentColor" class="opacity-75"/></svg>
                            Menganalisis...
                        </span>
                    </button>
                    @if($aiAnswer)
                        <div class="mt-4 bg-[var(--color-bg)] rounded-lg p-4 border border-[var(--color-border)]">
                            <div class="text-sm text-[var(--text-secondary)] whitespace-pre-wrap leading-relaxed">{!! nl2br(e($aiAnswer)) !!}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
