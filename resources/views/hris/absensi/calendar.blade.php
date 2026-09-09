@extends('layouts.app')
@section('title', 'Kalender Saya')
@section('page-title', 'Kalender Saya')

@section('content')
<div class="space-y-6 pt-5">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4 flex-wrap">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest" style="color:var(--lav-600)">Absensi Saya</p>
            <h1 class="font-display text-2xl font-extrabold" style="color:var(--fl-text-h,#1a0a3d)">Kalender Saya</h1>
            <p class="text-sm mt-0.5" style="color:var(--fl-text-muted,#6b7280)">Jadwal kerja, hari libur, absensi, cuti, dan lembur dalam satu tampilan.</p>
        </div>
        <a href="{{ route('hris.absensi.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium border transition-all"
           style="background:var(--fl-card-bg,#fff);border-color:var(--fl-card-border,#ede9fe);color:var(--fl-text-muted,#6b7280)">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Absensi
        </a>
    </div>

    {{-- Ringkasan bulan --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        @php
            $tiles = [
                ['label' => 'Hadir', 'value' => $summary['hadir'], 'color' => '#16a34a'],
                ['label' => 'Izin/Sakit', 'value' => $summary['izin'], 'color' => '#d97706'],
                ['label' => 'Cuti', 'value' => $summary['cuti'], 'color' => '#7c3aed'],
                ['label' => 'Alfa', 'value' => $summary['alfa'], 'color' => '#dc2626'],
                ['label' => 'Libur', 'value' => $summary['libur'], 'color' => '#9ca3af'],
                ['label' => 'Lembur (jam)', 'value' => rtrim(rtrim(number_format($summary['lembur_jam'], 1, ',', '.'), '0'), ','), 'color' => '#2563eb'],
            ];
        @endphp
        @foreach($tiles as $tile)
        <div class="rounded-2xl border p-3.5" style="background:var(--fl-card-bg,#fff);border-color:var(--fl-card-border,#ede9fe)">
            <p class="text-2xl font-extrabold" style="color:{{ $tile['color'] }}">{{ $tile['value'] }}</p>
            <p class="text-xs mt-0.5 font-medium" style="color:var(--fl-text-muted,#6b7280)">{{ $tile['label'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- Filter bulan + legenda --}}
    <div class="flex items-center justify-between gap-4 flex-wrap">
        <form method="GET" action="{{ route('hris.absensi.calendar') }}" class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('hris.absensi.calendar', ['year' => $start->copy()->subMonth()->year, 'month' => $start->copy()->subMonth()->month]) }}"
               class="w-9 h-9 flex items-center justify-center rounded-xl border transition-all shrink-0"
               style="border-color:var(--fl-card-border,#ede9fe);color:var(--fl-text-muted,#6b7280)">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <select name="month" onchange="this.form.submit()" class="fl-setting-input px-3 py-2 text-sm rounded-xl border" style="background:var(--fl-search-bg,#f5f3ff);border-color:var(--fl-card-border,#ede9fe);color:var(--fl-text-h,#1a0a3d)">
                @foreach(range(1, 12) as $m)
                <option value="{{ $m }}" @selected($m == $month)>{{ \Carbon\Carbon::create(null, $m)->locale('id')->isoFormat('MMMM') }}</option>
                @endforeach
            </select>
            <select name="year" onchange="this.form.submit()" class="fl-setting-input px-3 py-2 text-sm rounded-xl border" style="background:var(--fl-search-bg,#f5f3ff);border-color:var(--fl-card-border,#ede9fe);color:var(--fl-text-h,#1a0a3d)">
                @for($y = now()->year - 1; $y <= now()->year + 1; $y++)
                <option value="{{ $y }}" @selected($y == $year)>{{ $y }}</option>
                @endfor
            </select>
            <a href="{{ route('hris.absensi.calendar', ['year' => $start->copy()->addMonth()->year, 'month' => $start->copy()->addMonth()->month]) }}"
               class="w-9 h-9 flex items-center justify-center rounded-xl border transition-all shrink-0"
               style="border-color:var(--fl-card-border,#ede9fe);color:var(--fl-text-muted,#6b7280)">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
            <a href="{{ route('hris.absensi.calendar') }}"
               class="px-3 py-2 rounded-xl text-sm font-semibold text-white transition-all shrink-0"
               style="background:var(--hris-gradient);box-shadow:0 2px 8px rgba(109,40,217,0.3)">
                Hari Ini
            </a>
        </form>

        <div class="flex items-center gap-3 text-xs flex-wrap" style="color:var(--fl-text-muted,#6b7280)">
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full" style="background:#16a34a"></span> Hadir</span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full" style="background:#d97706"></span> Izin/Sakit</span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full" style="background:#7c3aed"></span> Cuti</span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full" style="background:#dc2626"></span> Alfa</span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full" style="background:#9ca3af"></span> Libur</span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full" style="background:#2563eb"></span> Terjadwal</span>
        </div>
    </div>

    {{-- Grid kalender --}}
    <div class="rounded-2xl border overflow-hidden" style="background:var(--fl-card-bg,#fff);border-color:var(--fl-card-border,#ede9fe)">
        <div class="overflow-x-auto">
        <div class="min-w-[700px]">
        <div class="grid grid-cols-7" style="border-bottom:1px solid var(--fl-card-border,#ede9fe)">
            @foreach(\App\Models\Shift::DAY_LABELS as $label)
            <div class="py-2.5 text-center text-xs font-semibold" style="color:var(--fl-text-muted,#6b7280)">{{ $label }}</div>
            @endforeach
        </div>

        <div class="grid grid-cols-7">
            @php $leadingBlanks = $start->dayOfWeek; @endphp
            @for($i = 0; $i < $leadingBlanks; $i++)
            <div class="border-b border-r min-h-[100px]" style="border-color:var(--fl-card-border,#ede9fe);background:var(--fl-search-bg,#f5f3ff)"></div>
            @endfor

            @foreach($days as $day)
            @php $status = $day['status']; @endphp
            <div class="border-b border-r p-2 min-h-[100px] flex flex-col gap-1.5 transition-colors"
                 style="border-color:var(--fl-card-border,#ede9fe);{{ $day['isToday'] ? 'box-shadow:inset 0 0 0 2px #7c3aed;' : '' }}{{ $status ? 'background:'.$status['bg'].';' : '' }}"
                 title="{{ $day['holiday']->name ?? '' }}">
                <div class="flex items-center justify-between gap-1">
                    <span class="text-xs font-bold shrink-0 {{ $day['isToday'] ? 'w-5 h-5 rounded-full flex items-center justify-center text-white' : '' }}"
                          style="{{ $day['isToday'] ? 'background:#7c3aed' : 'color:var(--fl-text-h,#1a0a3d)' }}">
                        {{ $day['date']->day }}
                    </span>
                    @if($day['overtime'])
                    <span class="text-[9px] font-bold px-1 py-0.5 rounded shrink-0" style="background:rgba(37,99,235,0.15);color:#2563eb" title="Lembur {{ rtrim(rtrim(number_format($day['overtime']->total_hours, 1), '0'), '.') }} jam">
                        L {{ rtrim(rtrim(number_format($day['overtime']->total_hours, 1), '0'), '.') }}j
                    </span>
                    @endif
                </div>

                @if($status)
                <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full self-start truncate max-w-full" style="color:{{ $status['color'] }};background:{{ $status['bg'] }}">
                    {{ $status['label'] }}
                </span>
                @endif

                @if($day['attendance'] && ($day['attendance']->check_in || $day['attendance']->check_out))
                <span class="text-[10px] leading-tight truncate" style="color:var(--fl-text-muted,#6b7280)">
                    {{ substr($day['attendance']->check_in ?? '--:--', 0, 5) }}&ndash;{{ substr($day['attendance']->check_out ?? '--:--', 0, 5) }}
                </span>
                @elseif($status && $status['key'] === 'scheduled' && $day['shift'])
                <span class="text-[10px] leading-tight truncate" style="color:var(--fl-text-muted,#6b7280)">
                    {{ $day['shift']->sessionsLabel() }}
                </span>
                @endif
            </div>
            @endforeach

            @php $trailingBlanks = (7 - (($leadingBlanks + count($days)) % 7)) % 7; @endphp
            @for($i = 0; $i < $trailingBlanks; $i++)
            <div class="border-b border-r min-h-[100px]" style="border-color:var(--fl-card-border,#ede9fe);background:var(--fl-search-bg,#f5f3ff)"></div>
            @endfor
        </div>
        </div>
        </div>
    </div>
</div>
@endsection
