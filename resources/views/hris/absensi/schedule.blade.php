@extends('layouts.app')
@section('title', 'Jadwal Shift Bulanan')
@section('page-title', 'Jadwal Shift Bulanan')

@section('content')
<div class="space-y-6 pt-5" x-data="shiftSchedule()">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4 flex-wrap">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest" style="color:var(--lav-600)">Konfigurasi HRIS</p>
            <h1 class="font-display text-2xl font-extrabold" style="color:var(--fl-text-h,#1a0a3d)">Jadwal Shift Bulanan</h1>
            <p class="text-sm mt-0.5" style="color:var(--fl-text-muted,#6b7280)">Atur shift tiap karyawan per tanggal untuk kebutuhan shift rotasi/gantian. Tanpa pengaturan di sini, karyawan memakai shift default dari halaman Data Karyawan.</p>
        </div>
        <a href="{{ route('hris.absensi.setting') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium border transition-all"
           style="background:var(--fl-card-bg,#fff);border-color:var(--fl-card-border,#ede9fe);color:var(--fl-text-muted,#6b7280)">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Pengaturan
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-xl px-4 py-3">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-xl px-4 py-3">{{ session('error') }}</div>
    @endif

    {{-- Filter bulan + legenda --}}
    <div class="flex items-center justify-between gap-4 flex-wrap">
        <form method="GET" action="{{ route('hris.absensi.schedule') }}" class="flex items-center gap-2">
            <select name="month" class="fl-setting-input px-3 py-2 text-sm rounded-xl border" style="background:var(--fl-search-bg,#f5f3ff);border-color:var(--fl-card-border,#ede9fe);color:var(--fl-text-h,#1a0a3d)">
                @foreach(range(1, 12) as $m)
                <option value="{{ $m }}" @selected($m == $month)>{{ \Carbon\Carbon::create(null, $m)->locale('id')->isoFormat('MMMM') }}</option>
                @endforeach
            </select>
            <select name="year" class="fl-setting-input px-3 py-2 text-sm rounded-xl border" style="background:var(--fl-search-bg,#f5f3ff);border-color:var(--fl-card-border,#ede9fe);color:var(--fl-text-h,#1a0a3d)">
                @for($y = now()->year - 1; $y <= now()->year + 1; $y++)
                <option value="{{ $y }}" @selected($y == $year)>{{ $y }}</option>
                @endfor
            </select>
            <button type="submit"
                    class="px-4 py-2 rounded-xl text-sm font-semibold text-white transition-all"
                    style="background:var(--hris-gradient);box-shadow:0 2px 8px rgba(109,40,217,0.3)">
                Tampilkan
            </button>
        </form>

        <div class="flex items-center gap-4 text-xs flex-wrap" style="color:var(--fl-text-muted,#6b7280)">
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full" style="background:#2563eb"></span> Shift default</span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full" style="background:#7c3aed"></span> Override</span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full" style="background:#9ca3af"></span> Libur</span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full" style="background:#d1d5db"></span> Belum diatur</span>
        </div>
    </div>

    @if($shifts->isEmpty())
    <div class="rounded-2xl border p-6 text-sm text-center" style="background:var(--fl-card-bg,#fff);border-color:var(--fl-card-border,#ede9fe);color:var(--fl-text-muted,#6b7280)">
        Belum ada shift kerja aktif. <a href="{{ route('hris.absensi.setting') }}" class="font-semibold" style="color:#7c3aed">Tambahkan shift dulu di Pengaturan Absensi</a> sebelum mengatur jadwal bulanan.
    </div>
    @endif

    {{-- Grid --}}
    <div class="rounded-2xl border overflow-hidden" style="background:var(--fl-card-bg,#fff);border-color:var(--fl-card-border,#ede9fe)">
        <div class="overflow-x-auto">
            <table class="text-xs border-collapse w-full">
                <thead>
                    <tr>
                        <th class="sticky left-0 z-10 px-3 py-2 text-left font-semibold border-b border-r"
                            style="background:var(--fl-search-bg,#f5f3ff);border-color:var(--fl-card-border,#ede9fe);color:var(--fl-text-h,#1a0a3d);min-width:190px">
                            Karyawan
                        </th>
                        @for($d = 1; $d <= $daysInMonth; $d++)
                        @php $colDate = $start->copy()->day($d); $isWeekend = in_array($colDate->dayOfWeek, [0, 6], true); @endphp
                        <th class="px-1.5 py-2 text-center font-semibold border-b whitespace-nowrap"
                            style="border-color:var(--fl-card-border,#ede9fe);{{ $isWeekend ? 'background:rgba(239,68,68,0.05);color:#ef4444' : 'color:var(--fl-text-muted,#6b7280)' }}">
                            {{ $d }}<br><span class="font-normal">{{ \App\Models\Shift::DAY_LABELS[$colDate->dayOfWeek] }}</span>
                        </th>
                        @endfor
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $emp)
                    <tr class="border-b" style="border-color:var(--fl-card-border,#ede9fe)">
                        <td class="sticky left-0 z-10 px-3 py-2 border-r" style="background:var(--fl-card-bg,#fff);border-color:var(--fl-card-border,#ede9fe)">
                            <p class="font-semibold truncate" style="color:var(--fl-text-h,#1a0a3d)">{{ $emp->name }}</p>
                            <button type="button" @click="openBulk({{ $emp->id }}, '{{ addslashes($emp->name) }}')"
                                    class="text-[10px] font-medium" style="color:#7c3aed">
                                Atur sebulan
                            </button>
                        </td>
                        @for($d = 1; $d <= $daysInMonth; $d++)
                        @php $cell = $grid[$emp->id][$d]; $cellDate = $start->copy()->day($d); @endphp
                        <td class="text-center px-0.5 py-1 border-r" style="border-color:var(--fl-card-border,#ede9fe)">
                            <button type="button"
                                    @click="openCell({{ $emp->id }}, '{{ addslashes($emp->name) }}', '{{ $cellDate->toDateString() }}', '{{ addslashes($cellDate->locale('id')->isoFormat('dddd, D MMMM YYYY')) }}', '{{ $cell['mode'] }}', {{ $cell['shift_id'] ?? 'null' }})"
                                    class="w-9 h-7 rounded-md text-[10px] font-bold transition-all hover:opacity-70"
                                    style="{{ $cell['is_override'] ? 'border:1.5px dashed '.$cell['color'] : 'border:1px solid transparent' }};color:{{ $cell['color'] }};background:{{ $cell['color'] }}1a">
                                {{ $cell['label'] }}
                            </button>
                        </td>
                        @endfor
                    </tr>
                    @empty
                    <tr><td colspan="{{ $daysInMonth + 1 }}" class="text-center py-8 text-sm" style="color:var(--fl-text-muted,#6b7280)">Belum ada karyawan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Cell modal (edit 1 karyawan x 1 tanggal) --}}
    <div x-show="cellModalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background:rgba(0,0,0,0.7);backdrop-filter:blur(4px)">
        <div class="relative w-full max-w-sm rounded-2xl overflow-hidden shadow-2xl max-h-[85vh] flex flex-col"
             style="background:var(--fl-card-bg,#fff);border:1px solid var(--fl-card-border,#ede9fe)"
             @click.stop>
            <div class="flex items-center justify-between px-6 py-4 border-b shrink-0" style="border-color:var(--fl-card-border,#ede9fe)">
                <div class="min-w-0">
                    <p class="font-semibold truncate" style="color:var(--fl-text-h,#1a0a3d)" x-text="cellUserName"></p>
                    <p class="text-xs" style="color:var(--fl-text-muted,#6b7280)" x-text="cellDateLabel"></p>
                </div>
                <button type="button" @click="cellModalOpen = false" class="p-1.5 rounded-lg shrink-0" style="color:var(--fl-text-muted,#6b7280)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form action="{{ route('hris.absensi.schedule.cell') }}" method="POST" class="p-6 space-y-4 overflow-y-auto">
                @csrf
                <input type="hidden" name="user_id" :value="cellUserId">
                <input type="hidden" name="date" :value="cellDate">
                <input type="hidden" name="mode" :value="cellMode">
                <input type="hidden" name="shift_id" :value="cellMode === 'shift' ? cellShiftId : ''">

                <div class="space-y-1.5">
                    <label class="flex items-center gap-2.5 px-3 py-2 rounded-xl border cursor-pointer" style="border-color:var(--fl-card-border,#ede9fe)">
                        <input type="radio" name="mode_choice" :checked="cellMode === 'default'" @click="cellMode = 'default'" class="accent-[#7c3aed]">
                        <span class="text-sm" style="color:var(--fl-text-body,#374151)">Default (ikuti shift profil)</span>
                    </label>
                    <label class="flex items-center gap-2.5 px-3 py-2 rounded-xl border cursor-pointer" style="border-color:var(--fl-card-border,#ede9fe)">
                        <input type="radio" name="mode_choice" :checked="cellMode === 'off'" @click="cellMode = 'off'" class="accent-[#7c3aed]">
                        <span class="text-sm" style="color:var(--fl-text-body,#374151)">Libur (override)</span>
                    </label>
                    @foreach($shifts as $shift)
                    <label class="flex items-center gap-2.5 px-3 py-2 rounded-xl border cursor-pointer" style="border-color:var(--fl-card-border,#ede9fe)">
                        <input type="radio" name="mode_choice" :checked="cellMode === 'shift' && cellShiftId === {{ $shift->id }}" @click="cellMode = 'shift'; cellShiftId = {{ $shift->id }}" class="accent-[#7c3aed]">
                        <span class="text-sm" style="color:var(--fl-text-body,#374151)">{{ $shift->name }} ({{ $shift->timeRangeLabel() }})</span>
                    </label>
                    @endforeach
                </div>

                <div class="flex gap-3 pt-2 shrink-0">
                    <button type="submit" class="flex-1 py-2.5 rounded-xl font-semibold text-sm text-white transition-all" style="background:var(--hris-gradient);box-shadow:0 4px 12px rgba(109,40,217,0.3)">Simpan</button>
                    <button type="button" @click="cellModalOpen = false" class="px-5 py-2.5 rounded-xl font-medium text-sm transition-all" style="background:var(--fl-search-bg,#f5f3ff);color:var(--fl-text-muted,#6b7280);border:1px solid var(--fl-card-border,#ede9fe)">Batal</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Bulk modal (terapkan ke 1 bulan penuh utk 1 karyawan) --}}
    <div x-show="bulkModalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background:rgba(0,0,0,0.7);backdrop-filter:blur(4px)">
        <div class="relative w-full max-w-sm rounded-2xl overflow-hidden shadow-2xl max-h-[85vh] flex flex-col"
             style="background:var(--fl-card-bg,#fff);border:1px solid var(--fl-card-border,#ede9fe)"
             @click.stop>
            <div class="flex items-center justify-between px-6 py-4 border-b shrink-0" style="border-color:var(--fl-card-border,#ede9fe)">
                <div class="min-w-0">
                    <p class="font-semibold" style="color:var(--fl-text-h,#1a0a3d)">Atur Sebulan</p>
                    <p class="text-xs truncate" style="color:var(--fl-text-muted,#6b7280)"
                       x-text="bulkUserName + ' — {{ \Carbon\Carbon::create(null, $month)->locale('id')->isoFormat('MMMM') }} {{ $year }}'"></p>
                </div>
                <button type="button" @click="bulkModalOpen = false" class="p-1.5 rounded-lg shrink-0" style="color:var(--fl-text-muted,#6b7280)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form action="{{ route('hris.absensi.schedule.bulk') }}" method="POST" class="p-6 space-y-4 overflow-y-auto">
                @csrf
                <input type="hidden" name="user_id" :value="bulkUserId">
                <input type="hidden" name="year" value="{{ $year }}">
                <input type="hidden" name="month" value="{{ $month }}">
                <input type="hidden" name="mode" :value="bulkMode">
                <input type="hidden" name="shift_id" :value="bulkMode === 'shift' ? bulkShiftId : ''">

                <p class="text-xs p-2.5 rounded-lg" style="background:rgba(245,158,11,0.08);color:#b45309">
                    Menerapkan ini akan menimpa semua pengaturan tanggal yang sudah dibuat sebelumnya di bulan ini untuk karyawan ini.
                </p>

                <div class="space-y-1.5">
                    <label class="flex items-center gap-2.5 px-3 py-2 rounded-xl border cursor-pointer" style="border-color:var(--fl-card-border,#ede9fe)">
                        <input type="radio" name="bulk_mode_choice" :checked="bulkMode === 'default'" @click="bulkMode = 'default'" class="accent-[#7c3aed]">
                        <span class="text-sm" style="color:var(--fl-text-body,#374151)">Reset ke shift default</span>
                    </label>
                    <label class="flex items-center gap-2.5 px-3 py-2 rounded-xl border cursor-pointer" style="border-color:var(--fl-card-border,#ede9fe)">
                        <input type="radio" name="bulk_mode_choice" :checked="bulkMode === 'off'" @click="bulkMode = 'off'" class="accent-[#7c3aed]">
                        <span class="text-sm" style="color:var(--fl-text-body,#374151)">Libur sebulan penuh</span>
                    </label>
                    @foreach($shifts as $shift)
                    <label class="flex items-center gap-2.5 px-3 py-2 rounded-xl border cursor-pointer" style="border-color:var(--fl-card-border,#ede9fe)">
                        <input type="radio" name="bulk_mode_choice" :checked="bulkMode === 'shift' && bulkShiftId === {{ $shift->id }}" @click="bulkMode = 'shift'; bulkShiftId = {{ $shift->id }}" class="accent-[#7c3aed]">
                        <span class="text-sm" style="color:var(--fl-text-body,#374151)">{{ $shift->name }} ({{ $shift->timeRangeLabel() }})</span>
                    </label>
                    @endforeach
                </div>

                <label x-show="bulkMode === 'shift'" x-cloak class="flex items-center gap-3 cursor-pointer select-none">
                    <input type="hidden" name="only_working_days" value="0">
                    <input type="checkbox" name="only_working_days" value="1" x-model="bulkOnlyWorkingDays" class="w-4 h-4 rounded accent-[#7c3aed]">
                    <span class="text-sm" style="color:var(--fl-text-body,#374151)">Cuma isi hari kerja shift ini, hari lain otomatis libur</span>
                </label>

                <div class="flex gap-3 pt-2 shrink-0">
                    <button type="submit" class="flex-1 py-2.5 rounded-xl font-semibold text-sm text-white transition-all" style="background:var(--hris-gradient);box-shadow:0 4px 12px rgba(109,40,217,0.3)">Terapkan</button>
                    <button type="button" @click="bulkModalOpen = false" class="px-5 py-2.5 rounded-xl font-medium text-sm transition-all" style="background:var(--fl-search-bg,#f5f3ff);color:var(--fl-text-muted,#6b7280);border:1px solid var(--fl-card-border,#ede9fe)">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function shiftSchedule() {
    return {
        cellModalOpen: false,
        cellUserId:    null,
        cellUserName:  '',
        cellDate:      null,
        cellDateLabel: '',
        cellMode:      'default',
        cellShiftId:   null,

        openCell(userId, userName, date, dateLabel, mode, shiftId) {
            this.cellUserId    = userId;
            this.cellUserName  = userName;
            this.cellDate      = date;
            this.cellDateLabel = dateLabel;
            this.cellMode      = mode;
            this.cellShiftId   = shiftId;
            this.cellModalOpen = true;
        },

        bulkModalOpen:        false,
        bulkUserId:           null,
        bulkUserName:         '',
        bulkMode:             'shift',
        bulkShiftId:          {{ $shifts->first()->id ?? 'null' }},
        bulkOnlyWorkingDays:  true,

        openBulk(userId, userName) {
            this.bulkUserId   = userId;
            this.bulkUserName = userName;
            this.bulkModalOpen = true;
        },
    };
}
</script>
@endpush
