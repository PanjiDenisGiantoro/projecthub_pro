@extends('layouts.app')

@section('title', 'My Profile')
@section('page-title', 'My Profile')

@section('content')
    <div class="max-w-6xl mx-auto py-6 px-4 sm:px-6 lg:px-8 space-y-8">

        {{-- Breadcrumb & Page Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <nav class="flex items-center gap-2 text-xs text-slate-400 mb-1.5">
                    <a href="{{ route('dashboard') }}"
                        class="hover:text-slate-600 dark:hover:text-slate-300 transition-colors">Dashboard</a>
                    <span>/</span>
                    <span class="text-slate-600 dark:text-slate-300 font-medium">Profile</span>
                </nav>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Pengaturan Akun & Profil</h1>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">Kelola informasi pribadi, foto
                    avatar, keamanan login, dan preferensi notifikasi Anda.</p>
            </div>
            <div class="flex items-center gap-3 shrink-0">
                <span
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold {{ $user->is_active ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/40' : 'bg-slate-100 text-slate-600' }}">
                    <span
                        class="w-2 h-2 rounded-full {{ $user->is_active ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400' }}"></span>
                    {{ $user->is_active ? 'Akun Aktif' : 'Non-Aktif' }}
                </span>
                <span
                    class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300 border border-blue-200 dark:border-blue-800/40 capitalize">
                    {{ \App\Support\RoleLabel::for($user->getRoleNames()->first()) ?: 'Member' }}
                </span>
            </div>
        </div>

        {{-- Hero Profile Banner Card --}}
        <div
            class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950 to-blue-950 text-white shadow-xl border border-slate-800/80 p-6 sm:p-8">
            {{-- Background decorative ambient circles --}}
            <div class="absolute -right-16 -top-16 w-64 h-64 rounded-full bg-blue-500/10 blur-3xl pointer-events-none">
            </div>
            <div class="absolute -left-16 -bottom-16 w-64 h-64 rounded-full bg-indigo-500/10 blur-3xl pointer-events-none">
            </div>

            <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                <div class="flex items-center gap-5">
                    {{-- Avatar Preview --}}
                    <div class="relative group shrink-0">
                        @if($user->avatar)
                            <img id="avatar-preview-hero" src="{{ Storage::url($user->avatar) }}" alt="{{ $user->name }}"
                                class="w-20 h-20 sm:w-24 sm:h-24 rounded-full object-cover ring-4 ring-white/20 shadow-full transition-transform group-hover:scale-105 duration-200">
                        @else
                            <div id="avatar-fallback-hero"
                                class="w-20 h-20 sm:w-24 sm:h-24 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center text-white font-extrabold text-2xl sm:text-3xl ring-4 ring-white/20 shadow-2xl transition-transform group-hover:scale-105 duration-200">
                                {{ strtoupper(substr($user->name, 0, 2)) }}
                            </div>
                        @endif
                        <div class="absolute bottom-0 right-0 w-5 h-5 rounded-full bg-emerald-500 border-2 border-slate-900 flex items-center justify-center"
                            title="Online & Aktif">
                            <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                        </div>
                    </div>

                    {{-- User Info Header --}}
                    <div class="min-w-0">
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-white">{{ $user->name }}</h2>
                            <span
                                class="px-2.5 py-0.5 rounded-md text-[11px] font-semibold bg-white/10 text-blue-200 backdrop-blur-md">
                                {{ \App\Support\RoleLabel::for($user->getRoleNames()->first()) }}
                            </span>
                        </div>
                        <p class="text-sm text-slate-300 mt-1 flex items-center gap-2">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <span>{{ $user->email }}</span>
                        </p>
                        <div class="flex items-center gap-4 mt-3 text-xs text-slate-400 flex-wrap">
                            @if($user->company)
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-blue-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    {{ $user->company->name }}
                                </span>
                            @endif
                            @if($user->organizationUnit)
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    {{ $user->organizationUnit->name }}
                                </span>
                            @endif
                            <span class="flex items-center gap-1.5 text-slate-400">
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Bergabung: {{ $user->created_at ? $user->created_at->format('d M Y') : 'Aktif' }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons in Hero Banner (Upload Foto & Connect Google Calendar & Meet) --}}
                <div
                    class="flex items-center gap-3 w-full md:w-auto border-t md:border-t-0 border-white/10 pt-4 md:pt-0 flex-wrap">
                    {{-- 1. Button Upload Foto Profil --}}
                    <form method="POST" action="{{ route('profile.avatar') }}" enctype="multipart/form-data"
                        id="hero-avatar-upload-form" class="m-0">
                        @csrf
                        @method('PUT')
                        <label
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 active:scale-98 text-white text-xs sm:text-sm font-semibold backdrop-blur-md border border-white/15 transition-all cursor-pointer shadow-sm">
                            <svg class="w-4 h-4 text-blue-300 shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span>Upload Foto Profil</span>
                            <input type="file" name="avatar" accept="image/*" class="hidden"
                                onchange="document.getElementById('hero-avatar-upload-form').submit()">
                        </label>
                    </form>

                    {{-- 2. Button Connect / Disconnect Google Calendar & Meet --}}
                    @if($googleToken)
                        <form method="POST" action="{{ route('google-calendar.disconnect') }}"
                            onsubmit="return confirm('Apakah Anda yakin ingin memutuskan koneksi Google Calendar?');"
                            class="m-0">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-500/20 hover:bg-rose-500/20 text-emerald-300 hover:text-rose-200 border border-emerald-400/30 hover:border-rose-400/30 text-xs sm:text-sm font-semibold backdrop-blur-md transition-all cursor-pointer shadow-sm group">
                                <span
                                    class="w-2 h-2 rounded-full bg-emerald-400 group-hover:bg-rose-400 transition-colors shrink-0"></span>
                                <span class="group-hover:hidden">Google Terhubung</span>
                                <span class="hidden group-hover:inline">Disconnect Google</span>
                            </button>
                        </form>
                    @else
                        <a href="{{ route('google-calendar.connect') }}"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 active:scale-98 text-white text-xs sm:text-sm font-semibold transition-all cursor-pointer shadow-md shadow-blue-600/30 border border-blue-400/30">
                            <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20a2 2 0 002 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zm0-12H5V6h14v2z" />
                            </svg>
                            <span>Connect Google Calendar & Meet</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- Main Content Grid: 2 Columns on desktop --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

            {{-- Left Column: Personal Info & Avatar (7 cols) --}}
            <div class="lg:col-span-7 space-y-8">

                {{-- 1. Informasi Pribadi & Profil Form --}}
                <div
                    class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-7 shadow-sm border border-slate-200/80 dark:border-slate-800 transition-shadow hover:shadow-md">
                    <div class="flex items-center justify-between pb-5 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7 7z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">Informasi Dasar Akun</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Informasi identitas akun yang
                                    ditampilkan ke seluruh rekan kerja.</p>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('profile.update') }}" class="mt-6 space-y-5">
                        @csrf
                        @method('PUT')

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2">Nama
                                Lengkap</label>
                            <div class="relative">
                                <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm font-medium text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all shadow-sm @error('name') border-rose-500 ring-1 ring-rose-500 @enderror">
                            </div>
                            @error('name')
                                <p class="text-xs text-rose-500 mt-1.5 font-medium flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2">Alamat
                                    Email</label>
                                <div class="relative">
                                    <input type="email" value="{{ $user->email }}" disabled
                                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 text-sm font-medium text-slate-500 dark:text-slate-400 cursor-not-allowed">
                                    <div
                                        class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-1 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/60 px-2 py-0.5 rounded-md">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                        Terverifikasi
                                    </div>
                                </div>
                                <p class="text-[11px] text-slate-400 mt-1.5">Email dikaitkan dengan otorisasi login akun
                                    perusahaan.</p>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2">Zona
                                    Waktu</label>
                                <select name="timezone"
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm font-medium text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all shadow-sm">
                                    <option value="Asia/Jakarta" {{ old('timezone', $user->timezone) === 'Asia/Jakarta' ? 'selected' : '' }}>Asia/Jakarta (WIB, UTC+7)</option>
                                    <option value="Asia/Makassar" {{ old('timezone', $user->timezone) === 'Asia/Makassar' ? 'selected' : '' }}>Asia/Makassar (WITA, UTC+8)</option>
                                    <option value="Asia/Jayapura" {{ old('timezone', $user->timezone) === 'Asia/Jayapura' ? 'selected' : '' }}>Asia/Jayapura (WIT, UTC+9)</option>
                                    <option value="UTC" {{ old('timezone', $user->timezone) === 'UTC' ? 'selected' : '' }}>UTC
                                        (Coordinated Universal Time)</option>
                                </select>
                            </div>
                        </div>

                        {{-- Readonly Org Details --}}
                        <div
                            class="rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800 p-4 grid grid-cols-2 sm:grid-cols-3 gap-4">
                            <div>
                                <p class="text-[11px] font-medium text-slate-400">Role / Posisi</p>
                                <p class="text-xs font-bold text-slate-800 dark:text-slate-200 mt-0.5 capitalize">
                                    {{ \App\Support\RoleLabel::for($user->getRoleNames()->first()) ?: '-' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-[11px] font-medium text-slate-400">Unit Organisasi</p>
                                <p class="text-xs font-bold text-slate-800 dark:text-slate-200 mt-0.5">
                                    {{ $user->organizationUnit?->name ?: '-' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-[11px] font-medium text-slate-400">Level Struktural</p>
                                <p class="text-xs font-bold text-slate-800 dark:text-slate-200 mt-0.5">
                                    {{ $user->structuralLevel?->name ?: '-' }}
                                </p>
                            </div>
                        </div>

                        <div class="flex justify-end pt-3">
                            <button type="submit"
                                class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 active:scale-98 text-white text-xs sm:text-sm font-semibold shadow-md shadow-blue-500/20 transition-all cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                                Simpan Informasi
                            </button>
                        </div>
                    </form>
                </div>

                {{-- 2. Foto Profil & Avatar Card --}}
                <div
                    class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-7 shadow-sm border border-slate-200/80 dark:border-slate-800 transition-shadow hover:shadow-md">
                    <div class="flex items-center justify-between pb-5 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">Foto Profil (Avatar)</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Upload foto berformat JPG, PNG, atau
                                    WEBP dengan ukuran maks. 2MB.</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-col sm:flex-row items-center gap-6">
                        <div class="relative shrink-0">
                            @if($user->avatar)
                                <img id="avatar-card-preview" src="{{ Storage::url($user->avatar) }}" alt="{{ $user->name }}"
                                    class="w-24 h-24 rounded-2xl object-cover ring-2 ring-slate-200 dark:ring-slate-700 shadow-md">
                            @else
                                <div id="avatar-card-fallback"
                                    class="w-24 h-24 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500 dark:text-slate-400 font-bold text-3xl ring-2 ring-slate-200 dark:ring-slate-700 shadow-md">
                                    {{ strtoupper(substr($user->name, 0, 2)) }}
                                </div>
                            @endif
                        </div>

                        <div class="flex-1 w-full space-y-3">
                            <form method="POST" action="{{ route('profile.avatar') }}" enctype="multipart/form-data"
                                class="space-y-3">
                                @csrf
                                @method('PUT')

                                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                                    <label class="flex-1 cursor-pointer">
                                        <div
                                            class="px-4 py-2.5 rounded-xl border-2 border-dashed border-slate-200 dark:border-slate-700 hover:border-blue-500 dark:hover:border-blue-400 text-center transition-all bg-slate-50/50 dark:bg-slate-800/30">
                                            <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Pilih
                                                Foto Baru</span>
                                            <input type="file" name="avatar" accept="image/*" class="hidden" onchange="
                                                                           if (this.files && this.files[0]) {
                                                                               const reader = new FileReader();
                                                                               reader.onload = function(e) {
                                                                                   const prev = document.getElementById('avatar-card-preview');
                                                                                   if (prev) prev.src = e.target.result;
                                                                                   const hero = document.getElementById('avatar-preview-hero');
                                                                                   if (hero) hero.src = e.target.result;
                                                                               };
                                                                               reader.readAsDataURL(this.files[0]);
                                                                               document.getElementById('save-avatar-btn').classList.remove('opacity-50', 'pointer-events-none');
                                                                           }
                                                                       ">
                                        </div>
                                    </label>
                                    <button type="submit" id="save-avatar-btn"
                                        class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs sm:text-sm font-semibold transition-all shadow-md shadow-blue-500/20 cursor-pointer">
                                        Simpan Foto
                                    </button>
                                </div>
                            </form>

                            @if($user->avatar)
                                <form method="POST" action="{{ route('profile.avatar.remove') }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="inline-flex items-center gap-1.5 text-xs text-rose-600 hover:text-rose-700 dark:text-rose-400 font-semibold transition-colors cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Hapus Foto Profil Saat Ini
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>

            </div>

            {{-- Right Column: Security, Notifications, Integrations (5 cols) --}}
            <div class="lg:col-span-5 space-y-8">

                {{-- 3. Ganti Password & Keamanan --}}
                <div
                    class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-7 shadow-sm border border-slate-200/80 dark:border-slate-800 transition-shadow hover:shadow-md">
                    <div class="flex items-center justify-between pb-5 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">Ubah Password</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Pastikan menggunakan kata sandi
                                    minimal 8 karakter yang aman.</p>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('profile.password') }}" class="mt-6 space-y-4">
                        @csrf
                        @method('PUT')

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Password
                                Saat Ini</label>
                            <input type="password" name="current_password" required placeholder="••••••••"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm font-medium text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all shadow-sm @error('current_password') border-rose-500 ring-1 ring-rose-500 @enderror">
                            @error('current_password')
                                <p class="text-xs text-rose-500 mt-1 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Password
                                Baru</label>
                            <input type="password" name="password" required placeholder="Minimal 8 karakter"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm font-medium text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all shadow-sm @error('password') border-rose-500 ring-1 ring-rose-500 @enderror">
                            @error('password')
                                <p class="text-xs text-rose-500 mt-1 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Konfirmasi
                                Password Baru</label>
                            <input type="password" name="password_confirmation" required placeholder="Ulangi password baru"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm font-medium text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all shadow-sm">
                        </div>

                        <div class="pt-2">
                            <button type="submit"
                                class="w-full py-2.5 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100 text-white text-xs sm:text-sm font-semibold transition-all shadow-md cursor-pointer">
                                Perbarui Password
                            </button>
                        </div>
                    </form>
                </div>

                {{-- 4. Preferensi & Integrasi --}}
                <div
                    class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-7 shadow-sm border border-slate-200/80 dark:border-slate-800 transition-shadow hover:shadow-md space-y-6">
                    <div class="flex items-center gap-3 pb-5 border-b border-slate-100 dark:border-slate-800">
                        <div
                            class="w-10 h-10 rounded-xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center font-bold">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">Preferensi & Integrasi</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Pengaturan pengingat dan koneksi aplikasi
                                pihak ketiga.</p>
                        </div>
                    </div>

                    {{-- A. Push Notification Card --}}
                    <div x-data="notificationToggle()" x-init="init()"
                        class="p-4 rounded-2xl bg-slate-50/70 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                        <div class="flex items-center justify-between gap-4">
                            <div class="min-w-0">
                                <p
                                    class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <svg class="w-4 h-4 text-blue-500 shrink-0" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                    </svg>
                                    Push Notifikasi Web
                                </p>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1" x-show="pushAvailable">Terima
                                    notifikasi instan pada browser untuk pesan, tiket, & deadline task.</p>
                                <p class="text-[11px] text-rose-500 mt-1" x-show="!pushAvailable">Browser ini belum
                                    mendukung web push notification.</p>
                            </div>
                            <button type="button" @click="toggle()" :disabled="loading || !pushAvailable"
                                class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors disabled:opacity-40 cursor-pointer shadow-inner"
                                :class="enabled ? 'bg-blue-600' : 'bg-slate-300 dark:bg-slate-700'">
                                <span
                                    class="inline-block h-4 w-4 transform rounded-full bg-white shadow-md transition-transform"
                                    :class="enabled ? 'translate-x-6' : 'translate-x-1'"></span>
                            </button>
                        </div>
                    </div>

                    {{-- B. Email Notification Card --}}
                    <div
                        class="p-4 rounded-2xl bg-slate-50/70 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                        <form method="POST" action="{{ route('profile.email-notifications') }}"
                            x-data="{ enabled: {{ $user->email_notifications_enabled ? 'true' : 'false' }} }">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="email_notifications_enabled" :value="enabled ? 0 : 1">
                            <div class="flex items-center justify-between gap-4">
                                <div class="min-w-0">
                                    <p
                                        class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                        <svg class="w-4 h-4 text-indigo-500 shrink-0" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                        Pengingat Email
                                    </p>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Terima email rangkuman
                                        otomatis saat ada tiket baru atau status penting.</p>
                                </div>
                                <button type="submit"
                                    class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors cursor-pointer shadow-inner"
                                    :class="enabled ? 'bg-blue-600' : 'bg-slate-300 dark:bg-slate-700'">
                                    <span
                                        class="inline-block h-4 w-4 transform rounded-full bg-white shadow-md transition-transform"
                                        :class="enabled ? 'translate-x-6' : 'translate-x-1'"></span>
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- C. Google Calendar Integration Card --}}
                    <div
                        class="p-4 rounded-2xl bg-slate-50/70 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 shrink-0 text-blue-500" viewBox="0 0 24 24" fill="currentColor">
                                        <path
                                            d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20a2 2 0 002 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zm0-12H5V6h14v2z" />
                                    </svg>
                                    <p class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white">Google Calendar &
                                        Meet</p>
                                </div>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                                    Otomatis jadwalkan meeting Google Meet dari Sprint, Milestone, dan Task.
                                </p>
                                <div class="mt-2.5">
                                    @if($googleToken)
                                        <span
                                            class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[10px] font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/40">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Terkoneksi dengan Google
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[10px] font-semibold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                            Belum Terhubung
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="shrink-0">
                                @if($googleToken)
                                    <form method="POST" action="{{ route('google-calendar.disconnect') }}"
                                        onsubmit="return confirm('Apakah Anda yakin ingin memutuskan koneksi Google Calendar?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="px-3 py-1.5 rounded-xl text-xs font-semibold text-rose-600 hover:text-rose-700 dark:text-rose-400 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 transition-colors cursor-pointer border border-rose-200 dark:border-rose-800/40">
                                            Disconnect
                                        </button>
                                    </form>
                                @else
                                    <a href="{{ route('google-calendar.connect') }}"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 transition-colors shadow-sm cursor-pointer">
                                        Connect
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>

                </div>

            </div>

        </div>

    </div>
@endsection