<div>
    @if($statusMessage)
        <div class="ka-status {{ $statusType === 'success' ? 'ka-status-success' : 'ka-status-error' }}">
            <span>{{ $statusMessage }}</span>
            <button wire:click="clearStatusMessage" aria-label="Tutup">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
    @endif

    @if(! $family)
        <div class="ka-onboard">
            <div class="ka-onboard-icon">👨‍👩‍👧</div>
            <div class="ka-onboard-title">Belum Ada Keluarga</div>
            <div class="ka-onboard-desc">Buat keluarga dulu di Dashboard Keluarga (mode web).</div>
        </div>
    @else
        <div class="ka-card">
            <div class="ka-card-body space-y-4">
                <div class="ka-seg">
                    <button type="button" class="{{ $formType === 'expense' ? 'active-expense' : '' }}" wire:click="$set('formType', 'expense')">Pengeluaran</button>
                    <button type="button" class="{{ $formType === 'income' ? 'active-income' : '' }}" wire:click="$set('formType', 'income')">Pemasukan</button>
                </div>

                <div>
                    <label class="ka-form-label">Kategori</label>
                    <select wire:model="formCategoryId" class="ka-input">
                        <option value="">Tanpa kategori</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->icon ?? '' }} {{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="ka-form-label">Jumlah (Rp)</label>
                    <input type="number" min="0" step="0.01" wire:model="formAmount" class="ka-input" placeholder="0" inputmode="decimal">
                    @error('formAmount') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="ka-form-label">Deskripsi</label>
                    <input type="text" wire:model="formDescription" class="ka-input" placeholder="Contoh: Belanja mingguan">
                    @error('formDescription') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="ka-form-label">Tanggal</label>
                    <input type="date" wire:model="formDate" class="ka-input">
                    @error('formDate') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="ka-form-label">Pembayar</label>
                    <div class="ka-seg">
                        <button type="button" class="{{ $formPayer === 'husband' ? 'active' : '' }}" wire:click="$set('formPayer', 'husband')">Suami</button>
                        <button type="button" class="{{ $formPayer === 'wife' ? 'active' : '' }}" wire:click="$set('formPayer', 'wife')">Istri</button>
                        <button type="button" class="{{ $formPayer === 'shared' ? 'active' : '' }}" wire:click="$set('formPayer', 'shared')">Bersama</button>
                    </div>
                </div>

                <button wire:click="save" wire:loading.attr="disabled" class="ka-btn ka-btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                    Simpan Transaksi
                </button>
            </div>
        </div>
    @endif
</div>
