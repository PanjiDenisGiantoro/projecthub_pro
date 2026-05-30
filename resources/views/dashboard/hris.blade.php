@extends('layouts.app')
@section('title', 'Dashboard HRIS')
@section('page-title', 'Dashboard HRIS')

@section('content')
<div class="space-y-6 pt-5">

    {{-- Greeting --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">
                Selamat datang, {{ auth()->user()->name }} 👋
            </h1>
            <p class="text-sm text-gray-500 mt-0.5">
                {{ \Carbon\Carbon::now()->locale('id')->isoFormat('dddd, D MMMM Y') }}
                &mdash; Human Resource Information System
            </p>
        </div>
        @can('access users')
        <a href="{{ route('users.index') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white rounded-xl shrink-0 transition-all hover:-translate-y-0.5"
           style="background:linear-gradient(135deg,#7c3aed,#6d28d9);box-shadow:0 4px 14px rgba(124,58,237,0.35)">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            Data Karyawan
        </a>
        @endcan
    </div>

    {{-- Stat Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        {{-- Total Karyawan --}}
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-violet-100">
                    <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ number_format($totalKaryawan) }}</p>
            <p class="text-xs text-gray-500 mt-0.5">Total Karyawan</p>
        </div>

        {{-- Total Departemen --}}
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-blue-100">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ number_format($totalDept) }}</p>
            <p class="text-xs text-gray-500 mt-0.5">Departemen</p>
        </div>

        {{-- Absensi Hari Ini --}}
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm opacity-60">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-emerald-100">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-[10px] font-semibold bg-violet-100 text-violet-600 px-2 py-0.5 rounded-full">Soon</span>
            </div>
            <p class="text-2xl font-bold text-gray-400">—</p>
            <p class="text-xs text-gray-400 mt-0.5">Hadir Hari Ini</p>
        </div>

        {{-- Cuti Pending --}}
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm opacity-60">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-amber-100">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <span class="text-[10px] font-semibold bg-violet-100 text-violet-600 px-2 py-0.5 rounded-full">Soon</span>
            </div>
            <p class="text-2xl font-bold text-gray-400">—</p>
            <p class="text-xs text-gray-400 mt-0.5">Pengajuan Cuti</p>
        </div>
    </div>

    {{-- Modul Core HRIS --}}
    <div>
        <h2 class="text-sm font-semibold text-gray-700 mb-3">Modul Core HRIS</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            @php
            $modules = [
                ['label' => 'Absensi',       'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',                                                                                                              'color' => 'emerald'],
                ['label' => 'Penggajian',    'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z', 'color' => 'blue'],
                ['label' => 'Pajak PPh 21',  'icon' => 'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z',                                                        'color' => 'orange'],
                ['label' => 'Cuti & Izin',   'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',                                                                 'color' => 'amber'],
                ['label' => 'Lembur',        'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',                                                                                                              'color' => 'red'],
                ['label' => 'Reimburse',     'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',         'color' => 'teal'],
            ];
            $colorMap = [
                'emerald' => ['bg' => 'bg-emerald-50', 'icon' => 'text-emerald-500', 'border' => 'border-emerald-100'],
                'blue'    => ['bg' => 'bg-blue-50',    'icon' => 'text-blue-500',    'border' => 'border-blue-100'],
                'orange'  => ['bg' => 'bg-orange-50',  'icon' => 'text-orange-500',  'border' => 'border-orange-100'],
                'amber'   => ['bg' => 'bg-amber-50',   'icon' => 'text-amber-500',   'border' => 'border-amber-100'],
                'red'     => ['bg' => 'bg-red-50',     'icon' => 'text-red-500',     'border' => 'border-red-100'],
                'teal'    => ['bg' => 'bg-teal-50',    'icon' => 'text-teal-500',    'border' => 'border-teal-100'],
            ];
            @endphp

            @foreach($modules as $mod)
            @php $c = $colorMap[$mod['color']]; @endphp
            <div class="bg-white rounded-2xl border {{ $c['border'] }} p-4 flex flex-col items-center gap-2 text-center opacity-70 cursor-not-allowed select-none">
                <div class="w-11 h-11 rounded-xl {{ $c['bg'] }} flex items-center justify-center">
                    <svg class="w-5 h-5 {{ $c['icon'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $mod['icon'] }}"/>
                    </svg>
                </div>
                <p class="text-xs font-semibold text-gray-700 leading-tight">{{ $mod['label'] }}</p>
                <span class="text-[10px] font-bold bg-violet-100 text-violet-600 px-2 py-0.5 rounded-full">Coming Soon</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Menu Pendukung --}}
    <div>
        <h2 class="text-sm font-semibold text-gray-700 mb-3">Menu Pendukung</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

            {{-- Data Karyawan --}}
            @can('access users')
            <a href="{{ route('users.index') }}"
               class="group bg-white rounded-2xl border border-violet-100 p-5 flex items-center gap-4 hover:border-violet-300 hover:shadow-md transition-all">
                <div class="w-12 h-12 rounded-xl bg-violet-100 flex items-center justify-center shrink-0 group-hover:bg-violet-200 transition-colors">
                    <svg class="w-6 h-6 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900 group-hover:text-violet-700 transition-colors">Data Karyawan</p>
                    <p class="text-xs text-gray-500 mt-0.5">Kelola data, role, dan akses karyawan</p>
                </div>
                <svg class="w-4 h-4 text-gray-300 group-hover:text-violet-400 ml-auto shrink-0 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
            @endcan

            {{-- Struktur Organisasi --}}
            @can('manage master data')
            <a href="{{ route('master.index') }}"
               class="group bg-white rounded-2xl border border-blue-100 p-5 flex items-center gap-4 hover:border-blue-300 hover:shadow-md transition-all">
                <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center shrink-0 group-hover:bg-blue-200 transition-colors">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900 group-hover:text-blue-700 transition-colors">Struktur Organisasi</p>
                    <p class="text-xs text-gray-500 mt-0.5">Kelola cabang, divisi, dan departemen</p>
                </div>
                <svg class="w-4 h-4 text-gray-300 group-hover:text-blue-400 ml-auto shrink-0 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
            @endcan

            {{-- Level Struktural --}}
            @can('manage master data')
            <a href="{{ route('structural-levels.index') }}"
               class="group bg-white rounded-2xl border border-indigo-100 p-5 flex items-center gap-4 hover:border-indigo-300 hover:shadow-md transition-all">
                <div class="w-12 h-12 rounded-xl bg-indigo-100 flex items-center justify-center shrink-0 group-hover:bg-indigo-200 transition-colors">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900 group-hover:text-indigo-700 transition-colors">Level Struktural</p>
                    <p class="text-xs text-gray-500 mt-0.5">Jabatan dan hierarki organisasi</p>
                </div>
                <svg class="w-4 h-4 text-gray-300 group-hover:text-indigo-400 ml-auto shrink-0 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
            @endcan

        </div>
    </div>

</div>
@endsection
