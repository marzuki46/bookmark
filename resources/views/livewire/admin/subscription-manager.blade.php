<div class="space-y-6">
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold text-[var(--text-primary)]">Langganan Keluarga</h1>
            <p class="text-sm text-[var(--text-tertiary)] mt-1">Satu langganan dihitung per keluarga dan berlaku untuk seluruh anggotanya.</p>
        </div>
        <div class="text-right">
            <p class="text-xs uppercase tracking-wide text-[var(--text-tertiary)]">Total keluarga</p>
            <p class="text-2xl font-bold text-[var(--text-primary)]">{{ $families->total() }}</p>
        </div>
    </div>

    @if($statusMessage)
        <div class="px-4 py-3 rounded-lg text-sm font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">{{ $statusMessage }}</div>
    @endif

    @if($grantFamilyId)
        <form wire:submit="grant" class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-5 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-[var(--text-primary)]">Aktifkan langganan keluarga</h3>
                <button type="button" wire:click="$set('grantFamilyId', null)" class="btn-secondary !py-1.5 text-xs">Batal</button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <label class="wp-form-label">Paket</label>
                    <select wire:model="grantPlanId" class="wp-form-input" required>
                        <option value="">Pilih paket...</option>
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->name }} &mdash; Rp{{ number_format($plan->price, 0, ',', '.') }} &middot; {{ $plan->durationLabel() }}</option>
                        @endforeach
                    </select>
                    @error('grantPlanId') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div class="flex items-end">
                    <button type="submit" class="btn-primary w-full justify-center">Aktifkan Langganan</button>
                </div>
            </div>
        </form>
    @endif

    <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-[var(--color-border)]">
            <input type="search" wire:model.live.debounce.300ms="search" class="wp-form-input" placeholder="Cari nama keluarga...">
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-[var(--text-tertiary)] border-b border-[var(--color-border)]">
                        <th class="px-5 py-3">Keluarga / Email</th>
                        <th class="px-5 py-3">Anggota</th>
                        <th class="px-5 py-3">Paket</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Aktif sampai</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($families as $family)
                        @php $sub = $family->latest_subscription; @endphp
                        <tr class="border-b border-[var(--color-border)] last:border-0 hover:bg-[var(--color-bg)]">
                            <td class="px-5 py-4">
                                <p class="font-semibold text-[var(--text-primary)]">{{ $family->name }}</p>
                                <p class="text-xs text-[var(--text-tertiary)]">{{ $family->owner?->name ?? '-' }} &middot; {{ $family->owner?->email ?? '-' }}</p>
                            </td>
                            <td class="px-5 py-4">{{ $family->members_count }}</td>
                            <td class="px-5 py-4">{{ $sub?->plan?->name ?? 'Belum ada' }}</td>
                            <td class="px-5 py-4">
                                <span class="text-xs px-2 py-1 rounded-full {{ $sub?->isUsable() ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                    {{ $sub?->isUsable() ? 'Aktif' : ($sub ? 'Tidak aktif' : 'Belum berlangganan') }}
                                </span>
                            </td>
                            <td class="px-5 py-4">{{ $sub?->expires_at?->format('d M Y') ?? ($sub ? 'Seumur hidup' : '-') }}</td>
                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                <button wire:click="openGrant({{ $family->id }})" class="btn-secondary !py-1 text-xs">{{ $sub ? 'Ganti Paket' : 'Aktifkan' }}</button>
                                @if($sub)
                                    <button wire:click="revoke({{ $family->id }})" class="btn-secondary !py-1 text-xs" wire:confirm="Cabut langganan keluarga {{ $family->name }}?">Cabut</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-8 text-center text-[var(--text-tertiary)]">Belum ada keluarga.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3">{{ $families->links() }}</div>
    </div>

    <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-[var(--color-border)]">
            <h2 class="text-base font-semibold text-[var(--text-primary)]">Riwayat Pembayaran</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-[var(--text-tertiary)] border-b border-[var(--color-border)]">
                        <th class="px-5 py-3">Tanggal</th><th class="px-5 py-3">Keluarga</th><th class="px-5 py-3">Paket</th><th class="px-5 py-3">Order</th><th class="px-5 py-3 text-right">Nominal</th><th class="px-5 py-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentPayments as $payment)
                        <tr class="border-b border-[var(--color-border)] last:border-0">
                            <td class="px-5 py-3">{{ $payment->paid_at?->format('d M Y H:i') ?? $payment->created_at?->format('d M Y H:i') }}</td>
                            <td class="px-5 py-3">{{ $payment->family?->name ?? $payment->user?->name ?? '-' }}</td>
                            <td class="px-5 py-3">{{ $payment->plan?->name ?? '-' }}</td>
                            <td class="px-5 py-3 text-xs font-mono">{{ $payment->order_id }}</td>
                            <td class="px-5 py-3 text-right font-semibold">Rp{{ number_format($payment->gross_amount, 0, ',', '.') }}</td>
                            <td class="px-5 py-3"><span class="text-xs px-2 py-1 rounded-full {{ $payment->status === 'paid' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $payment->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-8 text-center text-[var(--text-tertiary)]">Belum ada pembayaran.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
