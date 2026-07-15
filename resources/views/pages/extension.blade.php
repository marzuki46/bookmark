@extends('layouts.app')

@section('title', 'Chrome Extension')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-[var(--text-primary)]">Chrome Extension</h1>
        <p class="text-sm text-[var(--text-tertiary)] mt-1">Download, install, and connect your browser extension</p>
    </div>

    @php
        $tokenCount = auth()->user()->tokens()->count();
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-lg bg-[var(--emerald-50)] flex items-center justify-center text-[var(--emerald-600)]">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <div>
                    <div class="text-sm font-semibold text-[var(--text-primary)]">API Status</div>
                    <div class="text-xs text-[var(--emerald-600)] font-medium">Ready</div>
                </div>
            </div>
        </div>
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-lg bg-[var(--indigo-50)] flex items-center justify-center text-[var(--indigo-600)]">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                </div>
                <div>
                    <div class="text-sm font-semibold text-[var(--text-primary)]">Tokens</div>
                    <div class="text-xs text-[var(--text-tertiary)]">{{ $tokenCount }} active</div>
                </div>
            </div>
        </div>
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-lg bg-[var(--amber-50)] flex items-center justify-center text-[var(--amber-600)]">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                </div>
                <div>
                    <div class="text-sm font-semibold text-[var(--text-primary)]">Download</div>
                    <div class="text-xs text-[var(--text-tertiary)]">v2.0.0</div>
                </div>
            </div>
        </div>
    </div>

    <livewire:extension-manager />

    <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-[var(--color-border)]">
            <h2 class="text-sm font-semibold text-[var(--text-primary)]">Download Extension</h2>
        </div>
        <div class="p-5">
            <div class="flex items-start gap-4">
                <div class="w-16 h-16 rounded-xl bg-[var(--indigo-50)] flex items-center justify-center flex-shrink-0">
                    <svg class="w-8 h-8 text-[var(--indigo-600)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/><line x1="21.17" y1="8" x2="12" y2="8"/><line x1="3.95" y1="6.06" x2="8.54" y2="14"/><line x1="10.88" y1="21.94" x2="15.46" y2="14"/></svg>
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold text-[var(--text-primary)] mb-1">Personal Knowledge Hub Extension</h3>
                    <p class="text-sm text-[var(--text-tertiary)] mb-4">Floating overlay panel on any webpage. Save bookmarks, notes, AI render, search, and highlights — all without leaving the page.</p>
                    <a href="{{ route('extension.download') }}" class="btn-primary inline-flex">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Download Extension (.zip)
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-[var(--color-border)]">
            <h2 class="text-sm font-semibold text-[var(--text-primary)]">Setup Guide</h2>
        </div>
        <div class="p-5 space-y-4">
            <div class="flex gap-4">
                <div class="w-8 h-8 rounded-full bg-[var(--indigo-600)] text-white flex items-center justify-center text-sm font-bold flex-shrink-0">1</div>
                <div class="flex-1">
                    <h3 class="font-medium text-sm text-[var(--text-primary)] mb-1">Download & Extract</h3>
                    <p class="text-xs text-[var(--text-tertiary)]">Click download above. Extract the ZIP to a folder (e.g. <code class="bg-[var(--color-bg)] px-1 rounded">C:\Extensions\knowledge-hub</code>).</p>
                </div>
            </div>
            <div class="flex gap-4">
                <div class="w-8 h-8 rounded-full bg-[var(--indigo-600)] text-white flex items-center justify-center text-sm font-bold flex-shrink-0">2</div>
                <div class="flex-1">
                    <h3 class="font-medium text-sm text-[var(--text-primary)] mb-1">Load in Chrome</h3>
                    <p class="text-xs text-[var(--text-tertiary)]">Open <code class="bg-[var(--color-bg)] px-1 rounded">chrome://extensions</code>, enable <strong>Developer mode</strong>, click <strong>Load unpacked</strong>, select the extracted folder.</p>
                </div>
            </div>
            <div class="flex gap-4">
                <div class="w-8 h-8 rounded-full bg-[var(--indigo-600)] text-white flex items-center justify-center text-sm font-bold flex-shrink-0">3</div>
                <div class="flex-1">
                    <h3 class="font-medium text-sm text-[var(--text-primary)] mb-1">Configure Token</h3>
                    <p class="text-xs text-[var(--text-tertiary)] mb-2">Use the API Token section above to generate a token. Click the K icon on any page, paste the API URL and token in the overlay config.</p>
                </div>
            </div>
            <div class="flex gap-4">
                <div class="w-8 h-8 rounded-full bg-[var(--emerald-600)] text-white flex items-center justify-center text-sm font-bold flex-shrink-0">4</div>
                <div class="flex-1">
                    <h3 class="font-medium text-sm text-[var(--text-primary)] mb-1">Start Saving!</h3>
                    <p class="text-xs text-[var(--text-tertiary)]">The overlay floats on the current page. Save bookmarks, notes, AI render, search, and view page info — without leaving the page.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-[var(--color-border)]">
            <h2 class="text-sm font-semibold text-[var(--text-primary)]">Extension Features</h2>
        </div>
        <div class="p-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex items-start gap-3 p-3 rounded-lg border border-[var(--color-border)]">
                    <div class="w-8 h-8 rounded-lg bg-[var(--indigo-50)] flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-[var(--indigo-600)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"/></svg>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-[var(--text-primary)]">Quick Bookmark</div>
                        <div class="text-xs text-[var(--text-tertiary)]">One-click save current page with auto-title and URL</div>
                    </div>
                </div>
                <div class="flex items-start gap-3 p-3 rounded-lg border border-[var(--color-border)]">
                    <div class="w-8 h-8 rounded-lg bg-[var(--emerald-50)] flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-[var(--emerald-600)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-[var(--text-primary)]">Quick Note</div>
                        <div class="text-xs text-[var(--text-tertiary)]">Write notes directly from the overlay panel</div>
                    </div>
                </div>
                <div class="flex items-start gap-3 p-3 rounded-lg border border-[var(--color-border)]">
                    <div class="w-8 h-8 rounded-lg bg-[var(--amber-50)] flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-[var(--amber-600)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-[var(--text-primary)]">AI Render</div>
                        <div class="text-xs text-[var(--text-tertiary)]">Generate AI summary/notes from any page</div>
                    </div>
                </div>
                <div class="flex items-start gap-3 p-3 rounded-lg border border-[var(--color-border)]">
                    <div class="w-8 h-8 rounded-lg bg-[var(--amber-50)] flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-[var(--amber-600)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-[var(--text-primary)]">Quick Search</div>
                        <div class="text-xs text-[var(--text-tertiary)]">Search your knowledge base without leaving the page</div>
                    </div>
                </div>
                <div class="flex items-start gap-3 p-3 rounded-lg border border-[var(--color-border)]">
                    <div class="w-8 h-8 rounded-lg bg-[var(--red-50)] flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-[var(--red-600)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-[var(--text-primary)]">Read Later</div>
                        <div class="text-xs text-[var(--text-tertiary)]">Save with "read-later" tag for your reading queue</div>
                    </div>
                </div>
                <div class="flex items-start gap-3 p-3 rounded-lg border border-[var(--color-border)]">
                    <div class="w-8 h-8 rounded-lg bg-[var(--purple-50)] flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-[var(--purple-600)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-[var(--text-primary)]">Auto Metadata</div>
                        <div class="text-xs text-[var(--text-tertiary)]">Captures OG image, description, and favicon automatically</div>
                    </div>
                </div>
                <div class="flex items-start gap-3 p-3 rounded-lg border border-[var(--color-border)]">
                    <div class="w-8 h-8 rounded-lg bg-[var(--orange-50)] flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-[var(--orange-600)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-[var(--text-primary)]">Floating Overlay</div>
                        <div class="text-xs text-[var(--text-tertiary)]">Panel floats on the current page — no new tab needed</div>
                    </div>
                </div>
                <div class="flex items-start gap-3 p-3 rounded-lg border border-[var(--color-border)]">
                    <div class="w-8 h-8 rounded-lg bg-[var(--cyan-50)] flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-[var(--cyan-600)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 7 4 4 20 4 20 7"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/></svg>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-[var(--text-primary)]">Page Highlight</div>
                        <div class="text-xs text-[var(--text-tertiary)]">Select text on any page, right-click to save as highlight</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-[var(--color-border)]">
            <h2 class="text-sm font-semibold text-[var(--text-primary)]">9Router AI Skills</h2>
            <p class="text-xs text-[var(--text-tertiary)] mt-0.5">Copy a skill link and paste to your AI — no install needed</p>
        </div>
        <div class="p-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <a href="https://raw.githubusercontent.com/decolua/9router/refs/heads/master/skills/9router/SKILL.md" target="_blank" class="flex items-center gap-3 p-3 rounded-lg border border-[var(--color-border)] hover:bg-[var(--color-bg)] transition group">
                    <div class="w-9 h-9 rounded-lg bg-[var(--indigo-50)] flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-[var(--indigo-600)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-[var(--text-primary)] group-hover:text-[var(--indigo-600)] transition">9Router (Entry)</div>
                        <div class="text-xs text-[var(--text-tertiary)]">Setup + index of all capabilities</div>
                    </div>
                    <svg class="w-4 h-4 text-[var(--text-tertiary)] group-hover:text-[var(--indigo-600)] transition flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                </a>
                <a href="https://raw.githubusercontent.com/decolua/9router/refs/heads/master/skills/9router-chat/SKILL.md" target="_blank" class="flex items-center gap-3 p-3 rounded-lg border border-[var(--color-border)] hover:bg-[var(--color-bg)] transition group">
                    <div class="w-9 h-9 rounded-lg bg-[var(--emerald-50)] flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-[var(--emerald-600)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-[var(--text-primary)] group-hover:text-[var(--emerald-600)] transition">Chat</div>
                        <div class="text-xs text-[var(--text-tertiary)]">/v1/chat/completions — Chat / code-gen</div>
                    </div>
                    <svg class="w-4 h-4 text-[var(--text-tertiary)] group-hover:text-[var(--emerald-600)] transition flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                </a>
                <a href="https://raw.githubusercontent.com/decolua/9router/refs/heads/master/skills/9router-image/SKILL.md" target="_blank" class="flex items-center gap-3 p-3 rounded-lg border border-[var(--color-border)] hover:bg-[var(--color-bg)] transition group">
                    <div class="w-9 h-9 rounded-lg bg-[var(--amber-50)] flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-[var(--amber-600)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-[var(--text-primary)] group-hover:text-[var(--amber-600)] transition">Image Generation</div>
                        <div class="text-xs text-[var(--text-tertiary)]">/v1/images/generations — DALL-E, FLUX, Imagen</div>
                    </div>
                    <svg class="w-4 h-4 text-[var(--text-tertiary)] group-hover:text-[var(--amber-600)] transition flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                </a>
                <a href="https://raw.githubusercontent.com/decolua/9router/refs/heads/master/skills/9router-tts/SKILL.md" target="_blank" class="flex items-center gap-3 p-3 rounded-lg border border-[var(--color-border)] hover:bg-[var(--color-bg)] transition group">
                    <div class="w-9 h-9 rounded-lg bg-[var(--purple-50)] flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-purple-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 010 14.14M15.54 8.46a5 5 0 010 7.07"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-[var(--text-primary)] group-hover:text-purple-600 transition">Text-to-Speech</div>
                        <div class="text-xs text-[var(--text-tertiary)]">/v1/audio/speech — OpenAI, ElevenLabs, Edge</div>
                    </div>
                    <svg class="w-4 h-4 text-[var(--text-tertiary)] group-hover:text-purple-600 transition flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                </a>
                <a href="https://raw.githubusercontent.com/decolua/9router/refs/heads/master/skills/9router-stt/SKILL.md" target="_blank" class="flex items-center gap-3 p-3 rounded-lg border border-[var(--color-border)] hover:bg-[var(--color-bg)] transition group">
                    <div class="w-9 h-9 rounded-lg bg-[var(--red-50)] flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-[var(--red-600)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1a3 3 0 00-3 3v8a3 3 0 006 0V4a3 3 0 00-3-3z"/><path d="M19 10v2a7 7 0 01-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/><line x1="8" y1="23" x2="16" y2="23"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-[var(--text-primary)] group-hover:text-[var(--red-600)] transition">Speech-to-Text</div>
                        <div class="text-xs text-[var(--text-tertiary)]">/v1/audio/transcriptions — Whisper, Groq, Gemini</div>
                    </div>
                    <svg class="w-4 h-4 text-[var(--text-tertiary)] group-hover:text-[var(--red-600)] transition flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                </a>
                <a href="https://raw.githubusercontent.com/decolua/9router/refs/heads/master/skills/9router-embeddings/SKILL.md" target="_blank" class="flex items-center gap-3 p-3 rounded-lg border border-[var(--color-border)] hover:bg-[var(--color-bg)] transition group">
                    <div class="w-9 h-9 rounded-lg bg-[var(--cyan-50)] flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-[var(--cyan-600)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/><circle cx="12" cy="5" r="1"/><circle cx="12" cy="19" r="1"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-[var(--text-primary)] group-hover:text-[var(--cyan-600)] transition">Embeddings</div>
                        <div class="text-xs text-[var(--text-tertiary)]">/v1/embeddings — Vectors for RAG</div>
                    </div>
                    <svg class="w-4 h-4 text-[var(--text-tertiary)] group-hover:text-[var(--cyan-600)] transition flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                </a>
                <a href="https://raw.githubusercontent.com/decolua/9router/refs/heads/master/skills/9router-web-search/SKILL.md" target="_blank" class="flex items-center gap-3 p-3 rounded-lg border border-[var(--color-border)] hover:bg-[var(--color-bg)] transition group">
                    <div class="w-9 h-9 rounded-lg bg-[var(--orange-50)] flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-[var(--orange-600)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-[var(--text-primary)] group-hover:text-[var(--orange-600)] transition">Web Search</div>
                        <div class="text-xs text-[var(--text-tertiary)]">/v1/search — Tavily, Exa, Brave, Serper</div>
                    </div>
                    <svg class="w-4 h-4 text-[var(--text-tertiary)] group-hover:text-[var(--orange-600)] transition flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                </a>
                <a href="https://raw.githubusercontent.com/decolua/9router/refs/heads/master/skills/9router-web-fetch/SKILL.md" target="_blank" class="flex items-center gap-3 p-3 rounded-lg border border-[var(--color-border)] hover:bg-[var(--color-bg)] transition group">
                    <div class="w-9 h-9 rounded-lg bg-[var(--pink-50)] flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-pink-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-[var(--text-primary)] group-hover:text-pink-600 transition">Web Fetch</div>
                        <div class="text-xs text-[var(--text-tertiary)]">/v1/web/fetch — URL to markdown/text</div>
                    </div>
                    <svg class="w-4 h-4 text-[var(--text-tertiary)] group-hover:text-pink-600 transition flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
