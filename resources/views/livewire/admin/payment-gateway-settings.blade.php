<div class="space-y-6">
    <div>
        <h2 class="text-xl font-bold text-[var(--text-primary)]">Pengaturan Pembayaran</h2>
        <p class="text-sm text-[var(--text-tertiary)] mt-1">Pilih satu metode yang aktif. API key disimpan terenkripsi.</p>
    </div>

    <form wire:submit="save" class="space-y-5">
    @if($statusMessage)
        <div class="rounded-xl px-4 py-3 text-sm {{ $statusType === 'success' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200' }}">
            {{ $statusMessage }}
        </div>

    @endif

        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-5 space-y-4">
            <div>
                <h3 class="font-semibold text-[var(--text-primary)]">Pembayaran manual</h3>
                <p class="text-xs text-[var(--text-tertiary)] mt-1">Data ini tampil di aplikasi. Setelah transfer/QRIS, admin mengaktifkan langganan secara manual.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div><label class="wp-form-label">Nama bank</label><input type="text" wire:model="manualBankName" class="wp-form-input"></div>
                <div><label class="wp-form-label">Nomor rekening</label><input type="text" wire:model="manualBankAccount" class="wp-form-input"></div>
                <div><label class="wp-form-label">Nama pemilik rekening</label><input type="text" wire:model="manualAccountHolder" class="wp-form-input"></div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="wp-form-label">URL gambar QRIS</label><input type="url" wire:model="manualQrisImageUrl" class="wp-form-input" placeholder="https://..."></div>
                <div><label class="wp-form-label">WhatsApp admin</label><input type="text" wire:model="manualWhatsapp" class="wp-form-input" placeholder="628..."></div>
            </div>
            <div><label class="wp-form-label">Instruksi pembayaran</label><textarea wire:model="manualInstructions" class="wp-form-input" rows="3" placeholder="Kirim bukti pembayaran ke admin..."></textarea></div>
        </div>

        <div>
            <label class="wp-form-label">Metode pembayaran aktif</label>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-2">
                @foreach([
                    'manual' => ['title' => 'Manual', 'description' => 'Transfer bank / QRIS, verifikasi admin'],
                    'duitku' => ['title' => 'Duitku', 'description' => 'Gateway otomatis'],
                    'midtrans' => ['title' => 'Midtrans', 'description' => 'Gateway otomatis legacy'],
                ] as $value => $option)
                    <label class="relative flex cursor-pointer gap-3 rounded-xl border-2 p-4 transition {{ $provider === $value ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-100' : 'border-[var(--color-border)]' }}">
                        <input type="radio" wire:model.live="provider" value="{{ $value }}" class="mt-1 accent-indigo-600">
                        <span><strong class="block text-sm">{{ $option['title'] }}</strong><span class="block text-xs text-[var(--text-tertiary)] mt-1">{{ $option['description'] }}</span></span>
                    </label>
                @endforeach
            </div>
            <p class="text-xs text-[var(--text-tertiary)] mt-1">Mode saat ini: <strong>{{ $provider === 'manual' ? 'Manual, menunggu verifikasi admin' : 'Gateway otomatis' }}</strong></p>
            @error('provider') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-5 space-y-4 {{ $provider === 'manual' ? 'opacity-60' : '' }}">
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
