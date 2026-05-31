@extends('layouts.app')
@section('title', 'Absensi')
@section('page-title', 'Absensi')

@section('content')
<div class="space-y-6 pt-5">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Absensi</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ $today->locale('id')->isoFormat('dddd, D MMMM Y') }}</p>
        </div>
        @can('manage absensi')
        <a href="{{ route('hris.absensi.rekap') }}"
           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">
            Rekap Absensi
        </a>
        @endcan
    </div>

    {{-- Flash --}}
    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-xl px-4 py-3">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-xl px-4 py-3">{{ session('error') }}</div>
    @endif

    {{-- Check In / Out Card --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Status Hari Ini</h2>

        @if($attendance)
            <div class="grid grid-cols-2 gap-4 mb-5">
                <div class="bg-green-50 rounded-xl p-4 text-center">
                    <p class="text-xs text-gray-500 mb-1">Check In</p>
                    <p class="text-2xl font-bold text-green-700">{{ $attendance->check_in ?? '—' }}</p>
                </div>
                <div class="bg-blue-50 rounded-xl p-4 text-center">
                    <p class="text-xs text-gray-500 mb-1">Check Out</p>
                    <p class="text-2xl font-bold text-blue-700">{{ $attendance->check_out ?? '—' }}</p>
                </div>
            </div>

            @if(!$attendance->check_out)
            <form action="{{ route('hris.absensi.checkout') }}" method="POST">
                @csrf
                <button type="submit"
                        class="w-full py-3 rounded-xl font-semibold text-white transition-all"
                        style="background:linear-gradient(135deg,#2563eb,#1d4ed8)">
                    Check Out Sekarang
                </button>
            </form>
            @else
            <p class="text-center text-sm text-gray-500">Anda sudah check-in dan check-out hari ini.</p>
            @endif
        @else
            <p class="text-sm text-gray-500 mb-4">Anda belum check-in hari ini.</p>
            <form action="{{ route('hris.absensi.checkin') }}" method="POST">
                @csrf
                <button type="submit"
                        class="w-full py-3 rounded-xl font-semibold text-white transition-all"
                        style="background:linear-gradient(135deg,#7c3aed,#6d28d9)">
                    Check In Sekarang
                </button>
            </form>
        @endif
    </div>

    {{-- Riwayat Bulan Ini --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
        <div class="p-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-900">Riwayat Bulan Ini</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Tanggal</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Check In</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Check Out</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($bulan as $row)
                    <tr>
                        <td class="px-4 py-3 text-gray-700">{{ $row->date->locale('id')->isoFormat('ddd, D MMM') }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $row->check_in ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $row->check_out ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @php
                                $colors = ['hadir'=>'green','alpha'=>'red','izin'=>'yellow','sakit'=>'blue','cuti'=>'purple','libur'=>'gray'];
                                $c = $colors[$row->status] ?? 'gray';
                            @endphp
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-{{ $c }}-100 text-{{ $c }}-700">
                                {{ ucfirst($row->status) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400 text-sm">Belum ada data absensi bulan ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
