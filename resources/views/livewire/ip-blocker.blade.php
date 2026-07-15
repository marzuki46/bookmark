<div>
    <!-- Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl p-5 border border-gray-200 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M18.36 6.64a9 9 0 11-12.73 0M12 2v10"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['total_blocked'] }}</p>
                    <p class="text-xs text-gray-500">IP Aktif Diblokir</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-5 border border-gray-200 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['total_attempts_24h'] }}</p>
                    <p class="text-xs text-gray-500">Login Gagal (24 jam)</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-5 border border-gray-200 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['unique_ips_24h'] }}</p>
                    <p class="text-xs text-gray-500">IP Unik (24 jam)</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-5 border border-gray-200 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-yellow-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['auto_blocks_24h'] }}</p>
                    <p class="text-xs text-gray-500">Auto-Block (24 jam)</p>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-xl text-green-700 text-sm flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M5 13l4 4L19 7"/></svg>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            {{ session('error') }}
        </div>
    @endif

    <!-- Manual Block -->
    <div class="bg-white rounded-xl p-6 border border-gray-200 shadow-sm mb-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
            Blokir IP Manual
        </h3>
        <form wire:submit="blockIp" class="flex flex-col sm:flex-row gap-3">
            <input type="text" wire:model="newIp" placeholder="192.168.1.100"
                   class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 transition @error('newIp') border-red-500 @enderror" />
            <input type="text" wire:model="blockReason" placeholder="Alasan (opsional)"
                   class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 transition" />
            <button type="submit"
                    class="px-6 py-2.5 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700 transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                Blokir
            </button>
        </form>
        @error('newIp') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
    </div>

    <!-- Active Blocks -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M18.36 6.64a9 9 0 11-12.73 0M12 2v10"/></svg>
                IP Aktif Diblokir
                <span class="ml-auto text-xs font-normal text-gray-500">{{ $activeBlocks->count() }} IP</span>
            </h3>
        </div>
        @if($activeBlocks->isEmpty())
            <div class="p-8 text-center text-gray-400 text-sm">Tidak ada IP yang diblokir.</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <tr>
                            <th class="px-6 py-3 text-left">IP Address</th>
                            <th class="px-6 py-3 text-left">Alasan</th>
                            <th class="px-6 py-3 text-left">Diblokir</th>
                            <th class="px-6 py-3 text-left">Expired</th>
                            <th class="px-6 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($activeBlocks as $block)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 font-mono text-xs text-gray-900">{{ $block->ip_address }}</td>
                                <td class="px-6 py-3 text-gray-600 text-xs">{{ $block->reason ?: '-' }}</td>
                                <td class="px-6 py-3 text-gray-500 text-xs">{{ $block->blocked_at->format('d M H:i') }}</td>
                                <td class="px-6 py-3 text-xs">
                                    @if($block->expires_at)
                                        <span class="text-yellow-600">{{ $block->expires_at->format('d M H:i') }}</span>
                                    @else
                                        <span class="text-green-600">Permanen</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <button wire:click="unblockIp({{ $block->id }})"
                                            class="text-xs text-green-600 hover:text-green-800 font-medium transition">
                                        Buka Blokir
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Failed Login Logs -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                <svg class="w-4 h-4 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                Login Gagal Terbaru
            </h3>
            <button wire:click="clearOldLogs"
                    class="text-xs text-gray-500 hover:text-red-600 transition">Hapus Log >30 hari</button>
        </div>
        @if($recentLogs->isEmpty())
            <div class="p-8 text-center text-gray-400 text-sm">Belum ada log login gagal.</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <tr>
                            <th class="px-6 py-3 text-left">Email</th>
                            <th class="px-6 py-3 text-left">IP Address</th>
                            <th class="px-6 py-3 text-left">User Agent</th>
                            <th class="px-6 py-3 text-left">Waktu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($recentLogs as $log)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 text-gray-900 text-xs">{{ $log->email }}</td>
                                <td class="px-6 py-3 font-mono text-xs text-gray-700">{{ $log->ip_address }}</td>
                                <td class="px-6 py-3 text-gray-500 text-xs max-w-[200px] truncate">{{ $log->user_agent ?: '-' }}</td>
                                <td class="px-6 py-3 text-gray-500 text-xs">{{ $log->created_at->format('d M H:i:s') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
