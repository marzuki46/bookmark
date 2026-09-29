<div class="space-y-6">
    <div>
        <h2 class="text-lg font-semibold text-[var(--text-primary)]">Manajemen Lisensi</h2>
        <p class="text-sm text-[var(--text-tertiary)] mt-1">Lisensi dimiliki keluarga dan berlaku untuk seluruh anggotanya</p>
    </div>

    @if($statusMessage)
        <div class="px-4 py-3 rounded-lg text-sm font-medium border
            {{ $statusType === 'error' ? 'bg-red-50 text-red-700 border-red-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' }}">
            {{ $statusMessage }}
        </div>
    @endif

    @if($editFamilyId && $mode === 'grant')
        <form wire:submit="grant" class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-5 space-y-4 max-w-lg">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-[var(--text-primary)]">Beri lisensi ke {{ \App\Models\Family::find($editFamilyId)?->name }}</h3>
                <button type="button" wire:click="cancelEdit" class="btn-secondary !py-1.5 text-xs">Batal</button>
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
                <button type="submit" class="btn-primary">Aktifkan Lisensi</button>
            </div>
        </form>
    @endif

    @if($editFamilyId && $mode === 'extend')
        <form wire:submit="extend" class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-5 space-y-4 max-w-lg">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-[var(--text-primary)]">Perpanjang / ubah lisensi {{ \App\Models\Family::find($editFamilyId)?->name }}</h3>
                <button type="button" wire:click="cancelEdit" class="btn-secondary !py-1.5 text-xs">Batal</button>
            </div>
            <div>
                <label class="wp-form-label">Perpanjang dengan paket (berjalan dari tanggal berakhir saat ini)</label>
                <select wire:model="grantPlanId" class="wp-form-input">
                    <option value="">Pilih paket...</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}">{{ $plan->name }} &mdash; Rp{{ number_format($plan->price, 0, ',', '.') }}</option>
                    @endforeach
                </select>
                @error('grantPlanId') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </div>
            <div class="flex justify-end">
                <button type="submit" class="btn-primary">Perpanjang</button>
            </div>
        </form>
    @endif

    <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
        <div class="px-5 py-3 border-b border-[var(--color-border)] flex items-center justify-between gap-3 flex-wrap">
            <h3 class="text-sm font-semibold text-[var(--text-primary)]">Keluarga &amp; Status Lisensi</h3>
            <button type="button" wire:click="$set('mode', 'expiry')" class="btn-secondary !py-1 text-xs">Ubah Tanggal Berakhir</button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-[var(--text-tertiary)] border-b border-[var(--color-border)]">
                        <th class="px-5 py-3">Keluarga</th>
                        <th class="px-5 py-3">Aktif Sampai</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($families as $family)
                        @php
                            $sub = \App\Models\Subscription::query()->with('plan')->where('family_id', $family->id)->latest('id')->first();
                            $familyName = $family->name;
                        @endphp
                        <tr class="border-b border-[var(--color-border)] last:border-0">
                            <td class="px-5 py-3">
                                <p class="font-medium text-[var(--text-primary)]">{{ $familyName }}</p>
                                <p class="text-xs text-[var(--text-tertiary)]">Kepala: {{ $family->owner?->name ?? '-' }} &middot; {{ $family->members_count }} anggota</p>
                            </td>
                            <td class="px-5 py-3">
                                @if($sub)
                                    <p class="text-sm text-[var(--text-primary)]">{{ $sub->plan?->name }}</p>
                                    <p class="text-xs {{ $sub->isUsable() ? 'text-emerald-600' : 'text-red-500' }}">
                                        @if($sub->expires_at)
                                            Aktif sampai {{ $sub->expires_at->format('d M Y') }}
                                        @else
                                            Seumur hidup
                                        @endif
                                        &middot; {{ $sub->isUsable() ? 'aktif' : 'habis' }}
                                    </p>
                                @else
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-500">belum berlisensi</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                @if($sub)
                                    <button wire:click="openExtend({{ $family->id }})" class="btn-secondary !py-1 text-xs mr-1">Perpanjang</button>
                                    <button wire:click="revoke({{ $family->id }})" class="btn-secondary !py-1 text-xs"
                                            wire:confirm="Cabut lisensi keluarga {{ $family->name }}?">Cabut</button>
                                @else
                                    <button wire:click="openGrant({{ $family->id }})" class="btn-secondary !py-1 text-xs">Beri Lisensi</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-5 py-8 text-center text-[var(--text-tertiary)]">Belum ada keluarga.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3">{{ $families->links() }}</div>
    </div>

    <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-5 max-w-lg">
        <h3 class="text-sm font-semibold text-[var(--text-primary)]">Ubah Tanggal Berakhir (Expiry)</h3>
        <form wire:submit="saveExpiry" class="mt-3 space-y-4">
            <div>
                <label class="wp-form-label">Keluarga</label>
                <select wire:model="editFamilyId" class="wp-form-input">
                    <option value="">Pilih keluarga...</option>
                    @foreach(\App\Models\Family::query()->orderBy('name')->get() as $family)
                        <option value="{{ $family->id }}">{{ $family->name }}</option>
                    @endforeach
                </select>
                @error('editFamilyId') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="wp-form-label">Aktif sampai (kosongkan = seumur hidup)</label>
                <input type="date" wire:model="editExpiresAt" class="wp-form-input">
                @error('editExpiresAt') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </div>
            <div class="flex justify-end">
                <button type="submit" class="btn-primary">Simpan Tanggal</button>
            </div>
        </form>
        <p class="text-xs text-[var(--text-tertiary)] mt-3">Merubah tanggal tidak mengubah paket; hanya memperpanjang / mempersingkat masa aktif.</p>
    </div>
</div>