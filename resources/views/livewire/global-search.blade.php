<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-[var(--text-primary)]">Search</h1>
        <p class="text-sm text-[var(--text-tertiary)] mt-1">Search across all your content</p>
    </div>

    <div class="relative mb-5">
        <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-[var(--text-quaternary)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" wire:model.live.debounce.300ms="query" placeholder="Type to search bookmarks, notes, snippets, files, secrets..."
            class="wp-form-input !pl-12 !py-3 text-base" autofocus>
    </div>

    <div class="flex flex-wrap gap-2 mb-5">
        @php
            $types = [
                'all' => ['All', 'var(--text-primary)'],
                'bookmark' => ['Bookmarks', 'var(--indigo-600)'],
                'note' => ['Notes', 'var(--emerald-600)'],
                'prompt' => ['Prompts', 'var(--violet-600)'],
                'snippet' => ['Snippets', 'var(--emerald-600)'],
                'file' => ['Files', 'var(--amber-600)'],
                'secret' => ['Secrets', 'var(--red-500)'],
            ];
        @endphp
        @foreach($types as $key => $label)
            <button wire:click="$set('type', '{{ $key }}')" class="px-3 py-1.5 text-xs font-medium rounded-lg border transition {{ $type === $key ? 'bg-[var(--text-primary)] text-white border-[var(--text-primary)]' : 'border-[var(--color-border)] text-[var(--text-tertiary)] hover:border-[var(--text-secondary)]' }}">
                {{ $label[0] }}
                @if(isset($counts[$key]))
                    <span class="ml-1 opacity-60">{{ $counts[$key] }}</span>
                @endif
            </button>
        @endforeach
    </div>

    <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
        @if(empty($this->results) && strlen($this->query) < 2)
            <div class="empty-state">
                <div class="empty-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </div>
                <div class="empty-title">Search your knowledge</div>
                <div class="empty-desc">Type at least 2 characters to search across all your bookmarks, notes, snippets, files, and secrets.</div>
            </div>
        @elseif(empty($this->results) && strlen($this->query) >= 2)
            <div class="empty-state">
                <div class="empty-icon" style="background: var(--amber-50); color: var(--amber-600);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                </div>
                <div class="empty-title">No results found</div>
                <div class="empty-desc">Try different keywords or check the type filter.</div>
            </div>
        @else
            <div class="divide-y divide-[var(--color-border)]">
                @foreach($this->results as $result)
                    <div class="flex items-start gap-4 px-5 py-4 hover:bg-[var(--color-surface-hover)] transition">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5
                            @if($result['type'] === 'bookmark') bg-[var(--indigo-50)] text-[var(--indigo-600)]
                            @elseif($result['type'] === 'note') bg-[var(--emerald-50)] text-[var(--emerald-600)]
                            @elseif($result['type'] === 'prompt') bg-[var(--violet-50)] text-[var(--violet-600)]
                            @elseif($result['type'] === 'snippet') bg-[var(--emerald-50)] text-[var(--emerald-600)]
                            @elseif($result['type'] === 'file') bg-[var(--amber-50)] text-[var(--amber-600)]
                            @else bg-[var(--red-50)] text-[var(--red-500)] @endif">
                            @if($result['type'] === 'bookmark')
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"/></svg>
                            @elseif($result['type'] === 'note')
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>
                            @elseif($result['type'] === 'prompt')
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4"/><path d="M12 18v4"/></svg>
                            @elseif($result['type'] === 'snippet')
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 18l6-6-6-6"/><path d="M8 6l-6 6 6 6"/></svg>
                            @elseif($result['type'] === 'file')
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>
                            @else
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <a href="@if($result['type'] === 'bookmark') {{ $result['url'] ?? '#' }} @else # @endif" target="_blank" class="font-medium text-sm text-[var(--text-primary)] hover:text-[var(--indigo-600)] transition truncate">{{ $result['title'] ?? 'Untitled' }}</a>
                                <span class="text-[10px] font-semibold uppercase tracking-wider px-1.5 py-0.5 rounded bg-[var(--color-bg)] text-[var(--text-quaternary)]">{{ $result['type'] }}</span>
                            </div>
                            @if($result['url'] ?? null)
                                <div class="text-xs text-[var(--text-quaternary)] font-mono truncate mb-1">{{ $result['url'] }}</div>
                            @endif
                            @if($result['content'] ?? null)
                                <p class="text-xs text-[var(--text-tertiary)] line-clamp-2">{{ Str::limit(strip_tags($result['content']), 150) }}</p>
                            @endif
                        </div>
                        <span class="text-xs text-[var(--text-quaternary)] flex-shrink-0">{{ \Carbon\Carbon::parse($result['created_at'])->diffForHumans() }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
