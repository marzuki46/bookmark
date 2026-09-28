<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-[var(--text-primary)]">Dashboard KEUANGAN</h1>
        <p class="text-sm text-[var(--text-tertiary)] mt-1">Ringkasan sistem: pengguna, langganan, pembayaran, dan trafik hari ini</p>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-5">
            <p class="text-xs uppercase tracking-wide text-[var(--text-tertiary)]">Pengguna</p>
            <p class="text-3xl font-bold text-[var(--text-primary)] mt-1">{{ number_format($this->usersCount) }}</p>
            <p class="text-xs text-[var(--text-tertiary)] mt-1">{{ $this->adminsCount }} admin</p>
        </div>
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-5">
            <p class="text-xs uppercase tracking-wide text-[var(--text-tertiary)]">Langganan Aktif</p>
            <p class="text-3xl font-bold text-emerald-600 mt-1">{{ number_format($this->activeSubscriptions) }}</p>
        </div>
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-5">
            <p class="text-xs uppercase tracking-wide text-[var(--text-tertiary)]">Total Pembayaran Lunas</p>
            <p class="text-3xl font-bold text-[var(--text-primary)] mt-1">Rp{{ number_format($this->revenue, 0, ',', '.') }}</p>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-4">
                <p class="text-xs text-[var(--text-tertiary)]">URL hari ini</p>
                <p class="text-xl font-bold text-[var(--text-primary)]">{{ number_format($this->requestsToday) }}</p>
            </div>
            <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-4">
                <p class="text-xs text-[var(--text-tertiary)]">Login gagal hari ini</p>
                <p class="text-xl font-bold text-red-600">{{ number_format($this->failedLoginsToday) }}</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-[var(--color-border)]">
                <h3 class="text-sm font-semibold text-[var(--text-primary)]">Pembayaran Terbaru</h3>
            </div>
            <div class="p-5">
                @if($this->recentPayments->isEmpty())
                    <p class="text-sm text-[var(--text-tertiary)]">Belum ada pembayaran.</p>
                @else
                    <ul class="space-y-3">
                        @foreach($this->recentPayments as $payment)
                            <li class="flex items-center justify-between text-sm">
                                <div>
                                    <p class="font-medium text-[var(--text-primary)]">{{ $payment->user?->name ?? '?' }}</p>
                                    <p class="text-xs text-[var(--text-tertiary)]">{{ $payment->plan?->name ?? 'paket' }} &middot; {{ $payment->order_id }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold text-[var(--text-primary)]">Rp{{ number_format($payment->gross_amount, 0, ',', '.') }}</p>
                                    <span class="inline-block text-xs px-2 py-0.5 rounded-full
                                        {{ $payment->status === 'paid' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $payment->status }}</span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-[var(--color-border)]">
                <h3 class="text-sm font-semibold text-[var(--text-primary)]">Pengguna Terdaftar</h3>
            </div>
            <div class="p-5">
                @if($this->users->isEmpty())
                    <p class="text-sm text-[var(--text-tertiary)]">Belum ada pengguna.</p>
                @else
                    <ul class="space-y-3">
                        @foreach($this->users as $user)
                            <li class="flex items-center justify-between text-sm">
                                <div>
                                    <p class="font-medium text-[var(--text-primary)]">{{ $user->name }}
                                        @if($user->is_admin)<span class="ml-1 text-xs px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700">admin</span>@endif
                                    </p>
                                    <p class="text-xs text-[var(--text-tertiary)]">{{ $user->email }}</p>
                                </div>
                                <span class="text-xs px-2 py-0.5 rounded-full
                                    {{ $user->hasActiveSubscription() ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                    {{ $user->hasActiveSubscription() ? 'berlangganan' : 'gratis' }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>