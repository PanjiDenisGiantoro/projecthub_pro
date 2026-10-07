@extends('layouts.app')

@section('title', 'Edit Klien ' . $client->name)
@section('page-title', 'Edit Klien')

@section('content')
<div class="space-y-6 pt-5 pb-8 max-w-3xl mx-auto">

    {{-- Breadcrumb --}}
    <nav class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-2">
        <a href="{{ route('clients.index') }}" class="hover:text-blue-600 transition flex items-center gap-1">
            <span>&larr; Manajemen Klien</span>
        </a>
        <span>/</span>
        <span class="text-slate-800 dark:text-slate-200 font-semibold">{{ $client->name }}</span>
    </nav>

    {{-- Header Banner --}}
    <div class="relative overflow-hidden rounded-2xl border border-slate-200/80 dark:border-gray-700/80 bg-gradient-to-r from-blue-50/80 via-indigo-50/50 to-cyan-50/60 dark:from-gray-850 dark:via-gray-850 dark:to-gray-800 px-6 py-6 shadow-xs">
        <div>
            <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-blue-100/70 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-xs font-semibold mb-2">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-600 dark:bg-blue-400 animate-pulse"></span>
                Edit Akun Mitra Klien
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                Edit Data Klien
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Perbarui profil perusahaan, alamat email, atau reset kata sandi akses klien.
            </p>
        </div>
    </div>

    {{-- Form --}}
    <form method="POST" action="{{ route('clients.update', $client) }}" class="space-y-6">
        @csrf @method('PUT')

        @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 dark:bg-rose-950/40 dark:border-rose-900 text-rose-700 dark:text-rose-300 text-xs font-semibold">
            {{ $errors->first() }}
        </div>
        @endif

        {{-- Section 1: Profil Klien --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 p-6 shadow-xs space-y-4">
            <div class="border-b border-slate-100 dark:border-gray-700 pb-3">
                <h2 class="text-sm font-bold text-slate-900 dark:text-white">1. Profil Klien / Perusahaan</h2>
                <p class="text-xs text-slate-400">Informasi nama entitas dan kontak email resmi.</p>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5" for="name">
                        Nama Perusahaan / Klien <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="name" name="name" value="{{ old('name', $client->name) }}" required
                           class="w-full px-3.5 py-2.5 text-xs sm:text-sm bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-xl text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-2xs">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5" for="email">
                        Alamat Email <span class="text-rose-500">*</span>
                    </label>
                    <input type="email" id="email" name="email" value="{{ old('email', $client->email) }}" required
                           class="w-full px-3.5 py-2.5 text-xs sm:text-sm bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-xl text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-2xs">
                </div>

                <div class="pt-1">
                    <label class="flex items-center gap-2.5 cursor-pointer">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" id="is_active"
                               class="w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500"
                               {{ old('is_active', $client->is_active) ? 'checked' : '' }}>
                        <div>
                            <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Akun Aktif</span>
                            <p class="text-[11px] text-slate-400">Nonaktifkan untuk mencabut akses portal sementara.</p>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        {{-- Section 2: Ganti Password --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 p-6 shadow-xs space-y-4">
            <div class="border-b border-slate-100 dark:border-gray-700 pb-3">
                <h2 class="text-sm font-bold text-slate-900 dark:text-white">2. Ganti Kata Sandi</h2>
                <p class="text-xs text-slate-400">Kosongkan kolom di bawah jika tidak ingin mengganti password klien saat ini.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5" for="password">
                        Password Baru
                    </label>
                    <input type="password" id="password" name="password" minlength="8"
                           placeholder="••••••••"
                           class="w-full px-3.5 py-2.5 text-xs sm:text-sm bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-xl text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-2xs">
                    <p class="text-[11px] text-slate-400 mt-1">Minimal 8 karakter jika diisi.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5" for="password_confirmation">
                        Konfirmasi Password Baru
                    </label>
                    <input type="password" id="password_confirmation" name="password_confirmation"
                           placeholder="••••••••"
                           class="w-full px-3.5 py-2.5 text-xs sm:text-sm bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-xl text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-2xs">
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
                Simpan Perubahan
            </button>
        </div>
    </form>

</div>
@endsection
