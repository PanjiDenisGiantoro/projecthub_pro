@extends('layouts.app')
@section('title', 'Absensi')
@section('page-title', 'Absensi')

@push('head')
@if($setting->is_face_recognition_enabled)
<script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.js"></script>
@endif
@endpush

@section('content')
<div class="space-y-5 pt-5"
     x-data="absensiPage(
        {{ $setting->is_location_enabled ? 'true' : 'false' }},
        {{ $setting->is_face_recognition_enabled ? 'true' : 'false' }},
        {{ $setting->office_latitude ?? 'null' }},
        {{ $setting->office_longitude ?? 'null' }},
        {{ $setting->max_distance_meters }},
        {{ $setting->face_recognition_threshold }},
        {{ $setting->require_location_for_checkout ? 'true' : 'false' }},
        {{ $setting->require_face_for_checkout ? 'true' : 'false' }},
        @json(auth()->user()->face_descriptor)
     )">

    {{-- ── Header ────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between gap-3" x-data="{ importOpen: false }">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest" style="color:var(--lav-600,#2563eb)">HRIS</p>
            <h1 class="font-display text-2xl font-extrabold" style="color:var(--fl-text-h,#1a0a3d)">Absensi</h1>
            <p class="text-sm mt-0.5" style="color:var(--fl-text-muted,#6b7280)">
                {{ $today->locale('id')->isoFormat('dddd, D MMMM Y') }}
            </p>
        </div>
        <div class="flex items-center gap-2 flex-wrap justify-end">
            @can('export absensi')
            <a href="{{ route('hris.absensi.export') }}"
               class="inline-flex items-center gap-2 px-3 py-2 rounded-xl text-xs font-medium border transition-all"
               style="background:var(--fl-search-bg,#f5f3ff);border-color:var(--fl-card-border,#ede9fe);color:var(--fl-text-muted,#6b7280)">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M7 10l5 5 5-5M12 15V3"/></svg>
                Export Excel
            </a>
            @endcan
            @can('import absensi')
            <button type="button" @click="importOpen = true"
                    class="inline-flex items-center gap-2 px-3 py-2 rounded-xl text-xs font-medium border transition-all"
                    style="background:var(--fl-search-bg,#f5f3ff);border-color:var(--fl-card-border,#ede9fe);color:var(--fl-text-muted,#6b7280)">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M16 8l-4-5-4 5M12 3v12"/></svg>
                Import Excel
            </button>
            @endcan
            <a href="{{ route('hris.absensi.rekap') }}"
               class="inline-flex items-center gap-2 px-3 py-2 rounded-xl text-xs font-medium border transition-all"
               style="background:var(--fl-search-bg,#f5f3ff);border-color:var(--fl-card-border,#ede9fe);color:var(--fl-text-muted,#6b7280)">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Rekap
            </a>
        </div>

        {{-- Modal Import Excel Absensi --}}
        @can('import absensi')
        <div x-show="importOpen" x-cloak
             class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
             style="display: none;">
            <div @click.outside="importOpen = false" x-show="importOpen" x-transition
                 class="bg-white rounded-xl shadow-xl w-full max-w-md">
                <form method="POST" action="{{ route('hris.absensi.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="font-semibold text-gray-800">Import Data Absensi</h3>
                        <button type="button" @click="importOpen = false" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="px-5 py-4 space-y-3">
                        <p class="text-sm text-gray-500">
                            Unggah file Excel (.xlsx/.xls/.csv) berisi data absensi karyawan. Kombinasi email + tanggal yang sudah ada akan diperbarui, yang belum ada akan dibuat baru.
                        </p>
                        <a href="{{ route('hris.absensi.import.template') }}" class="inline-flex items-center gap-1.5 text-sm text-blue-600 hover:underline">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M7 10l5 5 5-5M12 15V3"/></svg>
                            Download Template Import
                        </a>
                        <div>
                            <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                                   class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('file') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="px-5 py-4 border-t border-gray-100 flex justify-end gap-2">
                        <button type="button" @click="importOpen = false" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg">Import</button>
                    </div>
                </form>
            </div>
        </div>
        @endcan
    </div>

    {{-- ── Main Check-in Card ──────────────────────────────────────────── --}}
    <div class="rounded-2xl border overflow-hidden"
         style="background:var(--fl-card-bg,#fff);border-color:var(--fl-card-border,#ede9fe);box-shadow:0 4px 24px rgba(109,40,217,0.08)">

        {{-- Top stripe --}}
        <div class="h-[3px]" style="background:linear-gradient(90deg,#7c3aed,#c4b5fd)"></div>

        <div class="p-6">
            {{-- Status today --}}
            @if($attendance)
            @php
                $noteParts = $attendance->notes ? explode('; ', $attendance->notes) : [];
                $lateNotes = array_filter($noteParts, fn ($n) => str_contains($n, 'Telat'));
                $earlyOutNotes = array_filter($noteParts, fn ($n) => str_contains($n, 'Pulang cepat'));
            @endphp
            <div class="grid grid-cols-2 gap-3 {{ $isSplitShift ? 'mb-2' : 'mb-5' }}">
                <div class="rounded-xl p-4 text-center" style="background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.15)">
                    <p class="text-xs font-semibold mb-1" style="color:rgba(16,185,129,0.8)">{{ $isSplitShift ? 'CHECK IN SESI 1' : 'CHECK IN' }}</p>
                    <p class="text-2xl font-extrabold" style="color:#10b981">{{ $attendance->check_in ?? '—' }}</p>
                    @if($attendance->distance_in !== null)
                    <p class="text-xs mt-1" style="color:rgba(16,185,129,0.6)">{{ $attendance->distance_in }}m dari kantor</p>
                    @endif
                    @if($attendance->face_verified_in)
                    <span class="inline-flex items-center gap-1 text-[10px] font-semibold mt-1 px-2 py-0.5 rounded-full" style="background:rgba(16,185,129,0.15);color:#10b981">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        Wajah Terverifikasi
                    </span>
                    @endif
                    @foreach($lateNotes as $note)
                    <span class="inline-flex items-center gap-1 text-[10px] font-semibold mt-1 px-2 py-0.5 rounded-full" style="background:rgba(245,158,11,0.15);color:#d97706">
                        {{ $note }}
                    </span>
                    @endforeach
                </div>
                <div class="rounded-xl p-4 text-center" style="background:rgba(59,130,246,0.08);border:1px solid rgba(59,130,246,0.15)">
                    <p class="text-xs font-semibold mb-1" style="color:rgba(59,130,246,0.8)">{{ $isSplitShift ? 'CHECK OUT SESI 1' : 'CHECK OUT' }}</p>
                    <p class="text-2xl font-extrabold" style="color:#3b82f6">{{ $attendance->check_out ?? '—' }}</p>
                    @if($attendance->check_out)
                    <p class="text-xs mt-1" style="color:rgba(59,130,246,0.6)">
                        {{ intdiv($attendance->workMinutes(), 60) }}j {{ $attendance->workMinutes() % 60 }}m kerja
                    </p>
                    @endif
                    @foreach($earlyOutNotes as $note)
                    <span class="inline-flex items-center gap-1 text-[10px] font-semibold mt-1 px-2 py-0.5 rounded-full" style="background:rgba(245,158,11,0.15);color:#d97706">
                        {{ $note }}
                    </span>
                    @endforeach
                </div>
            </div>
            @if($isSplitShift && $attendance->check_in_2)
            <div class="grid grid-cols-2 gap-3 mb-5">
                <div class="rounded-xl p-4 text-center" style="background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.15)">
                    <p class="text-xs font-semibold mb-1" style="color:rgba(16,185,129,0.8)">CHECK IN SESI 2</p>
                    <p class="text-2xl font-extrabold" style="color:#10b981">{{ $attendance->check_in_2 }}</p>
                </div>
                <div class="rounded-xl p-4 text-center" style="background:rgba(59,130,246,0.08);border:1px solid rgba(59,130,246,0.15)">
                    <p class="text-xs font-semibold mb-1" style="color:rgba(59,130,246,0.8)">CHECK OUT SESI 2</p>
                    <p class="text-2xl font-extrabold" style="color:#3b82f6">{{ $attendance->check_out_2 ?? '—' }}</p>
                </div>
            </div>
            @endif
            @endif

            @if($attendanceState === 'day_off')
            {{-- === HARI LIBUR SESUAI SHIFT === --}}
            <div class="flex items-center gap-3 py-4 px-4 rounded-xl" style="background:rgba(107,114,128,0.08);border:1px solid rgba(107,114,128,0.15)">
                <svg class="w-5 h-5 shrink-0" style="color:#6b7280" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <p class="text-sm font-semibold" style="color:#6b7280">
                    @if($todayHoliday)
                    Hari ini libur: {{ $todayHoliday->name }}.
                    @elseif($todayShift)
                    Hari ini hari libur sesuai shift kerja Anda ({{ $todayShift->name }}: {{ $todayShift->workingDaysLabel() }}).
                    @else
                    Hari ini hari libur sesuai jadwal shift Anda.
                    @endif
                </p>
            </div>
            @elseif($attendanceState === 'checkin_1' || $attendanceState === 'checkin_2')
            {{-- === CHECK-IN FLOW (sesi 1, atau sesi 2 buat shift split) === --}}
            <p class="text-sm mb-4" style="color:var(--fl-text-muted,#6b7280)">
                @if($attendanceState === 'checkin_2')
                Sesi 1 selesai. Anda belum check-in sesi 2 hari ini.
                @else
                Anda belum check-in hari ini.
                @endif
            </p>

            {{-- Step indicators --}}
            @if($setting->is_location_enabled || $setting->is_face_recognition_enabled)
            <div class="flex items-center gap-2 mb-5 text-xs font-semibold">
                @if($setting->is_location_enabled)
                <div class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-full transition-all"
                     :style="locStatus === 'valid'
                        ? 'background:rgba(16,185,129,0.12);color:#10b981;border:1px solid rgba(16,185,129,0.2)'
                        : locStatus === 'invalid'
                        ? 'background:rgba(239,68,68,0.1);color:#ef4444;border:1px solid rgba(239,68,68,0.2)'
                        : 'background:rgba(124,58,237,0.08);color:#a78bfa;border:1px solid rgba(124,58,237,0.15)'">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    </svg>
                    <span x-text="locStatus === 'valid' ? '✓ Lokasi OK' : locStatus === 'detecting' ? 'Deteksi...' : locStatus === 'invalid' ? '✗ Terlalu Jauh' : 'Lokasi'"></span>
                </div>
                <svg class="w-3 h-3" style="color:var(--fl-text-subtle,#9ca3af)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                @endif

                @if($setting->is_face_recognition_enabled)
                <div class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-full transition-all"
                     :style="faceStatus === 'verified'
                        ? 'background:rgba(16,185,129,0.12);color:#10b981;border:1px solid rgba(16,185,129,0.2)'
                        : faceStatus === 'error'
                        ? 'background:rgba(239,68,68,0.1);color:#ef4444;border:1px solid rgba(239,68,68,0.2)'
                        : 'background:rgba(124,58,237,0.08);color:#a78bfa;border:1px solid rgba(124,58,237,0.15)'">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span x-text="faceStatus === 'verified' ? '✓ Wajah OK' : faceStatus === 'detecting' ? 'Mendeteksi...' : faceStatus === 'loading' ? 'Memuat AI...' : faceStatus === 'error' ? '✗ Gagal' : 'Wajah'"></span>
                </div>
                <svg class="w-3 h-3" style="color:var(--fl-text-subtle,#9ca3af)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                @endif

                <div class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-full transition-all"
                     :style="canCheckIn
                        ? 'background:linear-gradient(135deg,rgba(124,58,237,0.15),rgba(109,40,217,0.08));color:#c4b5fd;border:1px solid rgba(124,58,237,0.25)'
                        : 'background:rgba(255,255,255,0.03);color:rgba(167,139,250,0.35);border:1px solid rgba(167,139,250,0.1)'">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Siap Check-in
                </div>
            </div>
            @endif

            {{-- Location panel --}}
            @if($setting->is_location_enabled)
            <div class="rounded-xl p-4 mb-4 transition-all"
                 :style="locStatus === 'valid'
                    ? 'background:rgba(16,185,129,0.06);border:1px solid rgba(16,185,129,0.15)'
                    : locStatus === 'invalid'
                    ? 'background:rgba(239,68,68,0.06);border:1px solid rgba(239,68,68,0.15)'
                    : 'background:rgba(124,58,237,0.06);border:1px solid rgba(124,58,237,0.12)'">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 mt-0.5"
                         :style="locStatus === 'valid' ? 'background:rgba(16,185,129,0.15)' : 'background:rgba(124,58,237,0.12)'">
                        <svg class="w-4 h-4" :style="locStatus === 'valid' ? 'color:#10b981' : 'color:#a78bfa'"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold" style="color:var(--fl-text-h,#1a0a3d)">
                            {{ $setting->office_name ?: 'Lokasi Kantor' }}
                        </p>
                        <p class="text-xs mt-0.5" style="color:var(--fl-text-muted,#6b7280)"
                           x-text="locMsg || 'Mendeteksi lokasi Anda...'"></p>
                        <div x-show="locStatus === 'valid'" class="mt-2">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 h-1.5 rounded-full overflow-hidden" style="background:rgba(167,139,250,0.15)">
                                    <div class="h-full rounded-full" style="background:#10b981;width:100%"></div>
                                </div>
                                <span class="text-xs font-bold" style="color:#10b981" x-text="locDistance + 'm / {{ $setting->max_distance_meters }}m'"></span>
                            </div>
                        </div>
                        <div x-show="locStatus === 'detecting'" class="mt-1.5">
                            <div class="h-1 rounded-full overflow-hidden" style="background:rgba(167,139,250,0.1)">
                                <div class="h-full rounded-full animate-pulse" style="background:#a78bfa;width:60%"></div>
                            </div>
                        </div>
                    </div>
                    <button type="button" @click="detectLocation()"
                            x-show="locStatus !== 'detecting'"
                            class="text-xs px-2.5 py-1.5 rounded-lg font-medium transition-all shrink-0"
                            style="background:rgba(124,58,237,0.1);color:#7c3aed;border:1px solid rgba(124,58,237,0.2)">
                        Refresh
                    </button>
                </div>
            </div>
            @endif

            {{-- Face recognition panel --}}
            @if($setting->is_face_recognition_enabled)
            <div class="rounded-xl overflow-hidden mb-4 border transition-all"
                 :style="faceStatus === 'verified'
                    ? 'border-color:rgba(16,185,129,0.2)'
                    : 'border-color:rgba(124,58,237,0.12)'">

                {{-- Camera off state --}}
                <div x-show="faceStatus === 'idle' || faceStatus === 'error'"
                     class="flex flex-col items-center justify-center gap-3 py-8 px-6 text-center"
                     style="background:rgba(124,58,237,0.04)">
                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center"
                         style="background:rgba(124,58,237,0.12)">
                        <svg class="w-7 h-7" style="color:#a78bfa" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold" style="color:var(--fl-text-h,#1a0a3d)">Verifikasi Wajah Diperlukan</p>
                        @php $desc = auth()->user()->face_descriptor; @endphp
                        <template x-if="faceStatus === 'error' && faceErrorMsg">
                            <p class="text-xs mt-1 font-medium" style="color:#dc2626" x-text="faceErrorMsg"></p>
                        </template>
                        <template x-if="!(faceStatus === 'error' && faceErrorMsg)">
                        @if($desc)
                        <p class="text-xs mt-1" style="color:var(--fl-text-muted,#6b7280)">Wajah Anda sudah terdaftar. Klik tombol untuk memulai verifikasi.</p>
                        @else
                        <p class="text-xs mt-1" style="color:#f59e0b">Wajah Anda belum terdaftar. Daftarkan wajah Anda terlebih dahulu.</p>
                        @endif
                        </template>
                    </div>
                    @if($desc)
                    <button type="button" @click="startFaceVerification()"
                            class="px-5 py-2 rounded-xl font-semibold text-sm transition-all"
                            style="background:var(--hris-gradient);color:#fff;box-shadow:0 4px 12px rgba(109,40,217,0.3)">
                        Mulai Kamera
                    </button>
                    @else
                    <button type="button" @click="openEnroll()"
                            class="px-5 py-2 rounded-xl font-semibold text-sm transition-all"
                            style="background:var(--hris-gradient);color:#fff;box-shadow:0 4px 12px rgba(109,40,217,0.3)">
                        Daftarkan Wajah Saya
                    </button>
                    @endif
                </div>

                {{-- Loading models --}}
                <div x-show="faceStatus === 'loading'"
                     class="flex flex-col items-center justify-center gap-3 py-8 px-6 text-center"
                     style="background:rgba(124,58,237,0.04)">
                    <svg class="w-8 h-8 animate-spin" style="color:#7c3aed" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <p class="text-sm" style="color:var(--fl-text-muted,#6b7280)">Memuat model AI pengenalan wajah...</p>
                </div>

                {{-- Camera active --}}
                <div x-show="faceStatus === 'detecting' || faceStatus === 'processing'"
                     class="relative" style="background:#000;aspect-ratio:4/3">
                    <video id="checkin-video" autoplay muted playsinline
                           class="w-full h-full object-cover" style="transform:scaleX(-1)"></video>
                    <canvas id="checkin-overlay" class="absolute inset-0 w-full h-full" style="transform:scaleX(-1)"></canvas>
                    {{-- Guidance overlay --}}
                    <div class="absolute inset-0 flex flex-col items-end justify-end p-3">
                        <div class="flex items-center gap-2 px-3 py-1.5 rounded-full" style="background:rgba(0,0,0,0.5)">
                            <div class="w-2 h-2 rounded-full animate-pulse bg-red-400"></div>
                            <span class="text-white text-xs font-medium">Live</span>
                        </div>
                    </div>
                    {{-- Face detection status --}}
                    <div class="absolute top-3 left-3 right-3">
                        <div class="px-3 py-1.5 rounded-full text-xs font-semibold text-center transition-all"
                             :style="faceMultiWarning
                                ? 'background:rgba(220,38,38,0.9);color:#fff'
                                : (faceDetected ? 'background:rgba(16,185,129,0.9);color:#fff' : 'background:rgba(0,0,0,0.5);color:rgba(255,255,255,0.8)')"
                             x-text="faceMultiWarning
                                ? '✗ Lebih dari 1 wajah terdeteksi — pastikan cuma Anda sendiri'
                                : (faceDetected ? (faceMatch ? '✓ Wajah cocok — ' + Math.round(faceConf*100) + '% yakin' : (faceConf > 0 ? 'Mencocokkan... ' + Math.round(faceConf*100) + '%' : 'Posisikan wajah di tengah')) : 'Wajah tidak terdeteksi')">
                        </div>
                    </div>
                </div>

                {{-- Verified state --}}
                <div x-show="faceStatus === 'verified'"
                     class="flex items-center gap-4 p-4"
                     style="background:rgba(16,185,129,0.08)">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0"
                         style="background:rgba(16,185,129,0.15)">
                        <svg class="w-6 h-6" style="color:#10b981" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold text-sm" style="color:#10b981">Wajah Terverifikasi</p>
                        <p class="text-xs" style="color:rgba(16,185,129,0.7)" x-text="'Keyakinan: ' + Math.round(faceConf*100) + '%'"></p>
                    </div>
                    <button type="button" @click="resetFace()"
                            class="text-xs px-3 py-1.5 rounded-lg" style="background:rgba(16,185,129,0.15);color:#10b981">
                        Ulangi
                    </button>
                </div>
            </div>
            @endif

            {{-- Check-in form --}}
            <form action="{{ route('hris.absensi.checkin') }}" method="POST" id="checkin-form">
                @csrf
                <input type="hidden" name="lat" x-model="gpsLat">
                <input type="hidden" name="lng" x-model="gpsLng">
                <input type="hidden" name="address" x-model="gpsAddress">
                <input type="hidden" name="face_verified" :value="faceStatus === 'verified' ? '1' : '0'">
                <button type="submit"
                        :disabled="!canCheckIn"
                        class="w-full py-3.5 rounded-xl font-bold text-white text-sm transition-all disabled:opacity-40 disabled:cursor-not-allowed"
                        style="background:var(--hris-gradient);box-shadow:0 4px 16px rgba(109,40,217,0.35)"
                        :class="canCheckIn ? 'hover:-translate-y-0.5 active:translate-y-0' : ''">
                    <span x-show="!checkingIn">
                        {{ $attendanceState === 'checkin_2' ? 'Check In Sesi 2 Sekarang' : 'Check In Sekarang' }}
                    </span>
                    <span x-show="checkingIn" class="flex items-center justify-center gap-2">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        Memproses...
                    </span>
                </button>
            </form>

            @elseif($attendanceState === 'checkout_1' || $attendanceState === 'checkout_2')
            {{-- === CHECK-OUT FLOW (sesi 1, atau sesi 2 buat shift split) === --}}
            @if($attendanceState === 'checkout_2')
            <p class="text-sm mb-4" style="color:var(--fl-text-muted,#6b7280)">Anda sudah check-in sesi 2. Jangan lupa check-out setelah selesai.</p>
            @endif

            {{-- Location panel for checkout --}}
            @if($setting->is_location_enabled && $setting->require_location_for_checkout)
            <div class="rounded-xl p-4 mb-4 transition-all"
                 :style="locStatus === 'valid'
                    ? 'background:rgba(16,185,129,0.06);border:1px solid rgba(16,185,129,0.15)'
                    : 'background:rgba(124,58,237,0.06);border:1px solid rgba(124,58,237,0.12)'">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 shrink-0" :style="locStatus === 'valid' ? 'color:#10b981' : 'color:#a78bfa'"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    </svg>
                    <p class="text-sm flex-1" style="color:var(--fl-text-body,#374151)"
                       x-text="locMsg || 'Mendeteksi lokasi untuk check-out...'"></p>
                </div>
            </div>
            @endif

            {{-- Face for checkout --}}
            @if($setting->is_face_recognition_enabled && $setting->require_face_for_checkout)
            <div class="rounded-xl overflow-hidden mb-4 border"
                 :style="faceStatus === 'verified' ? 'border-color:rgba(16,185,129,0.2)' : 'border-color:rgba(124,58,237,0.12)'">
                <div x-show="faceStatus === 'idle' || faceStatus === 'error'"
                     class="flex flex-col items-center py-6 gap-2" style="background:rgba(124,58,237,0.04)">
                    <p class="text-sm font-semibold" style="color:var(--fl-text-h,#1a0a3d)">Verifikasi Wajah untuk Check-Out</p>
                    <template x-if="faceStatus === 'error' && faceErrorMsg">
                        <p class="text-xs font-medium px-4 text-center" style="color:#dc2626" x-text="faceErrorMsg"></p>
                    </template>
                    <button type="button" @click="startFaceVerification()"
                            class="mt-1 px-5 py-2 rounded-xl font-semibold text-sm"
                            style="background:var(--hris-gradient);color:#fff">
                        Mulai Kamera
                    </button>
                </div>
                <div x-show="faceStatus === 'loading'" class="flex items-center justify-center py-6 gap-3" style="background:rgba(124,58,237,0.04)">
                    <svg class="w-6 h-6 animate-spin" style="color:#7c3aed" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <span class="text-sm" style="color:var(--fl-text-muted,#6b7280)">Memuat AI...</span>
                </div>
                <div x-show="faceStatus === 'detecting'" class="relative bg-black" style="aspect-ratio:16/9">
                    <video id="checkin-video" autoplay muted playsinline class="w-full h-full object-cover" style="transform:scaleX(-1)"></video>
                    <canvas id="checkin-overlay" class="absolute inset-0 w-full h-full" style="transform:scaleX(-1)"></canvas>
                </div>
                <div x-show="faceStatus === 'verified'" class="flex items-center gap-3 p-4" style="background:rgba(16,185,129,0.08)">
                    <svg class="w-5 h-5" style="color:#10b981" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span class="text-sm font-semibold" style="color:#10b981">Wajah Terverifikasi untuk Check-Out</span>
                </div>
            </div>
            @endif

            <form action="{{ route('hris.absensi.checkout') }}" method="POST">
                @csrf
                <input type="hidden" name="lat" x-model="gpsLat">
                <input type="hidden" name="lng" x-model="gpsLng">
                <input type="hidden" name="face_verified" :value="faceStatus === 'verified' ? '1' : '0'">
                <button type="submit"
                        :disabled="!canCheckOut"
                        class="w-full py-3.5 rounded-xl font-bold text-white text-sm transition-all disabled:opacity-40 disabled:cursor-not-allowed hover:-translate-y-0.5 active:translate-y-0"
                        style="background:linear-gradient(135deg,#2563eb,#1d4ed8);box-shadow:0 4px 16px rgba(37,99,235,0.35)">
                    {{ $attendanceState === 'checkout_2' ? 'Check Out Sesi 2 Sekarang' : 'Check Out Sekarang' }}
                </button>
            </form>

            @else
            <div class="flex items-center justify-center gap-3 py-4 rounded-xl" style="background:rgba(16,185,129,0.06);border:1px solid rgba(16,185,129,0.12)">
                <svg class="w-5 h-5 shrink-0" style="color:#10b981" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <p class="text-sm font-semibold" style="color:#10b981">Absensi hari ini sudah selesai.</p>
            </div>
            @endif
        </div>
    </div>

    {{-- ── Riwayat Bulan Ini ───────────────────────────────────────────── --}}
    <div class="rounded-2xl border overflow-hidden"
         style="background:var(--fl-card-bg,#fff);border-color:var(--fl-card-border,#ede9fe)">
        <div class="flex items-center justify-between px-5 py-4 border-b" style="border-color:var(--fl-card-border,#ede9fe)">
            <h2 class="font-semibold text-sm" style="color:var(--fl-text-h,#1a0a3d)">Riwayat Bulan Ini</h2>
            <span class="text-xs" style="color:var(--fl-text-subtle,#9ca3af)">{{ $bulan->count() }} hari tercatat</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr style="background:var(--fl-search-bg,#f5f3ff)">
                        <th class="px-4 py-2.5 text-left text-xs font-bold uppercase tracking-wider" style="color:var(--fl-text-subtle,#9ca3af)">Tanggal</th>
                        <th class="px-4 py-2.5 text-left text-xs font-bold uppercase tracking-wider" style="color:var(--fl-text-subtle,#9ca3af)">Masuk</th>
                        <th class="px-4 py-2.5 text-left text-xs font-bold uppercase tracking-wider" style="color:var(--fl-text-subtle,#9ca3af)">Keluar</th>
                        <th class="px-4 py-2.5 text-left text-xs font-bold uppercase tracking-wider" style="color:var(--fl-text-subtle,#9ca3af)">Durasi</th>
                        <th class="px-4 py-2.5 text-left text-xs font-bold uppercase tracking-wider" style="color:var(--fl-text-subtle,#9ca3af)">Status</th>
                        @if($setting->is_face_recognition_enabled)
                        <th class="px-4 py-2.5 text-center text-xs font-bold uppercase tracking-wider" style="color:var(--fl-text-subtle,#9ca3af)">Wajah</th>
                        @endif
                        @if($setting->is_location_enabled)
                        <th class="px-4 py-2.5 text-left text-xs font-bold uppercase tracking-wider" style="color:var(--fl-text-subtle,#9ca3af)">Jarak</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($bulan as $row)
                    @php
                        $colors = [
                            'hadir' => ['bg'=>'rgba(16,185,129,0.1)','text'=>'#10b981','border'=>'rgba(16,185,129,0.2)','label'=>'Hadir'],
                            'alpha' => ['bg'=>'rgba(239,68,68,0.1)','text'=>'#ef4444','border'=>'rgba(239,68,68,0.2)','label'=>'Alpha'],
                            'izin'  => ['bg'=>'rgba(245,158,11,0.1)','text'=>'#f59e0b','border'=>'rgba(245,158,11,0.2)','label'=>'Izin'],
                            'sakit' => ['bg'=>'rgba(59,130,246,0.1)','text'=>'#3b82f6','border'=>'rgba(59,130,246,0.2)','label'=>'Sakit'],
                            'cuti'  => ['bg'=>'rgba(124,58,237,0.1)','text'=>'#7c3aed','border'=>'rgba(124,58,237,0.2)','label'=>'Cuti'],
                            'libur' => ['bg'=>'rgba(107,114,128,0.1)','text'=>'#6b7280','border'=>'rgba(107,114,128,0.2)','label'=>'Libur'],
                        ];
                        $c = $colors[$row->status] ?? $colors['alpha'];
                        $mins = $row->workMinutes();
                    @endphp
                    <tr class="border-b transition-all" style="border-color:var(--fl-card-border,#ede9fe)" onmouseover="this.style.background='var(--fl-search-bg,#f5f3ff)'" onmouseout="this.style.background=''">
                        <td class="px-4 py-3 text-xs font-medium" style="color:var(--fl-text-body,#374151)">
                            {{ $row->date->locale('id')->isoFormat('ddd, D MMM') }}
                        </td>
                        <td class="px-4 py-3 text-sm font-semibold" style="color:var(--fl-text-h,#1a0a3d)">
                            {{ $row->check_in ?? '—' }}
                            @if($row->check_in_2)<span class="block text-xs font-normal" style="color:var(--fl-text-muted,#6b7280)">+ {{ $row->check_in_2 }}</span>@endif
                        </td>
                        <td class="px-4 py-3 text-sm font-semibold" style="color:var(--fl-text-h,#1a0a3d)">
                            {{ $row->check_out ?? '—' }}
                            @if($row->check_out_2)<span class="block text-xs font-normal" style="color:var(--fl-text-muted,#6b7280)">+ {{ $row->check_out_2 }}</span>@endif
                        </td>
                        <td class="px-4 py-3 text-xs" style="color:var(--fl-text-muted,#6b7280)">
                            @if($mins > 0){{ intdiv($mins,60) }}j {{ $mins%60 }}m @else — @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-xs px-2.5 py-1 rounded-full font-semibold"
                                  style="background:{{ $c['bg'] }};color:{{ $c['text'] }};border:1px solid {{ $c['border'] }}">
                                {{ $c['label'] }}
                            </span>
                        </td>
                        @if($setting->is_face_recognition_enabled)
                        <td class="px-4 py-3 text-center">
                            @if($row->face_verified_in)
                                <svg class="w-4 h-4 inline" style="color:#10b981" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            @else
                                <span style="color:var(--fl-text-subtle,#9ca3af)">—</span>
                            @endif
                        </td>
                        @endif
                        @if($setting->is_location_enabled)
                        <td class="px-4 py-3 text-xs" style="color:var(--fl-text-muted,#6b7280)">
                            {{ $row->distance_in !== null ? $row->distance_in.'m' : '—' }}
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="px-4 py-10 text-center text-sm" style="color:var(--fl-text-subtle,#9ca3af)">
                            Belum ada data absensi bulan ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Self Face Enrollment Modal ──────────────────────────────────────── --}}
    @if($setting->is_face_recognition_enabled && !auth()->user()->face_descriptor)
    <div x-show="enrollOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background:rgba(0,0,0,0.7);backdrop-filter:blur(4px)">
        <div class="relative w-full max-w-md rounded-2xl overflow-hidden shadow-2xl"
             style="background:var(--fl-card-bg,#fff);border:1px solid var(--fl-card-border,#ede9fe)"
             @click.stop>

            <div class="flex items-center justify-between px-6 py-4 border-b" style="border-color:var(--fl-card-border,#ede9fe)">
                <div>
                    <p class="font-semibold" style="color:var(--fl-text-h,#1a0a3d)">Daftarkan Wajah Saya</p>
                    <p class="text-xs mt-0.5" style="color:var(--fl-text-muted,#6b7280)">Data wajah tersimpan aman dan hanya digunakan untuk verifikasi absensi.</p>
                </div>
                <button @click="closeEnroll()" class="p-1.5 rounded-lg transition-all" style="color:var(--fl-text-muted,#6b7280)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="p-6 space-y-4">
                <div class="relative rounded-xl overflow-hidden bg-black" style="aspect-ratio:4/3">
                    <video id="enroll-video" autoplay muted playsinline
                           class="w-full h-full object-cover" style="transform:scaleX(-1)"></video>
                    <canvas id="enroll-overlay" class="absolute inset-0 w-full h-full" style="transform:scaleX(-1)"></canvas>

                    <div x-show="enrollStatus === 'loading'"
                         class="absolute inset-0 flex flex-col items-center justify-center"
                         style="background:rgba(0,0,0,0.6)">
                        <svg class="w-8 h-8 animate-spin text-white mb-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        <p class="text-white text-sm">Memuat model AI...</p>
                    </div>

                    <div x-show="enrollStatus === 'done'"
                         class="absolute inset-0 flex flex-col items-center justify-center"
                         style="background:rgba(16,185,129,0.2);backdrop-filter:blur(2px)">
                        <div class="w-16 h-16 rounded-full flex items-center justify-center mb-3"
                             style="background:rgba(16,185,129,0.9)">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <p class="text-white font-bold text-lg">Wajah Terdaftar!</p>
                    </div>

                    <div x-show="enrollStatus === 'ready'"
                         class="absolute bottom-3 left-0 right-0 text-center">
                        <span class="text-xs px-3 py-1 rounded-full text-white"
                              :style="enrollMultiWarning ? 'background:rgba(220,38,38,0.85)' : 'background:rgba(0,0,0,0.5)'"
                              x-text="enrollMultiWarning ? 'Terdeteksi lebih dari 1 wajah — pastikan cuma 1 orang' : (captureCount > 0 ? captureCount + '/3 frame diambil...' : 'Hadapkan wajah ke kamera')"></span>
                    </div>
                </div>

                <div class="text-xs p-3 rounded-xl" style="background:rgba(124,58,237,0.06);color:var(--fl-text-muted,#6b7280)">
                    <strong style="color:#7c3aed">Cara pendaftaran:</strong> Hadapkan wajah ke kamera dengan pencahayaan yang baik, pastikan cuma 1 orang di depan kamera. Sistem akan mengambil 3 frame dan menghitung descriptor wajah rata-rata.
                </div>

                <div class="flex gap-3">
                    <button type="button" @click="captureEnroll()"
                            :disabled="enrollStatus !== 'ready' || captureCount >= 3 || enrollMultiWarning"
                            class="flex-1 py-2.5 rounded-xl font-semibold text-sm transition-all disabled:opacity-40"
                            style="background:var(--hris-gradient);color:#fff;box-shadow:0 4px 12px rgba(109,40,217,0.3)">
                        <span x-text="captureCount < 3 ? 'Ambil Frame (' + captureCount + '/3)' : 'Menyimpan...'"></span>
                    </button>
                    <button type="button" @click="closeEnroll()"
                            class="px-5 py-2.5 rounded-xl font-medium text-sm transition-all"
                            style="background:var(--fl-search-bg,#f5f3ff);color:var(--fl-text-muted,#6b7280);border:1px solid var(--fl-card-border,#ede9fe)">
                        Batal
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
function absensiPage(locEnabled, faceEnabled, officeLat, officeLng, maxDist, faceThreshold, locCheckout, faceCheckout, storedDescriptor) {
    return {
        // Location state
        locEnabled,
        gpsLat: '', gpsLng: '', gpsAddress: '',
        locStatus: 'idle',   // idle | detecting | valid | invalid
        locMsg: '',
        locDistance: 0,

        // Face state
        faceEnabled,
        faceStatus: 'idle',  // idle | loading | detecting | verified | error
        faceErrorMsg: '',
        faceDetected: false,
        faceMatch: false,
        faceConf: 0,
        faceMultiWarning: false,
        faceStream: null,
        faceLoop: null,
        faceApiLoaded: false,
        referenceDescriptor: storedDescriptor ? new Float32Array(JSON.parse(storedDescriptor)) : null,

        checkingIn: false,

        // Self face enrollment state
        enrollOpen:    false,
        enrollStatus:  'idle', // idle | loading | ready | done | error
        captureCount:  0,
        capturedDescs: [],
        enrollStream:  null,
        enrollLoop:    null,
        enrollMultiWarning: false,

        get canCheckIn() {
            const locOk  = !this.locEnabled  || this.locStatus === 'valid';
            const faceOk = !this.faceEnabled || this.faceStatus === 'verified';
            return locOk && faceOk;
        },

        get canCheckOut() {
            const locOk  = !locCheckout  || this.locStatus === 'valid';
            const faceOk = !faceCheckout || this.faceStatus === 'verified';
            return locOk && faceOk;
        },

        async init() {
            if (this.locEnabled) this.detectLocation();
        },

        detectLocation() {
            if (!navigator.geolocation) {
                this.locStatus = 'invalid';
                this.locMsg = 'GPS tidak tersedia di perangkat ini.';
                return;
            }
            this.locStatus = 'detecting';
            this.locMsg = 'Mendeteksi lokasi GPS Anda...';
            navigator.geolocation.getCurrentPosition(
                async pos => {
                    this.gpsLat = pos.coords.latitude;
                    this.gpsLng = pos.coords.longitude;

                    if (officeLat && officeLng) {
                        this.locDistance = this.haversine(pos.coords.latitude, pos.coords.longitude, officeLat, officeLng);
                        if (this.locDistance <= maxDist) {
                            this.locStatus = 'valid';
                            this.locMsg    = `Lokasi valid — ${this.locDistance}m dari kantor (maks ${maxDist}m)`;
                        } else {
                            this.locStatus = 'invalid';
                            this.locMsg    = `Terlalu jauh dari kantor: ${this.locDistance}m (maks ${maxDist}m)`;
                        }
                    } else {
                        this.locStatus = 'valid';
                        this.locMsg    = `Lokasi terdeteksi (±${Math.round(pos.coords.accuracy)}m akurasi)`;
                    }

                    // Try reverse geocode (no API key needed for Nominatim)
                    try {
                        const r = await fetch(`https://nominatim.openstreetmap.org/reverse?lat=${pos.coords.latitude}&lon=${pos.coords.longitude}&format=json`);
                        const d = await r.json();
                        this.gpsAddress = d.display_name?.substring(0, 120) || '';
                    } catch(_) {}
                },
                err => {
                    this.locStatus = 'invalid';
                    this.locMsg = err.code === 1 ? 'Izin lokasi ditolak. Aktifkan GPS di browser.' : 'Gagal mendapatkan lokasi.';
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        },

        haversine(lat1, lng1, lat2, lng2) {
            const R = 6371000;
            const φ1 = lat1 * Math.PI / 180, φ2 = lat2 * Math.PI / 180;
            const Δφ = (lat2 - lat1) * Math.PI / 180;
            const Δλ = (lng2 - lng1) * Math.PI / 180;
            const a  = Math.sin(Δφ/2)**2 + Math.cos(φ1)*Math.cos(φ2)*Math.sin(Δλ/2)**2;
            return Math.round(R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)));
        },

        async startFaceVerification() {
            if (!this.referenceDescriptor) {
                this.faceErrorMsg = 'Wajah Anda belum terdaftar. Daftarkan wajah Anda terlebih dahulu.';
                this.faceStatus = 'error';
                return;
            }
            this.faceStatus = 'loading';
            try {
                await this.loadFaceModels();
            } catch (e) {
                this.faceErrorMsg = 'Gagal memuat model pengenalan wajah. Periksa koneksi internet Anda dan coba lagi.';
                this.faceStatus = 'error';
                return;
            }
            const opened = await this.openCamera();
            if (!opened) return;
            this.faceStatus = 'detecting';
            this.runFaceLoop();
        },

        async loadFaceModels() {
            if (this.faceApiLoaded) return;
            const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model';
            await Promise.all([
                faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                faceapi.nets.faceLandmark68TinyNet.loadFromUri(MODEL_URL),
                faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL),
            ]);
            this.faceApiLoaded = true;
        },

        async openCamera() {
            try {
                this.faceStream = await navigator.mediaDevices.getUserMedia({
                    video: { width: 640, height: 480, facingMode: 'user' }
                });
                await this.$nextTick();
                const video = document.getElementById('checkin-video');
                if (video) {
                    video.srcObject = this.faceStream;
                    await new Promise(r => video.onloadedmetadata = r);
                }
                return true;
            } catch(e) {
                this.faceErrorMsg = e.name === 'NotAllowedError'
                    ? 'Izin kamera ditolak. Aktifkan izin kamera untuk situs ini di pengaturan browser Anda, lalu coba lagi.'
                    : e.name === 'NotFoundError'
                    ? 'Kamera tidak ditemukan di perangkat ini.'
                    : 'Gagal mengakses kamera. Coba refresh halaman atau gunakan perangkat lain.';
                this.faceStatus = 'error';
                return false;
            }
        },

        runFaceLoop() {
            clearInterval(this.faceLoop);
            this.faceLoop = setInterval(async () => {
                const video  = document.getElementById('checkin-video');
                const canvas = document.getElementById('checkin-overlay');
                if (!video || video.readyState < 2 || !canvas) return;

                const results = await faceapi
                    .detectAllFaces(video, new faceapi.TinyFaceDetectorOptions({ scoreThreshold: 0.4 }))
                    .withFaceLandmarks(true)
                    .withFaceDescriptors();

                const ctx = canvas.getContext('2d');
                canvas.width  = video.videoWidth;
                canvas.height = video.videoHeight;
                ctx.clearRect(0, 0, canvas.width, canvas.height);

                // Lebih dari 1 wajah kedeteksi (mis. ada orang lain di belakang/samping)
                // — tolak verifikasi sama sekali, jangan sampai wajah orang lain
                // ketebak cocok gara-gara ada beberapa wajah dalam satu frame.
                if (results.length > 1) {
                    this.faceMultiWarning = true;
                    this.faceDetected = false;
                    this.faceMatch = false;
                    this.faceConf = 0;
                    results.forEach(r => {
                        const box = r.detection.box;
                        ctx.strokeStyle = '#ef4444';
                        ctx.lineWidth   = 3;
                        ctx.shadowColor = '#ef4444';
                        ctx.shadowBlur  = 8;
                        ctx.strokeRect(box.x, box.y, box.width, box.height);
                    });
                    return;
                }
                this.faceMultiWarning = false;

                const det = results[0] ?? null;

                if (det) {
                    this.faceDetected = true;
                    const dist = faceapi.euclideanDistance(det.descriptor, this.referenceDescriptor);
                    this.faceConf = Math.max(0, Math.min(1, 1 - (dist / faceThreshold)));

                    // Draw face box
                    const box = det.detection.box;
                    const matched = dist < faceThreshold;
                    this.faceMatch = matched;
                    ctx.strokeStyle = matched ? '#10b981' : '#a78bfa';
                    ctx.lineWidth   = 3;
                    ctx.shadowColor = matched ? '#10b981' : '#7c3aed';
                    ctx.shadowBlur  = 8;
                    ctx.strokeRect(box.x, box.y, box.width, box.height);

                    if (matched && this.faceConf > 0.6) {
                        clearInterval(this.faceLoop);
                        this.faceStatus = 'verified';
                        if (this.faceStream) this.faceStream.getTracks().forEach(t => t.stop());
                    }
                } else {
                    this.faceDetected = false;
                    this.faceMatch = false;
                    this.faceConf = 0;
                }
            }, 200);
        },

        resetFace() {
            clearInterval(this.faceLoop);
            if (this.faceStream) this.faceStream.getTracks().forEach(t => t.stop());
            this.faceStatus = 'idle';
            this.faceErrorMsg = '';
            this.faceDetected = false;
            this.faceMatch = false;
            this.faceConf = 0;
            this.faceMultiWarning = false;
        },

        // ── Self face enrollment ─────────────────────────────────────────
        async openEnroll() {
            this.enrollOpen   = true;
            this.enrollStatus = 'loading';
            this.captureCount = 0;
            this.capturedDescs= [];
            this.enrollMultiWarning = false;

            await this.$nextTick();
            await this.loadFaceModels();
            await this.startEnrollCamera();
            this.enrollStatus = 'ready';
            this.runEnrollDetectionLoop();
        },

        closeEnroll() {
            clearInterval(this.enrollLoop);
            if (this.enrollStream) {
                this.enrollStream.getTracks().forEach(t => t.stop());
                this.enrollStream = null;
            }
            this.enrollOpen   = false;
            this.enrollStatus = 'idle';
        },

        async startEnrollCamera() {
            try {
                this.enrollStream = await navigator.mediaDevices.getUserMedia({
                    video: { width: 640, height: 480, facingMode: 'user' }
                });
                const video = document.getElementById('enroll-video');
                video.srcObject = this.enrollStream;
                await new Promise(r => video.onloadedmetadata = r);
            } catch(e) {
                this.enrollStatus = 'error';
            }
        },

        runEnrollDetectionLoop() {
            const video  = document.getElementById('enroll-video');
            const canvas = document.getElementById('enroll-overlay');
            clearInterval(this.enrollLoop);
            this.enrollLoop = setInterval(async () => {
                if (!video || video.readyState < 2) return;
                const dets = await faceapi
                    .detectAllFaces(video, new faceapi.TinyFaceDetectorOptions({ scoreThreshold: 0.4 }))
                    .withFaceLandmarks(true);
                const ctx = canvas.getContext('2d');
                canvas.width  = video.videoWidth;
                canvas.height = video.videoHeight;
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                this.enrollMultiWarning = dets.length > 1;
                dets.forEach(det => {
                    const box = det.detection.box;
                    ctx.strokeStyle = this.enrollMultiWarning ? '#ef4444' : '#10b981';
                    ctx.lineWidth = 3;
                    ctx.strokeRect(box.x, box.y, box.width, box.height);
                    ctx.fillStyle = this.enrollMultiWarning ? 'rgba(239,68,68,0.2)' : 'rgba(16,185,129,0.2)';
                    ctx.fillRect(box.x, box.y, box.width, box.height);
                });
            }, 150);
        },

        async captureEnroll() {
            if (this.enrollStatus !== 'ready' || this.captureCount >= 3) return;
            const video   = document.getElementById('enroll-video');
            const results = await faceapi
                .detectAllFaces(video, new faceapi.TinyFaceDetectorOptions({ scoreThreshold: 0.5 }))
                .withFaceLandmarks(true)
                .withFaceDescriptors();

            if (results.length === 0) {
                Swal.fire({ toast: true, position: 'top-end', icon: 'warning',
                    title: 'Wajah tidak terdeteksi. Pastikan wajah terlihat jelas.',
                    showConfirmButton: false, timer: 2500,
                    background: '#d97706', color: '#fff', iconColor: '#fff' });
                return;
            }
            if (results.length > 1) {
                Swal.fire({ toast: true, position: 'top-end', icon: 'error',
                    title: 'Terdeteksi ' + results.length + ' wajah. Pastikan cuma 1 orang di depan kamera.',
                    showConfirmButton: false, timer: 3000,
                    background: '#dc2626', color: '#fff', iconColor: '#fff' });
                return;
            }

            const det = results[0];
            this.capturedDescs.push(Array.from(det.descriptor));
            this.captureCount++;

            if (this.captureCount === 3) {
                await this.saveEnrollment();
            }
        },

        async saveEnrollment() {
            const avg = this.capturedDescs[0].map((_, i) =>
                (this.capturedDescs[0][i] + this.capturedDescs[1][i] + this.capturedDescs[2][i]) / 3
            );

            clearInterval(this.enrollLoop);
            this.enrollStatus = 'loading';

            try {
                const resp = await fetch('{{ route('hris.absensi.enroll-face', auth()->id()) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ descriptor: JSON.stringify(avg) }),
                });
                const data = await resp.json();
                if (resp.ok) {
                    this.enrollStatus = 'done';
                    setTimeout(() => { this.closeEnroll(); location.reload(); }, 1800);
                } else {
                    throw new Error(data.message || 'Gagal menyimpan.');
                }
            } catch(e) {
                this.enrollStatus = 'error';
                Swal.fire({ icon: 'error', title: 'Gagal', text: e.message, confirmButtonColor: '#7c3aed' });
            }
        },
    };
}
</script>
@endpush
