<div class="space-y-6">
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold text-[var(--text-primary)]">Penyemangat Kang Cuan</h1>
            <p class="text-sm text-[var(--text-tertiary)] mt-1">Template pesan yang diminta HP (seperti alarm). Semakin banyak variasi, pengguna tidak bosan.</p>
        </div>
        <button wire:click="create" class="btn-primary">+ Tambah Template</button>
    </div>

    @if($statusMessage)
        <div class="px-4 py-3 rounded-lg text-sm font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">{{ $statusMessage }}</div>
    @endif

    @if($showForm)
        <form wire:submit="save" class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-5 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-[var(--text-primary)]">{{ $editingId ? 'Edit Template' : 'Tambah Template' }}</h3>
                <button type="button" wire:click="cancel" class="btn-secondary !py-1.5 text-xs">Batal</button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="wp-form-label">Waktu</label>
                    <select wire:model="slot" class="wp-form-input">
                        <option value="pagi">Pagi (penyemangat)</option>
                        <option value="malam">Malam (rekap harian)</option>
                        <option value="bulanan">Bulanan (refleksi)</option>
                    </select>
                    @error('slot') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                @if($slot === 'malam')
                    <div>
                        <label class="wp-form-label">Kondisi rekap</label>
                        <select wire:model="variant" class="wp-form-input">
                            <option value="">Pilih kondisi…</option>
                            <option value="pengeluaran-luas">Pengeluaran &gt; pemasukan</option>
                            <option value="pemasukan-luas">Pemasukan ≥ pengeluaran</option>
                            <option value="kosong">Tidak ada transaksi</option>
                        </select>
                        @error('variant') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                @endif
                <div class="flex items-end pb-1">
                    <label class="flex items-center gap-2 text-sm text-[var(--text-primary)]">
                        <input type="checkbox" wire:model="isActive" class="w-4 h-4 rounded border-slate-300">
                        Aktif dikirim
                    </label>
                </div>
            </div>
            <div>
                <label class="wp-form-label">Isi pesan</label>
                <textarea wire:model="content" rows="3" class="wp-form-input" required
                    placeholder="Gunakan {nama} agar otomatis diganti nama pengguna. Contoh: Selamat pagi Kak {nama}! ☀️ ..."></textarea>
                @error('content') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </div>
            <div class="flex justify-end">
                <button type="submit" class="btn-primary">Simpan</button>
            </div>
        </form>
    @endif

    <div class="flex gap-4 flex-wrap items-center">
        <div>
            <label class="wp-form-label">Filter waktu</label>
            <select wire:model.live="slotFilter" class="wp-form-input">
                <option value="">Semua</option>
                <option value="pagi">Pagi</option>
                <option value="malam">Malam</option>
                <option value="bulanan">Bulanan</option>
            </select>
        </div>
        <div class="pt-5">
            <label class="flex items-center gap-2 text-sm text-[var(--text-primary)]">
                <input type="checkbox" wire:model.live="showInactive" class="w-4 h-4 rounded border-slate-300">
                Tampilkan yang nonaktif
            </label>
        </div>
    </div>

    <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-[var(--text-tertiary)] border-b border-[var(--color-border)]">
                        <th class="px-5 py-3">Waktu</th>
                        <th class="px-5 py-3">Kondisi</th>
                        <th class="px-5 py-3">Isi pesan</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($messages as $message)
                        <tr class="border-b border-[var(--color-border)] last:border-0 align-top">
                            <td class="px-5 py-3">
                                <span class="text-xs px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700">
                                    {{ ['pagi' => 'Pagi', 'malam' => 'Malam', 'bulanan' => 'Bulanan'][$message->slot] }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-[var(--text-tertiary)]">
                                @if($message->slot === 'malam')
                                    {{ ['pengeluaran-luas' => 'Pengeluaran > pemasukan', 'pemasukan-luas' => 'Pemasukan ≥ pengeluaran', 'kosong' => 'Tidak ada transaksi'][$message->variant] ?? '—' }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-5 py-3 text-[var(--text-primary)] max-w-md whitespace-pre-line">{{ $message->content }}</td>
                            <td class="px-5 py-3">
                                <button wire:click="toggleActive({{ $message->id }})"
                                    class="text-xs px-2 py-0.5 rounded-full {{ $message->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                    {{ $message->is_active ? 'aktif' : 'nonaktif' }}
                                </button>
                            </td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <button wire:click="edit({{ $message->id }})" class="btn-secondary !py-1 text-xs">Edit</button>
                                <button wire:click="destroy({{ $message->id }})"
                                    wire:confirm="Hapus template ini?"
                                    class="text-xs px-2 py-1 rounded-lg text-red-600 hover:bg-red-50">Hapus</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-[var(--text-tertiary)]">Belum ada template.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>