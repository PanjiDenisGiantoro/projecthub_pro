@extends('layouts.app')

@section('title', 'Dashboard Baru')
@section('page-title', 'Dashboard Baru')

@section('content')
@php
    $tabs = [
        'upcoming' => ['label' => 'Belum Mulai', 'count' => $stats['todo']],
        'ongoing'  => ['label' => 'Berjalan',    'count' => $stats['in_progress'] + $stats['review']],
        'complete' => ['label' => 'Selesai',     'count' => $stats['done_tasks']],
    ];
    $pendingToday = $todaySchedule->filter(fn($m) => $m['startsAt'] && $m['startsAt']->isFuture())->count();
    $maxHours = max(1, $weeklyHours->max('hours'));
    $meetingProgress = $meetingStats['total'] > 0 ? round($meetingStats['past'] / $meetingStats['total'] * 100) : 0;

    $progressFor = function ($task) {
        return match ($task->status) {
            'todo' => 8, 'in_progress' => 55, 'review' => 80, 'done' => 100, default => 0,
        };
    };
    $dueLabel = function ($task) {
        if ($task->status === 'done') return ['text' => 'Selesai', 'cls' => 'text-emerald-600 bg-emerald-50'];
        $d = $task->daysRemaining();
        if ($d === null) return ['text' => 'Tanpa tenggat', 'cls' => 'text-slate-500 bg-slate-100'];
        if ($d < 0) return ['text' => 'Terlambat ' . abs($d) . ' hari', 'cls' => 'text-red-600 bg-red-50'];
        if ($d === 0) return ['text' => 'Jatuh tempo hari ini', 'cls' => 'text-amber-600 bg-amber-50'];
        return ['text' => 'Jatuh tempo ' . $d . ' hari lagi', 'cls' => 'text-amber-600 bg-amber-50'];
    };
    $dayLabels = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
@endphp

<div class="space-y-6 pt-5 pb-4" x-data="{ tab: 'ongoing' }" style="font-family:'Plus Jakarta Sans',Inter,sans-serif">

    <div class="flex items-center justify-between gap-3 flex-wrap">
        <p class="text-xs font-semibold text-orange-600 bg-orange-50 border border-orange-100 rounded-full px-3 py-1.5 inline-flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            Preview desain dashboard baru
        </p>
        <a href="{{ route('dashboard') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-700 inline-flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali ke dashboard lama
        </a>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">

        {{-- ═══════════ Kolom Kiri (2/3) ═══════════ --}}
        <div class="xl:col-span-2 space-y-6">

            {{-- ── Hero ─────────────────────────────────────────────────── --}}
            <div class="relative overflow-hidden rounded-3xl"
                 style="background:linear-gradient(120deg,#fff7ed 0%,#ffedd5 55%,#fed7aa 100%)">
                <div class="relative grid grid-cols-1 sm:grid-cols-3 gap-6 p-6 sm:p-7">
                    <div class="sm:col-span-2 flex flex-col justify-center">
                        @if(isset($companies) && $companies->isNotEmpty())
                        <form method="GET" action="{{ route('dashboard.v2') }}" class="mb-3">
                            <select name="company_id" onchange="this.form.submit()"
                                    class="text-xs font-semibold text-slate-700 bg-white/80 border border-orange-200 rounded-lg px-3 py-1.5 focus:outline-none cursor-pointer">
                                <option value="" {{ empty($selectedCompanyId) ? 'selected' : '' }}>Semua Company</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}" {{ (int) $selectedCompanyId === $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                                @endforeach
                            </select>
                        </form>
                        @endif
                        <h1 class="text-2xl sm:text-[28px] font-extrabold text-slate-900 tracking-tight leading-tight">
                            Atur Setiap Meeting<br class="hidden sm:block"> Lebih Cepat
                        </h1>
                        <p class="text-sm text-slate-600 mt-2 flex items-center gap-1.5">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Anda punya <strong class="text-slate-900">{{ $pendingToday }} jadwal</strong> yang menunggu hari ini
                        </p>
                        <a href="{{ route('meetings.index') }}"
                           class="mt-5 inline-flex items-center gap-2 w-fit px-5 py-2.5 rounded-xl text-sm font-semibold text-white shadow-lg shadow-orange-500/25 transition hover:-translate-y-0.5"
                           style="background:linear-gradient(135deg,#f97316,#ea580c)">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Meeting Baru
                        </a>
                    </div>
                    <div class="flex sm:justify-end items-end">
                        <div class="flex items-center gap-3 bg-white/70 backdrop-blur rounded-2xl px-4 py-3.5 border border-white/60 shadow-sm w-full sm:w-auto">
                            @if(auth()->user()->avatar)
                                <img src="{{ Storage::url(auth()->user()->avatar) }}" class="w-14 h-14 rounded-full object-cover ring-4 ring-white shrink-0" alt="{{ auth()->user()->name }}">
                            @else
                                <div class="w-14 h-14 rounded-full flex items-center justify-center text-white font-bold text-lg ring-4 ring-white shrink-0" style="background:linear-gradient(135deg,#f97316,#ea580c)">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                                </div>
                            @endif
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-slate-900 truncate">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-orange-600 font-medium capitalize truncate">{{ auth()->user()->getRoleNames()->first() }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Performance Metrics ──────────────────────────────────── --}}
            <div>
                <h2 class="text-sm font-bold text-slate-800 mb-3">Metrik Performa</h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:#fff7ed">
                                <svg class="w-4.5 h-4.5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            </div>
                        </div>
                        <p class="text-2xl font-extrabold text-slate-900">{{ $stats['completion_rate'] }}%</p>
                        <p class="text-xs text-slate-500 mt-0.5">Tingkat Penyelesaian Task</p>
                    </div>
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:#fff7ed">
                                <svg class="w-4.5 h-4.5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>
                        <p class="text-2xl font-extrabold text-slate-900">{{ $stats['done_this_week'] }}</p>
                        <p class="text-xs text-slate-500 mt-0.5">Task Selesai Minggu Ini</p>
                    </div>
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:#fff7ed">
                                <svg class="w-4.5 h-4.5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                        </div>
                        <p class="text-2xl font-extrabold text-slate-900">{{ $stats['collaborations'] }}</p>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $isManager ? 'Kolaborasi Tim' : 'Proyek Dikerjakan' }}</p>
                    </div>
                </div>
            </div>

            {{-- ── Jadwal Hari Ini + Ringkasan Meeting ──────────────────── --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                    <h3 class="text-sm font-bold text-slate-800 mb-4">Jadwal Hari Ini</h3>
                    @if($todaySchedule->isEmpty())
                        <p class="text-xs text-slate-400 py-6 text-center">Tidak ada jadwal meeting hari ini.</p>
                    @else
                        <div class="space-y-3">
                            @foreach($todaySchedule as $m)
                                <div class="flex items-center gap-3">
                                    <span class="text-xs font-semibold text-slate-500 w-12 shrink-0">{{ $m['startsAt']->format('H:i') }}</span>
                                    <div class="flex-1 min-w-0 rounded-xl px-3 py-2 bg-orange-50 border border-orange-100">
                                        <p class="text-xs font-semibold text-slate-800 truncate">{{ $m['title'] }}</p>
                                        @if($m['project'])<p class="text-[11px] text-slate-500 truncate">{{ $m['project'] }}</p>@endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="text-sm font-bold text-slate-800">Meeting Bulan Ini</h3>
                        <svg class="w-4 h-4 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2">{{ $meetingStats['total'] }}</p>
                    <div class="flex items-center gap-6 mt-3">
                        <div>
                            <p class="text-base font-bold text-slate-800">{{ $meetingStats['past'] }}</p>
                            <p class="text-[11px] text-slate-500">Sudah Berlangsung</p>
                        </div>
                        <div>
                            <p class="text-base font-bold text-slate-800">{{ $meetingStats['upcoming'] }}</p>
                            <p class="text-[11px] text-slate-500">Akan Datang</p>
                        </div>
                    </div>
                    <div class="mt-4 h-2 rounded-full bg-slate-100 overflow-hidden">
                        <div class="h-full rounded-full" style="width:{{ $meetingProgress }}%;background:linear-gradient(90deg,#fb923c,#ea580c)"></div>
                    </div>
                </div>
            </div>

            {{-- ── Meeting Mendatang + Jam Kerja ────────────────────────── --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-start">
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                    <h3 class="text-sm font-bold text-slate-800 mb-4">Meeting Mendatang</h3>
                    @if($upcomingMeetings->isEmpty())
                        <p class="text-xs text-slate-400 py-6 text-center">Belum ada meeting terjadwal.</p>
                    @else
                        <div class="space-y-3">
                            @foreach($upcomingMeetings as $m)
                                <div class="flex items-center gap-3 rounded-xl border border-slate-100 p-3 hover:border-orange-200 transition">
                                    <div class="w-11 h-11 rounded-xl flex flex-col items-center justify-center shrink-0 bg-orange-50 border border-orange-100">
                                        <span class="text-[10px] font-semibold text-orange-500 uppercase leading-none">{{ $m['startsAt'] ? $m['startsAt']->locale('id')->isoFormat('MMM') : '-' }}</span>
                                        <span class="text-sm font-extrabold text-orange-700 leading-none mt-0.5">{{ $m['startsAt'] ? $m['startsAt']->format('d') : '-' }}</span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-semibold text-slate-800 truncate">{{ $m['title'] }}</p>
                                        <p class="text-[11px] text-slate-500 truncate">{{ $m['project'] ?? '—' }} @if($m['startsAt']) &middot; {{ $m['startsAt']->format('H:i') }}@endif</p>
                                    </div>
                                    @if($m['meetLink'])
                                        <a href="{{ $m['meetLink'] }}" target="_blank" rel="noopener"
                                           class="shrink-0 text-[11px] font-semibold text-white px-3 py-1.5 rounded-lg transition"
                                           style="background:linear-gradient(135deg,#f97316,#ea580c)">Join</a>
                                    @else
                                        <a href="{{ $m['url'] }}" class="shrink-0 text-[11px] font-semibold text-orange-600 hover:text-orange-700 px-2">Lihat</a>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-bold text-slate-800">Jam Kerja 7 Hari</h3>
                    </div>
                    <p class="text-2xl font-extrabold text-slate-900 mt-2">{{ $totalHoursWeek }} <span class="text-sm font-medium text-slate-400">jam</span></p>
                    <div class="flex items-center gap-4 mt-1 mb-4">
                        <p class="text-[11px] text-slate-500">Rata-rata {{ $avgHoursDay }} jam/hari</p>
                    </div>
                    <div class="flex items-end gap-2 h-20">
                        @foreach($weeklyHours as $d)
                            <div class="flex-1 flex flex-col items-center gap-1.5">
                                <div class="w-full rounded-t-md" style="height:{{ $d['hours'] > 0 ? max(6, ($d['hours'] / $maxHours) * 64) : 3 }}px;background:{{ $d['hours'] > 0 ? 'linear-gradient(180deg,#fb923c,#ea580c)' : '#f1f5f9' }}"></div>
                                <span class="text-[10px] text-slate-400">{{ $d['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- ── Activity Heatmap ─────────────────────────────────────── --}}
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-slate-800">Aktivitas Penyelesaian Task <span class="text-slate-400 font-normal">(4 minggu terakhir)</span></h3>
                    <div class="flex items-center gap-1.5 text-[10px] text-slate-400">
                        <span>Sedikit</span>
                        <span class="w-3 h-3 rounded-sm bg-slate-100"></span>
                        <span class="w-3 h-3 rounded-sm bg-orange-200"></span>
                        <span class="w-3 h-3 rounded-sm bg-orange-400"></span>
                        <span class="w-3 h-3 rounded-sm bg-orange-600"></span>
                        <span>Banyak</span>
                    </div>
                </div>
                <div class="grid grid-cols-7 gap-2 mb-2">
                    @foreach($dayLabels as $lbl)
                        <span class="text-[10px] font-medium text-slate-400 text-center">{{ $lbl }}</span>
                    @endforeach
                </div>
                <div class="space-y-2">
                    @foreach($activityGrid as $week)
                        <div class="grid grid-cols-7 gap-2">
                            @foreach($week as $day)
                                @php
                                    $c = $day['count'];
                                    $cls = $c === 0 ? 'bg-slate-100' : ($c === 1 ? 'bg-orange-200' : ($c <= 3 ? 'bg-orange-400' : 'bg-orange-600'));
                                @endphp
                                <div class="aspect-square rounded-md {{ $cls }}" title="{{ $day['date']->locale('id')->isoFormat('D MMM') }} &middot; {{ $c }} task selesai"></div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>

        </div>

        {{-- ═══════════ Kolom Kanan — Task Summary ═══════════ --}}
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 xl:sticky xl:top-20">
            <h2 class="text-sm font-bold text-slate-800 mb-4">Ringkasan Task</h2>

            <div class="flex items-center gap-1 p-1 rounded-xl bg-slate-100 mb-4">
                @foreach($tabs as $key => $t)
                    <button @click="tab = '{{ $key }}'"
                            :class="tab === '{{ $key }}' ? 'bg-white shadow-sm text-slate-900' : 'text-slate-500'"
                            class="flex-1 text-[11px] font-semibold px-2 py-2 rounded-lg transition flex items-center justify-center gap-1.5">
                        {{ $t['label'] }}
                        @if($t['count'] > 0)
                            <span :class="tab === '{{ $key }}' ? 'bg-orange-500 text-white' : 'bg-slate-200 text-slate-600'"
                                  class="text-[10px] px-1.5 py-0.5 rounded-full leading-none">{{ $t['count'] }}</span>
                        @endif
                    </button>
                @endforeach
            </div>

            <div class="space-y-3">
                @foreach($taskSummary as $key => $list)
                    <div x-show="tab === '{{ $key }}'" x-cloak class="space-y-3">
                        @forelse($list as $task)
                            @php $due = $dueLabel($task); $progress = $progressFor($task); @endphp
                            <div class="rounded-xl border border-slate-100 p-3.5 hover:border-orange-200 transition">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="text-xs font-bold text-slate-800 leading-snug">{{ $task->title }}</p>
                                    <span class="shrink-0 text-[9px] font-semibold uppercase px-2 py-0.5 rounded-full {{ $due['cls'] }}">{{ ucfirst(str_replace('_',' ', $task->status)) }}</span>
                                </div>
                                @if($task->project)
                                    <p class="text-[11px] text-slate-400 mt-1 truncate">{{ $task->project->name }}</p>
                                @endif
                                <div class="flex items-center justify-between mt-2.5">
                                    <span class="text-[10px] font-medium px-2 py-0.5 rounded-full {{ $due['cls'] }}">{{ $due['text'] }}</span>
                                    <div class="flex items-center gap-1.5">
                                        @if($task->assignee?->avatar)
                                            <img src="{{ Storage::url($task->assignee->avatar) }}" class="w-5 h-5 rounded-full object-cover" alt="">
                                        @else
                                            <div class="w-5 h-5 rounded-full flex items-center justify-center text-white text-[9px] font-bold" style="background:linear-gradient(135deg,#f97316,#ea580c)">
                                                {{ strtoupper(substr($task->assignee->name ?? '?', 0, 2)) }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="mt-2.5 flex items-center gap-2">
                                    <div class="flex-1 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                        <div class="h-full rounded-full" style="width:{{ $progress }}%;background:linear-gradient(90deg,#fb923c,#ea580c)"></div>
                                    </div>
                                    <span class="text-[10px] font-semibold text-slate-500 w-8 text-right">{{ $progress }}%</span>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400 py-8 text-center">Tidak ada task di kategori ini.</p>
                        @endforelse
                    </div>
                @endforeach
            </div>
        </div>

    </div>
</div>
@endsection
