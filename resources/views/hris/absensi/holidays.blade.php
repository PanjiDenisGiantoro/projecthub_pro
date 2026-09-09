@extends('layouts.app')
@section('title', 'Hari Libur')
@section('page-title', 'Hari Libur')

@section('content')
<div class="space-y-6 pt-5" x-data="{ formOpen: {{ $errors->any() ? 'true' : 'false' }} }">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4 flex-wrap">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest" style="color:var(--lav-600)">Konfigurasi HRIS</p>
            <h1 class="font-display text-2xl font-extrabold" style="color:var(--fl-text-h,#1a0a3d)">Hari Libur</h1>
            <p class="text-sm mt-0.5" style="color:var(--fl-text-muted,#6b7280)">Kelola kalender hari libur nasional & perusahaan. Karyawan otomatis diliburkan di tanggal ini, kecuali di-override lewat Jadwal Shift.</p>
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

    <div class="rounded-2xl overflow-hidden border transition-all"
         style="background:var(--fl-card-bg,#fff);border-color:var(--fl-card-border,#ede9fe)">

        <div class="flex items-center justify-between gap-3 px-6 py-4 border-b" style="border-color:var(--fl-card-border,#ede9fe)">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                     style="background:rgba(239,68,68,0.12)">
                    <svg class="w-5 h-5" style="color:#ef4444" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-[15px]" style="color:var(--fl-text-h,#1a0a3d)">Daftar Hari Libur {{ $year }}</p>
                    <p class="text-xs mt-0.5" style="color:var(--fl-text-muted,#6b7280)">Dipakai untuk status "Libur" otomatis di absensi & hitungan rate lembur hari libur.</p>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <form method="GET" action="{{ route('hris.absensi.holidays') }}">
                    <select name="year" onchange="this.form.submit()"
                            class="fl-setting-input px-3 py-2 text-sm rounded-xl border"
                            style="background:var(--fl-search-bg,#f5f3ff);border-color:var(--fl-card-border,#ede9fe);color:var(--fl-text-h,#1a0a3d)">
                        @for($y = now()->year - 1; $y <= now()->year + 1; $y++)
                        <option value="{{ $y }}" @selected($y == $year)>{{ $y }}</option>
                        @endfor
                    </select>
                </form>
                <a href="{{ route('hris.absensi.holidays', ['year' => $year, 'fetch_api' => 1]) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold shrink-0 transition-all border"
                   style="background:var(--fl-card-bg,#fff);border-color:var(--fl-card-border,#ede9fe);color:var(--fl-text-muted,#6b7280)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Ambil dari API
                </a>
                <button type="button" @click="formOpen = !formOpen"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold shrink-0 transition-all"
                        style="background:var(--hris-gradient);color:#fff;box-shadow:0 2px 8px rgba(109,40,217,0.3)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Hari Libur
                </button>
            </div>
        </div>

        @if($apiError)
        <div class="px-6 py-4 border-b text-sm" style="border-color:var(--fl-card-border,#ede9fe);background:#fef2f2;color:#b91c1c">
            {{ $apiError }}
        </div>
        @endif

        @if($apiPreview !== null)
        <div class="px-6 py-4 border-b" style="border-color:var(--fl-card-border,#ede9fe);background:rgba(37,99,235,0.04)">
            <p class="text-xs mb-1" style="color:var(--fl-text-subtle,#9ca3af)">
                Sumber: date.nager.at (API publik). Cuma memuat hari libur dengan tanggal tetap (Tahun Baru, Hari Buruh, Kemerdekaan, Natal, dll) — <strong>hari libur kalender lunar (Idul Fitri, Idul Adha, Nyepi, Waisak, dll) dan cuti bersama tidak tersedia di API ini</strong>, tambahkan manual lewat form "Tambah Hari Libur".
            </p>
            @if($apiPreview->isEmpty())
            <p class="text-sm mt-2" style="color:var(--fl-text-muted,#6b7280)">Tidak ada hari libur baru dari API untuk tahun {{ $year }} (semua sudah tersimpan, atau API tidak punya data tetap untuk tahun ini).</p>
            @else
            <form action="{{ route('hris.absensi.holidays.import') }}" method="POST" class="mt-3 space-y-2">
                @csrf
                <p class="text-xs font-semibold mb-1" style="color:var(--fl-text-muted,#6b7280)">Pilih hari libur yang mau disimpan ({{ $apiPreview->count() }} ditemukan):</p>
                @foreach($apiPreview as $h)
                <label class="flex items-center gap-3 px-3 py-2 rounded-lg cursor-pointer" style="background:var(--fl-card-bg,#fff);border:1px solid var(--fl-card-border,#ede9fe)">
                    <input type="checkbox" name="import[]" value="{{ json_encode($h) }}" checked class="rounded" style="accent-color:#2563eb">
                    <span class="text-sm" style="color:var(--fl-text-h,#1a0a3d)">
                        {{ \Illuminate\Support\Carbon::parse($h['date'])->locale('id')->isoFormat('dddd, D MMMM Y') }} &mdash; {{ $h['name'] }}
                    </span>
                </label>
                @endforeach
                <button type="submit"
                        class="px-5 py-2.5 rounded-xl font-semibold text-sm text-white transition-all"
                        style="background:var(--hris-gradient);box-shadow:0 4px 12px rgba(109,40,217,0.3)">
                    Impor yang Dipilih
                </button>
            </form>
            @endif
        </div>
        @endif

        <div x-show="formOpen" x-cloak class="px-6 py-4 border-b" style="border-color:var(--fl-card-border,#ede9fe);background:var(--fl-search-bg,#f5f3ff)">
            <form action="{{ route('hris.absensi.holidays.store') }}" method="POST" class="flex items-end gap-3 flex-wrap">
                @csrf
                <div>
                    <label class="block text-xs font-semibold mb-1.5" style="color:var(--fl-text-muted,#6b7280)">Tanggal</label>
                    <input type="date" name="date" value="{{ old('date') }}" required
                           class="fl-setting-input px-3 py-2.5 text-sm rounded-xl border transition-all"
                           style="background:var(--fl-card-bg,#fff);border-color:var(--fl-card-border,#ede9fe);color:var(--fl-text-h,#1a0a3d)">
                    @error('date') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex-1 min-w-[220px]">
                    <label class="block text-xs font-semibold mb-1.5" style="color:var(--fl-text-muted,#6b7280)">Nama Hari Libur</label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="cth: Hari Raya Idul Fitri"
                           class="fl-setting-input w-full px-3 py-2.5 text-sm rounded-xl border transition-all"
                           style="background:var(--fl-card-bg,#fff);border-color:var(--fl-card-border,#ede9fe);color:var(--fl-text-h,#1a0a3d)">
                    @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <button type="submit"
                        class="px-5 py-2.5 rounded-xl font-semibold text-sm text-white transition-all"
                        style="background:var(--hris-gradient);box-shadow:0 4px 12px rgba(109,40,217,0.3)">
                    Simpan
                </button>
            </form>
        </div>

        <div class="p-6">
            @if($holidays->isEmpty())
            <p class="text-sm text-center py-6" style="color:var(--fl-text-muted,#6b7280)">Belum ada hari libur yang diatur untuk tahun {{ $year }}. Tambahkan tanggal libur nasional atau libur khusus perusahaan.</p>
            @else
            <div class="space-y-2">
                @foreach($holidays as $holiday)
                <div class="flex items-center gap-3 px-4 py-3 rounded-xl border transition-all"
                     style="background:var(--fl-search-bg,#f5f3ff);border-color:var(--fl-card-border,#ede9fe)">
                    <div class="w-11 h-11 rounded-xl flex flex-col items-center justify-center shrink-0 leading-none"
                         style="background:rgba(239,68,68,0.1);color:#ef4444">
                        <span class="text-sm font-extrabold">{{ $holiday->date->format('d') }}</span>
                        <span class="text-[10px] font-semibold uppercase">{{ $holiday->date->locale('id')->isoFormat('MMM') }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold truncate" style="color:var(--fl-text-h,#1a0a3d)">{{ $holiday->name }}</p>
                        <p class="text-xs mt-0.5" style="color:var(--fl-text-muted,#6b7280)">{{ $holiday->date->locale('id')->isoFormat('dddd, D MMMM Y') }}</p>
                    </div>
                    <form action="{{ route('hris.absensi.holidays.destroy', $holiday) }}" method="POST"
                          onsubmit="return confirm('Hapus hari libur {{ addslashes($holiday->name) }}?');">
                        @csrf @method('DELETE')
                        <button type="submit"
                                class="text-xs p-1.5 rounded-lg font-medium transition-all shrink-0"
                                style="background:rgba(239,68,68,0.08);color:#ef4444;border:1px solid rgba(239,68,68,0.15)">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </form>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
