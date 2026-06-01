@extends('layouts.app')
@section('title', 'Reimburse')
@section('page-title', 'Reimburse')

@section('content')
<div class="space-y-6 pt-5">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Reimburse</h1>
        <a href="{{ route('hris.reimburse.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white rounded-xl"
           style="background:linear-gradient(135deg,#7c3aed,#6d28d9)">
            + Ajukan Reimburse
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-xl px-4 py-3">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Karyawan</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Kategori</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Judul</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tanggal</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Jumlah</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($items as $item)
                <tr>
                    <td class="px-4 py-3 font-medium text-gray-900">{{ $item->user->name }}</td>
                    <td class="px-4 py-3">
                        <span class="text-xs px-2 py-0.5 rounded-full bg-violet-100 text-violet-700 font-medium capitalize">{{ $item->category }}</span>
                    </td>
                    <td class="px-4 py-3 text-gray-700">{{ $item->title }}</td>
                    <td class="px-4 py-3 text-gray-600 text-xs">{{ $item->expense_date->format('d/m/Y') }}</td>
                    <td class="px-4 py-3 text-right font-semibold text-gray-900">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
                    <td class="px-4 py-3">
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium
                            @if($item->status === 'approved' || $item->status === 'paid') bg-green-100 text-green-700
                            @elseif($item->status === 'rejected') bg-red-100 text-red-700
                            @else bg-yellow-100 text-yellow-700 @endif">
                            {{ ucfirst($item->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($item->status === 'pending')
                            @can('manage reimbursement')
                            <form action="{{ route('hris.reimburse.approve', $item) }}" method="POST" class="inline">
                                @csrf @method('PATCH')
                                <button class="text-xs text-green-600 hover:text-green-800 mr-2">Setujui</button>
                            </form>
                            @endcan
                            @if($item->user_id === auth()->id())
                            <form action="{{ route('hris.reimburse.destroy', $item) }}" method="POST" class="inline" onsubmit="return confirm('Hapus pengajuan?')">
                                @csrf @method('DELETE')
                                <button class="text-xs text-red-500 hover:text-red-700">Hapus</button>
                            </form>
                            @endif
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400 text-sm">Belum ada data reimburse.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $items->links() }}</div>
    </div>
</div>
@endsection
