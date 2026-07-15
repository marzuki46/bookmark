<div class="space-y-4">
    @if($createdTokenValue)
    <div class="bg-[var(--emerald-50)] border border-emerald-200 rounded-xl p-4">
        <div class="flex items-start gap-3">
            <div class="w-8 h-8 rounded-lg bg-[var(--emerald-600)] flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <div class="flex-1">
                <h3 class="text-sm font-semibold text-[var(--emerald-800)]">Token Generated</h3>
                <p class="text-xs text-[var(--emerald-700)] mt-1">Copy this token now. It won't be shown again.</p>
                <div class="flex items-center gap-2 mt-3">
                    <input type="text" value="{{ $createdTokenValue }}" class="wp-form-input flex-1 font-mono text-xs !bg-white" readonly id="newTokenValue">
                    <button onclick="navigator.clipboard.writeText(document.getElementById('newTokenValue').value).then(()=>{this.textContent='Copied!';setTimeout(()=>this.textContent='Copy',1500)})" class="px-3 py-2 text-xs font-medium rounded-lg bg-[var(--emerald-600)] text-white hover:bg-[var(--emerald-700)] transition whitespace-nowrap">Copy</button>
                </div>
            </div>
            <button wire:click="dismissToken" class="p-1 rounded-lg hover:bg-[var(--emerald-100)] transition text-[var(--emerald-600)]">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
    </div>
    @endif

    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-sm font-semibold text-[var(--text-primary)]">API Tokens</h3>
            <p class="text-xs text-[var(--text-tertiary)]">Used by the Chrome Extension to sync data</p>
        </div>
        @if(! $showCreateForm)
            <button wire:click="$set('showCreateForm', true)" class="btn-primary !py-1.5 !px-3 text-xs">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Generate New Token
            </button>
        @endif
    </div>

    @if($showCreateForm)
    <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-4">
        <form wire:submit="createToken" class="flex items-end gap-3">
            <div class="flex-1">
                <label class="wp-form-label">Token Name</label>
                <input type="text" wire:model="newTokenName" class="wp-form-input" placeholder="e.g. chrome-extension">
                @error('newTokenName') <span class="text-xs text-[var(--red-600)] mt-1">{{ $message }}</span> @enderror
            </div>
            <button type="submit" class="btn-primary">Generate</button>
            <button type="button" wire:click="$set('showCreateForm', false)" class="px-4 py-2 text-sm font-medium text-[var(--text-secondary)] rounded-lg border border-[var(--color-border)] hover:bg-[var(--color-bg)] transition">Cancel</button>
        </form>
    </div>
    @endif

    @if($this->tokens->isEmpty())
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-8 text-center">
            <div class="w-12 h-12 mx-auto mb-3 rounded-xl bg-[var(--indigo-50)] flex items-center justify-center">
                <svg class="w-6 h-6 text-[var(--indigo-600)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
            </div>
            <p class="text-sm text-[var(--text-tertiary)]">No tokens yet. Generate one to connect the Chrome Extension.</p>
        </div>
    @else
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-[var(--color-border)] text-left text-[var(--text-tertiary)]">
                            <th class="px-5 py-3 font-medium">Name</th>
                            <th class="px-5 py-3 font-medium">Last Used</th>
                            <th class="px-5 py-3 font-medium">Created</th>
                            <th class="px-5 py-3 font-medium text-center w-24">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($this->tokens as $token)
                        <tr class="border-b border-[var(--color-border)] last:border-0 hover:bg-[var(--color-bg)] transition">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-lg bg-[var(--indigo-50)] flex items-center justify-center">
                                        <svg class="w-4 h-4 text-[var(--indigo-600)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                                    </div>
                                    <span class="font-medium text-[var(--text-primary)]">{{ $token->name }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-[var(--text-tertiary)]">
                                {{ $token->last_used_at ? $token->last_used_at->diffForHumans() : 'Never' }}
                            </td>
                            <td class="px-5 py-3 text-[var(--text-tertiary)]">
                                {{ $token->created_at->format('d M Y') }}
                            </td>
                            <td class="px-5 py-3 text-center">
                                <button wire:click="revokeToken({{ $token->id }})" wire:confirm="Revoke this token? The extension will stop working until you generate a new one." class="p-1.5 rounded-lg hover:bg-[var(--red-50)] transition text-[var(--text-tertiary)] hover:text-[var(--red-600)]" title="Revoke">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-4">
        <div class="flex items-center gap-3">
            <label class="wp-form-label !mb-0 whitespace-nowrap">API URL</label>
            <input type="text" value="{{ url('/api') }}" class="wp-form-input flex-1 font-mono text-xs" readonly id="apiUrlField">
            <button onclick="navigator.clipboard.writeText(document.getElementById('apiUrlField').value).then(()=>{this.textContent='Copied!';setTimeout(()=>this.textContent='Copy',1500)})" class="px-3 py-2 text-xs font-medium rounded-lg border border-[var(--color-border)] hover:bg-[var(--color-bg)] transition whitespace-nowrap">Copy</button>
        </div>
    </div>
</div>
