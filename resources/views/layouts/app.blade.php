<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="vapid-public-key" content="{{ config('webpush.vapid.public_key') }}">
    <title>@yield('title', 'Dashboard') — Flovig</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script>(function(){var m=localStorage.getItem('flovig_mode')||'light';document.documentElement.setAttribute('data-mode',m);})();</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    @stack('head')
</head>
<body class="h-full font-sans antialiased" style="background-color:var(--fl-page)" x-data="{ sidebarOpen: false }">

{{-- Page Loading Overlay --}}
<div id="page-loader" class="fixed inset-0 z-[9999] flex items-center justify-center" style="background-color:var(--fl-page,#09061a)">
    <img src="{{ asset('flovig_loading_transparent.webp') }}" alt="Loading..." class="w-64 h-64 object-contain">
</div>

<div class="flex h-full">

    {{-- ── Desktop Sidebar ────────────────────────────────────────────────── --}}
    <aside class="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 ph-sidebar"
           style="z-index:20">

        {{-- Logo --}}
        <div class="flex items-center gap-3 px-5 h-16 shrink-0 ph-side-divider-b">
            <img src="{{ asset('flovig_logo.webp') }}" alt="Flovig" class="w-9 h-9 rounded-xl object-contain shrink-0">
            <span class="font-bold text-[15px] leading-none tracking-tight" style="color:var(--ph-logo-color)">
                Flovig
            </span>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-0.5 scrollbar-hide">
            @include('layouts.sidebar-nav')
        </nav>

        {{-- User section --}}
        <div class="px-3 py-3 shrink-0 ph-side-divider-t">
            <div x-data="{ userMenuOpen: false }" class="relative">
                <button @click="userMenuOpen = !userMenuOpen"
                        class="flex items-center gap-2.5 w-full px-3 py-2.5 rounded-xl ph-user-btn group text-left">
                    @if(auth()->user()->avatar)
                        <img src="{{ Storage::url(auth()->user()->avatar) }}"
                             alt="{{ auth()->user()->name }}"
                             class="w-8 h-8 rounded-full object-cover ring-2 ring-white/20 group-hover:ring-indigo-400 transition-all shrink-0">
                    @else
                        <div class="fl-avatar w-8 h-8 rounded-full flex items-center justify-center text-white font-bold text-xs shrink-0">
                            {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                        </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <p class="text-[13px] font-semibold truncate leading-tight" style="color:var(--ph-user-name)">{{ auth()->user()->name }}</p>
                        <p class="text-[11px] truncate capitalize leading-tight mt-0.5" style="color:var(--ph-user-role)">{{ auth()->user()->getRoleNames()->first() }}</p>
                    </div>
                    <svg class="w-3.5 h-3.5 shrink-0 transition-transform duration-200"
                         style="color:var(--ph-user-chev)"
                         :class="userMenuOpen ? 'rotate-180' : ''"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                {{-- Dropdown --}}
                <div x-show="userMenuOpen" x-cloak @click.away="userMenuOpen = false"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1"
                     class="absolute bottom-full left-0 right-0 mb-2 rounded-2xl overflow-hidden z-50"
                     style="background:var(--ph-drop-bg);border:1px solid var(--ph-drop-border);box-shadow:0 10px 40px rgba(0,0,0,0.25)">

                    <div class="px-4 py-3 ph-drop-divider-b">
                        <p class="text-[13px] font-semibold truncate" style="color:var(--ph-user-name)">{{ auth()->user()->name }}</p>
                        <p class="text-[11px] truncate" style="color:var(--ph-drop-email)">{{ auth()->user()->email }}</p>
                    </div>
                    <div class="p-1.5">
                        <a href="{{ route('profile') }}" class="ph-drop-link">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            Profil Saya
                        </a>
                        @if(auth()->user()->is_super_admin)
                            <a href="{{ route('superadmin.dashboard') }}" class="ph-drop-link">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                                Superadmin
                            </a>
                        @endif
                    </div>
                    <div class="p-1.5 ph-drop-divider-t" x-data="notificationToggle()" x-init="init()">
                        <div class="flex items-center justify-between gap-2 px-3 py-2">
                            <span class="flex items-center gap-2.5 text-[13px]" style="color:var(--ph-user-name)">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                </svg>
                                Notifikasi
                            </span>
                            <button type="button" @click="toggle()" :disabled="loading || !pushAvailable"
                                    class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors disabled:opacity-40"
                                    :class="enabled ? 'bg-violet-600' : 'bg-gray-400/50'">
                                <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white transition-transform"
                                      :class="enabled ? 'translate-x-4' : 'translate-x-0.5'"></span>
                            </button>
                        </div>
                    </div>
                    <div class="p-1.5 ph-drop-divider-t">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="w-full flex items-center gap-2.5 px-3 py-2 text-[13px] text-red-400 hover:bg-red-500/10 hover:text-red-300 rounded-lg transition-colors">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                                Keluar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </aside>

    {{-- ── Mobile Sidebar Overlay ──────────────────────────────────────────── --}}
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen=false"
         class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm lg:hidden"></div>

    <aside x-show="sidebarOpen" x-cloak
           class="fixed inset-y-0 left-0 z-50 w-64 flex flex-col lg:hidden ph-sidebar"
           style="box-shadow:4px 0 30px rgba(0,0,0,0.3)">
        <div class="flex items-center gap-3 px-5 h-16 shrink-0 ph-side-divider-b">
            <img src="{{ asset('flovig_logo.webp') }}" alt="Flovig" class="w-9 h-9 rounded-xl object-contain shrink-0">
            <span class="font-bold text-[15px] leading-none tracking-tight" style="color:var(--ph-logo-color)">
                Flovig
            </span>
            <button @click="sidebarOpen=false" class="ml-auto ph-close-btn shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-0.5 scrollbar-hide">
            @include('layouts.sidebar-nav')
        </nav>
        <div class="px-3 py-3 shrink-0 ph-side-divider-t">
            <div class="flex items-center gap-3 px-3 py-2">
                @if(auth()->user()->avatar)
                    <img src="{{ Storage::url(auth()->user()->avatar) }}"
                         alt="{{ auth()->user()->name }}"
                         class="w-8 h-8 rounded-full object-cover ring-2 ring-white/20 shrink-0">
                @else
                    <div class="fl-avatar w-8 h-8 rounded-full flex items-center justify-center text-white font-bold text-xs shrink-0">
                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </div>
                @endif
                <div class="flex-1 min-w-0">
                    <p class="text-[13px] font-semibold truncate leading-tight" style="color:var(--ph-user-name)">{{ auth()->user()->name }}</p>
                    <p class="text-[11px] truncate capitalize leading-tight mt-0.5" style="color:var(--ph-user-role)">{{ auth()->user()->getRoleNames()->first() }}</p>
                </div>
                @if(auth()->user()->is_super_admin)
                    <a href="{{ route('superadmin.dashboard') }}" class="text-slate-400 hover:text-indigo-400 p-1.5 rounded-lg hover:bg-indigo-500/10 transition-colors shrink-0" title="Superadmin">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-slate-400 hover:text-red-400 p-1.5 rounded-lg hover:bg-red-500/10 transition-colors shrink-0" title="Keluar">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- ── Main Content ────────────────────────────────────────────────────── --}}
    <div class="flex-1 flex flex-col lg:pl-64 min-h-0">

        {{-- Top bar --}}
        <header class="fl-topbar sticky top-0 z-30 flex items-center h-16 px-5 gap-3 shrink-0">
            <button @click="sidebarOpen=true" class="fl-hamburger lg:hidden p-1.5 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <h1 class="fl-topbar-title text-[14px] tracking-tight shrink-0 truncate max-w-[8rem] sm:max-w-none">
                @yield('page-title', 'Dashboard')
            </h1>

            {{-- Package switcher — visible when user has both packages or is super admin --}}
            @php
                $userPackages = auth()->user()->is_super_admin
                    ? ['task_management', 'hris']
                    : auth()->user()->activePackages();
            @endphp
            @if(count($userPackages) > 1)
            <div class="fl-pkg-switcher hidden sm:flex items-center rounded-full p-0.5 shrink-0 ml-2">
                @php $activePkg = session('active_package', $userPackages[0] ?? 'task_management'); @endphp
                <form method="POST" action="{{ route('switch.package') }}" class="contents">
                    @csrf
                    <input type="hidden" name="package" value="task_management">
                    <button type="submit"
                            class="fl-pkg-btn {{ $activePkg === 'task_management' ? 'fl-pkg-active' : '' }} flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium transition-all">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                        <span class="hidden md:inline">Task</span>
                    </button>
                </form>
                <form method="POST" action="{{ route('switch.package') }}" class="contents">
                    @csrf
                    <input type="hidden" name="package" value="hris">
                    <button type="submit"
                            class="fl-pkg-btn {{ $activePkg === 'hris' ? 'fl-pkg-active' : '' }} flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium transition-all">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span class="hidden md:inline">HRIS</span>
                    </button>
                </form>
            </div>
            @endif

            <div class="flex-1"></div>

            {{-- Search --}}
            <form method="GET" action="{{ route('search.index') }}" class="hidden sm:flex items-center">
                <div class="relative">
                    <input type="text" name="q" placeholder="Cari sesuatu..." value="{{ request('q') }}"
                           class="fl-search-input w-52 pl-9 pr-3 py-2 border rounded-xl text-sm transition-all">
                    <svg class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" style="color:var(--fl-search-ph)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </form>

            {{-- Dark / Light mode toggle --}}
            <button id="fl-mode-toggle" class="fl-mode-toggle" title="Ganti mode tampilan">
                {{-- Sun icon: shown in dark mode → click to go light --}}
                <svg id="fl-icon-sun" class="w-[18px] h-[18px] hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                {{-- Moon icon: shown in light mode → click to go dark --}}
                <svg id="fl-icon-moon" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                </svg>
            </button>

            {{-- Notification bell --}}
            <div x-data="notificationBell()" x-init="init()" class="relative">
                <button @click="open = !open" class="fl-bell-btn relative p-2 rounded-xl transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <span x-show="unreadCount > 0" x-cloak
                          class="absolute top-1 right-1 min-w-[16px] h-4 px-1 flex items-center justify-center rounded-full bg-red-500 text-white text-[10px] font-bold leading-none"
                          x-text="unreadCount > 99 ? '99+' : unreadCount"></span>
                </button>

                <div x-show="open" x-cloak @click.away="open = false"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="absolute right-0 mt-2 w-80 max-h-96 overflow-y-auto rounded-2xl overflow-hidden z-50"
                     style="background:var(--ph-drop-bg);border:1px solid var(--ph-drop-border);box-shadow:0 10px 40px rgba(0,0,0,0.25)">
                    <div class="px-4 py-3 flex items-center justify-between ph-drop-divider-b">
                        <p class="text-[13px] font-semibold" style="color:var(--ph-user-name)">Notifikasi</p>
                        <button @click="markAllRead()" x-show="unreadCount > 0" class="text-[11px] text-indigo-400 hover:text-indigo-300">Tandai semua dibaca</button>
                    </div>
                    <div x-show="pushAvailable && pushPermission !== 'granted'" x-cloak class="px-4 py-2.5 ph-drop-divider-b">
                        <button @click="subscribePush()" class="w-full text-[11.5px] font-medium text-indigo-400 hover:text-indigo-300 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            Aktifkan notifikasi push
                        </button>
                    </div>
                    <div class="max-h-80 overflow-y-auto">
                        <template x-if="items.length === 0">
                            <p class="px-4 py-6 text-center text-[12px]" style="color:var(--ph-drop-email)">Belum ada notifikasi.</p>
                        </template>
                        <template x-for="n in items" :key="n.id">
                            <button @click="markRead(n)" class="w-full text-left px-4 py-3 ph-drop-divider-b hover:bg-black/5 transition-colors" :class="!n.read_at ? 'bg-indigo-500/5' : ''">
                                <p class="text-[12.5px] font-semibold" style="color:var(--ph-user-name)" x-text="n.title"></p>
                                <p class="text-[12px] mt-0.5" style="color:var(--ph-drop-email)" x-text="n.message"></p>
                            </button>
                        </template>
                    </div>
                </div>
            </div>





            {{-- Avatar --}}
            <a href="{{ route('profile') }}" class="group flex items-center shrink-0">
                @if(auth()->user()->avatar)
                    <img src="{{ Storage::url(auth()->user()->avatar) }}"
                         alt="{{ auth()->user()->name }}"
                         class="w-9 h-9 rounded-full object-cover ring-2 ring-white/20 group-hover:ring-purple-400 transition-all">
                @else
                    <div class="fl-avatar w-9 h-9 rounded-full flex items-center justify-center text-white font-bold text-xs ring-2 ring-white/20 group-hover:ring-purple-400 transition-all">
                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </div>
                @endif
            </a>
        </header>

        {{-- ── Active-until warning banner ────────────────────────────────── --}}
        @if(auth()->check() && !auth()->user()->is_super_admin && !auth()->user()->isLifetime() && !auth()->user()->isExpired())
            @php $daysLeft = (int) now()->diffInDays(auth()->user()->active_until, false); @endphp
            @if($daysLeft <= 14)
            <div x-data="{ show: !localStorage.getItem('ph_banner_dismissed_{{ now()->toDateString() }}') }"
                 x-show="show"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-2"
                 class="fl-warn-banner flex items-center gap-3 px-5 py-3 text-sm font-medium shrink-0 border-b {{ $daysLeft <= 3 ? 'fl-err-banner bg-red-50 border-red-200 text-red-800' : 'bg-amber-50 border-amber-200 text-amber-800' }}">
                <svg class="w-4 h-4 shrink-0 {{ $daysLeft <= 3 ? 'text-red-500' : 'text-amber-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                @if($daysLeft <= 0)
                    <span>Masa aktif akun Anda <strong>berakhir hari ini</strong>. Segera hubungi administrator untuk memperpanjang.</span>
                @elseif($daysLeft === 1)
                    <span>Masa aktif akun Anda <strong>berakhir besok</strong>. Segera hubungi administrator.</span>
                @else
                    <span>Masa aktif akun Anda akan berakhir dalam <strong>{{ $daysLeft }} hari</strong>
                        ({{ auth()->user()->active_until->locale('id')->isoFormat('D MMMM Y') }}).
                        Hubungi administrator untuk memperpanjang.
                    </span>
                @endif
                <button @click="show = false; localStorage.setItem('ph_banner_dismissed_{{ now()->toDateString() }}', '1')"
                        class="ml-auto shrink-0 p-1 rounded-lg hover:bg-black/10 transition-colors"
                        title="Tutup peringatan">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            @endif
        @endif

        {{-- Flash messages --}}
        @if(session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({ toast:true, position:'top-end', icon:'success', title:@json(session('success')), showConfirmButton:false, timer:3500, timerProgressBar:true, background:'#4f46e5', color:'#fff', iconColor:'#fff', customClass:{popup:'swal-toast-popup'} });
            });
        </script>
        @endif
        @if(session('danger'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({ toast:true, position:'top-end', icon:'error', title:@json(session('danger')), showConfirmButton:false, timer:4000, timerProgressBar:true, background:'#dc2626', color:'#fff', iconColor:'#fff', customClass:{popup:'swal-toast-popup'} });
            });
        </script>
        @endif
        @if(session('warning'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({ toast:true, position:'top-end', icon:'warning', title:@json(session('warning')), showConfirmButton:false, timer:4000, timerProgressBar:true, background:'#d97706', color:'#fff', iconColor:'#fff', customClass:{popup:'swal-toast-popup'} });
            });
        </script>
        @endif
        @if(session('info'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({ toast:true, position:'top-end', icon:'info', title:@json(session('info')), showConfirmButton:false, timer:3500, timerProgressBar:true, background:'#4f46e5', color:'#fff', iconColor:'#fff', customClass:{popup:'swal-toast-popup'} });
            });
        </script>
        @endif
        @if(session('error'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({ icon:'error', title:'Terjadi Kesalahan', text:@json(session('error')), confirmButtonColor:'#6366f1', confirmButtonText:'Tutup' });
            });
        </script>
        @endif
        @if($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({ icon:'error', title:'Periksa Kembali', html:'<ul class="text-left text-sm space-y-1 mt-1">@foreach($errors->all() as $e)<li>• {{ $e }}</li>@endforeach</ul>', confirmButtonColor:'#6366f1', confirmButtonText:'Tutup' });
            });
        </script>
        @endif

        {{-- Page content --}}
        <main class="@yield('main-class', 'flex-1 px-6 pb-8 overflow-auto')">
            @yield('content')
        </main>
    </div>
</div>

{{-- ═══════════════════════════════════════
     AI Assistant — floating widget
═══════════════════════════════════════ --}}
<div x-data="aiAssistantWidget()" x-cloak>
    {{-- Bubble button --}}
    <button @click="toggle()"
            class="fixed bottom-5 right-5 z-40 w-14 h-14 rounded-full flex items-center justify-center transition-all hover:-translate-y-0.5"
            style="background:linear-gradient(135deg,#7c3aed,#6d28d9);box-shadow:0 6px 20px rgba(109,40,217,0.4)">
        <svg x-show="!open" class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
        </svg>
        <svg x-show="open" x-cloak class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>

    {{-- Panel --}}
    <div x-show="open" x-cloak x-transition
         class="fixed bottom-24 right-5 z-40 w-[min(360px,calc(100vw-2.5rem))] h-[min(520px,calc(100vh-8rem))] rounded-2xl overflow-hidden flex flex-col"
         style="background:var(--fl-card-bg,#fff);border:1px solid var(--fl-card-border,#ede9fe);box-shadow:0 10px 40px rgba(109,40,217,0.25)">

        {{-- Header --}}
        <div class="flex items-center gap-3 px-4 py-3 shrink-0" style="background:linear-gradient(135deg,#7c3aed,#6d28d9)">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0" style="background:rgba(255,255,255,0.15)">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-bold text-white">AI Assistant</p>
                <p class="text-[10px] text-white/70">Self-hosted · tidak dikirim ke pihak ketiga</p>
            </div>
            <button @click="clearHistory()" title="Hapus riwayat" class="p-1.5 rounded-lg text-white/70 hover:text-white hover:bg-white/10 transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </button>
        </div>

        {{-- Messages --}}
        <div class="flex-1 overflow-y-auto px-4 py-4 space-y-3" x-ref="aiMsgArea" style="background:var(--fl-search-bg,#f9fafb)">
            <div x-show="messages.length === 0" class="text-center py-8">
                <p class="text-sm" style="color:var(--fl-text-muted,#6b7280)">Halo! Ada yang bisa saya bantu?</p>
            </div>

            <template x-for="(m, i) in messages" :key="i">
                <div class="flex" :class="m.role === 'user' ? 'justify-end' : 'justify-start'">
                    <div class="max-w-[85%] px-3 py-2 rounded-2xl text-sm leading-relaxed whitespace-pre-wrap break-words"
                         :class="m.role === 'user'
                            ? 'text-white rounded-br-sm'
                            : 'rounded-bl-sm border'"
                         :style="m.role === 'user'
                            ? 'background:linear-gradient(135deg,#7c3aed,#6d28d9)'
                            : 'background:var(--fl-card-bg,#fff);border-color:var(--fl-card-border,#ede9fe);color:var(--fl-text-h,#1a0a3d)'"
                         x-text="m.content"></div>
                </div>
            </template>

            <div x-show="thinking" class="flex justify-start">
                <div class="px-3 py-2 rounded-2xl rounded-bl-sm border flex items-center gap-1"
                     style="background:var(--fl-card-bg,#fff);border-color:var(--fl-card-border,#ede9fe)">
                    <span class="w-1.5 h-1.5 rounded-full animate-bounce" style="background:#a78bfa;animation-delay:0ms"></span>
                    <span class="w-1.5 h-1.5 rounded-full animate-bounce" style="background:#a78bfa;animation-delay:150ms"></span>
                    <span class="w-1.5 h-1.5 rounded-full animate-bounce" style="background:#a78bfa;animation-delay:300ms"></span>
                </div>
            </div>
        </div>

        {{-- Input --}}
        <div class="p-3 border-t shrink-0" style="border-color:var(--fl-card-border,#ede9fe);background:var(--fl-card-bg,#fff)">
            <div class="flex gap-2 items-end">
                <textarea x-model="input"
                          x-ref="aiInput"
                          @keydown.enter="if(!$event.shiftKey){$event.preventDefault();send();}"
                          rows="1"
                          placeholder="Tulis pertanyaan…"
                          class="flex-1 px-3 py-2 text-sm rounded-xl border outline-none resize-none transition"
                          style="background:var(--fl-search-bg,#f5f3ff);border-color:var(--fl-card-border,#ede9fe);color:var(--fl-text-h,#1a0a3d);max-height:80px"></textarea>
                <button @click="send()" :disabled="thinking || !input.trim()"
                        class="shrink-0 w-9 h-9 rounded-xl flex items-center justify-center text-white transition disabled:opacity-40"
                        style="background:linear-gradient(135deg,#7c3aed,#6d28d9)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>

@stack('modals')
@stack('scripts')

<script>
function aiAssistantWidget() {
    return {
        open: false,
        input: '',
        messages: [],
        thinking: false,
        storageKey: 'ph_ai_assistant_history',
        csrf: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',

        init() {
            try {
                const raw = localStorage.getItem(this.storageKey);
                this.messages = raw ? JSON.parse(raw) : [];
            } catch (_) { this.messages = []; }
        },

        toggle() {
            this.open = !this.open;
            if (this.open) this.$nextTick(() => { this.scrollBottom(); this.$refs.aiInput?.focus(); });
        },

        clearHistory() {
            if (this.messages.length && !confirm('Hapus riwayat chat dengan AI?')) return;
            this.messages = [];
            localStorage.removeItem(this.storageKey);
        },

        save() {
            try { localStorage.setItem(this.storageKey, JSON.stringify(this.messages)); } catch (_) {}
        },

        scrollBottom() {
            const el = this.$refs.aiMsgArea;
            if (el) el.scrollTop = el.scrollHeight;
        },

        async send() {
            const text = this.input.trim();
            if (!text || this.thinking) return;

            this.messages.push({ role: 'user', content: text });
            this.input = '';
            this.thinking = true;
            this.$nextTick(() => this.scrollBottom());

            try {
                const res = await fetch('{{ route('ai.chat') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                    body: JSON.stringify({ messages: this.messages.slice(-20) }),
                });
                const data = await res.json();
                if (res.ok) {
                    this.messages.push({ role: 'assistant', content: data.reply || '(tidak ada jawaban)' });
                } else {
                    this.messages.push({ role: 'assistant', content: data.error || 'Terjadi kesalahan.' });
                }
            } catch (e) {
                this.messages.push({ role: 'assistant', content: 'Gagal terhubung ke AI Assistant.' });
            } finally {
                this.thinking = false;
                this.save();
                this.$nextTick(() => this.scrollBottom());
            }
        },
    };
}
</script>

<script>
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = atob(base64);
    return Uint8Array.from([...rawData].map((c) => c.charCodeAt(0)));
}

function notificationBell() {
    return {
        open: false,
        items: [],
        unreadCount: 0,
        csrf: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
        vapidKey: document.querySelector('meta[name="vapid-public-key"]')?.getAttribute('content') ?? '',
        pushAvailable: 'serviceWorker' in navigator && 'PushManager' in window,
        pushPermission: (typeof Notification !== 'undefined') ? Notification.permission : 'denied',
        init() {
            this.refreshCount();
            this.$watch('open', (v) => { if (v) this.loadNotifications(); });
            setInterval(() => this.refreshCount(), 30000);
        },
        async subscribePush() {
            if (!this.pushAvailable) { alert('Browser ini tidak mendukung push notification.'); return; }
            if (!this.vapidKey) { alert('VAPID key belum dikonfigurasi di server.'); return; }
            try {
                const permission = await Notification.requestPermission();
                this.pushPermission = permission;
                if (permission !== 'granted') { alert('Izin notifikasi ditolak/belum diberikan. Cek pengaturan situs di browser.'); return; }
                const registration = await navigator.serviceWorker.register('/sw.js');
                await navigator.serviceWorker.ready;
                const subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(this.vapidKey),
                });
                const res = await fetch('{{ route('push.subscribe') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                    body: JSON.stringify(subscription.toJSON()),
                });
                if (!res.ok) throw new Error('Server menolak subscription (HTTP ' + res.status + ')');
                alert('Notifikasi push berhasil diaktifkan.');
            } catch (e) {
                console.error('subscribePush failed:', e);
                alert('Gagal mengaktifkan notifikasi push: ' + e.message);
            }
        },
        async refreshCount() {
            const res = await fetch('{{ route('notifications.unreadCount') }}');
            if (!res.ok) return;
            const data = await res.json();
            this.unreadCount = data.count;
        },
        async loadNotifications() {
            const res = await fetch('{{ route('notifications.index') }}');
            if (!res.ok) return;
            const data = await res.json();
            this.items = data.data ?? [];
        },
        async markRead(n) {
            if (!n.read_at) {
                await fetch(`/notifications/${n.id}/read`, {
                    method: 'PUT',
                    headers: { 'X-CSRF-TOKEN': this.csrf },
                });
                n.read_at = new Date().toISOString();
                this.unreadCount = Math.max(0, this.unreadCount - 1);
            }
            if (n.data?.project_id) {
                window.location.href = `/projects/${n.data.project_id}`;
            }
        },
        async markAllRead() {
            await fetch('{{ route('notifications.markAllRead') }}', {
                method: 'PUT',
                headers: { 'X-CSRF-TOKEN': this.csrf },
            });
            this.items.forEach(n => n.read_at = n.read_at ?? new Date().toISOString());
            this.unreadCount = 0;
        },
    }
}

function notificationToggle() {
    return {
        enabled: false,
        loading: false,
        csrf: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
        vapidKey: document.querySelector('meta[name="vapid-public-key"]')?.getAttribute('content') ?? '',
        pushAvailable: 'serviceWorker' in navigator && 'PushManager' in window,
        async init() {
            if (!this.pushAvailable) return;
            try {
                const registration = await navigator.serviceWorker.getRegistration('/sw.js');
                const subscription = await registration?.pushManager.getSubscription();
                this.enabled = !!subscription && Notification.permission === 'granted';
            } catch (e) {
                console.error('notificationToggle init failed:', e);
            }
        },
        async toggle() {
            if (!this.pushAvailable) { alert('Browser ini tidak mendukung push notification.'); return; }
            this.loading = true;
            try {
                if (!this.enabled) {
                    await this.subscribe();
                } else {
                    await this.unsubscribe();
                }
            } catch (e) {
                console.error('notificationToggle failed:', e);
                alert('Gagal mengubah pengaturan notifikasi: ' + e.message);
            } finally {
                this.loading = false;
            }
        },
        async subscribe() {
            if (!this.vapidKey) { alert('VAPID key belum dikonfigurasi di server.'); return; }
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') { alert('Izin notifikasi ditolak/belum diberikan. Cek pengaturan situs di browser.'); return; }
            const registration = await navigator.serviceWorker.register('/sw.js');
            await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(this.vapidKey),
            });
            const res = await fetch('{{ route('push.subscribe') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                body: JSON.stringify(subscription.toJSON()),
            });
            if (!res.ok) throw new Error('Server menolak subscription (HTTP ' + res.status + ')');
            this.enabled = true;
        },
        async unsubscribe() {
            const registration = await navigator.serviceWorker.getRegistration('/sw.js');
            const subscription = await registration?.pushManager.getSubscription();
            if (subscription) {
                const endpoint = subscription.endpoint;
                await subscription.unsubscribe();
                const res = await fetch('{{ route('push.unsubscribe') }}', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                    body: JSON.stringify({ endpoint }),
                });
                if (!res.ok) throw new Error('Server menolak unsubscribe (HTTP ' + res.status + ')');
            }
            this.enabled = false;
        },
    }
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form[data-confirm-delete]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const name  = form.dataset.confirmDelete || 'data ini';
            const label = form.dataset.confirmLabel  || 'Hapus';
            Swal.fire({
                title: 'Hapus ' + name + '?',
                text: 'Data yang dihapus tidak bisa dikembalikan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor:  '#6b7280',
                confirmButtonText:  label,
                cancelButtonText:   'Batal',
                reverseButtons: true,
                focusCancel: true,
            }).then(function (result) {
                if (result.isConfirmed) { form.submit(); }
            });
        });
    });
    document.querySelectorAll('form[data-confirm-submit]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const title = form.dataset.confirmSubmit || 'Simpan perubahan?';
            const text  = form.dataset.confirmText   || '';
            const btn   = form.dataset.confirmBtn    || 'Ya, Simpan';
            Swal.fire({
                title, text, icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#6366f1',
                cancelButtonColor:  '#6b7280',
                confirmButtonText: btn,
                cancelButtonText: 'Batal',
                reverseButtons: true,
            }).then(function (result) {
                if (result.isConfirmed) {
                    form.removeAttribute('data-confirm-submit');
                    form.submit();
                }
            });
        });
    });
});
</script>


<style>
[x-cloak] { display: none !important; }
.swal-toast-popup {
    font-size: 1rem !important;
    min-width: 340px !important;
    padding: 0.85rem 1.25rem !important;
}
.swal-toast-popup .swal2-title {
    font-size: 0.975rem !important;
    font-weight: 600 !important;
}
#page-loader {
    transition: opacity 0.3s ease;
}
#page-loader.hidden {
    opacity: 0;
    pointer-events: none;
}
</style>
<script>
window.addEventListener('load', function () {
    var loader = document.getElementById('page-loader');
    if (loader) {
        loader.classList.add('hidden');
        setTimeout(function () { loader.style.display = 'none'; }, 300);
    }
});
</script>

{{-- ── Flovig Dark / Light Mode Toggle ────────────────────────────── --}}
<script>
(function () {
    var toggle  = document.getElementById('fl-mode-toggle');
    var iconSun = document.getElementById('fl-icon-sun');
    var iconMoon= document.getElementById('fl-icon-moon');

    function applyIcons(mode) {
        if (mode === 'dark') {
            iconSun.classList.remove('hidden');   // sun = "switch to light"
            iconMoon.classList.add('hidden');
        } else {
            iconMoon.classList.remove('hidden');  // moon = "switch to dark"
            iconSun.classList.add('hidden');
        }
    }

    var currentMode = document.documentElement.getAttribute('data-mode') || 'light';
    applyIcons(currentMode);

    if (toggle) {
        toggle.addEventListener('click', function () {
            var cur  = document.documentElement.getAttribute('data-mode') || 'light';
            var next = cur === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-mode', next);
            localStorage.setItem('flovig_mode', next);
            applyIcons(next);
        });
    }
})();
</script>
</body>
</html>
