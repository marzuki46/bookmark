@extends('layouts.app')

@section('title', 'Rilis Aplikasi Android')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-[var(--text-primary)]">Rilis Aplikasi Android</h1>
        <p class="text-sm text-[var(--text-tertiary)] mt-1">Unggah APK di sini. App akan mendeteksi rilis baru lewat menu "Periksa Pembaruan" (model Play Store: unduh langsung lalu pasang).</p>
    </div>

    @if (session('status'))
        <div class="rounded-xl px-4 py-3 text-sm bg-emerald-50 text-emerald-700 border border-emerald-200">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="rounded-xl px-4 py-3 text-sm bg-red-50 text-red-700 border border-red-200">
            <ul class="list-disc pl-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('keuangan.aplikasi.store') }}" method="POST" enctype="multipart/form-data" class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-5 space-y-4">
        @csrf
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="sm:col-span-1">
                <label class="wp-form-label">Versi Kode <span class="text-red-500">*</span></label>
                <input type="number" name="version_code" required min="1" placeholder="cth: 42" class="wp-form-input">
            </div>
            <div class="sm:col-span-1">
                <label class="wp-form-label">Versi Nama <span class="text-red-500">*</span></label>
                <input type="text" name="version_name" required maxlength="32" placeholder="cth: 3.4.0" class="wp-form-input">
            </div>
            <div class="sm:col-span-1">
                <label class="wp-form-label">Berkas APK <span class="text-red-500">*</span></label>
                <input type="file" name="apk" required accept=".apk" class="wp-form-input">
            </div>
        </div>
        <div>
            <label class="wp-form-label">Catatan Rilis</label>
            <textarea name="notes" rows="3" maxlength="5000" placeholder="Apa yang baru? Ditampilkan di layar Periksa Pembaruan" class="wp-form-input"></textarea>
        </div>
        <button type="submit" class="btn-primary">Unggah Rilis</button>
    </form>

    <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-[var(--text-tertiary)] border-b border-[var(--color-border)]">
                        <th class="px-5 py-3">Versi</th>
                        <th class="px-5 py-3">Nama</th>
                        <th class="px-5 py-3">Ukuran</th>
                        <th class="px-5 py-3">SHA-256</th>
                        <th class="px-5 py-3">Diupload</th>
                        <th class="px-5 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($releases as $release)
                        <tr class="border-b border-[var(--color-border)] last:border-0">
                            <td class="px-5 py-2.5 font-semibold">{{ $release->version_code }}</td>
                            <td class="px-5 py-2.5">{{ $release->version_name }}@if($loop->first)<span class="ml-2 text-xs px-2 py-0.5 rounded-full bg-teal-50 text-teal-700">Terbaru</span>@endif</td>
                            <td class="px-5 py-2.5 text-[var(--text-tertiary)]">{{ number_format($release->file_size / 1048576, 1) }} MB</td>
                            <td class="px-5 py-2.5 text-xs font-mono text-[var(--text-tertiary)] max-w-[220px] truncate" title="{{ $release->sha256 }}">{{ $release->sha256 }}</td>
                            <td class="px-5 py-2.5 text-[var(--text-tertiary)]">{{ $release->created_at->format('d M Y H:i') }}</td>
                            <td class="px-5 py-2.5 whitespace-nowrap">
                                <a href="{{ route('app-release.download', $release) }}" class="btn-secondary !py-1 !px-2 text-xs">Unduh</a>
                                <form action="{{ route('keuangan.aplikasi.destroy', $release) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Hapus rilis v{{ $release->version_name }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-secondary !py-1 !px-2 text-xs text-red-600">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-[var(--text-tertiary)]">
                                Belum ada rilis. Unggah APK pertama di form di atas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection