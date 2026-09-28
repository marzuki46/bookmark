<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-[var(--text-primary)]">Langganan</h1>
        <p class="text-sm text-[var(--text-tertiary)] mt-1">Beri akses berbayar secara manual atau pantau status tiap pengguna</p>
    </div>

    @if($statusMessage)
        <div class="px-4 py-3 rounded-lg text-sm font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">{{ $statusMessage }}</div>
    @endif

    @if($grantUserId)
        <form wire:submit="grant" class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-5 space-y-4 max-w-lg">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-[var(--text-primary)]">Beri akses ke {{ \App\Models\User::find($grantUserId)?->name }}</h3>
                <button type="button" wire:click="$set('grantUserId', null)" class="btn-secondary !py-1.5 text-xs">Batal</button>
            </div>
            <div>
                <label class="wp-form-label">Pilih paket</label>
                <select wire:model="grantPlanId" class="wp-form-input" required>
                    <option value="">Pilih...</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}">{{ $plan->name }} &mdash; Rp{{ number_format($plan->price, 0, ',', '.') }}</option>
                    @endforeach
                </select>
                @error('grantPlanId') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </div>
            <div class="flex justify-end">
                <button type="submit" class="btn-primary">Aktifkan</button>
            </div>
        </form>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-5">
        <div class="lg:col-span-3 bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
            <div class="px-5 py-3 border-b border-[var(--color-border)]">
                <h3 class="text-sm font-semibold text-[var(--text-primary)]">Pengguna &amp; Status</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-[var(--text-tertiary)] border-b border-[var(--color-border)]">
                            <th class="px-5 py-3">Pengguna</th>
                            <th class="px-5 py-3">Aktif Sampai</th>
                            <th class="px-5 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            @php $sub = $user->activeSubscription(); @endphp
                            <tr class="border-b border-[var(--color-border)] last:border-0">
                                <td class="px-5 py-3">
                                    <p class="font-medium text-[var(--text-primary)]">{{ $user->name }}</p>
                                    <p class="text-xs text-[var(--text-tertiary)]">{{ $user->email }}</p>
                                </td>
                                <td class="px-5 py-3">
                                    @if($sub)
                                        <p class="text-sm text-[var(--text-primary)]">{{ $sub->plan?->name }}</p>
                                        <p class="text-xs {{ $sub->isUsable() ? 'text-emerald-600' : 'text-red-500' }}">
                                            {{ $sub->expires_at ? $sub->expires_at->format('d M Y') : 'Seumur hidup' }}
                                            &middot; {{ $sub->isUsable() ? 'aktif' : 'habis' }}
                                        </p>
                                    @else
                                        <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-500">belum berlangganan</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-right whitespace-nowrap">
                                    <button wire:click="openGrant({{ $user->id }})" class="btn-secondary !py-1 text-xs mr-1">Beri Akses</button>
                                    @if($sub)
                                        <button wire:click="revoke({{ $user->id }})" class="btn-secondary !py-1 text-xs"
                                            wire:confirm="Cabut akses {{ $user->name }}?">Cabut</button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-5 py-8 text-center text-[var(--text-tertiary)]">Belum ada pengguna.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-3">{{ $users->links() }}</div>
        </div>

        <div class="lg:col-span-2 bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
            <div class="px-5 py-3 border-b border-[var(--color-border)]">
                <h3 class="text-sm font-semibold text-[var(--text-primary)]">Riwayat Pembayaran</h3>
            </div>
            <div class="p-5">
                @if($recentPayments->isEmpty())
                    <p class="text-sm text-[var(--text-tertiary)]">Belum ada pembayaran.</p>
                @else
                    <ul class="space-y-3">
                        @foreach($recentPayments as $payment)
                            <li class="flex items-center justify-between text-sm">
                                <div>
                                    <p class="font-medium text-[var(--text-primary)]">{{ $payment->user?->name ?? '?' }}</p>
                                    <p class="text-xs text-[var(--text-tertiary)]">{{ $payment->order_id }} &middot; {{ $payment->payment_type ?? 'manual' }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold text-[var(--text-primary)]">Rp{{ number_format($payment->gross_amount, 0, ',', '.') }}</p>
                                    <span class="text-xs px-2 py-0.5 rounded-full
                                        {{ $payment->status === 'paid' ? 'bg-emerald-50 text-emerald-700' : ($payment->status === 'pending' ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700') }}">{{ $payment->status }}</span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>