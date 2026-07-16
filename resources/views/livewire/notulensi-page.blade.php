<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-[var(--text-primary)]">Notulensi AI</h1>
            <p class="text-sm text-[var(--text-tertiary)] mt-1">Generate structured meeting notes from raw text</p>
        </div>
    </div>

    @if($statusMessage)
        <div class="mb-4 px-4 py-3 rounded-lg text-sm font-medium {{ $statusType === 'success' ? 'bg-[var(--emerald-50)] text-[var(--emerald-700)] border border-[var(--emerald-200)]' : 'bg-red-50 text-red-700 border border-red-200' }}"
            x-data x-init="setTimeout(() => $wire.clearStatusMessage(), 5000)">
            {{ $statusMessage }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Input --}}
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-[var(--color-border)]">
                <h2 class="text-sm font-semibold text-[var(--text-primary)]">Teks Rapat / Meeting Notes</h2>
            </div>
            <div class="p-5">
                <textarea wire:model="meetingText" class="wp-form-input" rows="16"
                    placeholder="Paste atau ketik teks rapat di sini...

Contoh:
Rapat tim marketing 15 Juli 2026. Hadir: Budi, Sari, Andi.
Bahasan: strategi Q3, budget iklan social media, timeline launching produk baru.
Keputusan: budget naik 20%, launching 1 Agustus.
Action items: Sari buat konten IG, Andi setup ads, Budi review landing page.

Semakin detail teks, semakin bagus notulensi yang dihasilkan."></textarea>

                <div class="flex items-center gap-3 mt-4">
                    <button wire:click="generate" {{ $generating ? 'disabled' : '' }}
                        class="btn-primary {{ $generating ? 'opacity-50' : '' }}">
                        @if($generating)
                            <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10" stroke-dasharray="30 60"/></svg>
                            Generating...
                        @else
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5a3 3 0 10-3 3.85A5 5 0 1112 5"/></svg>
                            Generate Notulensi
                        @endif
                    </button>
                    @if($meetingText || $result)
                        <button wire:click="clearAll" class="px-4 py-2 text-sm font-medium text-[var(--text-secondary)] rounded-lg border border-[var(--color-border)] hover:bg-[var(--color-bg)] transition">
                            Clear All
                        </button>
                    @endif
                </div>
            </div>
        </div>

        {{-- Result --}}
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-[var(--color-border)] flex items-center justify-between">
                <h2 class="text-sm font-semibold text-[var(--text-primary)]">Hasil Notulensi</h2>
                @if($result)
                    <button wire:click="saveAsNote" {{ $saved ? 'disabled' : '' }}
                        class="px-3 py-1.5 text-xs font-medium rounded-lg transition
                            {{ $saved
                                ? 'bg-[var(--emerald-50)] text-[var(--emerald-700)] border border-[var(--emerald-200)]'
                                : 'bg-[var(--indigo-600)] text-white hover:bg-[var(--indigo-700)]' }}">
                        @if($saved)
                            <span class="flex items-center gap-1">
                                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                Saved
                            </span>
                        @else
                            <span class="flex items-center gap-1">
                                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                                Save as Note
                            </span>
                        @endif
                    </button>
                @endif
            </div>

            @if($result)
                <div class="px-5 py-3 border-b border-[var(--color-border)]">
                    <input type="text" wire:model="noteTitle" class="wp-form-input text-sm" placeholder="Judul note (opsional, default: Notulensi - [tanggal])">
                </div>
            @endif

            <div class="p-5 min-h-[400px]">
                @if($result)
                    <div class="prose prose-sm max-w-none text-[var(--text-primary)] leading-relaxed whitespace-pre-wrap">{{ $result }}</div>
                @else
                    <div class="flex flex-col items-center justify-center h-[360px] text-center">
                        <div class="w-12 h-12 rounded-full bg-[var(--indigo-50)] flex items-center justify-center mb-3">
                            <svg class="w-6 h-6 text-[var(--indigo-500)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                                <path d="M14 2v6h6"/>
                                <path d="M16 13H8"/>
                                <path d="M16 17H8"/>
                                <path d="M10 9H8"/>
                            </svg>
                        </div>
                        <p class="text-sm text-[var(--text-tertiary)]">Hasil notulensi akan muncul di sini</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
