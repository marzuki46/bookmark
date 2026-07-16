<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-[var(--text-primary)]">AI Center</h1>
            <p class="text-sm text-[var(--text-tertiary)] mt-1">AI-powered insights for your knowledge base</p>
        </div>
        @if(! $isAiConfigured)
            <a href="{{ route('settings') }}" class="px-4 py-2 text-sm font-medium rounded-lg bg-[var(--amber-100)] text-[var(--amber-700)] border border-[var(--amber-200)] hover:bg-[var(--amber-200)] transition">
                Configure AI
            </a>
        @endif
    </div>

    @if(! $isAiConfigured)
        <div class="mb-6 px-5 py-4 rounded-xl bg-[var(--amber-50)] border border-[var(--amber-200)]">
            <p class="text-sm text-[var(--amber-700)] font-medium">AI belum dikonfigurasi.</p>
            <p class="text-xs text-[var(--amber-600)] mt-1">Silakan atur API key di <a href="{{ route('settings') }}" class="underline">Settings</a> untuk mengaktifkan fitur AI.</p>
        </div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3 mb-6">
        @foreach($stats as $label => $value)
            <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-3 text-center">
                <div class="text-xl font-bold text-[var(--text-primary)]">{{ $value }}</div>
                <div class="text-[10px] text-[var(--text-tertiary)] uppercase tracking-wider">{{ $label }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        {{-- Left: Features + Items --}}
        <div class="lg:col-span-2 space-y-5">
            {{-- Quick AI Actions --}}
            <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-[var(--color-border)]">
                    <h2 class="text-sm font-semibold text-[var(--text-primary)]">Quick AI Actions</h2>
                </div>
                <div class="p-5">
                    <div class="flex items-center gap-2 mb-3">
                        <input type="text" wire:model="batchInput" wire:keydown.enter="generateFromInput"
                            class="flex-1 px-3 py-2 text-sm bg-[var(--color-bg)] border border-[var(--color-border)] rounded-lg focus:outline-none focus:border-[var(--indigo-500)]"
                            placeholder="Ask AI anything about your data... (e.g. 'Ringkas semua bookmark saya')">
                        <button wire:click="generateFromInput" {{ $processing ? 'disabled' : '' }}
                            class="btn-primary text-sm {{ $processing ? 'opacity-50' : '' }}">
                            @if($processing)
                                <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10" stroke-dasharray="30 60"/></svg>
                            @else
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5a3 3 0 10-3 3.85A5 5 0 1112 5"/></svg>
                            @endif
                        </button>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button wire:click="$set('batchInput', 'Ringkasan semua data saya'); $wire.generateFromInput()" class="text-xs px-3 py-1.5 rounded-full bg-[var(--color-bg)] border border-[var(--color-border)] hover:bg-[var(--indigo-50)] hover:border-[var(--indigo-300)] transition">Overview</button>
                        <button wire:click="$set('batchInput', 'Apa saja bookmark terbaru saya?'); $wire.generateFromInput()" class="text-xs px-3 py-1.5 rounded-full bg-[var(--color-bg)] border border-[var(--color-border)] hover:bg-[var(--indigo-50)] hover:border-[var(--indigo-300)] transition">Recent Bookmarks</button>
                        <button wire:click="$set('batchInput', 'Saran organisasi untuk knowledge base saya'); $wire.generateFromInput()" class="text-xs px-3 py-1.5 rounded-full bg-[var(--color-bg)] border border-[var(--color-border)] hover:bg-[var(--indigo-50)] hover:border-[var(--indigo-300)] transition">Organization Tips</button>
                        <button wire:click="$set('batchInput', 'Apa todo yang belum selesai?'); $wire.generateFromInput()" class="text-xs px-3 py-1.5 rounded-full bg-[var(--color-bg)] border border-[var(--color-border)] hover:bg-[var(--indigo-50)] hover:border-[var(--indigo-300)] transition">Pending Tasks</button>
                    </div>
                </div>
            </div>

            {{-- AI Result --}}
            @if($aiResult)
                <div class="bg-[var(--color-surface)] border border-[var(--indigo-200)] rounded-xl overflow-hidden">
                    <div class="px-5 py-3 border-b border-[var(--indigo-100)] flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-[var(--indigo-700)]">AI Response</h2>
                        <button wire:click="clearResult" class="text-xs text-[var(--indigo-500)] hover:text-[var(--indigo-700)]">Clear</button>
                    </div>
                    <div class="p-5 text-sm text-[var(--text-primary)] leading-relaxed whitespace-pre-wrap">{{ $aiResult }}</div>
                </div>
            @endif

            {{-- Recent Items --}}
            <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-[var(--color-border)]">
                    <h2 class="text-sm font-semibold text-[var(--text-primary)]">Recent Items - AI Actions</h2>
                </div>
                <div>
                    @forelse($recentItems as $item)
                        <div class="flex items-center gap-3 px-5 py-3 border-b border-[var(--color-border)] last:border-b-0 hover:bg-[var(--color-bg)] transition">
                            <span class="w-2 h-2 rounded-full flex-shrink-0 @if($item->type === 'bookmark') bg-[var(--indigo-500)] @elseif($item->type === 'note') bg-[var(--emerald-500)] @elseif($item->type === 'snippet') bg-[var(--amber-500)] @elseif($item->type === 'worksheet') bg-[var(--violet-500)] @else bg-[var(--text-quaternary)] @endif"></span>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm text-[var(--text-primary)] truncate">{{ $item->title ?? 'Untitled' }}</div>
                                <div class="text-[10px] text-[var(--text-quaternary)]">{{ $item->type }} &middot; {{ $item->created_at->diffForHumans() }}</div>
                            </div>
                            <div class="flex items-center gap-1 flex-shrink-0">
                                <button wire:click="summarizeItem({{ $item->id }})" {{ $processing ? 'disabled' : '' }}
                                    class="text-[10px] px-2 py-1 rounded bg-[var(--indigo-50)] text-[var(--indigo-600)] hover:bg-[var(--indigo-100)] transition" title="Summarize">
                                    Summarize
                                </button>
                                <button wire:click="suggestTagsForItem({{ $item->id }})" {{ $processing ? 'disabled' : '' }}
                                    class="text-[10px] px-2 py-1 rounded bg-[var(--emerald-50)] text-[var(--emerald-600)] hover:bg-[var(--emerald-100)] transition" title="Suggest Tags">
                                    Tags
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="p-5 text-center text-sm text-[var(--text-tertiary)]">No items yet</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Right sidebar --}}
        <div class="space-y-5">
            {{-- AI Status --}}
            <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-[var(--color-border)]">
                    <h2 class="text-sm font-semibold text-[var(--text-primary)]">AI Status</h2>
                </div>
                <div class="p-5 space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-[var(--text-tertiary)]">Status</span>
                        <span class="px-2 py-0.5 rounded-full text-xs {{ $isAiConfigured ? 'bg-[var(--emerald-50)] text-[var(--emerald-700)]' : 'bg-red-50 text-red-700' }}">
                            {{ $isAiConfigured ? 'Configured' : 'Not configured' }}
                        </span>
                    </div>
                    @if($isAiConfigured)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-[var(--text-tertiary)]">Provider</span>
                            <span class="font-medium text-[var(--text-primary)]">{{ (new \App\Services\AIService(auth()->id()))->getSettings()['provider'] ?? 'Custom' }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-[var(--text-tertiary)]">Model</span>
                            <span class="font-medium text-[var(--text-primary)]">{{ (new \App\Services\AIService(auth()->id()))->getSettings()['model'] ?? '-' }}</span>
                        </div>
                    @endif
                    <hr class="border-[var(--color-border)]">
                    <a href="{{ route('settings') }}" class="block w-full text-center px-3 py-2 text-xs font-medium rounded-lg border border-[var(--color-border)] hover:bg-[var(--color-bg)] transition">Settings</a>
                </div>
            </div>

            {{-- AI Features Guide --}}
            <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-[var(--color-border)]">
                    <h2 class="text-sm font-semibold text-[var(--text-primary)]">AI Features</h2>
                </div>
                <div class="p-4 space-y-2">
                    @php
                        $features = [
                            ['name' => 'AI Chat Widget', 'desc' => 'Floating chat, bottom-right corner', 'color' => 'indigo'],
                            ['name' => 'Quick Summary', 'desc' => 'Click Summarize on any item', 'color' => 'emerald'],
                            ['name' => 'Tag Suggestions', 'desc' => 'AI suggests tags for items', 'color' => 'amber'],
                            ['name' => 'Notulensi AI', 'desc' => 'Generate meeting notes from text', 'color' => 'violet'],
                            ['name' => 'Dashboard Insight', 'desc' => 'AI overview of your knowledge base', 'color' => 'cyan'],
                            ['name' => 'Web Search', 'desc' => 'Prefix "search" to search the web', 'color' => 'rose'],
                        ];
                    @endphp
                    @foreach($features as $f)
                        <div class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-[var(--color-bg)] transition">
                            <span class="w-2 h-2 rounded-full flex-shrink-0 @if($f['color'] === 'indigo') bg-[var(--indigo-500)] @elseif($f['color'] === 'emerald') bg-[var(--emerald-500)] @elseif($f['color'] === 'amber') bg-[var(--amber-500)] @elseif($f['color'] === 'violet') bg-[var(--violet-500)] @elseif($f['color'] === 'cyan') bg-[var(--cyan-500)] @else bg-[var(--rose-500)] @endif"></span>
                            <div>
                                <div class="text-xs font-medium text-[var(--text-primary)]">{{ $f['name'] }}</div>
                                <div class="text-[10px] text-[var(--text-quaternary)]">{{ $f['desc'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
