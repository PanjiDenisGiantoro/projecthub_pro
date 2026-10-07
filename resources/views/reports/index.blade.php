@extends('layouts.app')

@section('title', 'Katalog Laporan')
@section('page-title', 'Katalog Laporan')

@section('content')
<div class="space-y-6 pt-5 pb-8">

    {{-- ── Hero Banner ──────────────────────────────────────────────────────── --}}
    <div class="relative overflow-hidden rounded-2xl border border-slate-200/80 dark:border-gray-700/80 bg-gradient-to-r from-blue-50/80 via-indigo-50/50 to-cyan-50/60 dark:from-gray-850 dark:via-gray-850 dark:to-gray-800 px-6 py-6 sm:px-7 shadow-xs">
        <img src="{{ asset('flovig_icon.png') }}" alt=""
             class="pointer-events-none select-none absolute -right-6 -bottom-8 w-44 h-auto opacity-[0.05] dark:opacity-[0.03]">
        <div>
            <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-blue-100/70 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-xs font-semibold mb-2">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-600 dark:bg-blue-400 animate-pulse"></span>
                Katalog Rekapitulasi Data
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                Pusat Laporan
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                Pilih modul laporan di bawah untuk melihat rekapitulasi data dan mengunduh format PDF/Excel.
            </p>
        </div>
    </div>

    {{-- ── Report Categories ────────────────────────────────────────────────── --}}
    <div class="space-y-6">
        @foreach($reports as $category => $items)
        <div>
            <div class="flex items-center gap-2 mb-3">
                <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ $category }}</h3>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($items as $key => $report)
                <a href="{{ route('reports.show', $key) }}"
                   class="group bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 p-5 shadow-xs hover:shadow-md hover:border-blue-400 dark:hover:border-blue-500 transition-all flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-sm font-bold text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                {{ $report['label'] }}
                            </h4>
                            <div class="w-7 h-7 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>
                        </div>
                        <p class="text-xs text-slate-400 line-clamp-2">{{ $report['description'] }}</p>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 dark:border-gray-800 flex items-center justify-between text-[11px] text-blue-600 dark:text-blue-400 font-semibold">
                        <span>Buka Laporan</span>
                        <span>&rarr;</span>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>

</div>
@endsection
