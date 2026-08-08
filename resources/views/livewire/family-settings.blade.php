<div class="space-y-6">
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold text-[var(--text-primary)]">Pengaturan Keluarga</h1>
            <p class="text-sm text-[var(--text-tertiary)] mt-1">Kelola nama keluarga, anggota, dan kode undangan</p>
        </div>
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

    @if(! $family)
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-8 text-center">
            <div class="text-3xl mb-2">🏠</div>
            <p class="text-sm text-[var(--text-tertiary)]">Buat keluarga dulu di menu Dashboard Keluarga.</p>
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-[var(--color-border)]">
                    <h3 class="text-sm font-semibold text-[var(--text-primary)]">Nama Keluarga</h3>
                </div>
                <form wire:submit="saveFamilyName" class="p-5 space-y-4">
                    <div>
                        <label class="wp-form-label">Nama</label>
                        <input type="text" wire:model="familyName" class="wp-form-input" required>
                        @error('familyName') <span class="text-xs text-red-600 mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="btn-primary">Simpan Nama</button>
                    </div>
                </form>
            </div>

            <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-[var(--color-border)] flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-[var(--text-primary)]">Kode Undangan</h3>
                    <button wire:click="regenerateInviteCode" wire:confirm="Buat kode undangan baru?" class="btn-secondary !py-1.5 text-xs">Regenerate</button>
                </div>
                <div class="p-5 space-y-3">
                    <p class="text-xs text-[var(--text-tertiary)]">Bagikan kode ini agar pasangan bisa bergabung ke keluarga Anda.</p>
                    <div class="flex items-center gap-3">
                        <code class="flex-1 text-center text-2xl font-bold tracking-[0.3em] text-[var(--indigo-600)] bg-[var(--color-bg)] border border-dashed border-[var(--color-border-strong)] rounded-lg px-4 py-3">
                            {{ $inviteCode }}
                        </code>
                    </div>
                    <button wire:click="openInvite" class="btn-secondary w-full justify-center">Saya punya kode dari keluarga lain</button>
                </div>
            </div>

            <div class="lg:col-span-2 bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-[var(--color-border)] flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-[var(--text-primary)]">Anggota Keluarga</h3>
                    <button wire:click="openCreateMember" class="btn-primary !py-1.5 text-xs">+ Tambah Anggota</button>
                </div>
                <div class="divide-y divide-[var(--color-border)]">
                    @forelse($members as $member)
                        <div class="px-5 py-3 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-9 h-9 rounded-full bg-indigo-50 text-indigo-600 font-semibold flex items-center justify-center shrink-0">
                                    {{ Str::upper($member->user->name[0] ?? '?') }}
                                </div>
                                <div class="min-w-0">
                                    <div class="text-sm font-medium text-[var(--text-primary)] truncate">
                                        {{ $member->user->name }}
                                        @if($member->role === 'owner')
                                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600 font-semibold ml-1">Owner</span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-[var(--text-tertiary)] truncate">{{ $member->user->email }}</div>
                                </div>
                            </div>
                            @if($member->role !== 'owner' && $member->user_id !== auth()->id())
                                <button wire:click="removeMember({{ $member->id }})" wire:confirm="Keluarkan anggota ini?" class="btn-icon text-red-500 hover:text-red-600" title="Keluarkan">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                                </button>
                            @endif
                        </div>
                    @empty
                        <div class="px-5 py-8 text-center text-xs text-[var(--text-quaternary)]">Belum ada anggota.</div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    {{-- Create Member Modal --}}
    @if($showCreateMemberModal && $family)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" wire:click="closeCreateMember"></div>
            <div class="relative bg-[var(--color-surface)] rounded-xl shadow-xl w-full max-w-md border border-[var(--color-border)]">
                <div class="flex items-center justify-between px-6 py-4 border-b border-[var(--color-border)]">
                    <h3 class="text-lg font-semibold text-[var(--text-primary)]">Tambah Anggota</h3>
                    <button wire:click="closeCreateMember" class="p-1 rounded hover:bg-[var(--color-bg)] transition text-[var(--text-quaternary)]">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <form wire:submit="createMember" class="p-6 space-y-4">
                    <p class="text-xs text-[var(--text-tertiary)]">Buat akun khusus keluarga (mis: untuk istri). Sampaikan email & password ke pasangan untuk login mandiri.</p>
                    <div>
                        <label class="wp-form-label">Nama *</label>
                        <input type="text" wire:model="memberName" class="wp-form-input" required>
                        @error('memberName') <span class="text-xs text-red-600 mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="wp-form-label">Email *</label>
                        <input type="email" wire:model="memberEmail" class="wp-form-input" required>
                        @error('memberEmail') <span class="text-xs text-red-600 mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="wp-form-label">Password *</label>
                        <input type="password" wire:model="memberPassword" class="wp-form-input" required>
                        @error('memberPassword') <span class="text-xs text-red-600 mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" wire:click="closeCreateMember" class="btn-secondary">Batal</button>
                        <button type="submit" class="btn-primary">Buat Akun</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Invite Modal --}}
    @if($showInviteModal && $family)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" wire:click="closeInvite"></div>
            <div class="relative bg-[var(--color-surface)] rounded-xl shadow-xl w-full max-w-md border border-[var(--color-border)]">
                <div class="flex items-center justify-between px-6 py-4 border-b border-[var(--color-border)]">
                    <h3 class="text-lg font-semibold text-[var(--text-primary)]">Gabung Keluarga Lain</h3>
                    <button wire:click="closeInvite" class="p-1 rounded hover:bg-[var(--color-bg)] transition text-[var(--text-quaternary)]">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <form wire:submit="joinFamily" class="p-6 space-y-4">
                    <div>
                        <label class="wp-form-label">Kode Undangan</label>
                        <input type="text" wire:model="joinCode" class="wp-form-input uppercase tracking-widest" placeholder="XXXXXX" required>
                        @error('joinCode') <span class="text-xs text-red-600 mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" wire:click="closeInvite" class="btn-secondary">Batal</button>
                        <button type="submit" class="btn-primary">Gabung</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
