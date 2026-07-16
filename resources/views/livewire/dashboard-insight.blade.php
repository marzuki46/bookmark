<div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
    <div class="px-5 py-4 border-b border-[var(--color-border)] flex items-center justify-between">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-[var(--indigo-50)] flex items-center justify-center">
                <svg class="w-4 h-4 text-[var(--indigo-600)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5a3 3 0 10-3 3.85A5 5 0 1112 5"/>
                </svg>
            </div>
            <h2 class="text-sm font-semibold text-[var(--text-primary)]">AI Insight</h2>
        </div>
        <button wire:click="generateInsight" {{ $loading ? 'disabled' : '' }}
            class="text-xs font-medium {{ $loading ? 'text-[var(--text-quaternary)]' : 'text-[var(--indigo-600)] hover:text-[var(--indigo-700)]' }} transition">
            @if($loading)
                <span class="flex items-center gap-1">
                    <svg class="w-3 h-3 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10" stroke-dasharray="30 60"/></svg>
                    Analyzing...
                </span>
            @elseif($generated)
                Refresh
            @else
                Generate Insight
            @endif
        </button>
    </div>
    <div class="p-5">
        @if($insight)
            <div class="text-sm text-[var(--text-primary)] leading-relaxed whitespace-pre-wrap">{{ $insight }}</div>
        @else
            <p class="text-sm text-[var(--text-tertiary)]">Click "Generate Insight" to get AI-powered analysis of your knowledge base.</p>
        @endif
    </div>
</div>
