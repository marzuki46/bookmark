<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Knowledge Hub') }} - @yield('title', 'Dashboard')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
                                        ['route' => 'worksheets', 'label' => 'Worksheets', 'icon' => 'table', 'active' => request()->routeIs('worksheets'), 'count' => App\Models\Item::where('user_id', auth()->id())->where('type', 'worksheet')->count()],
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
                                                            'table' => '<rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="9" y1="3" x2="9" y2="21"/><line x1="15" y1="3" x2="15" y2="21"/>',
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
