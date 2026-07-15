@extends('layouts.app')
@section('title', 'IP Blocker')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">IP Blocker</h1>
        <p class="text-sm text-gray-500 mt-1">Monitor login gagal dan kelola blokir IP address.</p>
    </div>

    <livewire:ip-blocker />
</div>
@endsection
