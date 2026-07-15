@extends('layouts.app')

@section('title', 'AI Center')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-[var(--text-primary)]">AI Center</h1>
        <p class="text-sm text-[var(--text-tertiary)] mt-1">AI-powered insights for your knowledge base</p>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-4">
            <div class="text-xs font-semibold text-[var(--text-tertiary)] uppercase tracking-wider mb-1">Total Items</div>
            <div class="text-2xl font-bold text-[var(--text-primary)]">{{ $stats['totalItems'] }}</div>
        </div>
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-4">
            <div class="text-xs font-semibold text-[var(--text-tertiary)] uppercase tracking-wider mb-1">Bookmarks</div>
            <div class="text-2xl font-bold text-[var(--indigo-600)]">{{ $stats['bookmarks'] }}</div>
        </div>
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-4">
            <div class="text-xs font-semibold text-[var(--text-tertiary)] uppercase tracking-wider mb-1">Tags</div>
            <div class="text-2xl font-bold text-[var(--emerald-600)]">{{ $stats['tags'] }}</div>
        </div>
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-4">
            <div class="text-xs font-semibold text-[var(--text-tertiary)] uppercase tracking-wider mb-1">Collections</div>
            <div class="text-2xl font-bold text-[var(--violet-600)]">{{ $stats['collections'] }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-[var(--color-border)]">
                <h2 class="text-sm font-semibold text-[var(--text-primary)]">AI Features</h2>
            </div>
            <div class="p-4 space-y-3">
                @php
                    $features = [
                        ['name' => 'Auto Summary', 'desc' => 'Automatically generate summaries for bookmarks and notes', 'icon' => 'M4 6h16M4 12h16M4 18h7', 'color' => 'indigo'],
                        ['name' => 'Auto Category', 'desc' => 'AI suggests categories based on content', 'icon' => 'M4 6h16M4 10h16M4 14h16M4 18h16', 'color' => 'emerald'],
                        ['name' => 'Auto Tagging', 'desc' => 'Automatically assign relevant tags', 'icon' => 'M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z', 'color' => 'amber'],
                        ['name' => 'Duplicate Detection', 'desc' => 'Find and merge duplicate content', 'icon' => 'M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2', 'color' => 'red'],
                        ['name' => 'Content Relationship', 'desc' => 'Discover connections between items', 'icon' => 'M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71', 'color' => 'violet'],
                        ['name' => 'Knowledge Gap', 'desc' => 'Identify missing topics in your knowledge base', 'icon' => 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z', 'color' => 'amber'],
                    ];
                @endphp
                @foreach($features as $feature)
                    <div class="flex items-center gap-3 p-3 rounded-lg border border-[var(--color-border)] hover:bg-[var(--color-surface-hover)] transition cursor-pointer">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 @if($feature['color'] === 'indigo') bg-[var(--indigo-50)] text-[var(--indigo-600)] @elseif($feature['color'] === 'emerald') bg-[var(--emerald-50)] text-[var(--emerald-600)] @elseif($feature['color'] === 'amber') bg-[var(--amber-50)] text-[var(--amber-600)] @elseif($feature['color'] === 'red') bg-[var(--red-50)] text-[var(--red-500)] @else bg-[var(--violet-50)] text-[var(--violet-600)] @endif">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $feature['icon'] }}"/></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-sm text-[var(--text-primary)]">{{ $feature['name'] }}</div>
                            <div class="text-xs text-[var(--text-tertiary)]">{{ $feature['desc'] }}</div>
                        </div>
                        <svg class="w-4 h-4 text-[var(--text-quaternary)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="space-y-5">
            <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-[var(--color-border)]">
                    <h2 class="text-sm font-semibold text-[var(--text-primary)]">Top Tags</h2>
                </div>
                <div class="p-4">
                    @if($topTags->isEmpty())
                        <p class="text-sm text-[var(--text-tertiary)] text-center py-4">No tags yet</p>
                    @else
                        <div class="space-y-2">
                            @foreach($topTags as $tag)
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-[var(--text-secondary)]">{{ $tag->name }}</span>
                                    <span class="text-xs text-[var(--text-quaternary)] bg-[var(--color-bg)] px-2 py-0.5 rounded-full">{{ $tag->items_count }} items</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-[var(--color-border)]">
                    <h2 class="text-sm font-semibold text-[var(--text-primary)]">Recent Activity</h2>
                </div>
                <div class="p-4">
                    @if($recentItems->isEmpty())
                        <p class="text-sm text-[var(--text-tertiary)] text-center py-4">No items yet</p>
                    @else
                        <div class="space-y-3">
                            @foreach($recentItems->take(5) as $item)
                                <div class="flex items-center gap-3">
                                    <span class="w-2 h-2 rounded-full @if($item->type === 'bookmark') bg-[var(--indigo-500)] @elseif($item->type === 'note') bg-[var(--emerald-500)] @elseif($item->type === 'prompt') bg-[var(--violet-500)] @else bg-[var(--amber-500)] @endif flex-shrink-0"></span>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm text-[var(--text-primary)] truncate">{{ $item->title ?? 'Untitled' }}</div>
                                        <div class="text-xs text-[var(--text-quaternary)]">{{ $item->type }} &middot; {{ $item->created_at->diffForHumans() }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
