<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-[var(--text-primary)]">
            {{ $editingId ? 'Edit Data' : 'Input Invoice Baru' }}
        </h1>
        <p class="text-sm text-[var(--text-tertiary)] mt-1">
            {{ $editingId ? 'Perbarui data invoice #' . $invNumber : 'Buat invoice baru untuk klien' }}
        </p>
    </div>

    <form wire:submit="save" class="space-y-6">

        {{-- Company & Client --}}
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-6">
            <h2 class="text-lg font-semibold text-[var(--text-primary)] mb-4">Informasi Klien & Perusahaan</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="wp-form-label">Perusahaan *</label>
                    <select wire:model.live="companyId" class="wp-form-input" required>
                        <option value="">-- Pilih Perusahaan --</option>
                        @foreach($companies as $company)
                            <option value="{{ $company['id'] }}">{{ $company['name'] }}</option>
                        @endforeach
                    </select>
                    @error('companyId') <span class="text-xs text-[var(--red-600)] mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="wp-form-label">Nama Klien *</label>
                    <input type="text" wire:model="clientName" class="wp-form-input" placeholder="Nama klien" required>
                    @error('clientName') <span class="text-xs text-[var(--red-600)] mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="wp-form-label">Alamat Klien</label>
                    <input type="text" wire:model="clientAddress" class="wp-form-input" placeholder="Alamat klien">
                </div>

                <div>
                    <label class="wp-form-label">Email Klien</label>
                    <input type="email" wire:model="clientEmail" class="wp-form-input" placeholder="email@klien.com">
                    @error('clientEmail') <span class="text-xs text-[var(--red-600)] mt-1">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        {{-- Invoice Details --}}
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-6">
            <h2 class="text-lg font-semibold text-[var(--text-primary)] mb-4">Detail Invoice</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="wp-form-label">Nomor Invoice</label>
                    <input type="text" wire:model="invNumber" class="wp-form-input font-mono" placeholder="INV-A260715-01">
                    @error('invNumber') <span class="text-xs text-[var(--red-600)] mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="wp-form-label">Status Pembayaran</label>
                    <select wire:model="status" class="wp-form-input">
                        <option value="unpaid">Belum Lunas</option>
                        <option value="paid">Lunas</option>
                    </select>
                </div>

                <div>
                    <label class="wp-form-label">Status Pekerjaan</label>
                    <select wire:model="workStatus" class="wp-form-input">
                        <option value="on_progress">Dalam Proses</option>
                        <option value="finished">Selesai</option>
                    </select>
                </div>

                <div>
                    <label class="wp-form-label">Tanggal Issue *</label>
                    <input type="date" wire:model="dateIssue" class="wp-form-input" required>
                    @error('dateIssue') <span class="text-xs text-[var(--red-600)] mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="wp-form-label">Tanggal Jatuh Tempo *</label>
                    <input type="date" wire:model="dateDue" class="wp-form-input" required>
                    @error('dateDue') <span class="text-xs text-[var(--red-600)] mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="wp-form-label">Deadline Internal</label>
                    <input type="date" wire:model="internalDeadline" class="wp-form-input">
                    @error('internalDeadline') <span class="text-xs text-[var(--red-600)] mt-1">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        {{-- Line Items --}}
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-[var(--text-primary)]">Item Invoice</h2>
                <button type="button" wire:click="addRow" class="btn-secondary !text-xs">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Tambah Baris
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-[var(--color-border)] text-left text-[var(--text-tertiary)]">
                            <th class="pb-2 font-medium w-10">#</th>
                            <th class="pb-2 font-medium">Deskripsi</th>
                            <th class="pb-2 font-medium w-24 text-right">Qty</th>
                            <th class="pb-2 font-medium w-36 text-right">Harga</th>
                            <th class="pb-2 font-medium w-36 text-right">Total</th>
                            <th class="pb-2 font-medium w-12"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $index => $item)
                            <tr class="border-b border-[var(--color-border)] last:border-0">
                                <td class="py-2 text-[var(--text-tertiary)]">{{ $index + 1 }}</td>
                                <td class="py-2 pr-2">
                                    <input type="text" wire:model="items.{{ $index }}.description" class="wp-form-input !py-1.5" placeholder="Deskripsi item" required>
                                    @error("items.{$index}.description") <span class="text-xs text-[var(--red-600)]">{{ $message }}</span> @enderror
                                </td>
                                <td class="py-2 px-1">
                                    <input type="number" step="0.01" min="0.01" wire:model="items.{{ $index }}.qty" class="wp-form-input !py-1.5 text-right" required>
                                    @error("items.{$index}.qty") <span class="text-xs text-[var(--red-600)]">{{ $message }}</span> @enderror
                                </td>
                                <td class="py-2 px-1">
                                    <input type="number" step="0.01" min="0" wire:model="items.{{ $index }}.price" class="wp-form-input !py-1.5 text-right" placeholder="0" required>
                                    @error("items.{$index}.price") <span class="text-xs text-[var(--red-600)]">{{ $message }}</span> @enderror
                                </td>
                                <td class="py-2 px-1 text-right font-medium text-[var(--text-primary)]">
                                    Rp {{ number_format($item['qty'] * $item['price'], 0, ',', '.') }}
                                </td>
                                <td class="py-2 pl-1 text-center">
                                    @if(count($items) > 1)
                                        <button type="button" wire:click="removeRow({{ $index }})" class="p-1 rounded hover:bg-[var(--red-50)] transition text-[var(--text-tertiary)] hover:text-[var(--red-600)]" title="Hapus baris">
                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @error('items') <span class="text-xs text-[var(--red-600)] mt-2 block">{{ $message }}</span> @enderror
        </div>

        {{-- Totals --}}
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-6">
            <h2 class="text-lg font-semibold text-[var(--text-primary)] mb-4">Ringkasan</h2>

            <div class="flex flex-col items-end space-y-3">
                <div class="flex items-center gap-4">
                    <label class="wp-form-label !mb-0 w-28 text-right">Pajak (%)</label>
                    <input type="number" step="0.01" min="0" max="100" wire:model="taxRate" class="wp-form-input !w-28 text-right !py-1.5">
                </div>

                @error('taxRate') <span class="text-xs text-[var(--red-600)]">{{ $message }}</span> @enderror

                <div class="w-full max-w-xs space-y-2 pt-3 border-t border-[var(--color-border)]">
                    <div class="flex justify-between text-sm">
                        <span class="text-[var(--text-tertiary)]">Subtotal</span>
                        <span class="font-medium text-[var(--text-primary)]">Rp {{ number_format($this->subtotal, 0, ',', '.') }}</span>
                    </div>

                    @if($taxRate > 0)
                        <div class="flex justify-between text-sm">
                            <span class="text-[var(--text-tertiary)]">Pajak ({{ $taxRate }}%)</span>
                            <span class="font-medium text-[var(--text-primary)]">Rp {{ number_format($this->taxAmount, 0, ',', '.') }}</span>
                        </div>
                    @endif

                    <div class="flex justify-between text-base pt-2 border-t border-[var(--color-border)]">
                        <span class="font-semibold text-[var(--text-primary)]">Grand Total</span>
                        <span class="font-bold text-[var(--indigo-600)]">Rp {{ number_format($this->grandTotal, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('invoices') }}" class="btn-secondary">Batal</a>
            <button type="submit" class="btn-primary">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                {{ $editingId ? 'Simpan Perubahan' : 'Simpan Invoice' }}
            </button>
        </div>
    </form>
</div>
