<div class="space-y-6">
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold text-[var(--text-primary)]">Paket Berbayar</h1>
            <p class="text-sm text-[var(--text-tertiary)] mt-1">Buat dan ubah paket lifetime / bulanan / tahunan</p>
        </div>
        <button wire:click="create" class="btn-primary">+ Tambah Paket</button>
    </div>

    @if($statusMessage)
        <div class="px-4 py-3 rounded-lg text-sm font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">{{ $statusMessage }}</div>
    @endif

    @if($editingId || $name !== '')
        <form wire:submit="save" class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-5 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-[var(--text-primary)]">{{ $editingId ? 'Edit Paket' : 'Tambah Paket' }}</h3>
                <button type="button" wire:click="create" class="btn-secondary !py-1.5 text-xs">Batal</button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="wp-form-label">Nama</label>
                    <input type="text" wire:model="name" class="wp-form-input" required>
                    @error('name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="wp-form-label">Slug (a-z0-9-, unik)</label>
                    <input type="text" wire:model="slug" class="wp-form-input" required>
                    @error('slug') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="wp-form-label">Durasi</label>
                    <select wire:model="durationType" class="wp-form-input">
                        <option value="monthly">Bulanan</option>
                        <option value="yearly">Tahunan</option>
                        <option value="lifetime">Seumur Hidup</option>
                    </select>
                    @error('durationType') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="wp-form-label">Harga (Rp)</label>
                    <input type="number" wire:model="price" class="wp-form-input" min="0" required>
                    @error('price') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="wp-form-label">Deskripsi</label>
                    <input type="text" wire:model="description" class="wp-form-input">
                </div>
                <div class="flex items-end pb-1">
                    <label class="flex items-center gap-2 text-sm text-[var(--text-primary)]">
                        <input type="checkbox" wire:model="isActive" class="w-4 h-4 rounded border-slate-300">
                        Aktif dijual
                    </label>
                </div>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="btn-primary">Simpan</button>
            </div>
        </form>
    @endif

    <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-[var(--text-tertiary)] border-b border-[var(--color-border)]">
                        <th class="px-5 py-3">Nama</th>
                        <th class="px-5 py-3">Slug</th>
                        <th class="px-5 py-3">Durasi</th>
                        <th class="px-5 py-3">Harga</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Langganan</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($plans as $plan)
                        <tr class="border-b border-[var(--color-border)] last:border-0">
                            <td class="px-5 py-3 font-medium text-[var(--text-primary)]">{{ $plan->name }}</td>
                            <td class="px-5 py-3 text-[var(--text-tertiary)]">{{ $plan->slug }}</td>
                            <td class="px-5 py-3">{{ $plan->durationLabel() }}</td>
                            <td class="px-5 py-3 font-semibold text-[var(--text-primary)]">Rp{{ number_format($plan->price, 0, ',', '.') }}</td>
                            <td class="px-5 py-3">
                                <button wire:click="toggleActive({{ $plan->id }})"
                                    class="text-xs px-2 py-0.5 rounded-full {{ $plan->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                    {{ $plan->is_active ? 'aktif' : 'nonaktif' }}
                                </button>
                            </td>
                            <td class="px-5 py-3 text-[var(--text-tertiary)]">{{ $plan->subscriptions_count }}</td>
                            <td class="px-5 py-3 text-right">
                                <button wire:click="edit({{ $plan->id }})" class="btn-secondary !py-1 text-xs">Edit</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-8 text-center text-[var(--text-tertiary)]">Belum ada paket.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>