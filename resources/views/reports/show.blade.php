@extends('layouts.app')

@section('title', $meta['label'] . ' — Laporan')
@section('page-title', 'Laporan & Rekapitulasi Data')

@section('content')
<div class="space-y-6 pt-5 pb-8" x-data="{ tableSearch: '', filterOpen: true }">

    {{-- ── Hero Banner ──────────────────────────────────────────────────────── --}}
    <div class="relative overflow-hidden rounded-2xl border border-slate-200/80 dark:border-gray-700/80 bg-gradient-to-r from-blue-50/80 via-indigo-50/50 to-cyan-50/60 dark:from-gray-850 dark:via-gray-850 dark:to-gray-800 px-6 py-6 sm:px-7 shadow-xs">
        <img src="{{ asset('flovig_icon.png') }}" alt=""
             class="pointer-events-none select-none absolute -right-6 -bottom-8 w-44 h-auto opacity-[0.05] dark:opacity-[0.03]">
        <div class="relative flex flex-col sm:flex-row sm:items-center justify-between gap-5">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                    Laporan & Ekspor Data
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-2 flex-wrap">
                    <span>Eksplorasi data, filter kustom, dan unduh laporan resmi dalam format PDF & Excel.</span>
                </p>
            </div>

            {{-- Quick Export Buttons --}}
            <div class="flex items-center gap-2.5 shrink-0">
                <a href="{{ route('reports.export', array_merge(['key' => $key, 'format' => 'pdf'], request()->except(['submitted']))) }}"
                   class="inline-flex items-center gap-2 px-3.5 py-2 text-xs sm:text-sm font-semibold text-rose-700 dark:text-rose-300 bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 dark:hover:bg-rose-900/60 border border-rose-200 dark:border-rose-900 rounded-xl transition-colors shadow-xs">
                    <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    Unduh PDF
                </a>
                <a href="{{ route('reports.export', array_merge(['key' => $key, 'format' => 'xlsx'], request()->except(['submitted']))) }}"
                   class="inline-flex items-center gap-2 px-3.5 py-2 text-xs sm:text-sm font-semibold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/40 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 border border-emerald-200 dark:border-emerald-900 rounded-xl transition-colors shadow-xs">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                    </svg>
                    Unduh Excel
                </a>
            </div>
        </div>
    </div>

    {{-- ── 2-Column Seamless Workspace Layout (Sidebar + Report Content) ───── --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 items-start">

        {{-- ── Left Sidebar (List of Reports by Category) ──────────────────── --}}
        <div class="lg:col-span-1 bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 p-4 shadow-xs sticky top-20">
            <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-100 dark:border-gray-700">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Katalog Laporan</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-600 dark:bg-blue-900/40 dark:text-blue-300">
                    {{ $reports->flatten(1)->count() }} Modul
                </span>
            </div>

            <div class="space-y-4 max-h-[calc(100vh-14rem)] overflow-y-auto pr-1">
                @foreach($reports as $category => $items)
                <div>
                    <h3 class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2 px-2 flex items-center gap-1.5">
                        <span>{{ $category }}</span>
                    </h3>
                    <div class="space-y-1">
                        @foreach($items as $reportKey => $report)
                        @php $isActive = ($key === $reportKey); @endphp
                        <a href="{{ route('reports.show', $reportKey) }}"
                           class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold transition-all {{ $isActive ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-gray-800' }}">
                            <div class="flex items-center gap-2.5 truncate">
                                <span class="w-2 h-2 rounded-full shrink-0 {{ $isActive ? 'bg-white' : 'bg-slate-300 dark:bg-gray-600' }}"></span>
                                <span class="truncate">{{ $report['label'] }}</span>
                            </div>
                            @if($isActive)
                            <svg class="w-3.5 h-3.5 shrink-0 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                            </svg>
                            @endif
                        </a>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- ── Right Main Workspace (Active Report Filters & Data Table) ───── --}}
        <div class="lg:col-span-3 space-y-5">

            {{-- Active Report Header & Filter Card --}}
            <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 p-5 shadow-xs">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300">
                                {{ $meta['category'] ?? 'Laporan' }}
                            </span>
                            <span class="text-xs text-slate-400">&bull;</span>
                            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">
                                {{ $rows->count() }} baris data ditemukan
                            </span>
                        </div>
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white">{{ $meta['label'] }}</h2>
                        <p class="text-xs text-slate-400 dark:text-slate-400 mt-0.5">{{ $meta['description'] ?? '' }}</p>
                    </div>

                    @if(!empty($filterDefs))
                    <button type="button" @click="filterOpen = !filterOpen"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-xl border border-slate-200 dark:border-gray-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-gray-800 transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                        <span x-text="filterOpen ? 'Sembunyikan Filter' : 'Filter Data'"></span>
                    </button>
                    @endif
                </div>

                {{-- Filter Form --}}
                @if(!empty($filterDefs))
                <form method="GET" action="{{ route('reports.show', $key) }}" x-show="filterOpen" x-collapse
                      class="pt-3 border-t border-slate-100 dark:border-gray-800">
                    <input type="hidden" name="submitted" value="1">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5">
                        @foreach($filterDefs as $field => $def)
                            @if($def['type'] === 'select')
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1.5">{{ $def['label'] }}</label>
                                <select name="{{ $field }}"
                                        class="w-full px-3.5 py-2 text-xs sm:text-sm bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-xl text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 cursor-pointer shadow-2xs">
                                    <option value="">Semua</option>
                                    @foreach($def['options'] as $value => $label)
                                    <option value="{{ $value }}" {{ (string) ($filters[$field] ?? '') === (string) $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            @elseif($def['type'] === 'date_range')
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1.5">{{ $def['label'] }} (Dari)</label>
                                <input type="date" name="{{ $field }}_from" value="{{ $filters[$field.'_from'] ?? '' }}"
                                       class="w-full px-3.5 py-2 text-xs sm:text-sm bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-xl text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-2xs">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1.5">{{ $def['label'] }} (Sampai)</label>
                                <input type="date" name="{{ $field }}_to" value="{{ $filters[$field.'_to'] ?? '' }}"
                                       class="w-full px-3.5 py-2 text-xs sm:text-sm bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-xl text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-2xs">
                            </div>
                            @endif
                        @endforeach
                    </div>

                    <div class="mt-4 flex items-center gap-2.5">
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-4 py-2 text-xs sm:text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-colors shadow-xs">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            Terapkan Filter
                        </button>
                        <a href="{{ route('reports.show', $key) }}"
                           class="px-3.5 py-2 text-xs sm:text-sm font-semibold text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 transition">
                            Reset
                        </a>
                    </div>
                </form>
                @endif
            </div>

            {{-- Modern Data Table Container --}}
            <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 shadow-xs overflow-hidden">

                {{-- Table Subheader / Search Filter --}}
                <div class="px-5 py-3.5 border-b border-slate-100 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50 dark:bg-gray-800/50">
                    <div class="relative w-full sm:w-64">
                        <input type="text" x-model="tableSearch"
                               placeholder="Cari dalam hasil..."
                               class="w-full pl-8 pr-3 py-1.5 text-xs bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-lg text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                        <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    <div class="flex items-center gap-2 text-xs text-slate-400">
                        <span>Menampilkan <strong class="text-slate-700 dark:text-slate-200 font-bold tabular-nums">{{ $rows->count() }}</strong> data</span>
                    </div>
                </div>

                @if($rows->isEmpty())
                <div class="py-16 text-center">
                    <div class="w-16 h-16 rounded-2xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Tidak ada data untuk filter ini</h3>
                    <p class="text-xs text-slate-400 mt-1">Coba sesuaikan tanggal atau pilihan filter di atas.</p>
                </div>
                @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50/80 dark:bg-gray-800/80 border-b border-slate-100 dark:border-gray-700 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                @foreach($columns as $label)
                                <th class="px-4 py-3.5 whitespace-nowrap">{{ $label }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-gray-800 text-slate-700 dark:text-slate-200">
                            @foreach($rows as $row)
                            @php
                                $rowJson = json_encode(array_values((array) $row));
                            @endphp
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-gray-800/50 transition-colors"
                                x-show="!tableSearch || {{ $rowJson }}.join(' ').toLowerCase().includes(tableSearch.toLowerCase())">
                                @foreach(array_keys($columns) as $field)
                                @php
                                    $val = $row[$field] ?? '-';
                                    $isStatus = in_array(strtolower($field), ['status', 'prioritas', 'priority', 'status_approval']);
                                @endphp
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if($isStatus && is_string($val))
                                        @php
                                            $st = strtolower($val);
                                            $statusColors = 'bg-slate-100 text-slate-700 dark:bg-gray-800 dark:text-slate-300';
                                            if (in_array($st, ['active', 'aktif', 'done', 'selesai', 'completed', 'paid', 'lunas', 'disetujui', 'approved'])) {
                                                $statusColors = 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300';
                                            } elseif (in_array($st, ['in_progress', 'progress', 'pending', 'waiting_approval', 'sent'])) {
                                                $statusColors = 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-300';
                                            } elseif (in_array($st, ['overdue', 'cancelled', 'batal', 'rejected', 'ditolak', 'critical', 'urgent', 'blocked'])) {
                                                $statusColors = 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300';
                                            } elseif (in_array($st, ['on_hold', 'draft', 'review', 'high', 'medium'])) {
                                                $statusColors = 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300';
                                            }
                                        @endphp
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-semibold border {{ $statusColors }}">
                                            <span class="w-1.5 h-1.5 rounded-full {{ str_contains($statusColors, 'emerald') ? 'bg-emerald-500' : (str_contains($statusColors, 'rose') ? 'bg-rose-500' : (str_contains($statusColors, 'blue') ? 'bg-blue-500' : 'bg-amber-500')) }}"></span>
                                            {{ ucfirst(str_replace('_', ' ', $val)) }}
                                        </span>
                                    @else
                                        <span class="font-medium {{ $loop->first ? 'text-slate-900 dark:text-white font-bold' : '' }}">
                                            {{ $val }}
                                        </span>
                                    @endif
                                </td>
                                @endforeach
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>

        </div>

    </div>

</div>
@endsection
