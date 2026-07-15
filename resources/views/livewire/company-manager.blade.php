<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-[var(--text-primary)]">Manajemen Perusahaan</h1>
            <p class="text-sm text-[var(--text-tertiary)] mt-1">Kelola profil perusahaan untuk invoice</p>
        </div>
        @if(! $showForm)
            <button wire:click="openCreate" class="btn-primary">
                + Tambah
            </button>
        @endif
    </div>

    {{-- Form Card --}}
    @if($showForm)
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-[var(--color-border)]">
                <h2 class="text-sm font-semibold text-[var(--text-primary)]">
                    {{ $editingId ? 'Edit Perusahaan' : 'Tambah Perusahaan' }}
                </h2>
            </div>
            <form wire:submit="save" class="p-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="wp-form-label">Nama Perusahaan *</label>
                        <input type="text" wire:model="formName" class="wp-form-input" placeholder="PT Contoh" required>
                        @error('formName') <span class="text-xs text-[var(--red-600)] mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="wp-form-label">PIC / Penanggung Jawab</label>
                        <input type="text" wire:model="formPicName" class="wp-form-input" placeholder="Nama PIC">
                    </div>

                    <div>
                        <label class="wp-form-label">Logo Perusahaan</label>
                        <input type="file" wire:model="logo" accept="image/*" class="wp-form-input !py-1.5">
                        @error('logo') <span class="text-xs text-[var(--red-600)] mt-1">{{ $message }}</span> @enderror
                        @if($editingId && ! $logo)
                            @php $company = App\Models\Company::find($editingId); @endphp
                            @if($company?->logo_path)
                                <div class="mt-2">
                                    <img src="{{ asset('storage/' . $company->logo_path) }}" alt="Logo" class="h-12 w-auto object-contain rounded border border-[var(--color-border)]">
                                </div>
                            @endif
                        @endif
                        @if($logo)
                            <div class="mt-2">
                                <img src="{{ $logo->temporaryUrl() }}" alt="Preview" class="h-12 w-auto object-contain rounded border border-[var(--color-border)]">
                            </div>
                        @endif
                    </div>

                    <div>
                        <label class="wp-form-label">Tanda Tangan</label>
                        <input type="file" wire:model="signature" accept="image/*" class="wp-form-input !py-1.5">
                        @error('signature') <span class="text-xs text-[var(--red-600)] mt-1">{{ $message }}</span> @enderror
                        @if($editingId && ! $signature)
                            @php $company = App\Models\Company::find($editingId); @endphp
                            @if($company?->signature_path)
                                <div class="mt-2">
                                    <img src="{{ asset('storage/' . $company->signature_path) }}" alt="Tanda Tangan" class="h-10 w-auto object-contain rounded border border-[var(--color-border)]">
                                </div>
                            @endif
                        @endif
                        @if($signature)
                            <div class="mt-2">
                                <img src="{{ $signature->temporaryUrl() }}" alt="Preview" class="h-10 w-auto object-contain rounded border border-[var(--color-border)]">
                            </div>
                        @endif
                    </div>

                    <div class="md:col-span-2">
                        <label class="wp-form-label">Alamat</label>
                        <textarea wire:model="formAddress" class="wp-form-input" rows="2" placeholder="Alamat lengkap"></textarea>
                    </div>

                    <div>
                        <label class="wp-form-label">Email</label>
                        <input type="email" wire:model="formEmail" class="wp-form-input" placeholder="info@perusahaan.com">
                    </div>

                    <div>
                        <label class="wp-form-label">Telepon</label>
                        <input type="text" wire:model="formPhone" class="wp-form-input" placeholder="0812xxxx">
                    </div>

                    <div>
                        <label class="wp-form-label">Bank</label>
                        <input type="text" wire:model="formBank" class="wp-form-input" placeholder="BCA / Mandiri / dll">
                    </div>

                    <div>
                        <label class="wp-form-label">No. Rekening</label>
                        <input type="text" wire:model="formAccNum" class="wp-form-input" placeholder="1234567890">
                    </div>

                    <div>
                        <label class="wp-form-label">Atas Nama Rekening</label>
                        <input type="text" wire:model="formAccName" class="wp-form-input" placeholder="Nama pemilik rekening">
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-[var(--color-border)]">
                    <button type="button" wire:click="closeForm" class="px-4 py-2 text-sm font-medium text-[var(--text-secondary)] rounded-lg border border-[var(--color-border)] hover:bg-[var(--color-bg)] transition">Batal</button>
                    <button type="submit" class="btn-primary">{{ $editingId ? 'Update' : 'Simpan' }}</button>
                </div>
            </form>
        </div>
    @endif

    {{-- Company Cards Grid --}}
    @if($this->companies->isEmpty() && ! $showForm)
        <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
            <div class="empty-state">
                <div class="empty-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 22V4a2 2 0 012-2h8a2 2 0 012 2v18z"/><path d="M6 12H4a2 2 0 00-2 2v6a2 2 0 002 2h2"/><path d="M18 9h2a2 2 0 012 2v9a2 2 0 01-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>
                </div>
                <div class="empty-title">Belum ada perusahaan</div>
                <div class="empty-desc">Tambahkan profil perusahaan untuk digunakan di invoice.</div>
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($this->companies as $company)
                <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden hover:shadow-md transition-all">
                    <div class="p-5">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center gap-3">
                                @if($company->logo_path)
                                    <img src="{{ asset('storage/' . $company->logo_path) }}" alt="Logo" class="w-10 h-10 object-contain rounded border border-[var(--color-border)]">
                                @else
                                    <div class="w-10 h-10 rounded-lg bg-[var(--indigo-50)] flex items-center justify-center text-[var(--indigo-600)] font-bold text-sm">
                                        {{ strtoupper(substr($company->name, 0, 2)) }}
                                    </div>
                                @endif
                                <div>
                                    <h3 class="font-semibold text-[var(--text-primary)] text-sm">{{ $company->name }}</h3>
                                    @if($company->pic_name)
                                        <p class="text-xs text-[var(--text-tertiary)]">PIC: {{ $company->pic_name }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-1">
                                <button wire:click="openEdit({{ $company->id }})" class="p-1.5 rounded-lg hover:bg-[var(--color-bg)] transition text-[var(--text-tertiary)]" title="Edit">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </button>
                                <button wire:click="deleteCompany({{ $company->id }})" wire:confirm="Hapus perusahaan ini beserta file terkait?" class="p-1.5 rounded-lg hover:bg-[var(--red-50)] transition text-[var(--text-tertiary)] hover:text-[var(--red-600)]" title="Hapus">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                                </button>
                            </div>
                        </div>

                        <div class="space-y-2 text-xs">
                            @if($company->address)
                                <div class="flex items-start gap-2 text-[var(--text-secondary)]">
                                    <svg class="w-3.5 h-3.5 mt-0.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                    <span>{{ $company->address }}</span>
                                </div>
                            @endif

                            @if($company->email)
                                <div class="flex items-center gap-2 text-[var(--text-secondary)]">
                                    <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                    <span>{{ $company->email }}</span>
                                </div>
                            @endif

                            @if($company->phone)
                                <div class="flex items-center gap-2 text-[var(--text-secondary)]">
                                    <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/></svg>
                                    <span>{{ $company->phone }}</span>
                                </div>
                            @endif

                            @if($company->bank_name)
                                <div class="flex items-center gap-2 text-[var(--text-secondary)]">
                                    <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="22" x2="21" y2="22"/><line x1="6" y1="18" x2="6" y2="11"/><line x1="10" y1="18" x2="10" y2="11"/><line x1="14" y1="18" x2="14" y2="11"/><line x1="18" y1="18" x2="18" y2="11"/><polygon points="12 2 20 7 4 7"/></svg>
                                    <span>{{ $company->bank_name }} - {{ $company->acc_number }} ({{ $company->acc_name }})</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
