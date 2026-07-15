@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-white px-6 py-12">
    <div class="w-full max-w-[900px]">
        <div class="bg-white rounded-2xl shadow-[0_8px_40px_-12px_rgba(0,0,0,0.12)] border border-slate-200/60 overflow-hidden">
            <div class="flex flex-col md:flex-row">
                <div class="md:w-[280px] bg-gradient-to-br from-indigo-600 to-indigo-700 p-8 flex flex-col justify-center">
                    <div class="inline-flex items-center justify-center w-12 h-12 bg-white/15 backdrop-blur rounded-xl mb-4">
                        <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                        </svg>
                    </div>
                    <h2 class="text-lg font-bold text-white">Knowledge Hub</h2>
                    <p class="text-[13px] text-indigo-200 mt-1.5 leading-relaxed">Your personal knowledge base for bookmarks, notes, and more.</p>
                </div>
                <div class="flex-1 p-8">
                    <div class="mb-6">
                        <h1 class="text-xl font-bold text-slate-900 tracking-tight">Sign in</h1>
                        <p class="text-[13px] text-slate-500 mt-0.5">Enter your credentials</p>
                    </div>

                    @if ($errors->any())
                    <div class="bg-red-50 text-red-700 text-sm p-3 rounded-xl mb-5 border border-red-200/80">
                        {{ $errors->first() }}
                    </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}">
                        @csrf
                        <div class="mb-4">
                            <label for="email" class="block text-[13px] font-semibold text-slate-700 mb-1.5">Email</label>
                            <input type="email" name="email" id="email" required autofocus
                                class="w-full h-10 px-3.5 border border-slate-300 rounded-lg text-[14px] text-slate-900 placeholder-slate-400 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all">
                        </div>
                        <div class="mb-5">
                            <label for="password" class="block text-[13px] font-semibold text-slate-700 mb-1.5">Password</label>
                            <input type="password" name="password" id="password" required
                                class="w-full h-10 px-3.5 border border-slate-300 rounded-lg text-[14px] text-slate-900 placeholder-slate-400 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all">
                        </div>
                        <button type="submit"
                            class="w-full h-10 flex items-center justify-center gap-2 bg-indigo-600 text-white rounded-lg text-[14px] font-semibold hover:bg-indigo-700 active:bg-indigo-800 transition-all shadow-sm">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4M10 17l5-5-5-5M13 12H3"/></svg>
                            Sign In
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <p class="text-center text-[12px] text-slate-400 mt-6">&copy; {{ date('Y') }} Knowledge Hub</p>
    </div>
</div>
@endsection
