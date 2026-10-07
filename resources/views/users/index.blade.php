@extends('layouts.app')

@section('title', 'Manajemen Pengguna')
@section('page-title', 'Manajemen Pengguna')

@section('content')
<div class="space-y-6 pt-5 pb-8"
     x-data="{ logsOpen: false, logsLoading: false, logsLoaded: false, importOpen: false }"
     x-init="$watch('logsOpen', value => {
         if (!value || logsLoaded) return;
         logsLoading = true;
         fetch('{{ route('users.logs') }}', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
             .then(r => r.text())
             .then(html => { $refs.userLogList.innerHTML = html; logsLoaded = true; })
             .catch(() => { $refs.userLogList.innerHTML = '<p class=&quot;px-4 py-6 text-center text-xs text-rose-400&quot;>Gagal memuat log aktivitas.</p>'; })
             .finally(() => { logsLoading = false; });
     })">

    {{-- ── Hero Banner ──────────────────────────────────────────────────────── --}}
    <div class="relative overflow-hidden rounded-2xl border border-slate-200/80 dark:border-gray-700/80 bg-gradient-to-r from-blue-50/80 via-indigo-50/50 to-cyan-50/60 dark:from-gray-850 dark:via-gray-850 dark:to-gray-800 px-6 py-6 sm:px-7 shadow-xs">
        <img src="{{ asset('flovig_icon.png') }}" alt=""
             class="pointer-events-none select-none absolute -right-6 -bottom-8 w-44 h-auto opacity-[0.05] dark:opacity-[0.03]">
        <div class="relative flex flex-col lg:flex-row lg:items-center justify-between gap-5">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-blue-100/70 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-xs font-semibold mb-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-600 dark:bg-blue-400 animate-pulse"></span>
                    Team Identity & Access Control
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                    Manajemen Pengguna
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Kelola akun anggota tim, otorisasi role, keterlibatan proyek, dan struktur organisasi.
                </p>
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center gap-2 flex-wrap shrink-0">
                @if(auth()->user()->hasRole('admin') || auth()->user()->is_super_admin)
                <a href="{{ route('custom-fields.index') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl border border-slate-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-gray-700 transition shadow-2xs">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Custom Fields
                </a>
                @endif

                @can('export user')
                <a href="{{ route('users.export', request()->query()) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl border border-slate-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-gray-700 transition shadow-2xs">
                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                    </svg>
                    Export Excel
                </a>
                @endcan

                @can('import user')
                <button type="button" @click="importOpen = true"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl border border-slate-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-gray-700 transition shadow-2xs">
                    <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    Import
                </button>
                @endcan

                @if($isAdmin)
                <button type="button" @click="logsOpen = !logsOpen"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl border border-slate-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-gray-700 transition shadow-2xs">
                    <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Activity Logs
                    @if($logsCount)
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 dark:bg-gray-700 dark:text-slate-300">{{ $logsCount }}</span>
                    @endif
                    <svg class="w-3 h-3 transition-transform" :class="logsOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                @endif

                @if($canCreate)
                <a href="{{ route('users.create') }}"
                   class="inline-flex items-center gap-2 px-4.5 py-2 text-xs sm:text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 rounded-xl transition-all shadow-sm shadow-blue-600/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                    Add User
                </a>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Activity Logs Drawer (Collapsible) ───────────────────────────────── --}}
    @if($isAdmin)
    <div x-show="logsOpen" x-cloak x-transition
         class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 shadow-xs overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-100 dark:border-gray-700 flex items-center justify-between bg-slate-50/50 dark:bg-gray-800/50">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Log Aktivitas Pengguna Terkini</h3>
            <button type="button" @click="logsOpen = false" class="text-slate-400 hover:text-slate-600 text-xs">&times; Tutup</button>
        </div>
        <div class="max-h-80 overflow-y-auto">
            <p x-show="logsLoading" class="px-4 py-8 text-center text-xs text-slate-400">Memuat log aktivitas...</p>
            <div x-show="!logsLoading" x-ref="userLogList" class="divide-y divide-slate-100 dark:divide-gray-800 text-xs"></div>
        </div>
    </div>
    @endif

    {{-- ── 3 Metric KPI Cards ──────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4.5">
        {{-- Total Users --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 shadow-xs hover:shadow-md transition-all duration-200 p-5 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Pengguna</span>
                <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
            </div>
            <div>
                <p class="text-3xl font-black text-slate-900 dark:text-white tabular-nums">{{ $totalUsers }}</p>
                <span class="text-xs font-semibold text-slate-400">anggota terdaftar</span>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-gray-800 flex items-center justify-between text-xs font-medium text-slate-500">
                <span>Seluruh akun</span>
                <span class="font-bold text-slate-700 dark:text-slate-300">Tim & Eksternal</span>
            </div>
        </div>

        {{-- Active Users --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 shadow-xs hover:shadow-md transition-all duration-200 p-5 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Akun Aktif</span>
                <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div>
                <p class="text-3xl font-black text-emerald-600 dark:text-emerald-400 tabular-nums">{{ $activeUsers }}</p>
                <span class="text-xs font-semibold text-slate-400">dapat mengakses sistem</span>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-gray-800 flex items-center justify-between text-xs font-medium text-slate-500">
                <span>Tingkat aktivitas</span>
                <span class="font-bold text-emerald-600">{{ $totalUsers > 0 ? round($activeUsers / $totalUsers * 100) : 0 }}% aktif</span>
            </div>
        </div>

        {{-- Inactive Users --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 shadow-xs hover:shadow-md transition-all duration-200 p-5 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Akun Nonaktif</span>
                <div class="w-9 h-9 rounded-xl bg-slate-50 dark:bg-gray-800 text-slate-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                </div>
            </div>
            <div>
                <p class="text-3xl font-black text-slate-500 dark:text-slate-400 tabular-nums">{{ $inactiveUsers }}</p>
                <span class="text-xs font-semibold text-slate-400">akses nonaktif</span>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-gray-800 flex items-center justify-between text-xs font-medium text-slate-500">
                <span>Status login</span>
                <span class="font-bold text-slate-400">Suspended</span>
            </div>
        </div>
    </div>

    {{-- ── Search & Filter Bar ─────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 p-4 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <form method="GET" action="{{ route('users.index') }}" class="flex flex-1 items-center gap-2.5 flex-wrap">
            <div class="relative w-full sm:w-72">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Cari nama, email, custom field..."
                       class="w-full pl-9 pr-3.5 py-2 text-xs sm:text-sm bg-slate-50 dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-xl text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            @if($isAdmin)
            <select name="role" onchange="this.form.submit()"
                    class="px-3.5 py-2 text-xs sm:text-sm bg-slate-50 dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-xl text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 cursor-pointer">
                <option value="">Semua Role</option>
                @foreach($roles as $role)
                    <option value="{{ $role->name }}" {{ request('role') === $role->name ? 'selected' : '' }}>
                        {{ \App\Support\RoleLabel::for($role->name) }}
                    </option>
                @endforeach
            </select>
            @endif

            @if(request('search') || request('role'))
            <a href="{{ route('users.index') }}"
               class="px-3 py-2 text-xs sm:text-sm font-semibold text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 transition">
                Reset
            </a>
            @endif
        </form>
    </div>

    {{-- ── Modern Users Table ──────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/80 dark:bg-gray-800/80 border-b border-slate-100 dark:border-gray-700 text-[11px] font-bold text-slate-400 dark:text-slate-400 uppercase tracking-wider">
                        <th class="px-5 py-3.5">Nama & Profil</th>
                        <th class="px-5 py-3.5">Email</th>
                        <th class="px-5 py-3.5">Role</th>
                        <th class="px-5 py-3.5">Proyek</th>
                        @if(session('active_package') === 'hris')
                        <th class="px-5 py-3.5">Level Struktural</th>
                        <th class="px-5 py-3.5">Departemen</th>
                        <th class="px-5 py-3.5">Tipe Karyawan</th>
                        @endif
                        @foreach($customFields as $cf)
                        <th class="px-5 py-3.5">{{ $cf->label }}</th>
                        @endforeach
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-5 py-3.5">Bergabung</th>
                        <th class="px-5 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-gray-800 text-slate-700 dark:text-slate-200">
                    @forelse($users as $u)
                    @php
                        $rc = [
                            'admin'     => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300',
                            'manager'   => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300',
                            'lead'      => 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-300',
                            'member'    => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-300',
                            'developer' => 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/40 dark:text-purple-300',
                            'client'    => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300',
                        ];
                    @endphp
                    <tr class="hover:bg-slate-50/80 dark:hover:bg-gray-800/50 transition-colors">
                        {{-- Nama & Avatar --}}
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-3">
                                @if($u->avatar)
                                    <img src="{{ Storage::url($u->avatar) }}" alt="{{ $u->name }}"
                                         class="w-9 h-9 rounded-xl object-cover ring-2 ring-slate-100 dark:ring-gray-700 shrink-0">
                                @else
                                    <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white font-bold text-xs shrink-0 shadow-2xs"
                                         style="background: linear-gradient(135deg, #3b82f6, #6366f1)">
                                        {{ strtoupper(substr($u->name, 0, 2)) }}
                                    </div>
                                @endif
                                <div>
                                    <div class="flex items-center gap-1.5">
                                        <p class="font-bold text-slate-900 dark:text-white leading-tight">{{ $u->name }}</p>
                                        @if($u->id === auth()->id())
                                            <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300">You</span>
                                        @endif
                                    </div>
                                    <span class="text-[10px] text-slate-400">ID: #{{ $u->id }}</span>
                                </div>
                            </div>
                        </td>

                        {{-- Email --}}
                        <td class="px-5 py-3.5 font-medium text-slate-600 dark:text-slate-300">
                            {{ $u->email }}
                        </td>

                        {{-- Role --}}
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-1 flex-wrap">
                                @foreach($u->getRoleNames() as $role)
                                    <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-semibold border {{ $rc[$role] ?? 'bg-slate-100 text-slate-700' }}">
                                        {{ \App\Support\RoleLabel::for($role) }}
                                    </span>
                                @endforeach
                            </div>
                        </td>

                        {{-- Proyek --}}
                        <td class="px-5 py-3.5">
                            @forelse($u->projects as $proj)
                                <a href="{{ route('projects.show', $proj) }}"
                                   class="inline-block px-2 py-0.5 mb-1 mr-1 rounded-md text-[11px] font-medium bg-slate-100 dark:bg-gray-800 text-slate-700 dark:text-slate-300 hover:bg-blue-50 hover:text-blue-700 transition">
                                    {{ $proj->name }}
                                </a>
                            @empty
                                <span class="text-slate-400 text-xs">—</span>
                            @endforelse
                        </td>

                        {{-- HRIS Columns --}}
                        @if(session('active_package') === 'hris')
                        <td class="px-5 py-3.5">
                            @if($u->structuralLevel)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 rounded-full font-medium text-[11px]">
                                    {{ $u->structuralLevel->name }}
                                </span>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5">
                            @if($u->organizationUnit)
                                <span class="text-slate-700 dark:text-slate-300 font-medium">{{ $u->organizationUnit->name }}</span>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="text-slate-700 dark:text-slate-300">{{ \App\Support\EmploymentType::displayFor($u->employment_type, $u->employment_type_other) }}</span>
                            @if($u->outsourcing_company_name)
                                <p class="text-[10px] text-slate-400 mt-0.5">{{ $u->outsourcing_company_name }}</p>
                            @endif
                            @if($u->contract_end_date)
                                @if($u->isContractExpired())
                                    <p class="text-[10px] text-rose-600 font-medium mt-0.5">Berakhir {{ $u->contract_end_date->format('d M Y') }}</p>
                                @elseif($u->isContractExpiringSoon())
                                    <p class="text-[10px] text-amber-600 font-medium mt-0.5">Sisa {{ $u->contractDaysRemaining() }} hari</p>
                                @endif
                            @endif
                        </td>
                        @endif

                        {{-- Custom Fields --}}
                        @foreach($customFields as $cf)
                        @php $cfVal = $u->custom_fields[$cf->key] ?? null; @endphp
                        <td class="px-5 py-3.5 text-slate-600 dark:text-slate-300">
                            @if($cf->type === 'checkbox')
                                <span class="{{ $cfVal ? 'text-emerald-600 font-bold' : 'text-slate-400' }}">{{ $cfVal ? 'Ya' : 'Tidak' }}</span>
                            @elseif($cfVal !== null && $cfVal !== '')
                                {{ $cfVal }}
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                        @endforeach

                        {{-- Status --}}
                        <td class="px-5 py-3.5">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border
                                {{ $u->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-slate-100 text-slate-500 border-slate-200 dark:bg-gray-800 dark:text-gray-400' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $u->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                {{ $u->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>

                        {{-- Bergabung --}}
                        <td class="px-5 py-3.5 text-slate-500 dark:text-slate-400">
                            {{ $u->created_at->format('d M Y') }}
                        </td>

                        {{-- Aksi --}}
                        <td class="px-5 py-3.5 text-right whitespace-nowrap">
                            @if($canCreate || $canUpdate || $canDelete)
                            <div class="flex items-center justify-end gap-2">
                                @if($canUpdate)
                                <a href="{{ route('users.edit', $u) }}"
                                   class="px-2.5 py-1 text-xs font-semibold text-blue-600 hover:text-blue-800 hover:bg-blue-50 dark:hover:bg-blue-950/40 rounded-lg transition">
                                    Edit
                                </a>
                                @endif
                                @can('view payroll')
                                @if(Route::has('hris.salary.index') && session('active_package') === 'hris')
                                <a href="{{ route('hris.salary.index', $u) }}"
                                   class="px-2.5 py-1 text-xs font-semibold text-slate-600 hover:text-slate-900 rounded-lg transition">
                                    Salary
                                </a>
                                @endif
                                @endcan
                                @if($canDelete && $u->id !== auth()->id())
                                <form method="POST" action="{{ route('users.destroy', $u) }}"
                                      data-confirm-delete="{{ $u->name }}" data-confirm-label="Delete User">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="px-2.5 py-1 text-xs font-semibold text-rose-600 hover:text-rose-800 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg transition">
                                        Delete
                                    </button>
                                </form>
                                @endif
                            </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ (session('active_package') === 'hris' ? 10 : 7) + $customFields->count() }}"
                            class="px-5 py-16 text-center">
                            <div class="w-16 h-16 rounded-2xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center mx-auto mb-3">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                            </div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Tidak ada pengguna ditemukan</h3>
                            <p class="text-xs text-slate-400 mt-1">Coba sesuaikan kata kunci pencarian atau filter role.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination & PerPage --}}
        <div class="px-5 py-3.5 border-t border-slate-100 dark:border-gray-800 flex items-center justify-between gap-3 flex-wrap">
            <x-per-page />
            @if($users->hasPages())
            {{ $users->links() }}
            @endif
        </div>
    </div>

    {{-- ── Modal Import Excel (Modern Dialog) ───────────────────────────────── --}}
    @can('import user')
    <div x-show="importOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
         style="display: none;">
        <div @click.outside="importOpen = false" x-show="importOpen" x-transition
             class="bg-white dark:bg-gray-850 rounded-2xl shadow-2xl border border-slate-200/90 dark:border-gray-700 w-full max-w-md overflow-hidden">
            <form method="POST" action="{{ route('users.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="px-6 py-4.5 border-b border-slate-100 dark:border-gray-700 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-slate-900 dark:text-white text-base">Import Data Karyawan</h3>
                        <p class="text-[11px] text-slate-400">Unggah file spreadsheet Excel / CSV</p>
                    </div>
                    <button type="button" @click="importOpen = false"
                            class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="p-6 space-y-4 text-xs">
                    <p class="text-slate-600 dark:text-slate-300 leading-relaxed">
                        Email yang sudah terdaftar akan diperbarui otomatis; baris dengan email baru akan didaftarkan sebagai akun baru.
                    </p>

                    <a href="{{ route('users.import.template') }}"
                       class="inline-flex items-center gap-1.5 font-bold text-blue-600 hover:text-blue-800 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Unduh Template Spreadsheet (.xlsx)
                    </a>

                    <div>
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                               class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900/40 dark:file:text-blue-300">
                        @error('file') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="pt-2">
                        <label class="flex items-start gap-2.5 cursor-pointer">
                            <input type="checkbox" name="send_verification" value="1"
                                   class="w-4 h-4 mt-0.5 text-blue-600 rounded border-slate-300 focus:ring-blue-500">
                            <div>
                                <span class="font-semibold text-slate-700 dark:text-slate-200">Kirim email verifikasi ke user baru</span>
                                <p class="text-[11px] text-slate-400 mt-0.5">Jika aktif, user baru harus klik tautan verifikasi sebelum login.</p>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-slate-100 dark:border-gray-700 flex justify-end gap-2.5 bg-slate-50/50 dark:bg-gray-800/50">
                    <button type="button" @click="importOpen = false"
                            class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800 dark:text-slate-300 transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 rounded-xl transition shadow-xs">
                        Mulai Import
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endcan

</div>
@endsection
