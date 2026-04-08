<!DOCTYPE html>
<html lang="id" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — ProjectHub Pro</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full" x-data="{ sidebarOpen: false }">

<div class="flex h-full">
    {{-- Sidebar --}}
    <aside class="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 bg-blue-900 text-white">
        {{-- Logo --}}
        <div class="flex items-center gap-3 px-6 py-5 border-b border-blue-800">
            <div class="w-8 h-8 bg-blue-400 rounded-lg flex items-center justify-center font-bold text-blue-900 text-sm">PH</div>
            <span class="font-semibold text-lg">ProjectHub Pro</span>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
            @include('layouts.sidebar-nav')
        </nav>

        {{-- User info --}}
        <div class="px-4 py-4 border-t border-blue-800">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-blue-400 flex items-center justify-center text-blue-900 font-bold text-sm">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-blue-300 truncate">{{ auth()->user()->getRoleNames()->first() }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" title="Logout" class="text-blue-300 hover:text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Mobile sidebar overlay --}}
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen=false" class="fixed inset-0 z-40 bg-black/50 lg:hidden"></div>
    <aside x-show="sidebarOpen" x-cloak class="fixed inset-y-0 left-0 z-50 w-64 bg-blue-900 text-white flex flex-col lg:hidden">
        <div class="flex items-center gap-3 px-6 py-5 border-b border-blue-800">
            <div class="w-8 h-8 bg-blue-400 rounded-lg flex items-center justify-center font-bold text-blue-900 text-sm">PH</div>
            <span class="font-semibold text-lg">ProjectHub Pro</span>
            <button @click="sidebarOpen=false" class="ml-auto text-blue-300 hover:text-white">✕</button>
        </div>
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
            @include('layouts.sidebar-nav')
        </nav>
    </aside>

    {{-- Main content --}}
    <div class="flex-1 flex flex-col lg:pl-64 min-h-0">
        {{-- Top bar --}}
        <header class="sticky top-0 z-30 bg-white border-b border-gray-200 flex items-center px-4 py-3 gap-4">
            <button @click="sidebarOpen=true" class="lg:hidden text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <h1 class="text-lg font-semibold text-gray-800 flex-1">@yield('page-title', 'Dashboard')</h1>
            {{-- Notifications --}}
            <a href="#" class="relative text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            </a>
        </header>

        {{-- Flash messages --}}
        <div class="px-6 pt-4">
            @if(session('success'))
                <div x-data="{show:true}" x-show="show" x-init="setTimeout(()=>show=false,4000)"
                     class="mb-4 bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    {{ session('success') }}
                </div>
            @endif
            @if($errors->any())
                <div class="mb-4 bg-red-50 border border-red-200 text-red-800 rounded-lg px-4 py-3">
                    <ul class="list-disc pl-4 text-sm space-y-1">
                        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                    </ul>
                </div>
            @endif
        </div>

        {{-- Page content --}}
        <main class="flex-1 px-6 pb-8 overflow-auto">
            @yield('content')
        </main>
    </div>
</div>

@stack('modals')
@stack('scripts')
</body>
</html>
