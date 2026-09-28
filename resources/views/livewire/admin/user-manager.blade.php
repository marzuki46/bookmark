<div class="space-y-6">
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold text-[var(--text-primary)]">Manajemen Pengguna</h1>
            <p class="text-sm text-[var(--text-tertiary)] mt-1">Tambah akun, lihat email, terbitkan kode login, atur admin</p>
        </div>
        <button wire:click="create" class="btn-primary">+ Tambah Pengguna</button>
    </div>

    @if($statusMessage)
        <div class="px-4 py-3 rounded-lg text-sm font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">{{ $statusMessage }}</div>
    @endif

    @if($issuedCode)
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-sm font-semibold text-amber-800">Kode login baru untuk {{ $issuedForEmail }}</h3>
                    <p class="text-xs text-amber-700 mt-1">Tunjukkan ke pengguna sekali saja. Kode lama otomatis tidak berlaku.</p>
                </div>
                <button wire:click="clearIssuedCode" class="btn-secondary !py-1.5 text-xs">Tutup</button>
            </div>
            <code class="block text-center text-2xl font-bold tracking-[0.25em] text-amber-800 bg-white border border-amber-200 rounded-lg px-4 py-3 mt-3">{{ $issuedCode }}</code>
        </div>
    @endif

    @if($editingId || $name !== '')
        <form wire:submit="save" class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-5 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-[var(--text-primary)]">{{ $editingId ? 'Edit Pengguna' : 'Tambah Pengguna' }}</h3>
                <button type="button" wire:click="cancelEdit" class="btn-secondary !py-1.5 text-xs">Batal</button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="wp-form-label">Nama</label>
                    <input type="text" wire:model="name" class="wp-form-input" required>
                    @error('name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="wp-form-label">Email (untuk login web &amp; dilihat admin)</label>
                    <input type="email" wire:model="email" class="wp-form-input" required>
                    @error('email') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="wp-form-label">Password web {{ $editingId ? '(kosongkan jika tetap)' : '(kosong = acak)' }}</label>
                    <input type="password" wire:model="password" class="wp-form-input">
                    @error('password') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div class="flex items-end pb-1">
                    <label class="flex items-center gap-2 text-sm text-[var(--text-primary)]">
                        <input type="checkbox" wire:model="isAdmin" class="w-4 h-4 rounded border-slate-300">
                        Jadikan admin (akses menu KEUANGAN)
                    </label>
                </div>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="btn-primary">Simpan</button>
            </div>
        </form>
    @endif

    <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
        <div class="px-5 py-3 border-b border-[var(--color-border)] flex flex-wrap items-center gap-3">
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari nama / email..." class="wp-form-input !w-64">
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-[var(--text-tertiary)] border-b border-[var(--color-border)]">
                        <th class="px-5 py-3">Nama</th>
                        <th class="px-5 py-3">Email</th>
                        <th class="px-5 py-3">Kode Login</th>
                        <th class="px-5 py-3">Role</th>
                        <th class="px-5 py-3">Dibuat</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr class="border-b border-[var(--color-border)] last:border-0">
                            <td class="px-5 py-3 font-medium text-[var(--text-primary)]">{{ $user->name }}</td>
                            <td class="px-5 py-3 text-[var(--text-tertiary)]">{{ $user->email }}</td>
                            <td class="px-5 py-3">
                                @if($user->app_login_code)
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700">sudah ada</span>
                                @else
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-500">belum</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                @if($user->is_admin)
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700">admin</span>
                                @else
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-500">user</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-[var(--text-tertiary)]">{{ $user->created_at->format('d M Y') }}</td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <button wire:click="issueCode({{ $user->id }})" class="btn-secondary !py-1 text-xs mr-1"
                                    wire:confirm="Terbitkan kode login baru? Kode lama tidak berlaku lagi.">Kode Login</button>
                                <button wire:click="edit({{ $user->id }})" class="btn-secondary !py-1 text-xs">Edit</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-8 text-center text-[var(--text-tertiary)]">Tidak ada pengguna.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3">{{ $users->links() }}</div>
    </div>
</div>