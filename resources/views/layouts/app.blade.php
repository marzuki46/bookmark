<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Knowledge Hub') }} - @yield('title', 'Dashboard')</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <style>
            /* Fallback CSS when Vite build is not available */
            *, *::before, *::after { box-sizing: border-box; }
            html { font-size: 14px; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }

            :root {
                --wp-admin-bg: #f1f2f3;
                --wp-admin-surface: #ffffff;
                --wp-admin-border: #dcdcde;
                --wp-admin-text: #1d2327;
                --wp-admin-text-muted: #646970;
                --wp-admin-text-light: #8c8f94;
                --wp-admin-blue: #135e96;
                --wp-admin-blue-hover: #0d4a7a;
                --wp-admin-blue-light: #e8f0fa;
                --wp-admin-green: #007c2d;
                --wp-admin-red: #d63638;
                --wp-sidebar-bg: #1d2327;
                --wp-sidebar-text: #ffffff;
                --wp-sidebar-hover: #333a3f;
                --wp-sidebar-active: #007cba;
                --wp-sidebar-border: #2f363d;
                --wp-sidebar-width: 200px;
                --wp-sidebar-collapsed-width: 48px;
                --wp-sidebar-parent-padding-left: 48px;
                --wp-sidebar-item-padding-left: 36px;
                --wp-space-xs: 4px; --wp-space-sm: 8px; --wp-space-md: 12px; --wp-space-lg: 16px; --wp-space-xl: 20px; --wp-space-2xl: 24px;
                --wp-radius-sm: 4px; --wp-radius-md: 6px; --wp-radius-lg: 8px;
                --wp-shadow-sm: 0 1px 1px rgba(0,0,0,.04);
                --wp-shadow-md: 0 2px 4px rgba(0,0,0,.08);
                --wp-shadow-lg: 0 4px 8px rgba(0,0,0,.12);
                --color-bg: #f8fafc; --color-surface: #ffffff; --color-surface-hover: #f1f5f9;
                --color-border: #e2e8f0; --color-border-strong: #cbd5e1;
                --text-primary: #0f172a; --text-secondary: #334155; --text-tertiary: #64748b; --text-quaternary: #94a3b8;
                --indigo-50: #eef2ff; --indigo-100: #e0e7ff; --indigo-600: #4f46e5; --indigo-700: #4338ca;
                --emerald-50: #ecfdf5; --emerald-600: #059669;
                --space-1:.25rem; --space-2:.5rem; --space-3:.75rem; --space-4:1rem; --space-5:1.25rem; --space-6:1.5rem; --space-8:2rem; --space-12:3rem;
                --radius-md:.5rem; --radius-lg:.75rem; --radius-xl:1rem;
            }

            body { margin: 0; font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; font-size: 13px; line-height: 1.4615; color: var(--wp-admin-text); background: var(--wp-admin-bg); min-height: 100vh; }
            a { color: var(--wp-admin-blue); text-decoration: none; }
            a:hover { color: var(--wp-admin-blue-hover); }

            /* Sidebar */
            #wp-admin-sidebar { position: fixed; top: 0; left: 0; bottom: 0; z-index: 100; width: var(--wp-sidebar-width); background: var(--wp-sidebar-bg); color: var(--wp-sidebar-text); display: flex; flex-direction: column; transition: width .15s ease, transform .15s ease; overflow-x: hidden; box-shadow: var(--wp-shadow-lg); }
            #wp-admin-sidebar.collapsed { width: var(--wp-sidebar-collapsed-width); }
            .wp-sidebar-inner { display: flex; flex-direction: column; height: 100%; overflow-y: auto; }
            .wp-sidebar-brand { padding: var(--wp-space-lg) var(--wp-space-md) var(--wp-space-lg) var(--wp-sidebar-parent-padding-left); border-bottom: 1px solid var(--wp-sidebar-border); flex-shrink: 0; }
            .wp-brand-link { display: flex; align-items: center; gap: var(--wp-space-sm); color: var(--wp-sidebar-text); text-decoration: none; font-weight: 600; font-size: 14px; white-space: nowrap; overflow: hidden; }
            .wp-brand-icon { width: 28px; height: 28px; background: var(--wp-admin-blue); border-radius: var(--wp-radius-md); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
            .wp-brand-icon svg { width: 18px; height: 18px; color: #fff; }
            .wp-brand-text { margin-left: 6px; }
            #wp-admin-sidebar.collapsed .wp-brand-text { display: none; }
            .wp-sidebar-toggle { margin-left: auto; background: transparent; border: none; color: var(--wp-sidebar-text-muted); cursor: pointer; padding: 4px; border-radius: 4px; }
            .wp-sidebar-toggle:hover { background: var(--wp-sidebar-hover); }
            .wp-sidebar-toggle svg { width: 18px; height: 18px; }

            .wp-sidebar-nav { flex: 1; overflow-y: auto; padding: var(--wp-space-sm) 0; }
            .wp-admin-menu { list-style: none; margin: 0; padding: 0; }
            .wp-menu-separator { height: 1px; background: var(--wp-sidebar-border); margin: var(--wp-space-sm) var(--wp-space-xs); }
            #wp-admin-sidebar.collapsed .wp-menu-separator { display: none; }
            .wp-menu-group { margin-bottom: 4px; }
            .wp-menu-group-label { display: block; padding: var(--wp-space-xs) var(--wp-space-md); font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; color: var(--wp-sidebar-text-light); }
            #wp-admin-sidebar.collapsed .wp-menu-group-label { display: none; }
            .wp-submenu-list { list-style: none; margin: 0; padding: 0; }
            .wp-menu-item { margin: 0 var(--wp-space-xs); }
            .wp-menu-link { display: flex; align-items: center; gap: var(--wp-space-sm); padding: var(--wp-space-sm) var(--wp-space-md) var(--wp-space-sm) var(--wp-sidebar-item-padding-left); border-radius: var(--wp-radius-sm); color: var(--wp-sidebar-text); text-decoration: none; font-size: 13px; white-space: nowrap; transition: background .1s ease; }
            .wp-menu-link:hover { background: var(--wp-sidebar-hover); }
            .wp-menu-item.current .wp-menu-link { background: var(--wp-sidebar-active); color: var(--wp-sidebar-active-text); font-weight: 500; }
            .wp-menu-icon { width: 20px; height: 20px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
            .wp-menu-icon svg { width: 20px; height: 20px; color: var(--wp-sidebar-text-muted); }
            .wp-menu-item.current .wp-menu-icon svg { color: #fff; }
            .wp-menu-text { flex: 1; overflow: hidden; text-overflow: ellipsis; }
            #wp-admin-sidebar.collapsed .wp-menu-text { display: none; }
            .wp-menu-count { display: inline-flex; align-items: center; justify-content: center; min-width: 18px; height: 18px; padding: 0 5px; border-radius: 9px; background: var(--wp-sidebar-border); color: var(--wp-sidebar-text-muted); font-size: 11px; font-weight: 600; flex-shrink: 0; }
            .wp-menu-item.current .wp-menu-count { background: var(--wp-admin-blue); color: #fff; }
            .wp-collapse-footer { padding: var(--wp-space-sm); border-top: 1px solid var(--wp-sidebar-border); flex-shrink: 0; }
            .wp-collapse-btn { display: flex; align-items: center; gap: var(--wp-space-sm); width: 100%; padding: var(--wp-space-sm); border: none; border-radius: var(--wp-radius-sm); background: transparent; color: var(--wp-sidebar-text-muted); font-size: 12px; cursor: pointer; }
            .wp-collapse-btn:hover { background: var(--wp-sidebar-hover); color: var(--wp-sidebar-text); }
            .wp-collapse-btn svg { width: 16px; height: 16px; }
            #wp-admin-sidebar.collapsed .wp-collapse-text { display: none; }

            /* Content */
            .wp-admin-content { flex: 1; margin-left: var(--wp-sidebar-width); min-width: 0; min-height: 100vh; transition: margin-left .15s ease; }
            .wp-admin-content.sidebar-collapsed { margin-left: var(--wp-sidebar-collapsed-width); }
            .wp-admin-bar { position: sticky; top: 0; z-index: 90; height: 48px; background: var(--wp-admin-surface); border-bottom: 1px solid var(--wp-admin-border); display: flex; align-items: center; justify-content: space-between; padding: 0 var(--wp-space-lg); flex-shrink: 0; }
            .wp-bar-left { display: flex; align-items: center; gap: var(--wp-space-md); }
            .wp-mobile-menu-btn { display: none; align-items: center; justify-content: center; width: 36px; height: 36px; border: none; border-radius: var(--wp-radius-sm); background: transparent; color: var(--wp-admin-text); cursor: pointer; }
            .wp-mobile-menu-btn:hover { background: var(--wp-admin-border); }
            .wp-mobile-menu-btn svg { width: 20px; height: 20px; }
            .wp-bar-right { display: flex; align-items: center; gap: var(--wp-space-md); }
            .wp-user-menu { position: relative; }
            .wp-user-avatar { display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 50%; background: var(--wp-admin-blue); color: #fff; font-size: 13px; font-weight: 600; border: none; cursor: pointer; }
            .wp-user-dropdown { position: absolute; top: calc(100% + 4px); right: 0; min-width: 200px; background: var(--wp-admin-surface); border: 1px solid var(--wp-admin-border); border-radius: var(--wp-radius-md); box-shadow: var(--wp-shadow-lg); padding: var(--wp-space-xs); z-index: 1000; }
            .wp-user-dropdown[hidden] { display: none; }
            .wp-user-info { padding: var(--wp-space-sm) var(--wp-space-md); border-bottom: 1px solid var(--wp-admin-border); margin-bottom: var(--wp-space-xs); }
            .wp-user-name { display: block; font-weight: 600; }
            .wp-user-email { display: block; font-size: 12px; color: var(--wp-admin-text-muted); }
            .wp-dropdown-divider { border: none; border-top: 1px solid var(--wp-admin-border); margin: var(--wp-space-xs) 0; }
            .wp-dropdown-item { display: flex; align-items: center; gap: var(--wp-space-sm); width: 100%; padding: var(--wp-space-sm) var(--wp-space-md); border: none; border-radius: var(--wp-radius-sm); background: transparent; color: var(--wp-admin-text); font-size: 13px; text-align: left; cursor: pointer; text-decoration: none; }
            .wp-dropdown-item:hover { background: var(--wp-admin-bg); }
            .wp-dropdown-item svg { width: 18px; height: 18px; color: var(--wp-admin-text-muted); flex-shrink: 0; }
            .wp-dropdown-danger { color: var(--wp-admin-red); }
            .wp-dropdown-danger:hover { background: var(--wp-admin-red-light); }
            .wp-main-content { flex: 1; padding: var(--wp-space-xl) var(--wp-space-2xl); max-width: 1440px; width: 100%; margin: 0 auto; }
            .wp-sidebar-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 99; opacity: 0; visibility: hidden; transition: opacity .2s ease, visibility .2s ease; }
            .wp-sidebar-overlay:not([hidden]) { opacity: 1; visibility: visible; }
            .wp-admin-wrap { display: flex; min-height: 100vh; }
            .wp-login-page { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: var(--wp-space-xl); background: var(--wp-admin-bg); }

            @media (max-width: 959px) {
                #wp-admin-sidebar { transform: translateX(-100%); width: var(--wp-sidebar-width); }
                #wp-admin-sidebar.mobile-open { transform: translateX(0); }
                .wp-admin-content, .wp-admin-content.sidebar-collapsed { margin-left: 0; }
                .wp-mobile-menu-btn { display: flex !important; }
            }
            @media (min-width: 960px) {
                .wp-sidebar-overlay { display: none !important; }
            }

            /* Dashboard/Feature styles */
            .page { padding: 1.5rem; max-width: 1400px; margin: 0 auto; }
            .btn-primary { display: inline-flex; align-items: center; gap: .5rem; padding: .5rem 1rem; font-size: .875rem; font-weight: 500; color: #fff; background: var(--indigo-600); border: none; border-radius: var(--radius-md); cursor: pointer; text-decoration: none; }
            .btn-primary:hover { background: var(--indigo-700); }
            .btn-secondary { display: inline-flex; align-items: center; gap: .5rem; padding: .5rem 1rem; font-size: .875rem; font-weight: 500; color: var(--text-secondary); background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-md); cursor: pointer; text-decoration: none; }
            .btn-secondary:hover { background: var(--color-bg); }
            .stats-grid { display: grid; grid-template-columns: 1fr; gap: 1rem; margin-bottom: 1.5rem; }
            @media (min-width: 640px) { .stats-grid { grid-template-columns: repeat(2,1fr); } }
            @media (min-width: 1024px) { .stats-grid { grid-template-columns: repeat(4,1fr); } }
            .stat-card { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-xl); padding: 1.25rem; transition: all .2s ease; position: relative; overflow: hidden; }
            .stat-card:hover { box-shadow: var(--wp-shadow-lg); transform: translateY(-2px); }
            .stat-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: .75rem; }
            .stat-label { font-size: .75rem; font-weight: 600; color: var(--text-tertiary); text-transform: uppercase; letter-spacing: .05em; }
            .stat-icon { width: 44px; height: 44px; border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
            .stat-value { font-size: 2.25rem; font-weight: 700; color: var(--text-primary); line-height: 1.1; letter-spacing: -.02em; }
            .stat-trend { display: inline-flex; align-items: center; gap: .25rem; font-size: .75rem; font-weight: 500; margin-top: .75rem; padding-top: .75rem; border-top: 1px solid var(--color-border); }
            .empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 3rem 1.5rem; text-align: center; }
            .empty-icon { width: 64px; height: 64px; border-radius: 1rem; background: var(--indigo-50); color: var(--indigo-600); display: flex; align-items: center; justify-content: center; margin-bottom: 1rem; }
            .empty-icon svg { width: 28px; height: 28px; }
            .empty-title { font-size: 1rem; font-weight: 600; color: var(--text-primary); margin-bottom: .25rem; }
            .empty-desc { font-size: .875rem; color: var(--text-tertiary); margin-bottom: 1rem; max-width: 280px; }

            .wp-form-input { width: 100%; padding: 8px 12px; border: 1px solid var(--wp-admin-border); border-radius: var(--wp-radius-sm); font-size: 14px; font-family: inherit; color: var(--wp-admin-text); background: var(--wp-admin-surface); }
            .wp-form-input:focus { outline: none; border-color: var(--wp-admin-blue-border); box-shadow: 0 0 0 2px var(--wp-admin-blue-light); }
            .wp-form-label { display: block; font-size: 13px; font-weight: 500; color: var(--wp-admin-text); margin-bottom: 4px; }

            .main-grid { display: grid; grid-template-columns: 1fr; gap: 1.25rem; }
            @media (min-width: 1024px) { .main-grid { grid-template-columns: 280px 1fr; } }
            .panel { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-xl); overflow: hidden; }
            .panel-header { padding: 1rem 1.25rem; border-bottom: 1px solid var(--color-border); display: flex; align-items: center; justify-content: space-between; }
            .panel-title { font-size: .875rem; font-weight: 600; color: var(--text-primary); }
            .panel-body { padding: .5rem 0; }
        </style>
    @endif

    @stack('head')
</head>
<body class="wp-admin-body">
    @auth
    <div class="wp-admin-wrap">
        <!-- Sidebar -->
        <aside id="wp-admin-sidebar" class="wp-admin-sidebar" role="navigation" aria-label="Main navigation">
            <div class="wp-sidebar-inner">
                <!-- Brand -->
                <div class="wp-sidebar-brand">
                    <a href="{{ route('dashboard') }}" class="wp-brand-link" aria-label="Knowledge Hub Home">
                        <svg class="wp-brand-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                        </svg>
                        <span class="wp-brand-text">Knowledge Hub</span>
                    </a>
                    <button id="wp-sidebar-toggle" class="wp-sidebar-toggle" aria-label="Collapse menu" aria-expanded="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M15 18l-6-6 6-6"/>
                        </svg>
                    </button>
                </div>

                <!-- Navigation Menu -->
                <nav class="wp-sidebar-nav">
                    <ul id="wp-admin-menu" class="wp-admin-menu" role="menubar">
                        @php
                            $menuGroups = [
                                'content' => [
                                    'label' => 'Content',
                                    'items' => [
                                        ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard', 'active' => request()->routeIs('dashboard')],
                                        ['route' => 'bookmarks', 'label' => 'Bookmarks', 'icon' => 'bookmark', 'active' => request()->routeIs('bookmarks'), 'count' => App\Models\Item::where('user_id', auth()->id())->where('type', 'bookmark')->count()],
                                        ['route' => 'notes', 'label' => 'Notes', 'icon' => 'note', 'active' => request()->routeIs('notes'), 'count' => App\Models\Item::where('user_id', auth()->id())->where('type', 'note')->count()],
                                        ['route' => 'prompts', 'label' => 'AI Prompts', 'icon' => 'sparkles', 'active' => request()->routeIs('prompts')],
                                        ['route' => 'snippets', 'label' => 'Code Snippets', 'icon' => 'code', 'active' => request()->routeIs('snippets')],
                                    ]
                                ],
                                'organize' => [
                                    'label' => 'Organize',
                                    'items' => [
                                        ['route' => 'collections', 'label' => 'Collections', 'icon' => 'folder', 'active' => request()->routeIs('collections')],
                                        ['route' => 'tags', 'label' => 'Tags', 'icon' => 'tag', 'active' => request()->routeIs('tags')],
                                    ]
                                ],
                                'media' => [
                                    'label' => 'Media & Files',
                                    'items' => [
                                        ['route' => 'files', 'label' => 'File Manager', 'icon' => 'file', 'active' => request()->routeIs('files')],
                                        ['route' => 'secrets', 'label' => 'Secret Vault', 'icon' => 'lock', 'active' => request()->routeIs('secrets')],
                                    ]
                                ],
                                'ai' => [
                                    'label' => 'AI Center',
                                    'items' => [
                                        ['route' => 'ai', 'label' => 'AI Dashboard', 'icon' => 'brain', 'active' => request()->routeIs('ai')],
                                        ['route' => 'search', 'label' => 'Search', 'icon' => 'search', 'active' => request()->routeIs('search')],
                                    ]
                                ],
                                'invoice' => [
                                    'label' => 'Invoice',
                                    'items' => [
                                        ['route' => 'invoices', 'label' => 'Dashboard', 'icon' => 'dashboard', 'active' => request()->routeIs('invoices') && !request()->routeIs('invoices.*')],
                                        ['route' => 'invoices.create', 'label' => 'Buat Invoice', 'icon' => 'file-plus', 'active' => request()->routeIs('invoices.create')],
                                        ['route' => 'financial', 'label' => 'Laporan Keuangan', 'icon' => 'bar-chart', 'active' => request()->routeIs('financial')],
                                        ['route' => 'bills', 'label' => 'Tagihan', 'icon' => 'receipt', 'active' => request()->routeIs('bills')],
                                        ['route' => 'companies', 'label' => 'Perusahaan', 'icon' => 'building', 'active' => request()->routeIs('companies')],
                                    ]
                                ],
                                'system' => [
                                    'label' => 'System',
                                    'items' => [
                                        ['route' => 'dead-links', 'label' => 'Dead Link Checker', 'icon' => 'link-x', 'active' => request()->routeIs('dead-links')],
                                        ['route' => 'ip-blocker', 'label' => 'IP Blocker', 'icon' => 'shield', 'active' => request()->routeIs('ip-blocker')],
                                        ['route' => 'activity', 'label' => 'Activity', 'icon' => 'activity', 'active' => request()->routeIs('activity')],
                                        ['route' => 'backup', 'label' => 'Backup', 'icon' => 'refresh', 'active' => request()->routeIs('backup')],
                                        ['route' => 'extension', 'label' => 'Chrome Extension', 'icon' => 'globe', 'active' => request()->routeIs('extension')],
                                        ['route' => 'settings', 'label' => 'Settings', 'icon' => 'cog', 'active' => request()->routeIs('settings')],
                                    ]
                                ],
                            ];
                        @endphp

                        @foreach($menuGroups as $groupKey => $group)
                            @if($loop->first)
                            @else
                                <li class="wp-menu-separator" role="separator" aria-hidden="true"></li>
                            @endif

                            <li class="wp-menu-group" role="none">
                                <span class="wp-menu-group-label">{{ $group['label'] }}</span>
                                <ul class="wp-submenu-list" role="menu">
                                    @foreach($group['items'] as $item)
                                        @php
                                            $isActive = $item['active'] ?? false;
                                            $routeName = $item['route'];
                                            $href = route($routeName);
                                            $iconName = $item['icon'];
                                            $label = $item['label'];
                                            $count = $item['count'] ?? null;
                                        @endphp

                                        <li class="wp-menu-item {{ $isActive ? 'current' : '' }}" role="none">
                                            <a href="{{ $href }}" class="wp-menu-link" role="menuitem" aria-current="{{ $isActive ? 'page' : 'false' }}">
                                                <span class="wp-menu-icon">
                                                    @php
                                                        $icons = [
                                                            'dashboard' => '<path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>',
                                                            'bookmark' => '<path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"/>',
                                                            'note' => '<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H8"/>',
                                                            'sparkles' => '<path d="M12 2v4"/><path d="M12 18v4"/><path d="M4.93 4.93l2.83 2.83"/><path d="M16.24 16.24l2.83 2.83"/><path d="M2 12h4"/><path d="M18 12h4"/><path d="M4.93 19.07l2.83-2.83"/><path d="M16.24 7.76l2.83-2.83"/>',
                                                            'code' => '<path d="M16 18l6-6-6-6"/><path d="M8 6l-6 6 6 6"/>',
                                                            'folder' => '<path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/>',
                                                            'tag' => '<path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7.01"/>',
                                                            'file' => '<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H8"/>',
                                                            'lock' => '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>',
                                                            'brain' => '<path d="M12 5a3 3 0 10-3 3.85A5 5 0 1112 5"/><path d="M12 5a3 3 0 013 3.85A5 5 0 0012 5"/><path d="M15 12a1 1 0 01-1 1h-2a1 1 0 00-1 1v3a1 1 0 001 1h2a1 1 0 001-1v-3a1 1 0 011-1"/><path d="M9 12a1 1 0 011-1h2a1 1 0 011 1v3a1 1 0 01-1 1h-2a1 1 0 01-1-1v-3a1 1 0 00-1-1"/>',
                                                            'search' => '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>',
                                                            'activity' => '<path d="M22 12h-4l-3 9L9 3l-3 9H2"/><path d="M22 17V2"/><path d="M22 7V7"/><path d="M22 12V12"/>',
                                                            'refresh' => '<path d="M23 4v6"/><path d="M1 20v-6"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/>',
                                                            'globe' => '<circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/>',
                                                            'link-x' => '<path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
                                                            'file-plus' => '<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/>',
                                                            'receipt' => '<path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1-2-1z"/><line x1="8" y1="10" x2="16" y2="10"/><line x1="8" y1="14" x2="16" y2="14"/>',
                                                            'building' => '<rect x="4" y="2" width="16" height="20" rx="2" ry="2"/><path d="M9 22V12h6v10"/><path d="M8 6h.01"/><path d="M16 6h.01"/><path d="M12 6h.01"/><path d="M12 10h.01"/><path d="M12 14h.01"/><path d="M16 10h.01"/><path d="M16 14h.01"/><path d="M8 10h.01"/><path d="M8 14h.01"/>',
                                                            'cog' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/>',
                                                            'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
                                                            'bar-chart' => '<path d="M18 20V10"/><path d="M12 20V4"/><path d="M6 20v-6"/>',
                                                        ];
                                                        $iconSvg = $icons[$iconName] ?? $icons['file'];
                                                    @endphp
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                        {!! $iconSvg !!}
                                                    </svg>
                                                </span>
                                                <span class="wp-menu-text">{{ $label }}</span>
                                                @if($count !== null && $count > 0)
                                                    <span class="wp-menu-count" aria-label="{{ $count }} items">{{ $count }}</span>
                                                @endif
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                        @endforeach

                        <!-- Collapse indicator -->
                        <li class="wp-collapse-footer" role="none">
                            <button id="wp-collapse-menu" class="wp-collapse-btn" aria-label="Collapse menu" aria-expanded="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M15 18l-6-6 6-6"/>
                                </svg>
                                <span class="wp-collapse-text">Collapse menu</span>
                            </button>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>

        <!-- Main Content -->
        <div id="wp-admin-content" class="wp-admin-content">
            <!-- Top Bar -->
            <header id="wp-admin-bar" class="wp-admin-bar" role="banner">
                <div class="wp-bar-left">
                    <button id="wp-mobile-menu-btn" class="wp-mobile-menu-btn" aria-label="Open menu" aria-expanded="false" aria-controls="wp-admin-sidebar">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 12h18M3 6h18M3 18h18"/>
                        </svg>
                    </button>
                </div>
                <div class="wp-bar-right">
                    <div class="wp-user-menu">
                        <button class="wp-user-avatar" aria-label="User menu" aria-expanded="false" aria-haspopup="true">
                            <span>{{ Str::upper(auth()->user()->name[0] ?? 'U') }}</span>
                        </button>
                        <div class="wp-user-dropdown" role="menu" hidden>
                            <div class="wp-user-info">
                                <span class="wp-user-name">{{ auth()->user()->name }}</span>
                                <span class="wp-user-email">{{ auth()->user()->email }}</span>
                            </div>
                            <hr class="wp-dropdown-divider">
                            <a href="{{ route('settings') }}" class="wp-dropdown-item" role="menuitem">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
                                Settings
                            </a>
                            <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                                @csrf
                                <button type="submit" class="wp-dropdown-item wp-dropdown-danger" role="menuitem">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                                    Log Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main id="wp-main-content" class="wp-main-content" role="main">
                @yield('content')
            </main>
        </div>

        <!-- Mobile Overlay -->
        <div id="wp-sidebar-overlay" class="wp-sidebar-overlay" hidden aria-hidden="true"></div>
    </div>
    @endauth

    @guest
    <main class="wp-login-page">
        @yield('content')
    </main>
    @endguest

    @stack('scripts')
    <script>
        // WordPress Admin Sidebar JS
        (function() {
            const sidebar = document.getElementById('wp-admin-sidebar');
            const content = document.getElementById('wp-admin-content');
            const toggleBtn = document.getElementById('wp-sidebar-toggle');
            const collapseBtn = document.getElementById('wp-collapse-menu');
            const mobileBtn = document.getElementById('wp-mobile-menu-btn');
            const overlay = document.getElementById('wp-sidebar-overlay');
            const userMenuBtn = document.querySelector('.wp-user-avatar');
            const userDropdown = document.querySelector('.wp-user-dropdown');
            let isCollapsed = false;
            let isMobileOpen = false;

            // Load collapsed state from localStorage
            if (localStorage.getItem('wpSidebarCollapsed') === 'true') {
                setCollapsed(true, false);
            }

            // Toggle collapse
            function setCollapsed(collapsed, save = true) {
                isCollapsed = collapsed;
                sidebar.classList.toggle('collapsed', collapsed);
                content.classList.toggle('sidebar-collapsed', collapsed);
                if (toggleBtn) toggleBtn.setAttribute('aria-expanded', !collapsed);
                if (collapseBtn) collapseBtn.setAttribute('aria-expanded', !collapsed);
                if (save) localStorage.setItem('wpSidebarCollapsed', collapsed);
            }

            if (toggleBtn) {
                toggleBtn.addEventListener('click', () => setCollapsed(!isCollapsed));
            }
            if (collapseBtn) {
                collapseBtn.addEventListener('click', () => setCollapsed(!isCollapsed));
            }

            // Mobile menu
            function setMobileOpen(open) {
                isMobileOpen = open;
                sidebar.classList.toggle('mobile-open', open);
                overlay.hidden = !open;
                overlay.setAttribute('aria-hidden', !open);
                mobileBtn?.setAttribute('aria-expanded', open);
                document.body.style.overflow = open ? 'hidden' : '';
            }

            mobileBtn?.addEventListener('click', () => setMobileOpen(!isMobileOpen));
            overlay?.addEventListener('click', () => setMobileOpen(false));

            // Close mobile menu on link click
            sidebar?.querySelectorAll('.wp-menu-link').forEach(link => {
                link.addEventListener('click', () => {
                    if (window.innerWidth < 960) setMobileOpen(false);
                });
            });

            // User dropdown
            userMenuBtn?.addEventListener('click', (e) => {
                e.stopPropagation();
                const expanded = userMenuBtn.getAttribute('aria-expanded') === 'true';
                userMenuBtn.setAttribute('aria-expanded', !expanded);
                userDropdown.hidden = expanded;
            });

            document.addEventListener('click', (e) => {
                if (!userMenuBtn?.contains(e.target) && !userDropdown?.contains(e.target)) {
                    userMenuBtn?.setAttribute('aria-expanded', 'false');
                    userDropdown.hidden = true;
                }
            });

            // Keyboard navigation for dropdown
            userDropdown?.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    userMenuBtn?.focus();
                    userMenuBtn?.setAttribute('aria-expanded', 'false');
                    userDropdown.hidden = true;
                }
            });

            // Responsive
            const mq = window.matchMedia('(max-width: 959px)');
            function handleResize(e) {
                if (e.matches) {
                    setCollapsed(false, false);
                    sidebar.classList.remove('collapsed');
                } else {
                    setMobileOpen(false);
                }
            }
            mq.addEventListener?.('change', handleResize);
            handleResize(mq);

            // Submenu hover (desktop only)
            const menuItems = document.querySelectorAll('.wp-menu-item');
            menuItems.forEach(item => {
                item.addEventListener('mouseenter', () => {
                    if (!isCollapsed && window.innerWidth >= 960) {
                        item.classList.add('hovered');
                    }
                });
                item.addEventListener('mouseleave', () => {
                    item.classList.remove('hovered');
                });
            });
        })();
    </script>
</body>
</html>