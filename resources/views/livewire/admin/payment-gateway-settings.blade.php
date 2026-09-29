<div class="space-y-6">
    <div>
        <h2 class="text-xl font-bold text-[var(--text-primary)]">Pengaturan Pembayaran</h2>
        <p class="text-sm text-[var(--text-tertiary)] mt-1">Atur gateway yang menerima pembayaran paket keluarga. API key disimpan terenkripsi.</p>
    </div>

    @if($statusMessage)
        <div class="rounded-xl px-4 py-3 text-sm {{ $statusType === 'success' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200' }}">
            {{ $statusMessage }}
        </div>
    @endif

    <form wire:submit="save" class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-5 space-y-5">
        <div>
            <label class="wp-form-label">Gateway aktif</label>
            <select wire:model="provider" class="wp-form-input">
                <option value="duitku">Duitku</option>
                <option value="midtrans">Midtrans (legacy)</option>
            </select>
            @error('provider') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="border-t border-[var(--color-border)] pt-5 space-y-4">
            <div>
                <h3 class="font-semibold text-[var(--text-primary)]">Duitku</h3>
                <p class="text-xs text-[var(--text-tertiary)] mt-1">Ambil Merchant Code dan API Key dari portal merchant Duitku.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="wp-form-label">Merchant Code</label>
                    <input type="text" wire:model="duitkuMerchantCode" class="wp-form-input" autocomplete="off">
                    @error('duitkuMerchantCode') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="wp-form-label">API Key</label>
                    <input type="password" wire:model="duitkuApiKey" class="wp-form-input" autocomplete="new-password" placeholder="{{ $duitkuApiKeyConfigured ? 'Tersimpan, isi hanya jika mengganti' : 'Masukkan API key' }}">
                    @error('duitkuApiKey') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="wp-form-label">Payment Method</label>
                    <input type="text" wire:model="duitkuPaymentMethod" maxlength="2" class="wp-form-input uppercase" placeholder="VC">
                    <p class="text-xs text-[var(--text-tertiary)] mt-1">Contoh: VC, VA, DA, SP.</p>
                    @error('duitkuPaymentMethod') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <label class="flex items-center gap-2 text-sm text-[var(--text-secondary)]">
                <input type="checkbox" wire:model="duitkuProduction" class="rounded border-[var(--color-border)]">
                Gunakan production Duitku
            </label>
        </div>

        <div class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-xs text-amber-800">
            Callback Duitku: <code>{{ url('/api/payments/duitku/callback') }}</code>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="btn-primary">Simpan Pengaturan Pembayaran</button>
        </div>
    </form>
</div>
