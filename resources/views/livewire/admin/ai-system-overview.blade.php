<div class="space-y-6">
    <div>
        <h2 class="text-xl font-bold text-[var(--text-primary)]">AI Sistem Hub</h2>
        <p class="text-sm text-[var(--text-tertiary)] mt-1">Provider dan model AI yang tersedia untuk fitur aplikasi. API key tidak pernah ditampilkan.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-5">
            <p class="text-xs uppercase tracking-wide text-[var(--text-tertiary)]">Ringkasan bookmark</p>
            <p class="text-2xl font-bold text-[var(--text-primary)] mt-1">{{ number_format($summaryCount) }}</p>
        </div>
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-5">
            <p class="text-xs uppercase tracking-wide text-[var(--text-tertiary)]">Analisis keluarga bulan ini</p>
            <p class="text-2xl font-bold text-[var(--text-primary)] mt-1">{{ number_format($familyUsageCount) }}</p>
        </div>
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-5">
            <p class="text-xs uppercase tracking-wide text-[var(--text-tertiary)]">Keluarga memakai AI</p>
            <p class="text-2xl font-bold text-[var(--text-primary)] mt-1">{{ number_format($familyUsageFamilies) }}</p>
        </div>
    </div>

    <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-[var(--color-border)]">
            <h3 class="font-semibold text-[var(--text-primary)]">Provider tersedia</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-[var(--text-tertiary)] border-b border-[var(--color-border)]">
                        <th class="px-5 py-3">Provider</th>
                        <th class="px-5 py-3">Model</th>
                        <th class="px-5 py-3">Endpoint</th>
                        <th class="px-5 py-3">Fitur</th>
                        <th class="px-5 py-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($providers as $provider)
                        <tr class="border-b border-[var(--color-border)] last:border-0">
                            <td class="px-5 py-4 font-semibold text-[var(--text-primary)]">{{ $provider['name'] }}</td>
                            <td class="px-5 py-4 font-mono text-xs">{{ $provider['model'] ?: '-' }}</td>
                            <td class="px-5 py-4 text-xs text-[var(--text-secondary)] max-w-xs truncate" title="{{ $provider['endpoint'] }}">{{ $provider['endpoint'] ?: '-' }}</td>
                            <td class="px-5 py-4 text-[var(--text-secondary)]">{{ $provider['features'] }}</td>
                            <td class="px-5 py-4">
                                <span class="text-xs px-2 py-1 rounded-full {{ $provider['configured'] ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                    {{ $provider['configured'] ? 'Terkonfigurasi' : 'Belum dikonfigurasi' }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
