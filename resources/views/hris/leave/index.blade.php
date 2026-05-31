@extends('layouts.app')
@section('title', 'Cuti & Izin')
@section('page-title', 'Cuti & Izin')

@section('content')
<div class="space-y-6 pt-5">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Cuti & Izin</h1>
        <a href="{{ route('hris.leave.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white rounded-xl"
           style="background:linear-gradient(135deg,#7c3aed,#6d28d9)">
            + Ajukan Cuti
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
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Jenis</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Periode</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Hari</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($requests as $req)
                <tr>
                    <td class="px-4 py-3 font-medium text-gray-900">{{ $req->user->name }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $req->leaveType->name }}</td>
                    <td class="px-4 py-3 text-gray-600 text-xs">
                        {{ $req->start_date->format('d/m/Y') }} — {{ $req->end_date->format('d/m/Y') }}
                    </td>
                    <td class="px-4 py-3 text-center font-semibold text-gray-700">{{ $req->total_days }}</td>
                    <td class="px-4 py-3">
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium
                            @if($req->status === 'approved') bg-green-100 text-green-700
                            @elseif($req->status === 'rejected') bg-red-100 text-red-700
                            @elseif($req->status === 'cancelled') bg-gray-100 text-gray-500
                            @else bg-yellow-100 text-yellow-700 @endif">
                            {{ ucfirst($req->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($req->status === 'pending')
                            @can('manage leave')
                            <div x-data="{ open: false }" class="relative inline-block">
                                <button @click="open=!open" class="text-xs text-gray-500 hover:text-gray-700">Aksi ▾</button>
                                <div x-show="open" @click.away="open=false" class="absolute right-0 mt-1 w-40 bg-white border border-gray-100 rounded-xl shadow-lg z-10">
                                    <form action="{{ route('hris.leave.approve', $req) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <button class="w-full text-left px-4 py-2 text-sm text-green-700 hover:bg-green-50">Setujui</button>
                                    </form>
                                    <button @click="open=false; document.getElementById('reject-{{ $req->id }}').showModal()"
                                            class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">Tolak</button>
                                </div>
                            </div>
                            @endcan
                            @if($req->user_id === auth()->id())
                            <form action="{{ route('hris.leave.destroy', $req) }}" method="POST" class="inline" onsubmit="return confirm('Batalkan pengajuan ini?')">
                                @csrf @method('DELETE')
                                <button class="text-xs text-red-500 hover:text-red-700 ml-2">Batal</button>
                            </form>
                            @endif
                        @endif
                    </td>
                </tr>
                {{-- Reject Modal --}}
                @can('manage leave')
                <dialog id="reject-{{ $req->id }}" class="rounded-2xl p-6 shadow-xl w-full max-w-md">
                    <form action="{{ route('hris.leave.reject', $req) }}" method="POST">
                        @csrf @method('PATCH')
                        <h3 class="font-bold text-gray-900 mb-3">Tolak Cuti</h3>
                        <textarea name="rejection_reason" rows="3" required placeholder="Alasan penolakan..." class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm mb-3 focus:ring-2 focus:ring-violet-500 focus:outline-none"></textarea>
                        <div class="flex gap-2 justify-end">
                            <button type="button" onclick="document.getElementById('reject-{{ $req->id }}').close()" class="px-4 py-2 text-sm text-gray-500 border border-gray-200 rounded-xl">Batal</button>
                            <button class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-xl hover:bg-red-700">Tolak</button>
                        </div>
                    </form>
                </dialog>
                @endcan
                @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400 text-sm">Belum ada pengajuan cuti.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $requests->links() }}</div>
    </div>
</div>
@endsection
