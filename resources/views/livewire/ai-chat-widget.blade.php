<div x-data="{ open: @js($open) }" x-on:open-chat.window="open = true" class="fixed bottom-6 right-6 z-50">
    {{-- Chat Toggle Button --}}
    <button x-on:click="open = !open" x-show="!open"
        class="w-14 h-14 rounded-full bg-[var(--indigo-600)] hover:bg-[var(--indigo-700)] text-white shadow-lg hover:shadow-xl transition-all flex items-center justify-center group">
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
        </svg>
        <span class="absolute -top-1 -right-1 w-4 h-4 bg-[var(--emerald-500)] rounded-full border-2 border-white"></span>
    </button>

    {{-- Chat Panel --}}
    <div x-show="open" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95 translate-y-4"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-4"
        class="absolute bottom-0 right-0 w-[380px] max-w-[calc(100vw-2rem)] h-[520px] max-h-[calc(100vh-6rem)] bg-[var(--color-surface)] rounded-xl shadow-2xl border border-[var(--color-border)] flex flex-col overflow-hidden">

        {{-- Header --}}
        <div class="flex items-center justify-between px-4 py-3 bg-[var(--indigo-600)] text-white">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5a3 3 0 10-3 3.85A5 5 0 1112 5"/>
                    <path d="M12 5a3 3 0 013 3.85A5 5 0 0012 5"/>
                    <path d="M15 12a1 1 0 01-1 1h-2a1 1 0 00-1 1v3a1 1 0 001 1h2a1 1 0 001-1v-3a1 1 0 011-1"/>
                </svg>
                <div>
                    <div class="text-sm font-semibold">Knowledge Hub AI</div>
                    <div class="text-[10px] opacity-80">Ask anything about your data</div>
                </div>
            </div>
            <div class="flex items-center gap-1">
                <button wire:click="clearChat" class="p-1.5 rounded hover:bg-white/20 transition text-xs" title="Clear chat">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                </button>
                <button x-on:click="open = false" class="p-1.5 rounded hover:bg-white/20 transition">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
        </div>

        {{-- Messages --}}
        <div class="flex-1 overflow-y-auto p-4 space-y-3" id="chat-messages"
            x-ref="chatMessages"
            x-effect="$refs.chatMessages.scrollTop = $refs.chatMessages.scrollHeight">

            @if(empty($messages))
                <div class="flex flex-col items-center justify-center h-full text-center px-4">
                    <div class="w-12 h-12 rounded-full bg-[var(--indigo-50)] flex items-center justify-center mb-3">
                        <svg class="w-6 h-6 text-[var(--indigo-500)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 5a3 3 0 10-3 3.85A5 5 0 1112 5"/>
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-[var(--text-primary)] mb-1">Hi! Saya AI assistant kamu.</p>
                    <p class="text-xs text-[var(--text-tertiary)]">Tanya tentang data kamu, cari info di web, atau minta notulensi rapat.</p>
                    <div class="mt-3 flex flex-wrap gap-1.5 justify-center">
                        <button wire:click="$set('message', 'Ringkasan semua data saya')" class="text-[10px] px-2 py-1 rounded-full bg-[var(--color-bg)] border border-[var(--color-border)] text-[var(--text-secondary)] hover:bg-[var(--indigo-50)] hover:text-[var(--indigo-600)] transition">
                            Ringkasan data
                        </button>
                        <button wire:click="$set('message', 'search tips productivity')" class="text-[10px] px-2 py-1 rounded-full bg-[var(--color-bg)] border border-[var(--color-border)] text-[var(--text-secondary)] hover:bg-[var(--indigo-50)] hover:text-[var(--indigo-600)] transition">
                            Search web
                        </button>
                        <button wire:click="$set('message', 'notulensi ')" class="text-[10px] px-2 py-1 rounded-full bg-[var(--color-bg)] border border-[var(--color-border)] text-[var(--text-secondary)] hover:bg-[var(--indigo-50)] hover:text-[var(--indigo-600)] transition">
                            Notulensi
                        </button>
                    </div>
                </div>
            @else
                @foreach($messages as $msg)
                    @if($msg['role'] === 'user')
                        <div class="flex justify-end">
                            <div class="max-w-[80%] px-3 py-2 rounded-2xl rounded-br-sm bg-[var(--indigo-600)] text-white text-sm leading-relaxed">
                                {!! nl2br(e($msg['content'])) !!}
                            </div>
                        </div>
                    @else
                        <div class="flex justify-start">
                            <div class="max-w-[80%] px-3 py-2 rounded-2xl rounded-bl-sm bg-[var(--color-bg)] border border-[var(--color-border)] text-[var(--text-primary)] text-sm leading-relaxed">
                                {!! nl2br(e($msg['content'])) !!}
                            </div>
                        </div>
                    @endif
                @endforeach

                @if($loading)
                    <div class="flex justify-start">
                        <div class="px-3 py-2 rounded-2xl rounded-bl-sm bg-[var(--color-bg)] border border-[var(--color-border)]">
                            <div class="flex items-center gap-1">
                                <span class="w-2 h-2 bg-[var(--text-quaternary)] rounded-full animate-bounce" style="animation-delay: 0ms"></span>
                                <span class="w-2 h-2 bg-[var(--text-quaternary)] rounded-full animate-bounce" style="animation-delay: 150ms"></span>
                                <span class="w-2 h-2 bg-[var(--text-quaternary)] rounded-full animate-bounce" style="animation-delay: 300ms"></span>
                            </div>
                        </div>
                    </div>
                @endif
            @endif
        </div>

        {{-- Input --}}
        <form wire:submit="send" class="p-3 border-t border-[var(--color-border)] bg-[var(--color-surface)]">
            <div class="flex items-end gap-2">
                <textarea wire:model="message" x-on:keydown.enter.prevent="if(!$event.shiftKey) { $wire.send() }"
                    class="flex-1 px-3 py-2 text-sm bg-[var(--color-bg)] border border-[var(--color-border)] rounded-xl resize-none focus:outline-none focus:border-[var(--indigo-500)] focus:ring-1 focus:ring-[var(--indigo-500)]"
                    rows="1" placeholder="Type a message..."
                    x-init="$watch('$wire.message', val => { $el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight, 120) + 'px'; })"
                    @if($loading) disabled @endif></textarea>
                <button type="submit" {{ $loading ? 'disabled' : '' }}
                    class="w-9 h-9 rounded-xl bg-[var(--indigo-600)] hover:bg-[var(--indigo-700)] disabled:opacity-50 text-white flex items-center justify-center transition flex-shrink-0">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                    </svg>
                </button>
            </div>
            <div class="mt-1.5 text-[10px] text-[var(--text-quaternary)] text-center">
                Enter to send &middot; Shift+Enter for new line &middot; Prefix "search " for web search
            </div>
        </form>
    </div>
</div>
