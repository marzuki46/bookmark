@extends('layouts.app')

@section('title', 'Dashboard')

@push('head')
<style>
    :root {
        --space-1: 0.25rem;   /* 4px */
        --space-2: 0.5rem;    /* 8px */
        --space-3: 0.75rem;   /* 12px */
        --space-4: 1rem;      /* 16px */
        --space-5: 1.25rem;   /* 20px */
        --space-6: 1.5rem;    /* 24px */
        --space-8: 2rem;      /* 32px */
        --space-10: 2.5rem;   /* 40px */
        --space-12: 3rem;     /* 48px */

        --radius-sm: 0.375rem;  /* 6px */
        --radius-md: 0.5rem;    /* 8px */
        --radius-lg: 0.75rem;   /* 12px */
        --radius-xl: 1rem;      /* 16px */
        --radius-2xl: 1.5rem;   /* 24px */

        --shadow-xs: 0 1px 2px 0 rgb(0 0 0 / 0.03);
        --shadow-sm: 0 1px 3px 0 rgb(0 0 0 / 0.06), 0 1px 2px -1px rgb(0 0 0 / 0.06);
        --shadow-md: 0 4px 8px -2px rgb(0 0 0 / 0.07), 0 2px 4px -2px rgb(0 0 0 / 0.04);
        --shadow-lg: 0 12px 24px -8px rgb(0 0 0 / 0.08), 0 4px 8px -4px rgb(0 0 0 / 0.05);
        --shadow-xl: 0 20px 40px -12px rgb(0 0 0 / 0.1), 0 8px 16px -8px rgb(0 0 0 / 0.06);

        --color-bg: #f8fafc;
        --color-surface: #ffffff;
        --color-surface-hover: #f1f5f9;
        --color-border: #e2e8f0;
        --color-border-strong: #cbd5e1;

        --text-primary: #0f172a;
        --text-secondary: #334155;
        --text-tertiary: #64748b;
        --text-quaternary: #94a3b8;

        --indigo-50: #eef2ff;
        --indigo-100: #e0e7ff;
        --indigo-500: #6366f1;
        --indigo-600: #4f46e5;
        --indigo-700: #4338ca;

        --emerald-50: #ecfdf5;
        --emerald-100: #d1fae5;
        --emerald-500: #10b981;
        --emerald-600: #059669;

        --amber-50: #fffbeb;
        --amber-100: #fef3c7;
        --amber-500: #f59e0b;
        --amber-600: #d97706;

        --violet-50: #f5f3ff;
        --violet-100: #ede9fe;
        --violet-500: #8b5cf6;
        --violet-600: #7c3aed;

        --red-50: #fef2f2;
        --red-500: #ef4444;
        --red-600: #dc2626;
    }

    .page { padding: var(--space-6) var(--space-6); max-width: 1400px; margin: 0 auto; }
    @media (min-width: 1024px) { .page { padding: var(--space-8) var(--space-8); } }

    .page-header { margin-bottom: var(--space-6); }
    .page-title { font-size: 1.875rem; font-weight: 700; color: var(--text-primary); letter-spacing: -0.02em; line-height: 1.2; }
    .page-subtitle { font-size: 0.875rem; color: var(--text-tertiary); margin-top: var(--space-1); font-weight: 400; }

    .btn-primary {
        display: inline-flex; align-items: center; gap: var(--space-2);
        padding: var(--space-2) var(--space-4); font-size: 0.875rem; font-weight: 500;
        color: white; background: var(--indigo-600); border: none; border-radius: var(--radius-md);
        cursor: pointer; transition: background 0.15s ease, box-shadow 0.15s ease;
        text-decoration: none;
    }
    .btn-primary:hover { background: var(--indigo-700); box-shadow: var(--shadow-md); }
    .btn-primary:active { background: var(--indigo-700); transform: scale(0.98); }
    .btn-primary:focus-visible { outline: 2px solid var(--indigo-500); outline-offset: 2px; }

    .btn-ghost {
        display: inline-flex; align-items: center; gap: var(--space-2);
        padding: var(--space-2) var(--space-3); font-size: 0.8125rem; font-weight: 500;
        color: var(--text-secondary); background: transparent; border: 1px solid var(--color-border);
        border-radius: var(--radius-md); cursor: pointer; transition: all 0.15s ease;
        text-decoration: none;
    }
    .btn-ghost:hover { background: var(--color-surface-hover); border-color: var(--color-border-strong); color: var(--text-primary); }

    /* Stat Cards */
    .stats-grid { display: grid; grid-template-columns: 1fr; gap: var(--space-4); margin-bottom: var(--space-6); }
    @media (min-width: 640px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (min-width: 1024px) { .stats-grid { grid-template-columns: repeat(4, 1fr); } }

    .stat-card {
        background: var(--color-surface);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-xl);
        padding: var(--space-5);
        transition: all 0.2s ease;
        position: relative;
        overflow: hidden;
    }
    .stat-card::before {
        content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
        background: linear-gradient(90deg, var(--accent-start), var(--accent-end));
        opacity: 0; transition: opacity 0.2s ease;
    }
    .stat-card:hover { box-shadow: var(--shadow-lg); transform: translateY(-2px); border-color: var(--color-border-strong); }
    .stat-card:hover::before { opacity: 1; }

    .stat-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: var(--space-3); }
    .stat-label { font-size: 0.75rem; font-weight: 600; color: var(--text-tertiary); text-transform: uppercase; letter-spacing: 0.05em; line-height: 1.4; }
    .stat-icon { width: 44px; height: 44px; border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .stat-icon svg { width: 22px; height: 22px; }
    .stat-value { font-size: 2.25rem; font-weight: 700; color: var(--text-primary); line-height: 1.1; letter-spacing: -0.02em; }
    .stat-trend { display: inline-flex; align-items: center; gap: var(--space-1); font-size: 0.75rem; font-weight: 500; margin-top: var(--space-3); padding-top: var(--space-3); border-top: 1px solid var(--color-border); }
    .stat-trend.positive { color: var(--emerald-600); }
    .stat-trend svg { width: 12px; height: 12px; flex-shrink: 0; }

    /* Sections */
    .section { margin-bottom: var(--space-6); }
    .section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: var(--space-4); flex-wrap: wrap; gap: var(--space-3); }
    .section-title { font-size: 1rem; font-weight: 600; color: var(--text-primary); letter-spacing: -0.01em; }
    .section-link { font-size: 0.8125rem; font-weight: 500; color: var(--indigo-600); text-decoration: none; transition: color 0.15s ease; }
    .section-link:hover { color: var(--indigo-700); text-decoration: underline; }

    /* Grid Layout */
    .main-grid { display: grid; grid-template-columns: 1fr; gap: var(--space-5); }
    @media (min-width: 1024px) { .main-grid { grid-template-columns: 280px 1fr; } }

    /* Panels */
    .panel { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-xl); overflow: hidden; }
    .panel-header { padding: var(--space-4) var(--space-5); border-bottom: 1px solid var(--color-border); display: flex; align-items: center; justify-content: space-between; }
    .panel-title { font-size: 0.875rem; font-weight: 600; color: var(--text-primary); }
    .panel-body { padding: var(--space-2) var(--space-0); }

    /* Action Cards */
    .action-list { display: flex; flex-direction: column; gap: var(--space-2); }
    .action-card {
        display: flex; align-items: center; gap: var(--space-3);
        padding: var(--space-3) var(--space-4);
        border-radius: var(--radius-lg);
        background: var(--color-surface);
        border: 1px solid var(--color-border);
        text-decoration: none;
        color: inherit;
        transition: all 0.15s ease;
    }
    .action-card:hover { background: var(--color-surface-hover); border-color: var(--color-border-strong); transform: translateX(2px); }
    .action-card:focus-visible { outline: 2px solid var(--indigo-500); outline-offset: 2px; }
    .action-icon { width: 36px; height: 36px; border-radius: var(--radius-md); background: var(--indigo-50); color: var(--indigo-600); display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: all 0.15s ease; }
    .action-card:hover .action-icon { background: var(--indigo-100); }
    .action-icon svg { width: 18px; height: 18px; }
    .action-content { flex: 1; min-width: 0; }
    .action-label { font-size: 0.875rem; font-weight: 500; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .action-desc { font-size: 0.75rem; color: var(--text-tertiary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 1px; }
    .action-chevron { width: 18px; height: 18px; color: var(--text-quaternary); flex-shrink: 0; transition: transform 0.15s ease; }
    .action-card:hover .action-chevron { transform: translateX(2px); color: var(--text-tertiary); }

    /* Insight Cards */
    .insight-list { display: flex; flex-direction: column; gap: var(--space-2); }
    .insight-card {
        display: flex; align-items: center; gap: var(--space-3);
        padding: var(--space-3) var(--space-4);
        border-radius: var(--radius-lg);
        background: var(--color-surface);
        border: 1px solid var(--color-border);
        transition: all 0.15s ease;
    }
    .insight-card:hover { background: var(--color-surface-hover); border-color: var(--color-border-strong); }
    .insight-icon { width: 32px; height: 32px; border-radius: var(--radius-md); background: var(--color-bg); color: var(--text-tertiary); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .insight-icon svg { width: 16px; height: 16px; }
    .insight-content { flex: 1; min-width: 0; }
    .insight-label { font-size: 0.75rem; font-weight: 500; color: var(--text-tertiary); text-transform: uppercase; letter-spacing: 0.03em; }
    .insight-value { font-size: 0.875rem; font-weight: 600; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 1px; }

    /* Bookmark List */
    .bookmark-list { display: flex; flex-direction: column; }
    .bookmark-item {
        display: flex; align-items: flex-start; gap: var(--space-4);
        padding: var(--space-4) var(--space-5);
        border-bottom: 1px solid var(--color-border);
        transition: background 0.15s ease;
    }
    .bookmark-item:last-child { border-bottom: none; }
    .bookmark-item:hover { background: var(--color-surface-hover); }
    .bookmark-favicon { width: 36px; height: 36px; border-radius: var(--radius-md); object-fit: cover; flex-shrink: 0; border: 1px solid var(--color-border); background: var(--color-bg); }
    .bookmark-favicon-placeholder { width: 36px; height: 36px; border-radius: var(--radius-md); background: var(--color-bg); border: 1px solid var(--color-border); display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: var(--text-quaternary); }
    .bookmark-favicon-placeholder svg { width: 18px; height: 18px; }
    .bookmark-main { flex: 1; min-width: 0; }
    .bookmark-header { display: flex; align-items: flex-start; gap: var(--space-2); flex-wrap: wrap; margin-bottom: var(--space-1); }
    .bookmark-title { font-size: 0.875rem; font-weight: 500; color: var(--text-primary); text-decoration: none; line-height: 1.4; transition: color 0.15s ease; }
    .bookmark-title:hover { color: var(--indigo-600); }
    .bookmark-type { font-size: 0.625rem; font-weight: 600; color: var(--text-tertiary); background: var(--color-bg); padding: 2px 8px; border-radius: 9999px; text-transform: uppercase; letter-spacing: 0.03em; white-space: nowrap; flex-shrink: 0; margin-top: 2px; }
    .bookmark-url { font-size: 0.75rem; color: var(--text-tertiary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: var(--space-2); font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
    .bookmark-tags { display: flex; flex-wrap: wrap; gap: var(--space-1.5); margin-bottom: var(--space-2); }
    .bookmark-tag { font-size: 0.6875rem; font-weight: 500; color: var(--indigo-600); background: var(--indigo-50); padding: 3px 10px; border-radius: 9999px; white-space: nowrap; }
    .bookmark-tag-more { font-size: 0.6875rem; font-weight: 500; color: var(--text-quaternary); background: var(--color-bg); padding: 3px 10px; border-radius: 9999px; }
    .bookmark-ai-summary { font-size: 0.75rem; color: var(--text-tertiary); line-height: 1.5; background: var(--color-bg); padding: var(--space-2) var(--space-3); border-radius: var(--radius-md); border-left: 2px solid var(--indigo-500); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .bookmark-meta { display: flex; flex-direction: column; align-items: flex-end; gap: var(--space-1); flex-shrink: 0; min-width: 100px; text-align: right; }
    .bookmark-time { font-size: 0.75rem; color: var(--text-quaternary); white-space: nowrap; }
    .bookmark-readtime { font-size: 0.6875rem; color: var(--text-quaternary); background: var(--color-bg); padding: 2px 8px; border-radius: var(--radius-sm); font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }

    /* Empty State */
    .empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: var(--space-12) var(--space-6); text-align: center; }
    .empty-icon { width: 64px; height: 64px; border-radius: var(--radius-xl); background: var(--indigo-50); color: var(--indigo-600); display: flex; align-items: center; justify-content: center; margin-bottom: var(--space-4); }
    .empty-icon svg { width: 28px; height: 28px; }
    .empty-title { font-size: 1rem; font-weight: 600; color: var(--text-primary); margin-bottom: var(--space-1); }
    .empty-desc { font-size: 0.875rem; color: var(--text-tertiary); margin-bottom: var(--space-4); max-width: 280px; }
    .empty-action { display: inline-flex; align-items: center; gap: var(--space-2); }

    /* Responsive */
    @media (max-width: 639px) {
        .page { padding: var(--space-4); }
        .bookmark-meta { min-width: 80px; }
        .bookmark-title { max-width: 100%; }
    }
</style>
@endpush

@section('content')
<div class="page">
    <!-- Page Header -->
    <header class="page-header flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h1 class="page-title">Dashboard</h1>
            <p class="page-subtitle">Welcome back, {{ auth()->user()->name }}. Here's your knowledge overview.</p>
        </div>
        <a href="{{ route('bookmarks') }}" class="btn-primary">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Bookmark
        </a>
    </header>

    <!-- Stats Grid -->
    <div class="stats-grid">
        @php
            $stats = [
                ['label' => 'Bookmarks', 'value' => $totalBookmarks, 'icon' => 'bookmark', 'accentStart' => '#6366f1', 'accentEnd' => '#8b5cf6', 'trend' => '+12%', 'trendLabel' => 'this month'],
                ['label' => 'Notes', 'value' => $totalNotes, 'icon' => 'note', 'accentStart' => '#10b981', 'accentEnd' => '#34d399', 'trend' => '+8%', 'trendLabel' => 'this month'],
                ['label' => 'Tags', 'value' => $totalTags, 'icon' => 'tag', 'accentStart' => '#f59e0b', 'accentEnd' => '#fbbf24', 'trend' => '+5%', 'trendLabel' => 'this month'],
                ['label' => 'Collections', 'value' => $totalCollections, 'icon' => 'folder', 'accentStart' => '#8b5cf6', 'accentEnd' => '#a78bfa', 'trend' => '+3%', 'trendLabel' => 'this month'],
            ];
            $icons = [
                'bookmark' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>',
                'note' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>',
                'tag' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>',
                'folder' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>',
            ];
        @endphp

        @foreach($stats as $stat)
            <article class="stat-card" style="--accent-start: {{ $stat['accentStart'] }}; --accent-end: {{ $stat['accentEnd'] }}">
                <div class="stat-header">
                    <span class="stat-label">{{ $stat['label'] }}</span>
                    <div class="stat-icon" style="background: {{ $stat['accentStart'] }}15; color: {{ $stat['accentStart'] }}">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $icons[$stat['icon']] !!}</svg>
                    </div>
                </div>
                <div class="stat-value">{{ $stat['value'] }}</div>
                <div class="stat-trend positive">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                    <span>{{ $stat['trend'] }} {{ $stat['trendLabel'] }}</span>
                </div>
            </article>
        @endforeach
    </div>

    <!-- Main Grid -->
    <div class="main-grid">
        <!-- Sidebar: Quick Actions + Insights -->
        <aside class="space-y-5">
            <!-- Quick Actions -->
            <section class="panel">
                <header class="panel-header">
                    <h2 class="panel-title">Quick Actions</h2>
                </header>
                <div class="panel-body">
                    <nav class="action-list">
                        @php
                            $actions = [
                                ['label' => 'Save Bookmark', 'desc' => 'Add URL, note, or snippet', 'icon' => 'plus', 'href' => route('bookmarks')],
                                ['label' => 'Create Note', 'desc' => 'Write a new note', 'icon' => 'pencil', 'href' => route('notes')],
                                ['label' => 'New Collection', 'desc' => 'Organize your items', 'icon' => 'folder-plus', 'href' => route('collections')],
                                ['label' => 'AI Summarize', 'desc' => 'Generate summary with AI', 'icon' => 'sparkles', 'href' => route('ai')],
                            ];
                            $aIcons = [
                                'plus' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>',
                                'pencil' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>',
                                'folder-plus' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2zM9 12h6m-6 0v6"/>',
                                'sparkles' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>',
                            ];
                        @endphp
                        @foreach($actions as $action)
                            <a href="{{ $action['href'] }}" class="action-card">
                                <span class="action-icon" style="background: var(--indigo-50); color: var(--indigo-600);">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $aIcons[$action['icon']] !!}</svg>
                                </span>
                                <div class="action-content">
                                    <span class="action-label">{{ $action['label'] }}</span>
                                    <span class="action-desc">{{ $action['desc'] }}</span>
                                </div>
                                <svg class="action-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        @endforeach
                    </nav>
                </div>
            </section>

            <!-- Insights -->
            <section class="panel">
                <header class="panel-header">
                    <h2 class="panel-title">Insights</h2>
                </header>
                <div class="panel-body">
                    <div class="insight-list">
                        @php
                            $insights = [
                                ['label' => 'Most used tag', 'value' => 'Laravel', 'icon' => 'tag'],
                                ['label' => 'Top domain', 'value' => 'github.com', 'icon' => 'globe'],
                                ['label' => 'Reading time', 'value' => '2.5 hrs', 'icon' => 'clock'],
                                ['label' => 'Items this week', 'value' => '12', 'icon' => 'calendar'],
                            ];
                            $iIcons = [
                                'tag' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>',
                                'globe' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>',
                                'clock' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                                'calendar' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
                            ];
                        @endphp
                        @foreach($insights as $insight)
                            <div class="insight-card">
                                <span class="insight-icon">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $iIcons[$insight['icon']] !!}</svg>
                                </span>
                                <div class="insight-content">
                                    <span class="insight-label">{{ $insight['label'] }}</span>
                                    <span class="insight-value">{{ $insight['value'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        </aside>

        <!-- Main: Recent Bookmarks -->
        <section class="panel">
            <header class="panel-header">
                <h2 class="panel-title">Recent Bookmarks</h2>
                <a href="{{ route('bookmarks') }}" class="section-link">View all</a>
            </header>
            <div class="bookmark-list">
                @if($recentBookmarks->isEmpty())
                    <div class="empty-state">
                        <div class="empty-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
                        </div>
                        <h3 class="empty-title">No bookmarks yet</h3>
                        <p class="empty-desc">Start building your knowledge hub by saving your first bookmark, note, or code snippet.</p>
                        <a href="{{ route('bookmarks') }}" class="empty-action btn-primary">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Add Bookmark
                        </a>
                    </div>
                @else
                    @foreach($recentBookmarks as $bookmark)
                        <article class="bookmark-item">
                            @if($bookmark->metadata['favicon'] ?? null)
                                <img src="{{ $bookmark->metadata['favicon'] }}" alt="" class="bookmark-favicon" loading="lazy" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="bookmark-favicon-placeholder" style="display:none;">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                                </div>
                            @else
                                <div class="bookmark-favicon-placeholder">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                                </div>
                            @endif

                            <div class="bookmark-main">
                                <div class="bookmark-header">
                                    <a href="{{ $bookmark->url }}" target="_blank" rel="noopener noreferrer" class="bookmark-title">{{ $bookmark->title ?? $bookmark->url }}</a>
                                    <span class="bookmark-type">{{ ucfirst($bookmark->type) }}</span>
                                </div>
                                <p class="bookmark-url">{{ $bookmark->url }}</p>

                                @if($bookmark->tags->isNotEmpty())
                                    <div class="bookmark-tags">
                                        @foreach($bookmark->tags->take(5) as $tag)
                                            <span class="bookmark-tag">{{ $tag->name }}</span>
                                        @endforeach
                                        @if($bookmark->tags->count() > 5)
                                            <span class="bookmark-tag-more">+{{ $bookmark->tags->count() - 5 }}</span>
                                        @endif
                                    </div>
                                @endif

                                @if($bookmark->aiSummary)
                                    <p class="bookmark-ai-summary">{{ $bookmark->aiSummary->summary }}</p>
                                @endif
                            </div>

                            <div class="bookmark-meta">
                                <time class="bookmark-time">{{ $bookmark->created_at->diffForHumans() }}</time>
                                @if($bookmark->metadata['reading_time'] ?? null)
                                    <span class="bookmark-readtime">{{ $bookmark->metadata['reading_time'] }} min read</span>
                                @endif
                            </div>
                        </article>
                    @endforeach
                @endif
            </div>
        </section>
    </div>
</div>
@endsection