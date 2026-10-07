@extends('layouts.app')

@section('title', 'Tambah Klien Baru')
@section('page-title', 'Tambah Klien Baru')

@section('content')
<div class="space-y-6 pt-5 pb-8 max-w-3xl mx-auto">

    {{-- Breadcrumb --}}
    <nav class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-2">
        <a href="{{ route('clients.index') }}" class="hover:text-blue-600 transition flex items-center gap-1">
            <span>&larr; Manajemen Klien</span>
        </a>
        <span>/</span>
        <span class="text-slate-800 dark:text-slate-200 font-semibold">Tambah Klien</span>
    </nav>

    {{-- Header Banner --}}
    <div class="relative overflow-hidden rounded-2xl border border-slate-200/80 dark:border-gray-700/80 bg-gradient-to-r from-blue-50/80 via-indigo-50/50 to-cyan-50/60 dark:from-gray-850 dark:via-gray-850 dark:to-gray-800 px-6 py-6 shadow-xs">
        <div>
            <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-blue-100/70 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-xs font-semibold mb-2">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-600 dark:bg-blue-400 animate-pulse"></span>
                Portal Akses Klien
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                Tambah Akun Klien
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Klien akan memiliki role <strong>Client</strong> dan hanya dapat mengakses proyek & dokumen yang ditugaskan kepada mereka.
            </p>
        </div>
    </div>

    {{-- Form --}}
    <form method="POST" action="{{ route('clients.store') }}" class="space-y-6">
        @csrf

        @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 dark:bg-rose-950/40 dark:border-rose-900 text-rose-700 dark:text-rose-300 text-xs font-semibold">
            {{ $errors->first() }}
        </div>
        @endif

        {{-- Section 1: Profil Klien --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 p-6 shadow-xs space-y-4">
            <div class="border-b border-slate-100 dark:border-gray-700 pb-3">
                <h2 class="text-sm font-bold text-slate-900 dark:text-white">1. Identitas Klien / Perusahaan</h2>
                <p class="text-xs text-slate-400">Informasi nama instansi dan alamat email resmi.</p>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5" for="name">
                        Nama Klien / Perusahaan <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus
                           placeholder="Contoh: PT Nusantara Digital / Bpk. Hendra"
                           class="w-full px-3.5 py-2.5 text-xs sm:text-sm bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-xl text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-2xs">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5" for="email">
                        Alamat Email Klien <span class="text-rose-500">*</span>
                    </label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required
                           placeholder="client@perusahaan.com"
                           class="w-full px-3.5 py-2.5 text-xs sm:text-sm bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-xl text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-2xs">
                </div>
            </div>
        </div>

        {{-- Section 2: Akses & Keamanan --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 p-6 shadow-xs space-y-4">
            <div class="border-b border-slate-100 dark:border-gray-700 pb-3">
                <h2 class="text-sm font-bold text-slate-900 dark:text-white">2. Kredensial & Status Akses</h2>
                <p class="text-xs text-slate-400">Atur kata sandi login dan status aktivasi portal.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5" for="password">
                        Password Awal <span class="text-rose-500">*</span>
                    </label>
                    <input type="password" id="password" name="password" required minlength="8"
                           placeholder="••••••••"
                           class="w-full px-3.5 py-2.5 text-xs sm:text-sm bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-xl text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-2xs">
                    <p class="text-[11px] text-slate-400 mt-1">Minimal 8 karakter.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5" for="password_confirmation">
                        Konfirmasi Password <span class="text-rose-500">*</span>
                    </label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required
                           placeholder="••••••••"
                           class="w-full px-3.5 py-2.5 text-xs sm:text-sm bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-xl text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-2xs">
                </div>

                <div class="sm:col-span-2 pt-2 space-y-3">
                    <label class="flex items-center gap-2.5 cursor-pointer">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" id="is_active"
                               class="w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500"
                               {{ old('_token') ? (old('is_active') ? 'checked' : '') : 'checked' }}>
                        <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Akun Aktif (Dapat langsung login)</span>
                    </label>

                    <label class="flex items-center gap-2.5 cursor-pointer">
                        <input type="checkbox" name="send_verification" value="1" id="send_verification"
                               class="w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500"
                               {{ old('send_verification') ? 'checked' : '' }}>
                        <div>
                            <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Kirim email verifikasi ke klien</span>
                            <p class="text-[11px] text-slate-400">Jika dicentang, klien harus memverifikasi email sebelum login.</p>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('clients.index') }}"
               class="px-5 py-2.5 text-xs sm:text-sm font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-300 transition">
                Batal
            </a>
            <button type="submit"
                    class="inline-flex items-center gap-2 px-6 py-2.5 text-xs sm:text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 rounded-xl transition-all shadow-sm shadow-blue-600/20">
                Simpan & Tambah Klien
            </button>
        </div>
    </form>

</div>
@endsection
