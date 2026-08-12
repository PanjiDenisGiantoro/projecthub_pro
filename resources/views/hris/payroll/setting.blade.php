@extends('layouts.app')
@section('title', 'Pengaturan PPh 21')
@section('page-title', 'Pengaturan PPh 21')

@section('content')
<div class="max-w-2xl mx-auto pt-5 space-y-6" x-data="{ method: '{{ old('method', $setting->method) }}' }">

    <div class="flex items-center gap-3">
        <a href="{{ route('hris.payroll.index') }}" class="text-gray-400 hover:text-gray-700">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Pengaturan PPh 21</h1>
            <p class="text-sm text-gray-500 mt-0.5">Pilih metode perhitungan potongan pajak penghasilan karyawan untuk perusahaan ini.</p>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-xl px-4 py-3">{{ session('success') }}</div>
    @endif

    <form action="{{ route('hris.payroll.setting.save') }}" method="POST" class="space-y-4">
        @csrf

        <label class="block rounded-2xl border-2 p-5 cursor-pointer transition-all"
               :class="method === 'progresif' ? 'border-violet-500 bg-violet-50' : 'border-gray-200 bg-white hover:border-gray-300'">
            <div class="flex items-start gap-3">
                <input type="radio" name="method" value="progresif" x-model="method" class="mt-1 accent-violet-600">
                <div>
                    <p class="font-semibold text-gray-900">Metode Progresif (Lama)</p>
                    <p class="text-sm text-gray-500 mt-1">
                        Proyeksi gaji setahun → kurangi biaya jabatan & PTKP → kena tarif berlapis (Pasal 17).
                        Hasil setahun dibagi 12 untuk potongan bulanan.
                    </p>
                </div>
            </div>
        </label>

        <label class="block rounded-2xl border-2 p-5 cursor-pointer transition-all"
               :class="method === 'ter' ? 'border-violet-500 bg-violet-50' : 'border-gray-200 bg-white hover:border-gray-300'">
            <div class="flex items-start gap-3">
                <input type="radio" name="method" value="ter" x-model="method" class="mt-1 accent-violet-600">
                <div>
                    <p class="font-semibold text-gray-900">Metode TER (Tarif Efektif Rata-rata)</p>
                    <p class="text-sm text-gray-500 mt-1">
                        Wajib sejak Januari 2024 (PP 58/2023 & PMK 168/2023). Bruto bulan berjalan langsung
                        dikalikan tarif TER (kategori A/B/C sesuai status PTKP) — tanpa proyeksi setahun.
                    </p>
                    <div class="mt-3 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 text-xs text-amber-800">
                        Masa pajak Desember tetap otomatis dihitung ulang pakai metode progresif
                        (rekonsiliasi tahunan) — sistem akan bandingkan total yang sudah dipotong Jan–Nov
                        dengan pajak riil setahun, lalu sesuaikan di payroll Desember.
                    </div>
                </div>
            </div>
        </label>

        <div class="bg-blue-50 border border-blue-200 rounded-xl px-4 py-3 text-xs text-blue-800">
            Tabel tarif TER (Kategori A/B/C) bisa dilihat & disesuaikan di
            <a href="{{ route('hris.master.index', ['tab' => 'tax-ter']) }}" class="font-semibold underline">Master Data HRIS → Tarif TER</a>.
        </div>

        <button type="submit"
                class="w-full py-2.5 rounded-xl font-semibold text-white text-sm"
                style="background:linear-gradient(135deg,#7c3aed,#6d28d9)">
            Simpan Pengaturan
        </button>
    </form>
</div>
@endsection
