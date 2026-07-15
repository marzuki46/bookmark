<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Dead Link Checker</h1>
            <p class="text-sm text-gray-500 mt-1">Hanya link <strong>404 Not Found</strong> yang akan dihapus. Link auth-required (401/403) dianggap hidup.</p>
        </div>
        <div class="flex gap-2">
            @if(!$scanning && !$scanComplete)
                <button wire:click="startScan" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition flex items-center gap-2">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                    Start Scan
                </button>
            @endif
            @if($scanning)
                <button wire:click="stopScan" class="px-5 py-2.5 bg-red-100 hover:bg-red-200 text-red-700 text-sm font-medium rounded-lg transition flex items-center gap-2">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
                    Stop
                </button>
            @endif
            @if($scanComplete)
                <button wire:click="resetScanState" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition flex items-center gap-2">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 4v6"/><path d="M1 20v-6"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/></svg>
                    Scan Again
                </button>
            @endif
            @if($deadCount > 0)
                <button wire:click="removeDead" class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition flex items-center gap-2">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                    Hapus 404 ({{ $deadCount }})
                </button>
            @endif
        </div>
    </div>

    {{-- Progress Bar --}}
    @if($scanning)
    <div wire:poll.500ms="processBatch" class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
        <div class="flex items-center gap-4 mb-4">
            <span class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                <svg class="w-4 h-4 animate-spin text-indigo-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10" stroke-opacity="0.25"/><path d="M12 2a10 10 0 019.95 9" stroke-opacity="1"/></svg>
                Scanning...
            </span>
            <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full bg-indigo-600 rounded-full transition-all duration-300" style="width: {{ $progress }}%"></div>
            </div>
            <span class="text-xs text-gray-500 font-medium">{{ $progress }}% &mdash; {{ $processed }}/{{ $total }}</span>
            <button wire:click="stopScan" class="px-3 py-1.5 bg-red-100 hover:bg-red-200 text-red-700 text-xs font-medium rounded-lg transition">Stop</button>
        </div>
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px">
            <div class="relative overflow-hidden rounded-xl border border-gray-200 p-4">
                <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Total</span>
                <div class="text-3xl font-bold text-gray-900 tracking-tight mt-2">{{ $total }}</div>
            </div>
            <div class="relative overflow-hidden rounded-xl border border-emerald-200 bg-emerald-50/50 p-4">
                <span class="text-[11px] font-semibold text-emerald-500 uppercase tracking-wider">Hidup</span>
                <div class="text-3xl font-bold text-emerald-600 tracking-tight mt-2">{{ $aliveCount }}</div>
            </div>
            <div class="relative overflow-hidden rounded-xl border border-red-200 bg-red-50/50 p-4">
                <span class="text-[11px] font-semibold text-red-500 uppercase tracking-wider">404 Dead</span>
                <div class="text-3xl font-bold text-red-600 tracking-tight mt-2">{{ $deadCount }}</div>
            </div>
            <div class="relative overflow-hidden rounded-xl border border-amber-200 bg-amber-50/50 p-4">
                <span class="text-[11px] font-semibold text-amber-500 uppercase tracking-wider">Timeout</span>
                <div class="text-3xl font-bold text-amber-600 tracking-tight mt-2">{{ $timeoutCount }}</div>
            </div>
        </div>
    </div>
    @endif

    {{-- Stats --}}
    @if($scanComplete && $processed > 0)
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px">
        <div class="relative overflow-hidden rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between mb-3">
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Scanned</span>
                <div class="w-11 h-11 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-gray-900 tracking-tight">{{ $total }}</div>
        </div>
        <div class="relative overflow-hidden rounded-xl border border-emerald-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between mb-3">
                <span class="text-xs font-semibold text-emerald-500 uppercase tracking-wider">Hidup</span>
                <div class="w-11 h-11 rounded-lg bg-emerald-100 flex items-center justify-center text-emerald-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-emerald-600 tracking-tight">{{ $aliveCount }}</div>
        </div>
        <div class="relative overflow-hidden rounded-xl border border-red-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between mb-3">
                <span class="text-xs font-semibold text-red-500 uppercase tracking-wider">404 Dead</span>
                <div class="w-11 h-11 rounded-lg bg-red-100 flex items-center justify-center text-red-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-red-600 tracking-tight">{{ $deadCount }}</div>
        </div>
        <div class="relative overflow-hidden rounded-xl border border-amber-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between mb-3">
                <span class="text-xs font-semibold text-amber-500 uppercase tracking-wider">Timeout</span>
                <div class="w-11 h-11 rounded-lg bg-amber-100 flex items-center justify-center text-amber-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-amber-600 tracking-tight">{{ $timeoutCount }}</div>
        </div>
    </div>
    @endif

    {{-- Results Table --}}
    @if(!empty($results) || $scanning)
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <h2 class="text-sm font-semibold text-gray-900">
                    @if($scanning) Live Results ({{ count($results) }} scanned) @else Scan Results ({{ count($results) }}) @endif
                </h2>
                @if(!empty($results))
                <label class="flex items-center gap-2 text-xs text-gray-500 cursor-pointer">
                    <input type="checkbox" wire:model.live="showDeadOnly" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    Show 404 only
                </label>
                @endif
            </div>
            @if(count($selectedIds) > 0)
                <button wire:click="removeSelected" class="px-4 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-medium rounded-lg transition flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                    Hapus Terpilih ({{ count($selectedIds) }})
                </button>
            @endif
        </div>

        @if(empty($results) && $scanning)
            <div class="p-8 text-center text-gray-400 text-sm">Checking first batch...</div>
        @else
            <div class="divide-y divide-gray-100 max-h-[500px] overflow-y-auto">
                {{-- Header row --}}
                <div class="px-5 py-2 flex items-center gap-3 text-[11px] font-semibold text-gray-400 uppercase tracking-wider bg-gray-50">
                    @php
                        $deadIds = array_column(array_filter($results, fn($r) => $r['status'] === 'dead'), 'id');
                        $allDeadSelected = count($deadIds) > 0 && empty(array_diff($deadIds, $selectedIds));
                    @endphp
                    <div class="flex-shrink-0 w-8">
                        @if(!empty($deadIds))
                            <div wire:click="toggleSelectAll" class="cursor-pointer flex items-center justify-center h-5">
                                <input type="checkbox" {{ $allDeadSelected ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 pointer-events-none">
                            </div>
                        @endif
                    </div>
                    <div class="flex-shrink-0 w-20">Status</div>
                    <div class="flex-shrink-0 w-[220px]">Title</div>
                    <div class="flex-1">URL</div>
                    <div class="flex-shrink-0 w-[160px] text-right">Info</div>
                    <div class="flex-shrink-0 w-16 text-right">Aksi</div>
                </div>

                @foreach($results as $result)
                    @if(!$showDeadOnly || $result['status'] === 'dead')
                    <div class="px-5 py-2.5 flex items-center gap-3 hover:bg-gray-50 transition text-sm {{ $result['status'] === 'dead' ? 'bg-red-50/40' : '' }}">
                        {{-- Checkbox --}}
                        <div class="flex-shrink-0 w-8">
                            @if($result['status'] === 'dead')
                                <div wire:click="toggleSelect({{ $result['id'] }})" class="cursor-pointer flex items-center justify-center h-5">
                                    <input type="checkbox" {{ in_array($result['id'], $selectedIds) ? 'checked' : '' }} class="rounded border-gray-300 text-red-600 focus:ring-red-500 pointer-events-none">
                                </div>
                            @endif
                        </div>

                        {{-- Badge --}}
                        <div class="flex-shrink-0 w-20">
                            @if($result['status'] === 'alive')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-700">{{ $result['code'] }} OK</span>
                            @elseif($result['status'] === 'auth_required')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-blue-100 text-blue-700">{{ $result['code'] }} Login</span>
                            @elseif($result['status'] === 'dead')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-red-100 text-red-700">404</span>
                            @elseif($result['status'] === 'timeout')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-700">Timeout</span>
                            @elseif($result['status'] === 'error')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-orange-100 text-orange-700">Error</span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-gray-100 text-gray-600">{{ $result['status'] }}</span>
                            @endif
                        </div>

                        {{-- Title --}}
                        <div class="flex-shrink-0 w-[220px] truncate font-medium text-gray-900" title="{{ $result['title'] }}">{{ $result['title'] }}</div>

                        {{-- URL --}}
                        <div class="flex-1 truncate text-xs text-gray-400 font-mono" title="{{ $result['url'] }}">{{ $result['url'] }}</div>

                        {{-- Message --}}
                        <div class="flex-shrink-0 text-xs text-gray-500 w-[160px] truncate text-right" title="{{ $result['message'] }}">
                            @if($result['status'] === 'alive') <span class="text-emerald-600">OK</span>
                            @elseif($result['status'] === 'auth_required') <span class="text-blue-600">{{ $result['message'] }}</span>
                            @elseif($result['status'] === 'dead') <span class="text-red-600 font-medium">{{ $result['message'] }}</span>
                            @elseif($result['status'] === 'timeout') <span class="text-amber-600">timeout 10s</span>
                            @else <span class="text-orange-600">{{ $result['message'] }}</span>
                            @endif
                        </div>

                        {{-- Action --}}
                        <div class="flex-shrink-0 w-16 text-right">
                            @if($result['status'] === 'dead')
                                <button wire:click="removeItem({{ $result['id'] }})" class="text-xs font-medium text-red-600 hover:text-red-800">Hapus</button>
                            @else
                                <a href="{{ $result['url'] }}" target="_blank" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">Buka</a>
                            @endif
                        </div>
                    </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
    @endif

    {{-- Empty State --}}
    @if(!$scanning && !$scanComplete)
    <div class="bg-white border border-gray-200 rounded-xl p-12 text-center shadow-sm">
        <div class="w-16 h-16 mx-auto mb-4 rounded-xl bg-indigo-50 flex items-center justify-center">
            <svg class="w-8 h-8 text-indigo-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Check Your Bookmarks</h3>
        <p class="text-sm text-gray-500 max-w-md mx-auto">Scan semua bookmark untuk menemukan link 404 (mati). Link yang butuh login (401/403) dianggap masih hidup.</p>
    </div>
    @endif
</div>
