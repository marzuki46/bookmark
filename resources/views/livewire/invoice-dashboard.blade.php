<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-[var(--text-primary)]">Dashboard</h1>
            <p class="text-sm text-[var(--text-tertiary)] mt-1">Laporan keuangan & proyek</p>
        </div>
        <form wire:submit="render" class="flex items-center gap-2">
            <input type="date" wire:model.live="startDate" class="wp-form-input !py-1.5 text-sm">
            <span class="text-[var(--text-tertiary)]">-</span>
            <input type="date" wire:model.live="endDate" class="wp-form-input !py-1.5 text-sm">
        </form>
    </div>

    {{-- Invoice Stat Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-[var(--indigo-50)] flex items-center justify-center">
                    <svg class="w-5 h-5 text-[var(--indigo-600)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                </div>
                <div>
                    <p class="text-xs text-[var(--text-tertiary)]">Total Tagihan</p>
                    <p class="text-lg font-bold text-[var(--text-primary)]">Rp {{ number_format($this->stats['total_invoiced'], 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-[var(--emerald-50)] flex items-center justify-center">
                    <svg class="w-5 h-5 text-[var(--emerald-600)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                </div>
                <div>
                    <p class="text-xs text-[var(--text-tertiary)]">Total Uang Masuk</p>
                    <p class="text-lg font-bold text-[var(--text-primary)]">Rp {{ number_format($this->stats['total_received'], 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        <div wire:click="$set('showUnpaidModal', true)" class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-4 cursor-pointer hover:shadow-md transition-all">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-[var(--amber-50)] flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </div>
                <div>
                    <p class="text-xs text-[var(--text-tertiary)]">Piutang</p>
                    <p class="text-lg font-bold text-[var(--text-primary)]">Rp {{ number_format($this->unpaidTotal, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        <div wire:click="$set('showProjectModal', true)" class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-4 cursor-pointer hover:shadow-md transition-all">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-[var(--red-50)] flex items-center justify-center">
                    <svg class="w-5 h-5 text-[var(--red-600)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                </div>
                <div>
                    <p class="text-xs text-[var(--text-tertiary)]">Project Belum Selesai</p>
                    <p class="text-lg font-bold text-[var(--text-primary)]">{{ $this->unfinished->count() }} proyek</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Bill Stat Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div wire:click="$set('billFilter', 'this_month'); $set('showBillModal', true)" class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-4 cursor-pointer hover:shadow-md transition-all">
            <p class="text-xs text-[var(--text-tertiary)] mb-1">Beban Bulan Ini</p>
            <p class="text-lg font-bold text-[var(--indigo-600)]">Rp {{ number_format($this->billStats['this_month'], 0, ',', '.') }}</p>
        </div>

        <div wire:click="$set('billFilter', 'next_month'); $set('showBillModal', true)" class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-4 cursor-pointer hover:shadow-md transition-all">
            <p class="text-xs text-[var(--text-tertiary)] mb-1">Beban Bulan Depan</p>
            <p class="text-lg font-bold text-[var(--text-secondary)]">Rp {{ number_format($this->billStats['next_month'], 0, ',', '.') }}</p>
        </div>

        <div wire:click="$set('billFilter', 'paid_year'); $set('showBillModal', true)" class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-4 cursor-pointer hover:shadow-md transition-all">
            <p class="text-xs text-[var(--text-tertiary)] mb-1">Sudah Dibayar Th Ini</p>
            <p class="text-lg font-bold text-[var(--emerald-600)]">Rp {{ number_format($this->billStats['paid_year'], 0, ',', '.') }}</p>
        </div>

        <div wire:click="$set('billFilter', 'this_year'); $set('showBillModal', true)" class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-4 cursor-pointer hover:shadow-md transition-all">
            <p class="text-xs text-[var(--text-tertiary)] mb-1">Total Beban Th Ini</p>
            <p class="text-lg font-bold text-[var(--text-primary)]">Rp {{ number_format($this->billStats['this_year'], 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- Main Invoice Table --}}
    <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
        <div class="px-6 py-4 border-b border-[var(--color-border)]">
            <h2 class="text-lg font-semibold text-[var(--text-primary)]">Daftar Invoice</h2>
        </div>

        @if($this->invoices->isEmpty())
            <div class="empty-state">
                <div class="empty-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                </div>
                <div class="empty-title">Belum ada invoice</div>
                <div class="empty-desc">Invoice yang dibuat akan muncul di sini.</div>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-[var(--color-border)] text-left text-[var(--text-tertiary)]">
                            <th class="px-4 py-3 font-medium">Tanggal</th>
                            <th class="px-4 py-3 font-medium">No. Inv</th>
                            <th class="px-4 py-3 font-medium">Klien</th>
                            <th class="px-4 py-3 font-medium text-right">Total</th>
                            <th class="px-4 py-3 font-medium text-right">Sisa</th>
                            <th class="px-4 py-3 font-medium text-center">Status</th>
                            <th class="px-4 py-3 font-medium text-center">Progres</th>
                            <th class="px-4 py-3 font-medium text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($this->invoices as $invoice)
                            <tr class="border-b border-[var(--color-border)] last:border-0 hover:bg-[var(--color-bg)] transition">
                                <td class="px-4 py-3 text-[var(--text-secondary)]">{{ $invoice->date_issue?->format('d/m/Y') ?? '-' }}</td>
                                <td class="px-4 py-3 font-medium text-[var(--text-primary)]">{{ $invoice->inv_number }}</td>
                                <td class="px-4 py-3 text-[var(--text-secondary)]">{{ $invoice->client_name ?? $invoice->company->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-right font-medium text-[var(--text-primary)]">Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right {{ $invoice->remaining > 0 ? 'text-amber-600' : 'text-[var(--emerald-600)]' }}">
                                    Rp {{ number_format($invoice->remaining, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($invoice->status === 'paid')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[var(--emerald-50)] text-[var(--emerald-600)]">LUNAS</span>
                                    @elseif($invoice->status === 'partial')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[var(--amber-50)] text-amber-600">CICILAN</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[var(--red-50)] text-[var(--red-600)]">BELUM</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($invoice->work_status === 'finished')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[var(--emerald-50)] text-[var(--emerald-600)]">SELESAI</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[var(--indigo-50)] text-[var(--indigo-600)]">PROSES</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        @if($invoice->remaining > 0)
                                            <button wire:click="openPayment({{ $invoice->id }})" class="p-1.5 rounded-lg hover:bg-[var(--emerald-50)] transition text-[var(--emerald-600)]" title="Bayar">
                                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                                            </button>
                                        @endif
                                        <button wire:click="toggleWork({{ $invoice->id }})" class="p-1.5 rounded-lg hover:bg-[var(--color-bg)] transition text-[var(--text-tertiary)]" title="{{ $invoice->work_status === 'finished' ? 'Tandai Proses' : 'Tandai Selesai' }}">
                                            @if($invoice->work_status === 'finished')
                                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 102.13-9.36L1 10"/></svg>
                                            @else
                                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                            @endif
                                        </button>
                                        <a href="{{ route('invoices.edit', $invoice->id) }}" class="p-1.5 rounded-lg hover:bg-[var(--color-bg)] transition text-[var(--text-tertiary)]" title="Edit">
                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        </a>
                                        <a href="{{ route('invoices.print', $invoice->id) }}" target="_blank" class="p-1.5 rounded-lg hover:bg-[var(--color-bg)] transition text-[var(--text-tertiary)]" title="Print">
                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                                        </a>
                                        <button wire:click="deleteInvoice({{ $invoice->id }})" wire:confirm="Hapus invoice ini beserta item dan pembayarannya?" class="p-1.5 rounded-lg hover:bg-[var(--red-50)] transition text-[var(--text-tertiary)] hover:text-[var(--red-600)]" title="Hapus">
                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Unpaid Invoices Modal --}}
    @if($showUnpaidModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data>
            <div class="fixed inset-0 bg-black/50" wire:click="$set('showUnpaidModal', false)"></div>
            <div class="relative bg-[var(--color-surface)] rounded-xl shadow-xl w-full max-w-2xl border border-[var(--color-border)] max-h-[80vh] flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-[var(--color-border)]">
                    <h3 class="text-lg font-semibold text-[var(--text-primary)]">Piutang Belum Lunas</h3>
                    <button wire:click="$set('showUnpaidModal', false)" class="p-1 rounded hover:bg-[var(--color-bg)] transition text-[var(--text-quaternary)]">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <div class="overflow-y-auto p-6">
                    @if($this->allUnpaid->isEmpty())
                        <p class="text-center text-[var(--text-tertiary)] py-8">Semua invoice sudah lunas!</p>
                    @else
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-[var(--color-border)] text-left text-[var(--text-tertiary)]">
                                    <th class="pb-2 font-medium">No. Inv</th>
                                    <th class="pb-2 font-medium">Klien</th>
                                    <th class="pb-2 font-medium text-right">Total</th>
                                    <th class="pb-2 font-medium text-right">Dibayar</th>
                                    <th class="pb-2 font-medium text-right">Sisa</th>
                                    <th class="pb-2 font-medium text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->allUnpaid as $inv)
                                    <tr class="border-b border-[var(--color-border)] last:border-0">
                                        <td class="py-2.5 font-medium text-[var(--text-primary)]">{{ $inv['inv_number'] }}</td>
                                        <td class="py-2.5 text-[var(--text-secondary)]">{{ $inv['client_name'] ?? '-' }}</td>
                                        <td class="py-2.5 text-right text-[var(--text-primary)]">Rp {{ number_format($inv['grand_total'], 0, ',', '.') }}</td>
                                        <td class="py-2.5 text-right text-[var(--emerald-600)]">Rp {{ number_format($inv['total_paid'], 0, ',', '.') }}</td>
                                        <td class="py-2.5 text-right font-medium text-amber-600">Rp {{ number_format($inv['remaining'], 0, ',', '.') }}</td>
                                        <td class="py-2.5 text-center">
                                            <button wire:click="$set('showUnpaidModal', false); openPayment({{ $inv['id'] }})" class="text-xs px-2 py-1 rounded-lg bg-[var(--emerald-50)] text-[var(--emerald-600)] hover:bg-[var(--emerald-600)] hover:text-white transition">Bayar</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="mt-4 pt-4 border-t border-[var(--color-border)] flex justify-between">
                            <span class="font-medium text-[var(--text-secondary)]">Total Piutang</span>
                            <span class="font-bold text-amber-600">Rp {{ number_format($this->unpaidTotal, 0, ',', '.') }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Unfinished Projects Modal --}}
    @if($showProjectModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data>
            <div class="fixed inset-0 bg-black/50" wire:click="$set('showProjectModal', false)"></div>
            <div class="relative bg-[var(--color-surface)] rounded-xl shadow-xl w-full max-w-2xl border border-[var(--color-border)] max-h-[80vh] flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-[var(--color-border)]">
                    <h3 class="text-lg font-semibold text-[var(--text-primary)]">Project Belum Selesai</h3>
                    <button wire:click="$set('showProjectModal', false)" class="p-1 rounded hover:bg-[var(--color-bg)] transition text-[var(--text-quaternary)]">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <div class="overflow-y-auto p-6">
                    @if($this->unfinished->isEmpty())
                        <p class="text-center text-[var(--text-tertiary)] py-8">Semua project sudah selesai!</p>
                    @else
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-[var(--color-border)] text-left text-[var(--text-tertiary)]">
                                    <th class="pb-2 font-medium">No. Inv</th>
                                    <th class="pb-2 font-medium">Klien</th>
                                    <th class="pb-2 font-medium">Deadline</th>
                                    <th class="pb-2 font-medium text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->unfinished as $inv)
                                    <tr class="border-b border-[var(--color-border)] last:border-0">
                                        <td class="py-2.5 font-medium text-[var(--text-primary)]">{{ $inv->inv_number }}</td>
                                        <td class="py-2.5 text-[var(--text-secondary)]">{{ $inv->client_name ?? $inv->company->name ?? '-' }}</td>
                                        <td class="py-2.5 {{ $inv->internal_deadline && $inv->internal_deadline->isPast() ? 'text-[var(--red-600)] font-medium' : 'text-[var(--text-secondary)]' }}">
                                            {{ $inv->internal_deadline?->format('d/m/Y') ?? '-' }}
                                        </td>
                                        <td class="py-2.5 text-center">
                                            <button wire:click="toggleWork({{ $inv->id }})" class="text-xs px-2 py-1 rounded-lg bg-[var(--emerald-50)] text-[var(--emerald-600)] hover:bg-[var(--emerald-600)] hover:text-white transition">Tandai Selesai</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Bill Details Modal --}}
    @if($showBillModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data>
            <div class="fixed inset-0 bg-black/50" wire:click="$set('showBillModal', false)"></div>
            <div class="relative bg-[var(--color-surface)] rounded-xl shadow-xl w-full max-w-2xl border border-[var(--color-border)] max-h-[80vh] flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-[var(--color-border)]">
                    <h3 class="text-lg font-semibold text-[var(--text-primary)]">
                        @if($billFilter === 'this_month') Detail Beban Bulan Ini
                        @elseif($billFilter === 'next_month') Detail Beban Bulan Depan
                        @elseif($billFilter === 'paid_year') Sudah Dibayar Tahun Ini
                        @else Total Beban Tahun Ini
                        @endif
                    </h3>
                    <button wire:click="$set('showBillModal', false)" class="p-1 rounded hover:bg-[var(--color-bg)] transition text-[var(--text-quaternary)]">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <div class="overflow-y-auto p-6">
                    @php
                        $bills = match($billFilter) {
                            'this_month' => $this->billDetails['this_month'],
                            'next_month' => $this->billDetails['next_month'],
                            'paid_year' => $this->billDetails['paid_year'],
                            default => $this->billDetails['this_year'],
                        };
                    @endphp
                    @if($bills->isEmpty())
                        <p class="text-center text-[var(--text-tertiary)] py-8">Tidak ada data.</p>
                    @else
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-[var(--color-border)] text-left text-[var(--text-tertiary)]">
                                    <th class="pb-2 font-medium">Deskripsi</th>
                                    <th class="pb-2 font-medium">Jatuh Tempo</th>
                                    <th class="pb-2 font-medium">Status</th>
                                    <th class="pb-2 font-medium text-right">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($bills as $bill)
                                    <tr class="border-b border-[var(--color-border)] last:border-0">
                                        <td class="py-2.5 text-[var(--text-primary)]">{{ $bill->description }}</td>
                                        <td class="py-2.5 text-[var(--text-secondary)]">{{ $bill->due_date->format('d/m/Y') }}</td>
                                        <td class="py-2.5">
                                            @if($bill->status === 'paid')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-[var(--emerald-50)] text-[var(--emerald-600)]">Lunas</span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-[var(--amber-50)] text-amber-600">Belum</span>
                                            @endif
                                        </td>
                                        <td class="py-2.5 text-right font-medium text-[var(--text-primary)]">Rp {{ number_format($bill->amount, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="mt-4 pt-4 border-t border-[var(--color-border)] flex justify-between">
                            <span class="font-medium text-[var(--text-secondary)]">Total</span>
                            <span class="font-bold text-[var(--text-primary)]">Rp {{ number_format($bills->sum('amount'), 0, ',', '.') }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Payment Modal --}}
    @if($showPaymentModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data>
            <div class="fixed inset-0 bg-black/50" wire:click="closePaymentModal"></div>
            <div class="relative bg-[var(--color-surface)] rounded-xl shadow-xl w-full max-w-md border border-[var(--color-border)]">
                <div class="flex items-center justify-between px-6 py-4 border-b border-[var(--color-border)]">
                    <h3 class="text-lg font-semibold text-[var(--text-primary)]">Tambah Pembayaran</h3>
                    <button wire:click="closePaymentModal" class="p-1 rounded hover:bg-[var(--color-bg)] transition text-[var(--text-quaternary)]">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <form wire:submit="addPayment" class="p-6 space-y-4">
                    <input type="hidden" wire:model="paymentInvoiceId">
                    <div class="bg-[var(--color-bg)] rounded-lg p-3 text-sm">
                        <p class="text-[var(--text-tertiary)]">Invoice: <span class="font-medium text-[var(--text-primary)]">{{ \App\Models\Invoice::find($paymentInvoiceId)?->inv_number }}</span></p>
                    </div>
                    <div>
                        <label class="wp-form-label">Jumlah Bayar *</label>
                        <input type="number" step="0.01" min="0" wire:model="paymentAmount" class="wp-form-input" placeholder="0" required>
                        @error('paymentAmount') <span class="text-xs text-[var(--red-600)] mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="wp-form-label">Tanggal Bayar *</label>
                        <input type="date" wire:model="paymentDate" class="wp-form-input" required>
                        @error('paymentDate') <span class="text-xs text-[var(--red-600)] mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="wp-form-label">Catatan</label>
                        <input type="text" wire:model="paymentNote" class="wp-form-input" placeholder="Catatan opsional...">
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" wire:click="closePaymentModal" class="px-4 py-2 text-sm font-medium text-[var(--text-secondary)] rounded-lg border border-[var(--color-border)] hover:bg-[var(--color-bg)] transition">Batal</button>
                        <button type="submit" class="btn-primary">Simpan Pembayaran</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
