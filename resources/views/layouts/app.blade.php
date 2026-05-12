<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — ProjectHub Pro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    @stack('head')
</head>
<body class="h-full font-sans antialiased bg-[#f0f4ff]" x-data="{ sidebarOpen: false }">

{{-- ── Decorative background blobs (glassmorphism backdrop) ──────────────── --}}
<div aria-hidden="true" class="fixed inset-0 overflow-hidden pointer-events-none" style="z-index:0">
    <div class="absolute -top-80 -right-40 w-[42rem] h-[42rem] rounded-full opacity-40"
         style="background: radial-gradient(circle at 40% 40%, #818cf8 0%, #c4b5fd 45%, transparent 70%)"></div>
    <div class="absolute top-1/3 -left-56 w-[36rem] h-[36rem] rounded-full opacity-25"
         style="background: radial-gradient(circle at 60% 60%, #67e8f9 0%, #818cf8 50%, transparent 70%)"></div>
    <div class="absolute bottom-10 right-1/4 w-[30rem] h-[30rem] rounded-full opacity-20"
         style="background: radial-gradient(circle at 50% 50%, #f0abfc 0%, #a78bfa 50%, transparent 70%)"></div>
</div>

<div class="flex h-full" style="position:relative;z-index:1">

    {{-- ── Desktop Sidebar ────────────────────────────────────────────────── --}}
    <aside class="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 bg-white border-r border-gray-100"
           style="box-shadow: 2px 0 20px rgba(79,70,229,0.06); z-index:20">

        {{-- Logo --}}
        <div class="flex items-center gap-3 px-5 h-16 border-b border-gray-50 shrink-0">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center font-extrabold text-white text-sm shrink-0"
                 style="background: linear-gradient(135deg, #4f46e5, #7c3aed); box-shadow: 0 6px 16px rgba(79,70,229,0.35)">PH</div>
            <span class="font-bold text-[15px] text-gray-900 leading-none tracking-tight">
                ProjectHub <span class="font-medium text-indigo-500">Pro</span>
            </span>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-0.5 scrollbar-hide">
            @include('layouts.sidebar-nav')
        </nav>

        {{-- User section --}}
        <div class="px-3 py-3 border-t border-gray-100 shrink-0">
            <div x-data="{ userMenuOpen: false }" class="relative">
                <button @click="userMenuOpen = !userMenuOpen"
                        class="flex items-center gap-2.5 w-full px-3 py-2.5 rounded-xl hover:bg-indigo-50/60 transition-colors group text-left">
                    @if(auth()->user()->avatar)
                        <img src="{{ Storage::url(auth()->user()->avatar) }}"
                             alt="{{ auth()->user()->name }}"
                             class="w-8 h-8 rounded-full object-cover ring-2 ring-indigo-100 group-hover:ring-indigo-300 transition-all shrink-0">
                    @else
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-white font-bold text-xs shrink-0"
                             style="background: linear-gradient(135deg, #4f46e5, #7c3aed); box-shadow: 0 2px 8px rgba(79,70,229,0.3)">
                            {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                        </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <p class="text-[13px] font-semibold text-gray-800 truncate leading-tight">{{ auth()->user()->name }}</p>
                        <p class="text-[11px] text-indigo-500 truncate capitalize leading-tight mt-0.5">{{ auth()->user()->getRoleNames()->first() }}</p>
                    </div>
                    <svg class="w-3.5 h-3.5 text-gray-400 shrink-0 transition-transform duration-200"
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
                     class="absolute bottom-full left-0 right-0 mb-2 bg-white border border-gray-100 rounded-2xl overflow-hidden z-50"
                     style="box-shadow: 0 10px 40px rgba(79,70,229,0.12)">

                    <div class="px-4 py-3 border-b border-gray-50">
                        <p class="text-[13px] font-semibold text-gray-800 truncate">{{ auth()->user()->name }}</p>
                        <p class="text-[11px] text-gray-400 truncate">{{ auth()->user()->email }}</p>
                    </div>
                    <div class="p-1.5">
                        <a href="{{ route('profile') }}"
                           class="flex items-center gap-2.5 px-3 py-2 text-[13px] text-gray-600 hover:bg-indigo-50 hover:text-indigo-700 rounded-lg transition-colors">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            Profil Saya
                        </a>
                    </div>
                    <div class="p-1.5 border-t border-gray-50">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="w-full flex items-center gap-2.5 px-3 py-2 text-[13px] text-red-500 hover:bg-red-50 hover:text-red-600 rounded-lg transition-colors">
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
         class="fixed inset-0 z-40 bg-black/30 backdrop-blur-sm lg:hidden"></div>

    <aside x-show="sidebarOpen" x-cloak
           class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-100 flex flex-col lg:hidden"
           style="box-shadow: 4px 0 30px rgba(79,70,229,0.1)">
        <div class="flex items-center gap-3 px-5 h-16 border-b border-gray-50 shrink-0">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center font-extrabold text-white text-sm shrink-0"
                 style="background: linear-gradient(135deg, #4f46e5, #7c3aed); box-shadow: 0 6px 16px rgba(79,70,229,0.35)">PH</div>
            <span class="font-bold text-[15px] text-gray-900 leading-none tracking-tight">
                ProjectHub <span class="font-medium text-indigo-500">Pro</span>
            </span>
            <button @click="sidebarOpen=false" class="ml-auto text-gray-400 hover:text-gray-700 p-1.5 rounded-lg hover:bg-gray-100 transition-colors shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-0.5 scrollbar-hide">
            @include('layouts.sidebar-nav')
        </nav>
        <div class="px-3 py-3 border-t border-gray-100 shrink-0">
            <div class="flex items-center gap-3 px-3 py-2">
                @if(auth()->user()->avatar)
                    <img src="{{ Storage::url(auth()->user()->avatar) }}"
                         alt="{{ auth()->user()->name }}"
                         class="w-8 h-8 rounded-full object-cover ring-2 ring-indigo-100 shrink-0">
                @else
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-white font-bold text-xs shrink-0"
                         style="background: linear-gradient(135deg, #4f46e5, #7c3aed)">
                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </div>
                @endif
                <div class="flex-1 min-w-0">
                    <p class="text-[13px] font-semibold text-gray-800 truncate leading-tight">{{ auth()->user()->name }}</p>
                    <p class="text-[11px] text-indigo-500 truncate capitalize leading-tight mt-0.5">{{ auth()->user()->getRoleNames()->first() }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-gray-400 hover:text-red-500 p-1.5 rounded-lg hover:bg-red-50 transition-colors shrink-0" title="Keluar">
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
        <header class="sticky top-0 z-30 bg-white/90 backdrop-blur-md border-b border-gray-100 flex items-center h-16 px-5 gap-3 shrink-0"
                style="box-shadow: 0 1px 12px rgba(79,70,229,0.06)">
            <button @click="sidebarOpen=true" class="lg:hidden text-gray-400 hover:text-indigo-600 p-1.5 rounded-lg hover:bg-indigo-50 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <h1 class="text-[14px] font-semibold text-gray-700 flex-1 truncate tracking-tight">
                @yield('page-title', 'Dashboard')
            </h1>

            {{-- Search --}}
            <form method="GET" action="{{ route('search.index') }}" class="hidden sm:flex items-center">
                <div class="relative">
                    <input type="text" name="q" placeholder="Cari sesuatu..." value="{{ request('q') }}"
                           class="w-52 pl-9 pr-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400/30 focus:border-indigo-400 bg-gray-50/80 hover:border-gray-300 transition-all placeholder:text-gray-400 text-gray-700">
                    <svg class="w-3.5 h-3.5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </form>

            {{-- Notification bell --}}
            <button class="relative text-gray-400 hover:text-indigo-600 p-2 rounded-xl hover:bg-indigo-50 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
            </button>

            {{-- Avatar --}}
            <a href="{{ route('profile') }}" class="group flex items-center shrink-0">
                @if(auth()->user()->avatar)
                    <img src="{{ Storage::url(auth()->user()->avatar) }}"
                         alt="{{ auth()->user()->name }}"
                         class="w-9 h-9 rounded-full object-cover ring-2 ring-indigo-100 group-hover:ring-indigo-400 transition-all">
                @else
                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-white font-bold text-xs ring-2 ring-indigo-100 group-hover:ring-indigo-400 transition-all"
                         style="background: linear-gradient(135deg, #4f46e5, #7c3aed)">
                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </div>
                @endif
            </a>
        </header>

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
                Swal.fire({ icon:'error', title:'Terjadi Kesalahan', text:@json(session('error')), confirmButtonColor:'#4f46e5', confirmButtonText:'Tutup' });
            });
        </script>
        @endif
        @if($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({ icon:'error', title:'Periksa Kembali', html:'<ul class="text-left text-sm space-y-1 mt-1">@foreach($errors->all() as $e)<li>• {{ $e }}</li>@endforeach</ul>', confirmButtonColor:'#4f46e5', confirmButtonText:'Tutup' });
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
                confirmButtonColor: '#4f46e5',
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
</style>
</body>
</html>
