<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ config('app.name', 'Clips') }} · Keluarga - @yield('title', 'Beranda')</title>
    <link rel="manifest" href="{{ asset('manifest-keluarga.json') }}">
    <meta name="theme-color" content="#6366f1">
    <link rel="icon" type="image/png" href="{{ asset('icons/keluarga/icon-192.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="keluarga-app-body">
    <div class="keluarga-shell">
        {{-- Top bar --}}
        <header class="keluarga-topbar" role="banner">
            <div class="keluarga-topbar-inner">
                <a href="{{ route('keluarga.app') }}" class="keluarga-brand">
                    <span class="keluarga-brand-icon">👨‍👩‍👧</span>
                    <span class="keluarga-brand-text">
                        <strong>{{ auth()->user()->family()?->name ?? 'Keluarga' }}</strong>
                        <small>{{ auth()->user()->name }}</small>
                    </span>
                </a>
                <div class="keluarga-topbar-actions">
                    @if(! auth()->user()->isFamilyOnly())
                        <a href="{{ route('dashboard') }}" class="keluarga-icon-btn" title="Buka Clips (modul lengkap)">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
                        </a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="keluarga-icon-btn" title="Keluar">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        {{-- Content --}}
        <main class="keluarga-content" role="main">
            @yield('content')
        </main>

        {{-- Bottom nav --}}
        <nav class="keluarga-bottombar" role="navigation" aria-label="Navigasi Keluarga">
            @php
                $appItems = [
                    ['route' => 'keluarga.app', 'label' => 'Beranda', 'icon' => '<path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>'],
                    ['route' => 'keluarga.app.allocation', 'label' => 'Jatah', 'icon' => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>'],
                    ['route' => 'keluarga.app.add', 'label' => 'Tambah', 'icon' => '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>', 'fab' => true],
                    ['route' => 'keluarga.app.goals', 'label' => 'Tabungan', 'icon' => '<path d="M19 5l-7 7-7-7"/><path d="M19 12l-7 7-7-7"/>'],
                    ['route' => 'keluarga.app.debts', 'label' => 'Hutang', 'icon' => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>'],
                ];
            @endphp
            @foreach($appItems as $item)
                @php
                    $active = request()->routeIs($item['route']);
                @endphp
                <a href="{{ route($item['route']) }}" class="keluarga-nav-item {{ $active ? 'active' : '' }} {{ ($item['fab'] ?? false) ? 'fab' : '' }}">
                    @if($item['fab'] ?? false)
                        <span class="keluarga-fab-btn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">{!! $item['icon'] !!}</svg>
                        </span>
                        <span class="keluarga-nav-label">{{ $item['label'] }}</span>
                    @else
                        <svg class="keluarga-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{!! $item['icon'] !!}</svg>
                        <span class="keluarga-nav-label">{{ $item['label'] }}</span>
                    @endif
                </a>
            @endforeach
        </nav>
    </div>

    @stack('scripts')
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw-keluarga.js').catch(() => {});
            });
        }
    </script>
</body>
</html>
