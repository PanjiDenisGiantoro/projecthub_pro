@extends('layouts.app')
@section('title', 'Lembur')
@section('page-title', 'Lembur')

@section('content')
<div class="space-y-6 pt-5">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Lembur</h1>
        <a href="{{ route('hris.overtime.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white rounded-xl"
           style="background:linear-gradient(135deg,#7c3aed,#6d28d9)">
            + Ajukan Lembur
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-xl px-4 py-3">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-xl px-4 py-3">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Karyawan</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tanggal</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Jam</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Total Jam</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Total</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($overtimes as $ot)
                <tr>
                    <td class="px-4 py-3 font-medium text-gray-900">{{ $ot->user->name }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $ot->date->locale('id')->isoFormat('ddd, D MMM Y') }}</td>
                    <td class="px-4 py-3 text-gray-600 text-xs">{{ $ot->start_time }} — {{ $ot->end_time }}</td>
                    <td class="px-4 py-3 text-center font-semibold text-gray-700">{{ $ot->total_hours }}j</td>
                    <td class="px-4 py-3 text-right text-gray-700">
                        {{ $ot->total_amount > 0 ? 'Rp ' . number_format($ot->total_amount, 0, ',', '.') : '—' }}
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium
                            @if($ot->status === 'approved') bg-green-100 text-green-700
                            @elseif($ot->status === 'rejected') bg-red-100 text-red-700
                            @elseif($ot->status === 'processed') bg-blue-100 text-blue-700
                            @else bg-yellow-100 text-yellow-700 @endif">
                            {{ ucfirst($ot->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($ot->status === 'pending')
                            @can('manage overtime')
                            <form action="{{ route('hris.overtime.approve', $ot) }}" method="POST" class="inline">
                                @csrf @method('PATCH')
                                <button class="text-xs text-green-600 hover:text-green-800 mr-2">Setujui</button>
                            </form>
                            @endcan
                            @if($ot->user_id === auth()->id())
                            <form action="{{ route('hris.overtime.destroy', $ot) }}" method="POST" class="inline" onsubmit="return confirm('Hapus pengajuan ini?')">
                                @csrf @method('DELETE')
                                <button class="text-xs text-red-500 hover:text-red-700">Hapus</button>
                            </form>
                            @endif
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400 text-sm">Belum ada data lembur.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $overtimes->links() }}</div>
    </div>
</div>
@endsection
