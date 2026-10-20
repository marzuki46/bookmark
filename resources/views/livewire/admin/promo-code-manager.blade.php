<div class="space-y-6">
    @if($statusMessage)
        <div class="rounded-xl px-4 py-3 text-sm bg-emerald-50 text-emerald-700 border border-emerald-200">{{ $statusMessage }}</div>
    @endif
    @if($createdCode)
        <div class="rounded-xl px-4 py-4 bg-indigo-50 border border-indigo-200 text-indigo-900">
            <p class="text-sm font-medium">Kode promo baru — salin sebelum meninggalkan halaman:</p>
            <code class="block mt-2 text-xl font-bold tracking-wider">{{ $createdCode }}</code>
        </div>
    @endif

    <form wire:submit="create" class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-5 space-y-4">
        <div><h2 class="text-lg font-semibold">Buat promo gratis</h2><p class="text-xs text-[var(--text-tertiary)] mt-1">Tidak memakai OTP. User harus login, lalu satu akun hanya dapat memakai kode yang sama sekali.</p></div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="wp-form-label">Nama promo</label><input wire:model="name" class="wp-form-input" placeholder="Promo chat pelanggan"></div>
            <div><label class="wp-form-label">Durasi gratis</label><select wire:model="durationDays" class="wp-form-input"><option value="30">1 bulan</option><option value="60">2 bulan</option></select></div>
            <div><label class="wp-form-label">Batas pemakaian total (opsional)</label><input type="number" wire:model="maxRedemptions" class="wp-form-input" min="1"></div>
            <div><label class="wp-form-label">Berlaku sampai (opsional)</label><input type="datetime-local" wire:model="expiresAt" class="wp-form-input"></div>
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="isActive"> Aktifkan sekarang</label>
        <button class="btn-primary" type="submit">Buat kode promo</button>
    </form>

    <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
        <div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="text-left text-xs uppercase text-[var(--text-tertiary)] border-b border-[var(--color-border)]"><th class="px-5 py-3">Nama</th><th class="px-5 py-3">Kode</th><th class="px-5 py-3">Durasi</th><th class="px-5 py-3">Pemakaian</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Aksi</th></tr></thead>
        <tbody>@forelse($promos as $promo)<tr class="border-b border-[var(--color-border)] last:border-0"><td class="px-5 py-3">{{ $promo->name }}</td><td class="px-5 py-3"><code class="font-semibold tracking-wide">{{ $promo->displayCode() }}</code></td><td class="px-5 py-3">{{ (int) ($promo->duration_days / 30) }} bulan</td><td class="px-5 py-3">{{ $promo->redemptions_count }}{{ $promo->max_redemptions ? ' / '.$promo->max_redemptions : '' }}</td><td class="px-5 py-3">{{ $promo->is_active ? 'Aktif' : 'Nonaktif' }}</td><td class="px-5 py-3"><button wire:click="toggle({{ $promo->id }})" class="btn-secondary !py-1 !px-2 text-xs">{{ $promo->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button></td></tr>@empty<tr><td colspan="6" class="px-5 py-6 text-center text-[var(--text-tertiary)]">Belum ada promo.</td></tr>@endforelse</tbody></table></div>
    </div>
</div>
