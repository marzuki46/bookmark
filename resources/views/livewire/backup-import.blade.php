<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-[var(--text-primary)]">Backup & Restore</h1>
        <p class="text-sm text-[var(--text-tertiary)] mt-1">Export and import your knowledge base</p>
    </div>

    @if($statusMessage)
        <div class="px-4 py-3 rounded-lg text-sm font-medium flex items-center justify-between
            {{ $statusType === 'success' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200' }}">
            <span>{{ $statusMessage }}</span>
            <button wire:click="clearStatusMessage" class="p-1 rounded hover:bg-white/50">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        {{-- Export --}}
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-6">
            <div class="flex items-center gap-4 mb-5">
                <div class="w-12 h-12 rounded-xl bg-[var(--indigo-50)] flex items-center justify-center text-[var(--indigo-600)]">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-[var(--text-primary)]">Export Data</h2>
                    <p class="text-sm text-[var(--text-tertiary)]">Download all your data as JSON</p>
                </div>
            </div>
            <div class="bg-[var(--color-bg)] rounded-lg p-4 mb-5">
                <div class="text-xs font-semibold text-[var(--text-tertiary)] uppercase tracking-wider mb-2">Data Summary</div>
                <div class="grid grid-cols-2 gap-2">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-[var(--text-secondary)]">Bookmarks</span>
                        <span class="font-medium text-[var(--text-primary)]">{{ $statsBookmarks }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-[var(--text-secondary)]">Notes</span>
                        <span class="font-medium text-[var(--text-primary)]">{{ $statsNotes }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-[var(--text-secondary)]">Prompts</span>
                        <span class="font-medium text-[var(--text-primary)]">{{ $statsPrompts }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-[var(--text-secondary)]">Snippets</span>
                        <span class="font-medium text-[var(--text-primary)]">{{ $statsSnippets }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-[var(--text-secondary)]">Files</span>
                        <span class="font-medium text-[var(--text-primary)]">{{ $statsFiles }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-[var(--text-secondary)]">Secrets</span>
                        <span class="font-medium text-[var(--text-primary)]">{{ $statsSecrets }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-[var(--text-secondary)]">Tags</span>
                        <span class="font-medium text-[var(--text-primary)]">{{ $statsTags }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-[var(--text-secondary)]">Collections</span>
                        <span class="font-medium text-[var(--text-primary)]">{{ $statsCollections }}</span>
                    </div>
                    <div class="col-span-2 pt-2 mt-2 border-t border-[var(--color-border)] flex items-center justify-between text-sm font-semibold">
                        <span class="text-[var(--text-primary)]">Total Items</span>
                        <span class="text-[var(--text-primary)]">{{ $statsBookmarks + $statsNotes + $statsPrompts + $statsSnippets + $statsFiles + $statsSecrets }}</span>
                    </div>
                </div>
            </div>
            <a href="{{ url('/api/export') }}" class="btn-primary w-full justify-center">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Export All Data
            </a>
        </div>

        {{-- Import --}}
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-6">
            <div class="flex items-center gap-4 mb-5">
                <div class="w-12 h-12 rounded-xl bg-[var(--emerald-50)] flex items-center justify-center text-[var(--emerald-600)]">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-[var(--text-primary)]">Import Data</h2>
                    <p class="text-sm text-[var(--text-tertiary)]">Restore from a JSON backup file</p>
                </div>
            </div>

            <div wire:click="openImport" class="border-2 border-dashed border-[var(--color-border)] rounded-lg p-8 text-center cursor-pointer hover:border-[var(--indigo-400)] hover:bg-[var(--indigo-50)]/30 transition">
                <svg class="w-10 h-10 mx-auto text-[var(--text-quaternary)] mb-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>
                <p class="text-sm text-[var(--text-tertiary)]">Click to select your backup file</p>
                <p class="text-xs text-[var(--text-quaternary)] mt-1">Supports JSON backup files</p>
            </div>
        </div>
    </div>

    {{-- Import Modal --}}
    @if($showImportModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data>
            <div class="fixed inset-0 bg-black/50" wire:click="closeImportModal"></div>
            <div class="relative bg-[var(--color-surface)] rounded-xl shadow-xl w-full max-w-lg border border-[var(--color-border)]">
                <div class="flex items-center justify-between px-6 py-4 border-b border-[var(--color-border)]">
                    <h3 class="text-lg font-semibold text-[var(--text-primary)]">Import Backup</h3>
                    <button wire:click="closeImportModal" class="p-1 rounded hover:bg-[var(--color-bg)] transition text-[var(--text-quaternary)]">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    @if(!$importFile)
                        <div class="text-center py-8 border-2 border-dashed border-[var(--color-border)] rounded-xl">
                            <svg class="w-12 h-12 mx-auto text-[var(--text-quaternary)] mb-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            <p class="text-sm text-[var(--text-secondary)] mb-1">Select your JSON backup file</p>
                            <label class="btn-primary cursor-pointer inline-flex items-center gap-2 mt-3">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                Choose File
                                <input type="file" wire:model="importFile" accept=".json" class="hidden">
                            </label>
                            @error('importFile') <p class="text-xs text-[var(--red-500)] mt-2">{{ $message }}</p> @enderror
                        </div>
                    @else
                        <div class="bg-[var(--color-bg)] rounded-lg p-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-[var(--emerald-50)] flex items-center justify-center">
                                    <svg class="w-5 h-5 text-[var(--emerald-600)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-[var(--text-primary)] truncate">{{ $importFile->getClientOriginalName() }}</p>
                                    <p class="text-xs text-[var(--text-quaternary)]">{{ number_format($importFile->getSize() / 1024, 1) }} KB</p>
                                </div>
                                <button wire:click="$set('importFile', null)" class="p-1 rounded hover:bg-[var(--color-surface-hover)] transition text-[var(--text-quaternary)]">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                </button>
                            </div>
                        </div>

                        @if($importType === 'invalid')
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-sm text-red-700">
                                Invalid backup file. Please select a valid JSON backup file.
                            </div>
                        @endif

                        @if($importPreview !== null && $importType === 'valid')
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <p class="text-sm font-medium text-[var(--text-primary)]">{{ $importCount }} items found</p>
                                    @if($importCount > 10)
                                        <p class="text-xs text-[var(--text-quaternary)]">Showing first 10</p>
                                    @endif
                                </div>
                                <div class="max-h-60 overflow-y-auto border border-[var(--color-border)] rounded-lg">
                                    @foreach($importPreview as $item)
                                        <div class="px-3 py-2 border-b border-[var(--color-border)] last:border-0 text-sm">
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-mono px-1.5 py-0.5 rounded bg-[var(--color-bg)] text-[var(--text-quaternary)]">{{ $item['type'] ?? 'bookmark' }}</span>
                                                <span class="font-medium text-[var(--text-primary)] truncate">{{ $item['title'] ?? '(untitled)' }}</span>
                                            </div>
                                            @if(!empty($item['url']))
                                                <div class="text-xs text-[var(--text-quaternary)] truncate font-mono mt-0.5">{{ $item['url'] }}</div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" wire:click="closeImportModal" class="px-4 py-2 text-sm font-medium text-[var(--text-secondary)] rounded-lg border border-[var(--color-border)] hover:bg-[var(--color-bg)] transition">Cancel</button>
                            <button type="button" wire:click="doImport" wire:loading.attr="disabled" class="btn-primary" {{ $importType !== 'valid' ? 'disabled' : '' }}>
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                Import {{ $importCount }} Items
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
