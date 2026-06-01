@extends('layouts.app')
@section('title', 'Input Gaji Baru')
@section('page-title', 'Input Gaji Karyawan')

@section('content')
<div class="max-w-2xl mx-auto pt-5 space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('hris.salary.index', $user) }}" class="text-gray-400 hover:text-gray-700">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Input Gaji Baru</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ $user->name }}</p>
        </div>
    </div>

    @if($latest)
    <div class="bg-blue-50 border border-blue-200 text-blue-800 text-sm rounded-xl px-4 py-3">
        Gaji aktif saat ini: <strong>Rp {{ number_format($latest->gaji_pokok, 0, ',', '.') }}</strong>
        (berlaku sejak {{ $latest->effective_date->format('d M Y') }}).
        Data baru akan menggantikan gaji aktif mulai tanggal efektif yang dipilih.
    </div>
    @endif

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <form action="{{ route('hris.salary.store', $user) }}" method="POST" class="space-y-5">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Berlaku Sejak (Tanggal Efektif) *</label>
                <input type="date" name="effective_date" value="{{ old('effective_date', today()->format('Y-m-d')) }}" required
                       class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-violet-500 focus:outline-none">
                @error('effective_date')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <hr class="border-gray-100">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Komponen Gaji</p>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Gaji Pokok (Rp) *</label>
                    <input type="number" name="gaji_pokok" value="{{ old('gaji_pokok', $latest?->gaji_pokok) }}" required min="0" step="50000"
                           class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-violet-500 focus:outline-none">
                    @error('gaji_pokok')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tunjangan Jabatan (Rp)</label>
                    <input type="number" name="tunjangan_jabatan" value="{{ old('tunjangan_jabatan', $latest?->tunjangan_jabatan ?? 0) }}" min="0" step="50000"
                           class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-violet-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tunjangan Transport (Rp)</label>
                    <input type="number" name="tunjangan_transport" value="{{ old('tunjangan_transport', $latest?->tunjangan_transport ?? 0) }}" min="0" step="50000"
                           class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-violet-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tunjangan Makan (Rp)</label>
                    <input type="number" name="tunjangan_makan" value="{{ old('tunjangan_makan', $latest?->tunjangan_makan ?? 0) }}" min="0" step="50000"
                           class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-violet-500 focus:outline-none">
                </div>
            </div>

            <hr class="border-gray-100">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Pajak & BPJS</p>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status Pajak (PTKP) *</label>
                    <select name="status_pajak" required class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-violet-500 focus:outline-none">
                        @foreach($statusOptions as $code => $label)
                        <option value="{{ $code }}" @selected(old('status_pajak', $latest?->status_pajak ?? 'TK/0') === $code)>
                            {{ $code }} — {{ $label }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">NPWP</label>
                    <input type="text" name="npwp" value="{{ old('npwp', $latest?->npwp) }}" maxlength="20" placeholder="XX.XXX.XXX.X-XXX.XXX"
                           class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-violet-500 focus:outline-none">
                    <p class="text-xs text-gray-400 mt-1">Kosongkan jika tidak punya (akan dikenakan tarif +20%)</p>
                </div>
            </div>

            <div class="flex gap-6">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="bpjs_kesehatan" value="1"
                           {{ old('bpjs_kesehatan', $latest?->bpjs_kesehatan ?? true) ? 'checked' : '' }}
                           class="w-4 h-4 text-violet-600 rounded">
                    <span class="text-sm text-gray-700">BPJS Kesehatan (1%)</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="bpjs_ketenagakerjaan" value="1"
                           {{ old('bpjs_ketenagakerjaan', $latest?->bpjs_ketenagakerjaan ?? true) ? 'checked' : '' }}
                           class="w-4 h-4 text-violet-600 rounded">
                    <span class="text-sm text-gray-700">BPJS Ketenagakerjaan (JHT 2% + JP 1%)</span>
                </label>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="flex-1 py-2.5 rounded-xl font-semibold text-white text-sm"
                        style="background:linear-gradient(135deg,#7c3aed,#6d28d9)">
                    Simpan Data Gaji
                </button>
                <a href="{{ route('hris.salary.index', $user) }}"
                   class="px-6 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
