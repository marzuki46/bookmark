<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-[var(--text-primary)]">Settings</h1>
        <p class="text-sm text-[var(--text-tertiary)] mt-1">Manage your account and preferences</p>
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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div class="lg:col-span-2 space-y-5">
            {{-- General --}}
            <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-[var(--color-border)]">
                    <h2 class="text-sm font-semibold text-[var(--text-primary)]">General</h2>
                </div>
                <div class="p-5 space-y-4">
                    <div>
                        <label class="wp-form-label">Name</label>
                        <input type="text" value="{{ auth()->user()->name }}" class="wp-form-input" disabled>
                    </div>
                    <div>
                        <label class="wp-form-label">Current Email</label>
                        <input type="email" value="{{ auth()->user()->email }}" class="wp-form-input" disabled>
                    </div>
                    <hr class="border-[var(--color-border)]">
                    <div class="bg-[var(--amber-50)] border border-[var(--amber-200)] rounded-lg p-3">
                        <p class="text-xs text-[var(--amber-700)]">Changing email requires password verification. A confirmation link will be sent to your current email.</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="wp-form-label">New Email</label>
                            <input type="email" wire:model="newEmail" class="wp-form-input" placeholder="new@email.com">
                        </div>
                        <div>
                            <label class="wp-form-label">Confirm New Email</label>
                            <input type="email" wire:model="confirmEmail" class="wp-form-input" placeholder="Confirm new email">
                        </div>
                    </div>
                    <div>
                        <label class="wp-form-label">Current Password (required)</label>
                        <input type="password" wire:model="currentPassword" class="wp-form-input" placeholder="Enter current password">
                    </div>
                    <button wire:click="updateEmail" class="btn-primary">Send Confirmation Email</button>
                </div>
            </div>

            {{-- AI Settings --}}
            <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-[var(--color-border)]">
                    <h2 class="text-sm font-semibold text-[var(--text-primary)]">AI Settings</h2>
                </div>
                <div class="p-5 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="wp-form-label">AI Provider</label>
                            <select wire:model="aiProvider" class="wp-form-input">
                                <option value="openai">OpenAI</option>
                                <option value="gemini">Google Gemini</option>
                                <option value="claude">Anthropic Claude</option>
                                <option value="custom">Custom (9Router / OpenAI-compatible)</option>
                            </select>
                        </div>
                        <div>
                            <label class="wp-form-label">Model</label>
                            <input type="text" wire:model="aiModel" class="wp-form-input"
                                placeholder="{{ match($aiProvider) { 'openai' => 'gpt-4o-mini', 'gemini' => 'gemini-2.0-flash', 'claude' => 'claude-sonnet-4-20250514', default => 'openai/gpt-4o-mini' } }}">
                        </div>
                    </div>

                    @if($aiProvider === 'custom')
                        <div class="bg-[var(--indigo-50)] border border-[var(--indigo-200)] rounded-lg p-4 text-sm text-[var(--indigo-700)]">
                            <strong>9Router Custom</strong> — Uses OpenAI-compatible API format. Set your 9Router endpoint, API key, and model name.
                        </div>
                    @endif

                    <div>
                        <label class="wp-form-label">API URL</label>
                        <input type="url" wire:model="aiApiUrl" class="wp-form-input"
                            placeholder="{{ match($aiProvider) { 'openai' => 'https://api.openai.com/v1', 'gemini' => 'https://generativelanguage.googleapis.com/v1beta', 'claude' => 'https://api.anthropic.com', default => 'https://9router.com/v1' } }}">
                        @if($aiProvider === 'custom')
                            <p class="text-xs text-[var(--text-quaternary)] mt-1">Example: https://9router.com/v1</p>
                        @endif
                    </div>

                    <div>
                        <label class="wp-form-label">API Key</label>
                        <input type="password" wire:model="aiApiKey" class="wp-form-input" placeholder="sk-...">
                    </div>

                    <div class="space-y-2">
                        <div class="flex items-center justify-between p-3 rounded-lg border border-[var(--color-border)]">
                            <div>
                                <div class="text-sm font-medium text-[var(--text-primary)]">Auto Summary</div>
                                <div class="text-xs text-[var(--text-tertiary)]">Automatically generate AI summaries</div>
                            </div>
                            <button wire:click="$set('aiAutoSummary', {{ $aiAutoSummary ? 'false' : 'true' }})"
                                class="w-10 h-6 rounded-full relative transition-colors {{ $aiAutoSummary ? 'bg-[var(--indigo-600)]' : 'bg-[var(--color-bg)]' }}">
                                <div class="w-4 h-4 bg-white rounded-full absolute top-1 shadow transition-transform {{ $aiAutoSummary ? 'left-5' : 'left-1' }}"></div>
                            </button>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-lg border border-[var(--color-border)]">
                            <div>
                                <div class="text-sm font-medium text-[var(--text-primary)]">Auto Category</div>
                                <div class="text-xs text-[var(--text-tertiary)]">AI suggests categories for new items</div>
                            </div>
                            <button wire:click="$set('aiAutoCategory', {{ $aiAutoCategory ? 'false' : 'true' }})"
                                class="w-10 h-6 rounded-full relative transition-colors {{ $aiAutoCategory ? 'bg-[var(--indigo-600)]' : 'bg-[var(--color-bg)]' }}">
                                <div class="w-4 h-4 bg-white rounded-full absolute top-1 shadow transition-transform {{ $aiAutoCategory ? 'left-5' : 'left-1' }}"></div>
                            </button>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-lg border border-[var(--color-border)]">
                            <div>
                                <div class="text-sm font-medium text-[var(--text-primary)]">Auto Tagging</div>
                                <div class="text-xs text-[var(--text-tertiary)]">AI assigns tags automatically</div>
                            </div>
                            <button wire:click="$set('aiAutoTagging', {{ $aiAutoTagging ? 'false' : 'true' }})"
                                class="w-10 h-6 rounded-full relative transition-colors {{ $aiAutoTagging ? 'bg-[var(--indigo-600)]' : 'bg-[var(--color-bg)]' }}">
                                <div class="w-4 h-4 bg-white rounded-full absolute top-1 shadow transition-transform {{ $aiAutoTagging ? 'left-5' : 'left-1' }}"></div>
                            </button>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button wire:click="saveAiSettings" class="btn-primary">Save AI Settings</button>
                    </div>
                </div>
            </div>

            {{-- Security --}}
            <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-[var(--color-border)]">
                    <h2 class="text-sm font-semibold text-[var(--text-primary)]">Security</h2>
                </div>
                <div class="p-5 space-y-4">
                    <div class="bg-[var(--amber-50)] border border-[var(--amber-200)] rounded-lg p-3">
                        <p class="text-xs text-[var(--amber-700)]">Changes require email confirmation. You'll receive an email with an approval link.</p>
                    </div>
                    <div>
                        <label class="wp-form-label">Current Password (required for all changes)</label>
                        <input type="password" wire:model="currentPassword" class="wp-form-input" placeholder="Enter current password">
                    </div>
                    <hr class="border-[var(--color-border)]">
                    <h3 class="text-sm font-medium text-[var(--text-primary)]">Change Password</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="wp-form-label">New Password</label>
                            <input type="password" wire:model="newPassword" class="wp-form-input" placeholder="Min. 8 characters">
                        </div>
                        <div>
                            <label class="wp-form-label">Confirm Password</label>
                            <input type="password" wire:model="confirmPassword" class="wp-form-input" placeholder="Confirm new password">
                        </div>
                    </div>
                    <button wire:click="updatePassword" class="btn-primary">Send Confirmation Email</button>
                    <hr class="border-[var(--color-border)]">
                    <h3 class="text-sm font-medium text-[var(--text-primary)]">Change Secret Vault PIN</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="wp-form-label">New PIN (4-6 digits)</label>
                            <input type="password" wire:model="newPin" class="wp-form-input" placeholder="----" maxlength="6" inputmode="numeric">
                        </div>
                        <div>
                            <label class="wp-form-label">Confirm PIN</label>
                            <input type="password" wire:model="confirmPin" class="wp-form-input" placeholder="Confirm PIN" maxlength="6" inputmode="numeric">
                        </div>
                    </div>
                    <button wire:click="updatePin" class="btn-primary">Send PIN Change Email</button>
                </div>
            </div>
        </div>

        <div class="space-y-5">
            {{-- API Token --}}
            <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-[var(--color-border)]">
                    <h2 class="text-sm font-semibold text-[var(--text-primary)]">API Token</h2>
                </div>
                <div class="p-5 space-y-3">
                    <p class="text-xs text-[var(--text-tertiary)]">Used by the Chrome Extension to sync data.</p>
                    <div class="flex items-center gap-2">
                        <input type="text" value="{{ auth()->user()->createToken('chrome-extension')->plainTextToken ?? '••••••••••••••••' }}" class="wp-form-input flex-1 text-xs" disabled>
                    </div>
                    <p class="text-xs text-[var(--text-quaternary)]">Generate a new token from the Extension page.</p>
                </div>
            </div>

            {{-- System --}}
            <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-[var(--color-border)]">
                    <h2 class="text-sm font-semibold text-[var(--text-primary)]">System</h2>
                </div>
                <div class="p-5 space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-[var(--text-tertiary)]">Version</span>
                        <span class="font-medium text-[var(--text-primary)]">1.0.0</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-[var(--text-tertiary)]">PHP</span>
                        <span class="font-medium text-[var(--text-primary)]">{{ PHP_VERSION }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-[var(--text-tertiary)]">Laravel</span>
                        <span class="font-medium text-[var(--text-primary)]">{{ app()->version() }}</span>
                    </div>
                    <hr class="border-[var(--color-border)]">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-[var(--text-tertiary)]">AI Provider</span>
                        <span class="font-medium text-[var(--text-primary)]">{{ $aiProvider === 'custom' ? '9Router Custom' : ucfirst($aiProvider) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-[var(--text-tertiary)]">AI Model</span>
                        <span class="font-medium text-[var(--text-primary)]">{{ $aiModel ?: '(not set)' }}</span>
                    </div>
                    <hr class="border-[var(--color-border)]">
                    <button wire:click="clearCache" class="w-full px-4 py-2.5 text-sm font-medium rounded-lg border border-[var(--color-border)] hover:bg-[var(--color-bg)] transition flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/></svg>
                        Clear All Cache
                    </button>
                    @if($cacheStatus)
                        <p class="text-xs text-[var(--emerald-600)] text-center">{{ $cacheStatus }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
