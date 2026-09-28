<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-[var(--text-primary)]">Log Akses</h1>
        <p class="text-sm text-[var(--text-tertiary)] mt-1">URL yang diakses, siapa yang mengakses, dan percobaan login gagal</p>
    </div>

    <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
        <div class="px-5 py-3 border-b border-[var(--color-border)] flex flex-wrap items-center gap-3">
            <input type="search" wire:model.live.debounce.300ms="userSearch" placeholder="Cari nama/email pengguna..." class="wp-form-input !w-56">
            <select wire:model.live="method" class="wp-form-input !w-28">
                <option value="">Semua method</option>
                @foreach(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $m)
                    <option value="{{ $m }}">{{ $m }}</option>
                @endforeach
            </select>
            <select wire:model.live="status" class="wp-form-input !w-36">
                <option value="">Semua status</option>
                <option value="200">200 OK</option>
                <option value="302">302 redirect</option>
                <option value="401">401 unauth</option>
                <option value="403">403 forbidden</option>
                <option value="404">404 not found</option>
                <option value="422">422 validasi</option>
                <option value="429">429 throttled</option>
                <option value="500">500 error</option>
            </select>
            <input type="date" wire:model.live="date" class="wp-form-input !w-40">
            <button wire:click="clearFilters" class="btn-secondary !py-1.5 text-xs">Reset</button>
            <span class="ml-auto text-xs text-[var(--text-tertiary)]">{{ number_format($requests->total()) }} baris</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-[var(--text-tertiary)] border-b border-[var(--color-border)]">
                        <th class="px-5 py-3">Waktu</th>
                        <th class="px-5 py-3">Pengguna</th>
                        <th class="px-5 py-3">IP</th>
                        <th class="px-5 py-3">Method</th>
                        <th class="px-5 py-3">URL</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Durasi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $log)
                        <tr class="border-b border-[var(--color-border)] last:border-0">
                            <td class="px-5 py-2.5 whitespace-nowrap text-[var(--text-tertiary)]">{{ $log->created_at->format('d M H:i:s') }}</td>
                            <td class="px-5 py-2.5">{{ $log->user?->email ?? 'guest' }}</td>
                            <td class="px-5 py-2.5 text-[var(--text-tertiary)]">{{ $log->ip_address }}</td>
                            <td class="px-5 py-2.5">
                                <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">{{ $log->method }}</span>
                            </td>
                            <td class="px-5 py-2.5 max-w-md truncate text-[var(--text-tertiary)]" title="{{ $log->url }}">{{ $log->url }}</td>
                            <td class="px-5 py-2.5">
                                <span class="text-xs px-2 py-0.5 rounded-full
                                    {{ $log->status_code < 400 ? 'bg-emerald-50 text-emerald-700' : ($log->status_code < 500 ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700') }}">{{ $log->status_code }}</span>
                            </td>
                            <td class="px-5 py-2.5 text-[var(--text-tertiary)]">{{ $log->duration_ms }} ms</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-8 text-center text-[var(--text-tertiary)]">Belum ada log.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3">{{ $requests->links() }}</div>
    </div>

    <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-[var(--color-border)]">
            <h3 class="text-sm font-semibold text-[var(--text-primary)]">Percobaan Login Gagal &mdash; 20 Terakhir</h3>
        </div>
        <div class="p-5">
            @if($failedLogins->isEmpty())
                <p class="text-sm text-[var(--text-tertiary)]">Tidak ada percobaan login gagal.</p>
            @else
                <ul class="space-y-2 text-sm">
                    @foreach($failedLogins as $f)
                        <li class="flex items-center justify-between gap-2">
                            <span class="text-[var(--text-primary)]">{{ $f->created_at->format('d M H:i') }} &middot; <b>{{ $f->email }}</b></span>
                            <span class="shrink-0 text-[var(--text-tertiary)]">{{ $f->ip_address }} {{ $f->was_blocked ? '&middot; diblokir' : '' }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>