@extends('layouts.app')

@section('title', 'Activity')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-[var(--text-primary)]">Activity Log</h1>
        <p class="text-sm text-[var(--text-tertiary)] mt-1">Track all changes in your knowledge base</p>
    </div>

    @php
        $recentBookmarks = App\Models\Item::where('user_id', auth()->id())->where('type', 'bookmark')->latest()->take(10)->get();
        $recentNotes = App\Models\Item::where('user_id', auth()->id())->where('type', 'note')->latest()->take(10)->get();
        $recentSnippets = App\Models\Item::where('user_id', auth()->id())->where('type', 'snippet')->latest()->take(5)->get();
        $recentFiles = App\Models\Item::where('user_id', auth()->id())->where('type', 'file')->latest()->take(5)->get();
        $allRecent = App\Models\Item::where('user_id', auth()->id())->latest()->take(20)->get();
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div class="lg:col-span-2">
            <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-[var(--color-border)]">
                    <h2 class="text-sm font-semibold text-[var(--text-primary)]">Recent Timeline</h2>
                </div>
                @if($allRecent->isEmpty())
                    <div class="empty-state py-8">
                        <div class="empty-title">No activity yet</div>
                        <div class="empty-desc">Start adding content to see your activity timeline.</div>
                    </div>
                @else
                    <div class="relative">
                        <div class="absolute left-8 top-0 bottom-0 w-px bg-[var(--color-border)]"></div>
                        <div class="divide-y divide-[var(--color-border)]">
                            @foreach($allRecent as $item)
                                <div class="flex items-start gap-4 px-5 py-4 hover:bg-[var(--color-surface-hover)] transition">
                                    <div class="relative z-10 w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0
                                        @if($item->type === 'bookmark') bg-[var(--indigo-50)] text-[var(--indigo-600)]
                                        @elseif($item->type === 'note') bg-[var(--emerald-50)] text-[var(--emerald-600)]
                                        @elseif($item->type === 'prompt') bg-[var(--violet-50)] text-[var(--violet-600)]
                                        @elseif($item->type === 'snippet') bg-[var(--emerald-50)] text-[var(--emerald-600)]
                                        @elseif($item->type === 'file') bg-[var(--amber-50)] text-[var(--amber-600)]
                                        @else bg-[var(--red-50)] text-[var(--red-500)] @endif">
                                        @if($item->type === 'bookmark')
                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"/></svg>
                                        @elseif($item->type === 'note')
                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>
                                        @elseif($item->type === 'snippet')
                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 18l6-6-6-6"/><path d="M8 6l-6 6 6 6"/></svg>
                                        @else
                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0 pt-1">
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium text-sm text-[var(--text-primary)]">{{ $item->title ?? 'Untitled' }}</span>
                                            <span class="text-[10px] font-semibold uppercase tracking-wider px-1.5 py-0.5 rounded bg-[var(--color-bg)] text-[var(--text-quaternary)]">{{ $item->type }}</span>
                                        </div>
                                        <div class="text-xs text-[var(--text-tertiary)] mt-0.5">Created {{ $item->created_at->diffForHumans() }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="space-y-5">
            <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-[var(--color-border)]">
                    <h2 class="text-sm font-semibold text-[var(--text-primary)]">By Type</h2>
                </div>
                <div class="p-4 space-y-3">
                    @php
                        $counts = [
                            'bookmark' => App\Models\Item::where('user_id', auth()->id())->where('type', 'bookmark')->count(),
                            'note' => App\Models\Item::where('user_id', auth()->id())->where('type', 'note')->count(),
                            'prompt' => App\Models\Item::where('user_id', auth()->id())->where('type', 'prompt')->count(),
                            'snippet' => App\Models\Item::where('user_id', auth()->id())->where('type', 'snippet')->count(),
                            'file' => App\Models\Item::where('user_id', auth()->id())->where('type', 'file')->count(),
                            'secret' => App\Models\Item::where('user_id', auth()->id())->where('type', 'secret')->count(),
                        ];
                        $maxCount = max(max($counts), 1);
                    @endphp
                    @foreach($counts as $type => $count)
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-medium text-[var(--text-secondary)] capitalize">{{ $type }}s</span>
                                <span class="text-xs text-[var(--text-quaternary)]">{{ $count }}</span>
                            </div>
                            <div class="h-2 bg-[var(--color-bg)] rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500
                                    @if($type === 'bookmark') bg-[var(--indigo-500)]
                                    @elseif($type === 'note') bg-[var(--emerald-500)]
                                    @elseif($type === 'prompt') bg-[var(--violet-500)]
                                    @elseif($type === 'snippet') bg-[var(--emerald-500)]
                                    @elseif($type === 'file') bg-[var(--amber-500)]
                                    @else bg-[var(--red-500)] @endif" style="width: {{ $maxCount > 0 ? ($count / $maxCount * 100) : 0 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
