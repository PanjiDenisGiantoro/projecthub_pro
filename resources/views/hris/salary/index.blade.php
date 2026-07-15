@extends('layouts.app')
@section('title', 'Data Gaji — ' . $user->name)
@section('page-title', 'Data Gaji Karyawan')

@section('content')
<div class="space-y-6 pt-5">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('users.index') }}" class="text-gray-400 hover:text-gray-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Data Gaji</h1>
                <p class="text-sm text-gray-500 mt-0.5">{{ $user->name }} &mdash; {{ $user->email }}</p>
            </div>
        </div>
        <a href="{{ route('hris.salary.create', $user) }}"
           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white rounded-xl"
           style="background:linear-gradient(135deg,#7c3aed,#6d28d9)">
            + Input Gaji Baru
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-xl px-4 py-3">{{ session('success') }}</div>
    @endif

    {{-- Salary aktif sekarang --}}
    @php $current = $salaries->first(); @endphp
    @if($current)
    <div class="bg-white rounded-2xl border border-violet-200 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-gray-900">Gaji Aktif Saat Ini</h2>
            <span class="text-xs text-violet-700 bg-violet-100 px-2 py-0.5 rounded-full font-medium">
                Efektif: {{ $current->effective_date->format('d M Y') }}
            </span>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            @foreach([
                ['Gaji Pokok', $current->gaji_pokok],
                ['Tunjangan Transport', $current->tunjangan_transport],
                ['Tunjangan Makan', $current->tunjangan_makan],
                ['Tunjangan Jabatan', $current->tunjangan_jabatan],
            ] as [$label, $val])
            <div class="bg-gray-50 rounded-xl p-3">
                <p class="text-xs text-gray-500 mb-0.5">{{ $label }}</p>
                <p class="font-semibold text-gray-900">Rp {{ number_format($val, 0, ',', '.') }}</p>
            </div>
            @endforeach
        </div>
        <div class="mt-3 flex flex-wrap gap-4 text-sm">
            <span class="text-gray-600">Status Pajak: <strong>{{ $current->status_pajak }}</strong></span>
            <span class="text-gray-600">NPWP: <strong>{{ $current->npwp ?: '—' }}</strong></span>
            <span class="{{ $current->bpjs_kesehatan ? 'text-green-600' : 'text-gray-400' }}">
                BPJS Kes: {{ $current->bpjs_kesehatan ? '✓' : '✗' }}
            </span>
            <span class="{{ $current->bpjs_ketenagakerjaan ? 'text-green-600' : 'text-gray-400' }}">
                BPJS TK: {{ $current->bpjs_ketenagakerjaan ? '✓' : '✗' }}
            </span>
        </div>
    </div>
    @else
    <div class="bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-xl px-4 py-3">
        Belum ada data gaji untuk karyawan ini. Klik "Input Gaji Baru" untuk menambahkan.
    </div>
    @endif

    {{-- Riwayat Gaji --}}
    @if($salaries->count() > 0)
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
        <div class="p-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-900">Riwayat Perubahan Gaji</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Berlaku Sejak</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Gaji Pokok</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Tunjangan</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Total Bruto</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Pajak</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($salaries as $s)
                    @php
                        $tunjangan = $s->tunjangan_transport + $s->tunjangan_makan + $s->tunjangan_jabatan;
                        $bruto = $s->gaji_pokok + $tunjangan;
                    @endphp
                    <tr class="{{ $loop->first ? 'bg-violet-50' : '' }}">
                        <td class="px-4 py-3">
                            <span class="font-medium text-gray-900">{{ $s->effective_date->format('d M Y') }}</span>
                            @if($loop->first)
                            <span class="ml-2 text-[10px] bg-violet-200 text-violet-700 px-1.5 py-0.5 rounded-full font-semibold">Aktif</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right font-medium text-gray-900">Rp {{ number_format($s->gaji_pokok, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">Rp {{ number_format($tunjangan, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-gray-900">Rp {{ number_format($bruto, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 font-mono font-medium">{{ $s->status_pajak }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-3">
                                <a href="{{ route('hris.salary.edit', [$user, $s]) }}"
                                   class="text-xs text-violet-600 hover:text-violet-800">Edit</a>
                                @if(!$loop->first)
                                <form action="{{ route('hris.salary.destroy', [$user, $s]) }}" method="POST" class="inline" onsubmit="return confirm('Hapus data gaji ini?')">
                                    @csrf @method('DELETE')
                                    <button class="text-xs text-red-500 hover:text-red-700">Hapus</button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>
@endsection
