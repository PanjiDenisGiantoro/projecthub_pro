<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') — Super Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-950 text-white antialiased">

{{-- Page Loading Overlay --}}
<div id="page-loader" style="position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;background:#020617;transition:opacity 0.3s ease">
    <img src="{{ asset('flovig_loading_white.gif') }}" alt="Loading..." style="width:16rem;height:16rem;object-fit:contain">
</div>

<div class="flex h-full">

    {{-- Sidebar --}}
    <aside class="w-60 flex flex-col fixed inset-y-0 bg-slate-900 border-r border-white/5">
        {{-- Logo --}}
        <div class="flex items-center gap-3 px-5 h-16 border-b border-white/5 shrink-0">
            <img src="{{ asset('logo.png') }}" alt="Flovig" class="w-8 h-8 rounded-lg object-contain shrink-0">
            <div class="min-w-0">
                <p class="text-sm font-bold text-white leading-none">Super Admin</p>
                <p class="text-xs text-amber-400 mt-0.5">Flovig</p>
            </div>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
            @php
                $a = 'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold bg-amber-500/15 text-amber-400';
                $i = 'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-400 hover:bg-white/5 hover:text-white transition-colors';
            @endphp

            <a href="{{ route('superadmin.dashboard') }}" class="{{ request()->routeIs('superadmin.dashboard') ? $a : $i }}">
                <svg class="w-4.5 h-4.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Dashboard
            </a>

            <a href="{{ route('superadmin.companies') }}" class="{{ request()->routeIs('superadmin.companies') ? $a : $i }}">
                <svg class="w-4.5 h-4.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                Semua Perusahaan
            </a>

            <a href="{{ route('superadmin.users') }}" class="{{ request()->routeIs('superadmin.users') ? $a : $i }}">
                <svg class="w-4.5 h-4.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                Semua User
            </a>

            <a href="{{ route('superadmin.registered-users') }}" class="{{ request()->routeIs('superadmin.registered-users') ? $a : $i }}">
                <svg class="w-4.5 h-4.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                Pelanggan
            </a>

            <div class="pt-3 mt-3 border-t border-white/5">
                <a href="{{ route('dashboard') }}" class="{{ $i }}">
                    <svg class="w-4.5 h-4.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Kembali ke App
                </a>
            </div>
        </nav>

        {{-- User --}}
        <div class="px-4 py-3 border-t border-white/5 shrink-0">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-white font-bold text-xs shrink-0"
                     style="background: linear-gradient(135deg, #f59e0b, #ef4444)">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-semibold text-white truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-amber-400 truncate">Super Admin</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-slate-500 hover:text-red-400 transition-colors" title="Logout">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Main --}}
    <div class="flex-1 ml-60 min-h-full">
        {{-- Topbar --}}
        <header class="h-16 bg-slate-900/50 border-b border-white/5 flex items-center px-8 sticky top-0 backdrop-blur z-10">
            <h1 class="text-sm font-semibold text-white">@yield('page-title', 'Dashboard')</h1>
            <div class="ml-auto flex items-center gap-2">
                <span class="bg-amber-500/15 text-amber-400 text-xs font-bold px-2.5 py-1 rounded-full">SUPER ADMIN</span>
            </div>
        </header>

        <main class="p-8">
            @if(session('success'))
                <div class="mb-6 bg-green-500/10 border border-green-500/20 text-green-400 rounded-xl px-4 py-3 text-sm">
                    {{ session('success') }}
                </div>
            @endif
            @yield('content')
        </main>
    </div>

</div>
<script>
window.addEventListener('load', function () {
    var loader = document.getElementById('page-loader');
    if (loader) {
        loader.style.opacity = '0';
        loader.style.pointerEvents = 'none';
        setTimeout(function () { loader.style.display = 'none'; }, 300);
    }
});
</script>
</body>
</html>
