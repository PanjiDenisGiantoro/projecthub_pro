<!DOCTYPE html>
<html lang="id" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — ProjectHub Pro</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    @stack('head')
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
            {{-- Global Search --}}
            <form method="GET" action="{{ route('search.index') }}" class="hidden sm:flex items-center">
                <div class="relative">
                    <input type="text" name="q" placeholder="Cari..." value="{{ request('q') }}"
                           class="w-52 pl-8 pr-3 py-1.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50">
                    <svg class="w-4 h-4 text-gray-400 absolute left-2 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
            </form>
            {{-- Notifications --}}
            <a href="#" class="relative text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            </a>
        </header>

        {{-- Flash messages via SweetAlert --}}
        @if(session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: @json(session('success')),
                    showConfirmButton: false,
                    timer: 3500,
                    timerProgressBar: true,
                    customClass: { popup: 'swal-toast-popup' }
                });
            });
        </script>
        @endif
        @if(session('error'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Terjadi Kesalahan',
                    text: @json(session('error')),
                    confirmButtonColor: '#2563eb',
                    confirmButtonText: 'Tutup'
                });
            });
        </script>
        @endif
        @if($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Periksa Kembali',
                    html: '<ul class="text-left text-sm space-y-1 mt-1">@foreach($errors->all() as $e)<li>• {{ $e }}</li>@endforeach</ul>',
                    confirmButtonColor: '#2563eb',
                    confirmButtonText: 'Tutup'
                });
            });
        </script>
        @endif

        {{-- Page content --}}
        <main class="flex-1 px-6 pb-8 overflow-auto">
            @yield('content')
        </main>
    </div>
</div>

@stack('modals')
@stack('scripts')

<script>
// ─── Global: Delete confirmation ─────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {

    // Delete forms — ganti onsubmit="confirm" dengan SweetAlert
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
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });

    // Submit forms — optional confirm sebelum simpan/update
    document.querySelectorAll('form[data-confirm-submit]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const title = form.dataset.confirmSubmit  || 'Simpan perubahan?';
            const text  = form.dataset.confirmText    || '';
            const btn   = form.dataset.confirmBtn     || 'Ya, Simpan';
            Swal.fire({
                title: title,
                text: text,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#2563eb',
                cancelButtonColor:  '#6b7280',
                confirmButtonText:  btn,
                cancelButtonText:   'Batal',
                reverseButtons: true,
            }).then(function (result) {
                if (result.isConfirmed) {
                    // Submit tanpa listener agar tidak loop
                    form.removeAttribute('data-confirm-submit');
                    form.submit();
                }
            });
        });
    });

});
</script>

<style>
.swal-toast-popup { font-size: 0.875rem !important; }
</style>
</body>
</html>
