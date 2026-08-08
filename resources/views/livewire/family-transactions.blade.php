<div class="space-y-6">
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold text-[var(--text-primary)]">Transaksi Keluarga</h1>
            <p class="text-sm text-[var(--text-tertiary)] mt-1">Catat pemasukan & pengeluaran keluarga (suami/istri/bersama)</p>
        </div>
        @if($family)
            <div class="flex items-center gap-2">
                <button wire:click="openCreateCategory('expense')" class="btn-ghost">+ Kategori</button>
                <button wire:click="openCreate('expense')" class="btn-primary">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Tambah
                </button>
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
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-8 text-center">
            <div class="text-3xl mb-2">🏠</div>
            <p class="text-sm text-[var(--text-tertiary)]">Buat keluarga dulu di menu Dashboard Keluarga.</p>
        </div>
    @else
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-[var(--color-border)]">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 class="text-sm font-semibold text-[var(--text-primary)]">Daftar Transaksi</h3>
                    <div class="flex items-center gap-2 flex-wrap">
                        <input type="month" wire:model.live="month" class="wp-form-input !py-1.5 text-xs w-36">
                        <div class="relative">
                            <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-[var(--text-quaternary)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <input type="text" wire:model.live="search" class="wp-form-input !py-1.5 !pl-8 text-xs w-40" placeholder="Cari...">
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
                        <select wire:model.live="filterPayer" class="wp-form-input !py-1.5 text-xs w-28">
                            <option value="all">Semua Payer</option>
                            <option value="husband">Suami</option>
                            <option value="wife">Istri</option>
                            <option value="shared">Bersama</option>
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
                        <button wire:click="openCreate('expense')" class="empty-action btn-primary">Tambah Transaksi</button>
                    </div>
                @else
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-[var(--color-border)] text-left text-[var(--text-tertiary)]">
                                <th class="px-4 py-3 font-medium">Tanggal</th>
                                <th class="px-4 py-3 font-medium">Deskripsi</th>
                                <th class="px-4 py-3 font-medium">Kategori</th>
                                <th class="px-4 py-3 font-medium">Payer</th>
                                <th class="px-4 py-3 font-medium text-right">Jumlah</th>
                                <th class="px-4 py-3 font-medium text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transactions as $tx)
                                <tr class="border-b border-[var(--color-border)] last:border-0 hover:bg-[var(--color-bg)] transition">
                                    <td class="px-4 py-3 text-[var(--text-secondary)] whitespace-nowrap">{{ $tx->date->format('d/m/Y') }}</td>
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
                                    <td class="px-4 py-3 text-xs">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-[var(--color-bg)] text-[var(--text-secondary)]">
                                            {{ $tx->payer === 'husband' ? '👨 Suami' : ($tx->payer === 'wife' ? '👩 Istri' : '🤝 Bersama') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium {{ $tx->type === 'income' ? 'text-emerald-600' : 'text-red-600' }}">
                                        {{ $tx->type === 'income' ? '+' : '-' }} {{ number_format($tx->amount, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            <button wire:click="openEdit({{ $tx->id }})" class="p-1 rounded hover:bg-[var(--color-bg)] transition text-[var(--text-tertiary)] hover:text-[var(--text-primary)]" title="Edit">
                                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                            </button>
                                            <button wire:click="delete({{ $tx->id }})" wire:confirm="Hapus transaksi ini?" class="p-1 rounded hover:bg-red-50 transition text-[var(--text-tertiary)] hover:text-red-600" title="Hapus">
                                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if($transactions->hasPages())
                        <div class="px-4 py-3 border-t border-[var(--color-border)]">{{ $transactions->links() }}</div>
                    @endif
                @endif
            </div>
        </div>
    @endif

    {{-- Transaction Modal --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" wire:click="closeModal"></div>
            <div class="relative bg-[var(--color-surface)] rounded-xl shadow-xl w-full max-w-lg border border-[var(--color-border)] max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between px-6 py-4 border-b border-[var(--color-border)]">
                    <h3 class="text-lg font-semibold text-[var(--text-primary)]">{{ $editingId ? 'Edit' : 'Tambah' }} Transaksi</h3>
                    <button wire:click="closeModal" class="p-1 rounded hover:bg-[var(--color-bg)] transition text-[var(--text-quaternary)]">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <form wire:submit="save" class="p-6 space-y-4">
                    <div class="flex gap-2 p-1 bg-[var(--color-bg)] rounded-lg">
                        <button type="button" wire:click="$set('formType', 'expense')"
                            class="flex-1 py-2 text-sm font-medium rounded-md transition {{ $formType === 'expense' ? 'bg-white shadow text-red-600' : 'text-[var(--text-tertiary)]' }}">💸 Pengeluaran</button>
                        <button type="button" wire:click="$set('formType', 'income')"
                            class="flex-1 py-2 text-sm font-medium rounded-md transition {{ $formType === 'income' ? 'bg-white shadow text-emerald-600' : 'text-[var(--text-tertiary)]' }}">💵 Pemasukan</button>
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
                        <input type="text" wire:model="formDescription" class="wp-form-input" placeholder="Mis: Belanja bulanan, Gaji suami..." required>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="wp-form-label">Kategori</label>
                            <select wire:model="formCategoryId" class="wp-form-input">
                                <option value="">-- Pilih --</option>
                                @foreach($categories->where('type', $formType) as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->icon }} {{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="wp-form-label">Payer</label>
                            <select wire:model="formPayer" class="wp-form-input">
                                <option value="shared">🤝 Bersama</option>
                                <option value="husband">👨 Suami</option>
                                <option value="wife">👩 Istri</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="wp-form-label">Metode</label>
                            <select wire:model="formPaymentMethod" class="wp-form-input">
                                <option value="">-- Pilih --</option>
                                <option value="tunai">Tunai</option>
                                <option value="transfer">Transfer</option>
                                <option value="qris">QRIS</option>
                                <option value="e_wallet">E-Wallet</option>
                                <option value="lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div>
                            <label class="wp-form-label">Catatan</label>
                            <input type="text" wire:model="formNotes" class="wp-form-input" placeholder="Opsional">
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" wire:click="closeModal" class="btn-secondary">Batal</button>
                        <button type="submit" class="btn-primary">{{ $editingId ? 'Simpan' : 'Tambah' }}</button>
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
                            class="flex-1 py-2 text-sm font-medium rounded-md transition {{ $catFormType === 'expense' ? 'bg-white shadow text-red-600' : 'text-[var(--text-tertiary)]' }}">💸 Pengeluaran</button>
                        <button type="button" wire:click="$set('catFormType', 'income')"
                            class="flex-1 py-2 text-sm font-medium rounded-md transition {{ $catFormType === 'income' ? 'bg-white shadow text-emerald-600' : 'text-[var(--text-tertiary)]' }}">💵 Pemasukan</button>
                    </div>
                    <div>
                        <label class="wp-form-label">Nama Kategori *</label>
                        <input type="text" wire:model="catFormName" class="wp-form-input" required>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="wp-form-label">Icon</label>
                            <input type="text" wire:model="catFormIcon" class="wp-form-input" maxlength="5">
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
</div>
