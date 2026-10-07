@extends('layouts.app')

@section('title', 'Manajemen Klien')
@section('page-title', 'Manajemen Klien')

@section('content')
<div class="space-y-6 pt-5 pb-8">

    {{-- ── Hero Banner ──────────────────────────────────────────────────────── --}}
    <div class="relative overflow-hidden rounded-2xl border border-slate-200/80 dark:border-gray-700/80 bg-gradient-to-r from-blue-50/80 via-indigo-50/50 to-cyan-50/60 dark:from-gray-850 dark:via-gray-850 dark:to-gray-800 px-6 py-6 sm:px-7 shadow-xs">
        <img src="{{ asset('flovig_icon.png') }}" alt=""
             class="pointer-events-none select-none absolute -right-6 -bottom-8 w-44 h-auto opacity-[0.05] dark:opacity-[0.03]">
        <div class="relative flex flex-col sm:flex-row sm:items-center justify-between gap-5">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-blue-100/70 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-xs font-semibold mb-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-600 dark:bg-blue-400 animate-pulse"></span>
                    External Stakeholders & Clients
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                    Manajemen Klien
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Kelola akun perwakilan klien, akses portal proyek, dan status kerjasama perusahaan.
                </p>
            </div>

            @can('create user')
            <div class="shrink-0">
                <a href="{{ route('clients.create') }}"
                   class="inline-flex items-center gap-2 px-4.5 py-2.5 text-xs sm:text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 rounded-xl transition-all shadow-sm shadow-blue-600/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                    Tambah Klien
                </a>
            </div>
            @endcan
        </div>
    </div>

    {{-- ── 3 Metric KPI Cards ──────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4.5">
        {{-- Total Clients --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 shadow-xs hover:shadow-md transition-all duration-200 p-5 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Klien</span>
                <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
            </div>
            <div>
                <p class="text-3xl font-black text-slate-900 dark:text-white tabular-nums">{{ $totalClients }}</p>
                <span class="text-xs font-semibold text-slate-400">perusahaan terdaftar</span>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-gray-800 flex items-center justify-between text-xs font-medium text-slate-500">
                <span>Database mitra</span>
                <span class="font-bold text-slate-700 dark:text-slate-300">Aktif & Terhubung</span>
            </div>
        </div>

        {{-- Active Clients --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 shadow-xs hover:shadow-md transition-all duration-200 p-5 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Klien Aktif</span>
                <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div>
                <p class="text-3xl font-black text-emerald-600 dark:text-emerald-400 tabular-nums">{{ $activeClients }}</p>
                <span class="text-xs font-semibold text-slate-400">dapat login ke portal</span>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-gray-800 flex items-center justify-between text-xs font-medium text-slate-500">
                <span>Rasio keaktifan</span>
                <span class="font-bold text-emerald-600">{{ $totalClients > 0 ? round($activeClients / $totalClients * 100) : 0 }}% aktif</span>
            </div>
        </div>

        {{-- Inactive Clients --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 shadow-xs hover:shadow-md transition-all duration-200 p-5 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Klien Nonaktif</span>
                <div class="w-9 h-9 rounded-xl bg-slate-50 dark:bg-gray-800 text-slate-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                </div>
            </div>
            <div>
                <p class="text-3xl font-black text-slate-500 dark:text-slate-400 tabular-nums">{{ $inactiveClients }}</p>
                <span class="text-xs font-semibold text-slate-400">akses ditangguhkan</span>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-gray-800 flex items-center justify-between text-xs font-medium text-slate-500">
                <span>Status akun</span>
                <span class="font-bold text-slate-400">Nonaktif</span>
            </div>
        </div>
    </div>

    {{-- ── Search & Filter Bar ─────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 p-4 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <form method="GET" action="{{ route('clients.index') }}" class="flex flex-1 items-center gap-2.5 flex-wrap">
            <div class="relative w-full sm:w-64">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Cari nama / email klien..."
                       class="w-full pl-9 pr-3.5 py-2 text-xs sm:text-sm bg-slate-50 dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-xl text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            <select name="status" onchange="this.form.submit()"
                    class="px-3.5 py-2 text-xs sm:text-sm bg-slate-50 dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-xl text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 cursor-pointer">
                <option value="">Semua Status</option>
                <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Aktif</option>
                <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Nonaktif</option>
            </select>

            @if(request('search') || request('status') !== null)
            <a href="{{ route('clients.index') }}"
               class="px-3 py-2 text-xs sm:text-sm font-semibold text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 transition">
                Reset
            </a>
            @endif
        </form>
    </div>

    {{-- ── Clients Modern Table ────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/80 dark:bg-gray-800/80 border-b border-slate-100 dark:border-gray-700 text-[11px] font-bold text-slate-400 dark:text-slate-400 uppercase tracking-wider">
                        <th class="px-5 py-3.5">Perusahaan & Kontak</th>
                        <th class="px-5 py-3.5">Email Klien</th>
                        <th class="px-5 py-3.5">Proyek Terkait</th>
                        <th class="px-5 py-3.5">Status Akun</th>
                        <th class="px-5 py-3.5">Bergabung</th>
                        @canany(['update user', 'delete user'])
                        <th class="px-5 py-3.5 text-right">Aksi</th>
                        @endcanany
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-gray-800 text-slate-700 dark:text-slate-200">
                    @forelse($clients as $client)
                    <tr class="hover:bg-slate-50/80 dark:hover:bg-gray-800/50 transition-colors">
                        {{-- Client Name & Avatar --}}
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-3">
                                @if($client->avatar)
                                    <img src="{{ Storage::url($client->avatar) }}" alt="{{ $client->name }}"
                                         class="w-9 h-9 rounded-xl object-cover ring-2 ring-slate-100 dark:ring-gray-700 shrink-0">
                                @else
                                    <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white font-bold text-xs shrink-0 shadow-2xs"
                                         style="background: linear-gradient(135deg, #3b82f6, #6366f1)">
                                        {{ strtoupper(substr($client->name, 0, 2)) }}
                                    </div>
                                @endif
                                <div>
                                    <p class="font-bold text-slate-900 dark:text-white leading-tight">{{ $client->name }}</p>
                                    <span class="text-[10px] text-slate-400">ID: #{{ $client->id }}</span>
                                </div>
                            </div>
                        </td>

                        {{-- Email --}}
                        <td class="px-5 py-3.5 font-medium text-slate-600 dark:text-slate-300">
                            {{ $client->email }}
                        </td>

                        {{-- Proyek --}}
                        <td class="px-5 py-3.5">
                            @forelse($client->clientProjects as $proj)
                                <a href="{{ route('projects.show', $proj) }}"
                                   class="inline-block px-2.5 py-1 mb-1 mr-1 rounded-lg text-[11px] font-semibold bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-950/40 dark:text-blue-300 transition-colors">
                                    {{ $proj->name }}
                                </a>
                            @empty
                                <span class="text-xs text-slate-400">— Belum ada proyek</span>
                            @endforelse
                        </td>

                        {{-- Status --}}
                        <td class="px-5 py-3.5">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border
                                {{ $client->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-slate-100 text-slate-500 border-slate-200 dark:bg-gray-800 dark:text-gray-400' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $client->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                {{ $client->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>

                        {{-- Bergabung --}}
                        <td class="px-5 py-3.5 text-slate-500 dark:text-slate-400">
                            {{ $client->created_at->format('d M Y') }}
                        </td>

                        {{-- Aksi --}}
                        @canany(['update user', 'delete user'])
                        <td class="px-5 py-3.5 text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-2">
                                @can('update user')
                                <a href="{{ route('clients.edit', $client) }}"
                                   class="px-2.5 py-1 text-xs font-semibold text-blue-600 hover:text-blue-800 hover:bg-blue-50 dark:hover:bg-blue-950/40 rounded-lg transition">
                                    Edit
                                </a>
                                @endcan
                                @can('delete user')
                                <form method="POST" action="{{ route('clients.destroy', $client) }}"
                                      data-confirm-delete="{{ $client->name }}" data-confirm-label="Hapus Client">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="px-2.5 py-1 text-xs font-semibold text-rose-600 hover:text-rose-800 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg transition">
                                        Hapus
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </td>
                        @endcanany
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 py-16 text-center">
                            <div class="w-16 h-16 rounded-2xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center mx-auto mb-3">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Belum ada klien</h3>
                            <p class="text-xs text-slate-400 mt-1">Tambahkan akun klien baru untuk memulai kolaborasi proyek.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination & PerPage --}}
        <div class="px-5 py-3.5 border-t border-slate-100 dark:border-gray-800 flex items-center justify-between gap-3 flex-wrap">
            <x-per-page />
            @if($clients->hasPages())
            {{ $clients->links() }}
            @endif
        </div>
    </div>

</div>
@endsection
