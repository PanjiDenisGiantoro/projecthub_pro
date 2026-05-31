@extends('layouts.app')
@section('title', 'Rekap Absensi')
@section('page-title', 'Rekap Absensi')

@section('content')
<div class="space-y-6 pt-5">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Rekap Absensi</h1>
    </div>

    {{-- Filter --}}
    <form method="GET" action="{{ route('hris.absensi.rekap') }}" class="flex gap-3">
        <select name="year" class="border border-gray-200 rounded-xl px-3 py-2 text-sm">
            @for($y = now()->year; $y >= now()->year - 2; $y--)
            <option value="{{ $y }}" @selected($y == $year)>{{ $y }}</option>
            @endfor
        </select>
        <select name="month" class="border border-gray-200 rounded-xl px-3 py-2 text-sm">
            @foreach(range(1, 12) as $m)
            <option value="{{ $m }}" @selected($m == $month)>{{ \Carbon\Carbon::create(null, $m)->locale('id')->isoFormat('MMMM') }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-4 py-2 bg-violet-600 text-white text-sm font-medium rounded-xl hover:bg-violet-700">Filter</button>
    </form>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Karyawan</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tanggal</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Masuk</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Pulang</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($rekap as $row)
                <tr>
                    <td class="px-4 py-3 font-medium text-gray-900">{{ $row->user->name }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $row->date->locale('id')->isoFormat('ddd, D MMM') }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $row->check_in ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $row->check_out ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium
                            @if($row->status === 'hadir') bg-green-100 text-green-700
                            @elseif($row->status === 'alpha') bg-red-100 text-red-700
                            @elseif($row->status === 'cuti') bg-purple-100 text-purple-700
                            @elseif($row->status === 'sakit') bg-blue-100 text-blue-700
                            @else bg-gray-100 text-gray-700 @endif">
                            {{ ucfirst($row->status) }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400 text-sm">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $rekap->withQueryString()->links() }}</div>
    </div>
</div>
@endsection
