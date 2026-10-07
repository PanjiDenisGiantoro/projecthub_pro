@extends('layouts.app')

@section('title', 'Invoice & Billing')
@section('page-title', 'Invoice & Billing')

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
                    Financial & Client Billing
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                    Invoice & Penagihan
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-2 flex-wrap">
                    <span>Pantau arus kas, status invoice klien, dan pembayaran termin proyek secara transparan.</span>
                </p>
            </div>

            @if(auth()->user()->hasRole(['admin','member']))
            <div class="shrink-0">
                <a href="{{ route('invoices.create') }}"
                   class="inline-flex items-center gap-2 px-4.5 py-2.5 text-xs sm:text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 rounded-xl transition-all shadow-sm shadow-blue-600/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    Buat Invoice Baru
                </a>
            </div>
            @endif
        </div>
    </div>

    {{-- ── 4 Financial KPI Cards ───────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4.5">
        {{-- Total Billed --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 shadow-xs hover:shadow-md transition-all duration-200 p-5 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Ditagihkan</span>
                <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>
            <div>
                <p class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tabular-nums">
                    Rp {{ number_format($stats['total_amount'], 0, ',', '.') }}
                </p>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-gray-800/80 flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
                <span>Total tagihan diterbitkan</span>
                <span class="font-bold text-slate-800 dark:text-slate-200">{{ $stats['total_count'] }} invoice</span>
            </div>
        </div>

        {{-- Total Paid --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 shadow-xs hover:shadow-md transition-all duration-200 p-5 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Sudah Lunas</span>
                <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div>
                <p class="text-2xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400 tabular-nums">
                    Rp {{ number_format($stats['paid_amount'], 0, ',', '.') }}
                </p>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-gray-800/80 flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
                <span class="text-emerald-600 font-semibold">{{ $stats['paid_count'] }} invoice lunas</span>
                <span>{{ $stats['total_amount'] > 0 ? round($stats['paid_amount'] / $stats['total_amount'] * 100) : 0 }}% rasio</span>
            </div>
        </div>

        {{-- Pending / Sent --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 shadow-xs hover:shadow-md transition-all duration-200 p-5 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Menunggu Pembayaran</span>
                <div class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div>
                <p class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tabular-nums">
                    Rp {{ number_format($stats['pending_amount'], 0, ',', '.') }}
                </p>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-gray-800/80 flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
                <span>Tagihan berjalan</span>
                <span class="font-bold text-amber-600 dark:text-amber-400">{{ $stats['pending_count'] }} pending</span>
            </div>
        </div>

        {{-- Overdue --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 shadow-xs hover:shadow-md transition-all duration-200 p-5 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Jatuh Tempo (Overdue)</span>
                <div class="w-9 h-9 rounded-xl {{ $stats['overdue_count'] > 0 ? 'bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400' : 'bg-slate-50 text-slate-400' }} flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
            <div>
                <p class="text-2xl sm:text-3xl font-black {{ $stats['overdue_count'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-white' }} tabular-nums">
                    Rp {{ number_format($stats['overdue_amount'], 0, ',', '.') }}
                </p>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-gray-800/80 flex items-center justify-between text-xs font-medium">
                <span class="text-slate-500 dark:text-slate-400">Status penagihan</span>
                <span class="font-bold {{ $stats['overdue_count'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                    {{ $stats['overdue_count'] > 0 ? $stats['overdue_count'] . ' invoice telat' : 'Semua aman' }}
                </span>
            </div>
        </div>
    </div>

    {{-- ── Filters & Search Bar ─────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 p-4 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        {{-- Status Pills --}}
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0 text-xs font-semibold">
            <a href="{{ route('invoices.index', array_merge(request()->except('status'), ['status' => ''])) }}"
               class="px-3 py-1.5 rounded-xl transition-all shrink-0 {{ !request('status') ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-gray-800' }}">
                Semua
            </a>
            @foreach(['draft' => 'Draft', 'sent' => 'Terkirim', 'paid' => 'Lunas', 'overdue' => 'Jatuh Tempo', 'cancelled' => 'Dibatalkan'] as $key => $lbl)
            <a href="{{ route('invoices.index', array_merge(request()->except('status'), ['status' => $key])) }}"
               class="px-3 py-1.5 rounded-xl transition-all shrink-0 {{ request('status') === $key ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-gray-800' }}">
                {{ $lbl }}
            </a>
            @endforeach
        </div>

        {{-- Search Input Form --}}
        <form method="GET" action="{{ route('invoices.index') }}" class="relative shrink-0">
            @if(request('status'))
            <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Cari no. invoice, klien, proyek..."
                   class="w-full sm:w-72 pl-9 pr-3.5 py-2 text-xs sm:text-sm bg-slate-50 dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-xl text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </form>
    </div>

    {{-- ── Invoices Modern Table ───────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/80 dark:bg-gray-800/80 border-b border-slate-100 dark:border-gray-700 text-[11px] font-bold text-slate-400 dark:text-slate-400 uppercase tracking-wider">
                        <th class="px-5 py-3.5">No. Invoice</th>
                        <th class="px-5 py-3.5">Klien / Pembayar</th>
                        <th class="px-5 py-3.5">Proyek Terkait</th>
                        <th class="px-5 py-3.5">Total Tagihan</th>
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-5 py-3.5">Jatuh Tempo</th>
                        <th class="px-5 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-gray-800 text-slate-700 dark:text-slate-200">
                    @forelse($invoices as $inv)
                    @php
                        $statusMap = [
                            'draft'     => ['bg' => 'bg-slate-100 text-slate-700 dark:bg-gray-800 dark:text-slate-300 border-slate-200', 'dot' => 'bg-slate-400',   'label' => 'Draft'],
                            'sent'      => ['bg' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-300',     'dot' => 'bg-blue-500',    'label' => 'Terkirim'],
                            'paid'      => ['bg' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300', 'dot' => 'bg-emerald-500', 'label' => 'Lunas'],
                            'overdue'   => ['bg' => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300',     'dot' => 'bg-rose-500',    'label' => 'Jatuh Tempo'],
                            'cancelled' => ['bg' => 'bg-gray-100 text-gray-500 border-gray-200 dark:bg-gray-800 dark:text-gray-400',       'dot' => 'bg-gray-400',    'label' => 'Batal'],
                        ];
                        $st = $statusMap[$inv->status] ?? $statusMap['draft'];
                        $isOverdue = $inv->status === 'overdue' || ($inv->status !== 'paid' && $inv->due_date && $inv->due_date->isPast());
                    @endphp
                    <tr class="hover:bg-slate-50/80 dark:hover:bg-gray-800/50 transition-colors">
                        {{-- No Invoice --}}
                        <td class="px-5 py-3.5 font-mono font-bold text-slate-900 dark:text-white">
                            <a href="{{ route('invoices.show', $inv) }}" class="hover:text-blue-600 transition flex items-center gap-1.5">
                                <span>{{ $inv->invoice_number }}</span>
                            </a>
                        </td>

                        {{-- Klien --}}
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-2.5">
                                @if($inv->client && $inv->client->avatar)
                                    <img src="{{ Storage::url($inv->client->avatar) }}" alt="{{ $inv->client->name }}"
                                         class="w-7 h-7 rounded-lg object-cover ring-1 ring-slate-200 dark:ring-gray-700">
                                @else
                                    <div class="w-7 h-7 rounded-lg flex items-center justify-center text-white font-bold text-[10px]"
                                         style="background: linear-gradient(135deg, #3b82f6, #6366f1)">
                                        {{ strtoupper(substr($inv->client->name ?? 'C', 0, 2)) }}
                                    </div>
                                @endif
                                <div>
                                    <p class="font-bold text-slate-900 dark:text-white leading-tight">{{ $inv->client->name ?? '—' }}</p>
                                    <p class="text-[10px] text-slate-400">{{ $inv->client->email ?? '' }}</p>
                                </div>
                            </div>
                        </td>

                        {{-- Proyek --}}
                        <td class="px-5 py-3.5">
                            @if($inv->project)
                                <a href="{{ route('projects.show', $inv->project) }}"
                                   class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-slate-100 dark:bg-gray-800 text-slate-700 dark:text-slate-300 hover:bg-blue-50 hover:text-blue-600 transition">
                                    {{ $inv->project->name }}
                                </a>
                            @else
                                <span class="inline-block px-2 py-0.5 rounded text-[10px] bg-slate-50 dark:bg-gray-800 text-slate-400">
                                    Internal
                                </span>
                            @endif
                        </td>

                        {{-- Total --}}
                        <td class="px-5 py-3.5">
                            <span class="font-extrabold text-sm text-slate-900 dark:text-white tabular-nums">
                                Rp {{ number_format($inv->total, 0, ',', '.') }}
                            </span>
                        </td>

                        {{-- Status --}}
                        <td class="px-5 py-3.5">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border {{ $st['bg'] }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $st['dot'] }}"></span>
                                {{ $st['label'] }}
                            </span>
                        </td>

                        {{-- Jatuh Tempo --}}
                        <td class="px-5 py-3.5">
                            <div class="flex flex-col">
                                <span class="font-semibold {{ $isOverdue ? 'text-rose-600 dark:text-rose-400 font-bold' : 'text-slate-600 dark:text-slate-300' }}">
                                    {{ $inv->due_date ? $inv->due_date->format('d M Y') : '—' }}
                                </span>
                                @if($isOverdue)
                                <span class="text-[10px] text-rose-500 font-medium">Lewat deadline</span>
                                @endif
                            </div>
                        </td>

                        {{-- Aksi --}}
                        <td class="px-5 py-3.5 text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-1.5">
                                <a href="{{ route('invoices.show', $inv) }}"
                                   class="px-2.5 py-1 text-xs font-semibold text-blue-600 hover:text-blue-800 hover:bg-blue-50 dark:hover:bg-blue-950/40 rounded-lg transition">
                                    Detail
                                </a>
                                <a href="{{ route('invoices.pdf', $inv) }}"
                                   title="Unduh PDF"
                                   class="p-1 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-gray-800 rounded-lg transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-5 py-16 text-center">
                            <div class="w-16 h-16 rounded-2xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center mx-auto mb-3">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Belum ada invoice</h3>
                            <p class="text-xs text-slate-400 mt-1">Buat invoice baru untuk memulai penagihan klien Anda.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination & PerPage --}}
        <div class="px-5 py-3.5 border-t border-slate-100 dark:border-gray-800 flex items-center justify-between gap-3 flex-wrap">
            <x-per-page />
            @if($invoices->hasPages())
            {{ $invoices->links() }}
            @endif
        </div>
    </div>

</div>
@endsection
