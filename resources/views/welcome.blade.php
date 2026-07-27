<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ config('app.name', 'Clips') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --bg: #fafafa;
            --surface: #ffffff;
            --text: #18181b;
            --text-sec: #71717a;
            --border: #e4e4e7;
            --accent: #4f46e5;
            --accent-light: #eef2ff;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #09090b;
                --surface: #18181b;
                --text: #fafafa;
                --text-sec: #a1a1aa;
                --border: #27272a;
                --accent: #818cf8;
                --accent-light: #1e1b4b;
            }
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.25rem 2rem;
            max-width: 1100px;
            margin: 0 auto;
            width: 100%;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 700;
            font-size: 1.25rem;
            color: var(--text);
            text-decoration: none;
        }
        .brand svg { width: 24px; height: 24px; color: var(--accent); }
        .nav-links { display: flex; gap: 0.75rem; }
        .nav-links a {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-sec);
            text-decoration: none;
            border-radius: 0.5rem;
            transition: all 0.15s;
        }
        .nav-links a:hover { color: var(--text); background: var(--accent-light); }
        .nav-links .btn-primary {
            background: var(--accent);
            color: #fff;
            padding: 0.5rem 1.25rem;
            border-radius: 0.5rem;
        }
        .nav-links .btn-primary:hover { opacity: 0.9; }

        .hero {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4rem 2rem 6rem;
        }
        .hero-inner {
            max-width: 640px;
            text-align: center;
        }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--accent);
            background: var(--accent-light);
            padding: 0.375rem 0.875rem;
            border-radius: 9999px;
            margin-bottom: 1.5rem;
            border: 1px solid color-mix(in srgb, var(--accent) 20%, transparent);
        }
        .hero-badge .dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--accent);
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }
        h1 {
            font-size: clamp(2.25rem, 5vw, 3.5rem);
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -0.03em;
            margin-bottom: 1.25rem;
        }
        h1 span { color: var(--accent); }
        .hero-desc {
            font-size: 1.125rem;
            color: var(--text-sec);
            line-height: 1.6;
            max-width: 480px;
            margin: 0 auto 2rem;
        }
        .hero-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        .hero-actions a {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            font-size: 0.9375rem;
            font-weight: 600;
            border-radius: 0.625rem;
            text-decoration: none;
            transition: all 0.15s;
        }
        .btn-accent {
            background: var(--accent);
            color: #fff;
        }
        .btn-accent:hover { opacity: 0.9; transform: translateY(-1px); }
        .btn-ghost {
            color: var(--text);
            border: 1px solid var(--border);
            background: var(--surface);
        }
        .btn-ghost:hover { border-color: var(--accent); color: var(--accent); }
        .btn-ghost svg { width: 18px; height: 18px; }

        .features {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 2rem 6rem;
            width: 100%;
        }
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1rem;
        }
        .feature-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            padding: 1.5rem;
            transition: border-color 0.2s;
        }
        .feature-card:hover { border-color: var(--accent); }
        .feature-icon {
            width: 40px; height: 40px;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.25rem;
        }
        .fi-indigo { background: #eef2ff; color: #4f46e5; }
        .fi-emerald { background: #ecfdf5; color: #059669; }
        .fi-amber { background: #fffbeb; color: #d97706; }
        .fi-rose { background: #fff1f2; color: #e11d48; }
        @media (prefers-color-scheme: dark) {
            .fi-indigo { background: #1e1b4b; }
            .fi-emerald { background: #064e3b; }
            .fi-amber { background: #451a03; }
            .fi-rose { background: #4c0519; }
        }
        .feature-card h3 {
            font-size: 0.9375rem;
            font-weight: 600;
            margin-bottom: 0.375rem;
        }
        .feature-card p {
            font-size: 0.8125rem;
            color: var(--text-sec);
            line-height: 1.5;
        }

        .footer {
            text-align: center;
            padding: 1.5rem 2rem;
            font-size: 0.75rem;
            color: var(--text-sec);
            border-top: 1px solid var(--border);
        }
    </style>
</head>
<body>
    <nav class="nav">
        <a href="/" class="brand">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"/>
            </svg>
            Clips
        </a>
        <div class="nav-links">
            @if (Route::has('login'))
                @auth
                    <a href="{{ url('/dashboard') }}" class="btn-primary">Dashboard</a>
                @else
                    <a href="{{ route('login') }}">Sign in</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="btn-primary">Get Started</a>
                    @endif
                @endauth
            @endif
        </div>
    </nav>

    <main class="hero">
        <div class="hero-inner">
            <div class="hero-badge">
                <span class="dot"></span>
                AI-Powered Knowledge Manager
            </div>
            <h1>Save what matters.<br><span>Find it instantly.</span></h1>
            <p class="hero-desc">
                Clips helps you save, organize, and rediscover your bookmarks, notes, and code snippets — powered by AI.
            </p>
            <div class="hero-actions">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn-accent">
                            Open Dashboard
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="btn-accent">
                            Start Saving
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        </a>
                    @endauth
                @endif
            </div>
        </div>
    </main>

    <section class="features">
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon fi-indigo">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"/></svg>
                </div>
                <h3>Smart Clips</h3>
                <p>Save URLs, notes, code snippets, and more. Auto-fetch metadata, favicons, and OG images.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon fi-emerald">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5a3 3 0 10-3 3.85A5 5 0 1112 5"/><path d="M12 5a3 3 0 013 3.85A5 5 0 0012 5"/></svg>
                </div>
                <h3>AI Assistant</h3>
                <p>Ask questions about your saved data. Get summaries, organize, and discover patterns with AI.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon fi-amber">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </div>
                <h3>Global Search</h3>
                <p>Full-text search across all your content. Find any clip, note, or snippet in milliseconds.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon fi-rose">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                </div>
                <h3>Chrome Extension</h3>
                <p>Save directly from any webpage with the floating overlay or side panel. No context switching.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon fi-indigo">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/></svg>
                </div>
                <h3>Collections & Tags</h3>
                <p>Organize with tags, collections, and folders. Filter and sort to find exactly what you need.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon fi-emerald">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                </div>
                <h3>Quick Notepad</h3>
                <p>Instant notes with color labels and auto-save. Like Google Keep, but integrated with your clips.</p>
            </div>
        </div>
    </section>

    <footer class="footer">
        &copy; {{ date('Y') }} Clips. All rights reserved.
    </footer>
</body>
</html>
