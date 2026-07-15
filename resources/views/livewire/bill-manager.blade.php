<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-[var(--text-primary)]">Tagihan Operasional</h1>
            <p class="text-sm text-[var(--text-tertiary)] mt-1">Kelola tagihan berulang: server, domain, tools, internet</p>
        </div>
        <button wire:click="{{ $showForm ? 'closeForm' : 'openCreate' }}" class="btn-primary">
            @if($showForm)
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                Tutup Form
            @else
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Tambah Tagihan
            @endif
        </button>
    </div>

    @php
        $bills = $this->bills;
        $totalAmount = $bills->sum('amount');
        $unpaidBills = $bills->where('status', 'unpaid');
        $paidBills = $bills->where('status', 'paid');
        $overdueBills = $unpaidBills->filter(fn($b) => $b->due_date->isPast());
        $totalUnpaid = $unpaidBills->sum('amount');
        $totalPaid = $paidBills->sum('amount');
        $totalOverdue = $overdueBills->sum('amount');
    @endphp

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-[var(--indigo-50)] flex items-center justify-center text-[var(--indigo-600)]">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                </div>
                <div>
                    <div class="text-xs text-[var(--text-tertiary)]">Total Tagihan</div>
                    <div class="text-lg font-bold text-[var(--text-primary)]">{{ $bills->count() }}</div>
                </div>
            </div>
        </div>
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-[var(--red-50)] flex items-center justify-center text-[var(--red-600)]">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </div>
                <div>
                    <div class="text-xs text-[var(--text-tertiary)]">Belum Dibayar</div>
                    <div class="text-lg font-bold text-[var(--red-600)]">Rp {{ number_format($totalUnpaid, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-[var(--emerald-50)] flex items-center justify-center text-[var(--emerald-600)]">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <div>
                    <div class="text-xs text-[var(--text-tertiary)]">Sudah Dibayar</div>
                    <div class="text-lg font-bold text-[var(--emerald-600)]">Rp {{ number_format($totalPaid, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-[var(--amber-50)] flex items-center justify-center text-[var(--amber-600)]">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </div>
                <div>
                    <div class="text-xs text-[var(--text-tertiary)]">Jatuh Tempo</div>
                    <div class="text-lg font-bold text-[var(--amber-600)]">{{ $overdueBills->count() }} <span class="text-xs font-normal">/ Rp {{ number_format($totalOverdue, 0, ',', '.') }}</span></div>
                </div>
            </div>
        </div>
    </div>

    @if($showForm)
    <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden" x-data="{ open: true }" x-init="$nextTick(() => open = true)">
        <div class="px-5 py-3 border-b border-[var(--color-border)] flex items-center justify-between bg-[var(--color-bg)]">
            <h2 class="text-sm font-semibold text-[var(--text-primary)]">
                {{ $editingId ? 'Edit Tagihan' : 'Tambah Tagihan Baru' }}
            </h2>
        </div>
        <form wire:submit="save" class="p-5">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <div>
                    <label class="wp-form-label">Kategori</label>
                    <select wire:model="formCategory" class="wp-form-input">
                        @foreach(self::CATEGORIES as $cat)
                            <option value="{{ $cat }}">{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <label class="wp-form-label">Deskripsi</label>
                    <input type="text" wire:model="formDescription" class="wp-form-input" placeholder="Contoh: Hosting Bulanan" required>
                    @error('formDescription') <span class="text-xs text-[var(--red-600)] mt-1">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="wp-form-label">Nominal (Rp)</label>
                    <input type="number" step="0.01" min="0" wire:model="formAmount" class="wp-form-input" placeholder="0" required>
                    @error('formAmount') <span class="text-xs text-[var(--red-600)] mt-1">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="wp-form-label">Jatuh Tempo</label>
                    <input type="date" wire:model="formDueDate" class="wp-form-input" required>
                    @error('formDueDate') <span class="text-xs text-[var(--red-600)] mt-1">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="flex items-center justify-between mt-4 pt-4 border-t border-[var(--color-border)]">
                <div class="flex items-center gap-2">
                    <label class="wp-form-label !mb-0">Status:</label>
                    <select wire:model="formStatus" class="wp-form-input !w-auto !py-1.5">
                        <option value="unpaid">Belum Dibayar</option>
                        <option value="paid">Lunas</option>
                    </select>
                </div>
                <div class="flex gap-3">
                    <button type="button" wire:click="closeForm" class="px-4 py-2 text-sm font-medium text-[var(--text-secondary)] rounded-lg border border-[var(--color-border)] hover:bg-[var(--color-bg)] transition">Batal</button>
                    <button type="submit" class="btn-primary">{{ $editingId ? 'Update Tagihan' : 'Simpan Tagihan' }}</button>
                </div>
            </div>
        </form>
    </div>
    @endif

    <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
        <div class="px-6 py-4 border-b border-[var(--color-border)] flex items-center justify-between">
            <h2 class="text-lg font-semibold text-[var(--text-primary)]">Daftar Tagihan</h2>
            <span class="text-sm text-[var(--text-tertiary)]">{{ $bills->count() }} total</span>
        </div>

        @if($bills->isEmpty())
            <div class="empty-state">
                <div class="empty-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                </div>
                <div class="empty-title">Belum ada tagihan</div>
                <div class="empty-desc">Tambahkan tagihan operasional pertama Anda.</div>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-[var(--color-border)] text-left text-[var(--text-tertiary)]">
                            <th class="px-5 py-3 font-medium w-32">Jatuh Tempo</th>
                            <th class="px-5 py-3 font-medium w-28">Kategori</th>
                            <th class="px-5 py-3 font-medium">Deskripsi</th>
                            <th class="px-5 py-3 font-medium text-right w-36">Nominal</th>
                            <th class="px-5 py-3 font-medium text-center w-24">Status</th>
                            <th class="px-5 py-3 font-medium text-center w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bills as $bill)
                            @php
                                $isOverdue = $bill->status === 'unpaid' && $bill->due_date->isPast();
                            @endphp
                            <tr class="border-b border-[var(--color-border)] last:border-0 hover:bg-[var(--color-bg)] transition {{ $isOverdue ? 'bg-[var(--red-50)]/30' : '' }}">
                                <td class="px-5 py-3.5 {{ $isOverdue ? 'text-[var(--red-600)] font-semibold' : 'text-[var(--text-secondary)]' }}">
                                    @if($isOverdue)
                                        <span class="inline-flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full bg-[var(--red-500)] animate-pulse"></span>
                                            {{ $bill->due_date->format('d M Y') }}
                                        </span>
                                    @else
                                        {{ $bill->due_date->format('d M Y') }}
                                    @endif
                                </td>
                                <td class="px-5 py-3.5">
                                    @php
                                        $catColors = [
                                            'Server' => ['bg' => 'bg-[var(--indigo-50)]', 'text' => 'text-[var(--indigo-600)]', 'border' => 'border-[var(--indigo-200)]'],
                                            'Domain' => ['bg' => 'bg-purple-50', 'text' => 'text-purple-600', 'border' => 'border-purple-200'],
                                            'Tools' => ['bg' => 'bg-[var(--emerald-50)]', 'text' => 'text-[var(--emerald-600)]', 'border' => 'border-emerald-200'],
                                            'Internet' => ['bg' => 'bg-[var(--amber-50)]', 'text' => 'text-amber-600', 'border' => 'border-amber-200'],
                                            'Lainnya' => ['bg' => 'bg-[var(--color-bg)]', 'text' => 'text-[var(--text-secondary)]', 'border' => 'border-[var(--color-border)]'],
                                        ];
                                        $cc = $catColors[$bill->category] ?? $catColors['Lainnya'];
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $cc['bg'] }} {{ $cc['text'] }} {{ $cc['border'] }}">{{ $bill->category }}</span>
                                </td>
                                <td class="px-5 py-3.5 font-medium text-[var(--text-primary)]">{{ $bill->description }}</td>
                                <td class="px-5 py-3.5 text-right font-bold text-[var(--text-primary)] tabular-nums">Rp {{ number_format($bill->amount, 0, ',', '.') }}</td>
                                <td class="px-5 py-3.5 text-center">
                                    @if($bill->status === 'paid')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-[var(--emerald-50)] text-[var(--emerald-600)] border border-emerald-200">
                                            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                            Lunas
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-[var(--red-50)] text-[var(--red-600)] border border-red-200">
                                            Belum
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center justify-center gap-1">
                                        @if($bill->status === 'unpaid')
                                            <button wire:click="payBill({{ $bill->id }})" wire:confirm="Tandai tagihan ini sebagai lunas?" class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-lg bg-[var(--emerald-50)] text-[var(--emerald-600)] hover:bg-[var(--emerald-100)] border border-emerald-200 transition" title="Tandai Lunas">
                                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                                Bayar
                                            </button>
                                        @endif
                                        <button wire:click="openEdit({{ $bill->id }})" class="p-1.5 rounded-lg hover:bg-[var(--color-bg)] transition text-[var(--text-tertiary)]" title="Edit">
                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        </button>
                                        <button wire:click="deleteBill({{ $bill->id }})" wire:confirm="Hapus tagihan ini?" class="p-1.5 rounded-lg hover:bg-[var(--red-50)] transition text-[var(--text-tertiary)] hover:text-[var(--red-600)]" title="Hapus">
                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-[var(--color-border)] flex items-center justify-between text-sm bg-[var(--color-bg)]">
                <div class="flex items-center gap-4 text-[var(--text-tertiary)]">
                    <span>{{ $bills->count() }} tagihan</span>
                    <span class="w-px h-4 bg-[var(--color-border)]"></span>
                    <span class="text-[var(--emerald-600)]">{{ $paidBills->count() }} lunas</span>
                    <span class="text-[var(--red-600)]">{{ $unpaidBills->count() }} belum</span>
                </div>
                <div class="font-bold text-[var(--text-primary)] tabular-nums">
                    Total: Rp {{ number_format($totalAmount, 0, ',', '.') }}
                </div>
            </div>
        @endif
    </div>
</div>
