@extends('layouts.app')

@section('title', 'Manajemen Aplikasi')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-[var(--text-primary)]">Manajemen Aplikasi</h1>
        <p class="text-sm text-[var(--text-tertiary)] mt-1">Rilis APK, kelola lisensi keluarga, dan pantau log akses dalam satu tempat</p>
    </div>

    <nav class="flex gap-2 flex-wrap border-b border-[var(--color-border)] pb-2" aria-label="Sub-menu Manajemen Aplikasi">
        @php
            $tabs = [
                'rilis' => ['label' => 'Rilis Aplikasi', 'url' => route('keuangan.aplikasi-manajemen', ['tab' => 'rilis'])],
                'lisensi' => ['label' => 'Manajemen Lisensi', 'url' => route('keuangan.aplikasi-manajemen', ['tab' => 'lisensi'])],
                'pembayaran' => ['label' => 'Pembayaran', 'url' => route('keuangan.aplikasi-manajemen', ['tab' => 'pembayaran'])],
                'ai' => ['label' => 'AI Sistem Hub', 'url' => route('keuangan.aplikasi-manajemen', ['tab' => 'ai'])],
                'log' => ['label' => 'Log Akses', 'url' => route('keuangan.aplikasi-manajemen', ['tab' => 'log'])],
            ];
        @endphp
        @foreach($tabs as $key => $tab)
            <a href="{{ $tab['url'] }}"
               class="px-4 py-2 rounded-full text-sm font-medium transition
                   {{ $activeTab === $key ? 'bg-indigo-600 text-white' : 'text-[var(--text-secondary)] hover:bg-[var(--color-bg)]' }}"
               aria-current="{{ $activeTab === $key ? 'page' : 'false' }}">
                {{ $tab['label'] }}
            </a>
        @endforeach
    </nav>

    @if($activeTab === 'rilis')
        @include('partials.keuangan-app-releases', ['releases' => $releases])
    @elseif($activeTab === 'lisensi')
        <livewire:admin.license-manager />
    @elseif($activeTab === 'pembayaran')
        <livewire:admin.payment-gateway-settings />
    @elseif($activeTab === 'ai')
        <livewire:admin.ai-system-overview />
    @else
        <livewire:admin.access-logs />
    @endif
</div>
@endsection
