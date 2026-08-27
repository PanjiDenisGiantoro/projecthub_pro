@extends('layouts.app')
@section('title', 'Cuti & Izin')
@section('page-title', 'Cuti & Izin')

@section('content')
<div class="space-y-6 pt-5">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Cuti & Izin</h1>
        <a href="{{ route('hris.leave.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white rounded-xl"
           style="background:var(--hris-gradient)">
            + Ajukan Cuti
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-xl px-4 py-3">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-xl px-4 py-3">{{ session('error') }}</div>
    @endif

    <form method="GET" class="flex gap-2">
        <select name="status" onchange="this.form.submit()" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500 bg-white">
            <option value="">Semua Status</option>
            @foreach(['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected','cancelled'=>'Cancelled'] as $s => $sl)
                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ $sl }}</option>
            @endforeach
        </select>
        @if(request('status'))
            <a href="{{ route('hris.leave.index') }}" class="text-sm text-gray-400 hover:text-gray-600 px-2 py-2">✕ Reset</a>
        @endif
    </form>

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
                            @can('approve leave')
                            <div x-data="{ open: false }" class="relative inline-block">
                                <button @click="open=!open" class="text-xs text-gray-500 hover:text-gray-700">Aksi ▾</button>
                                <div x-show="open" @click.away="open=false" class="absolute right-0 mt-1 w-40 bg-white border border-gray-100 rounded-xl shadow-lg z-10">
                                    <form action="{{ route('hris.leave.approve', $req) }}" method="POST"
                                          data-confirm-submit="Setujui cuti {{ $req->user->name }}?"
                                          data-confirm-text="{{ $req->leaveType->name }}, {{ $req->total_days }} hari ({{ $req->start_date->format('d/m/Y') }} — {{ $req->end_date->format('d/m/Y') }}). Persetujuan tidak bisa dibatalkan dari sini."
                                          data-confirm-btn="Ya, Setujui">
                                        @csrf @method('PATCH')
                                        <button class="w-full text-left px-4 py-2 text-sm text-green-700 hover:bg-green-50">Setujui</button>
                                    </form>
                                    <button @click="open=false; document.getElementById('reject-{{ $req->id }}').showModal()"
                                            class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">Tolak</button>
                                </div>
                            </div>
                            @endcan
                            @can('update leave')
                            <button onclick="document.getElementById('edit-leave-{{ $req->id }}').showModal()"
                                    class="text-xs text-blue-500 hover:text-blue-700 ml-2">Edit</button>
                            @endcan
                            @if($req->user_id === auth()->id())
                            <form action="{{ route('hris.leave.destroy', $req) }}" method="POST" class="inline"
                                  data-confirm-submit="Batalkan pengajuan cuti ini?" data-confirm-btn="Ya, Batalkan">
                                @csrf @method('DELETE')
                                <button class="text-xs text-red-500 hover:text-red-700 ml-2">Batal</button>
                            </form>
                            @endif
                        @endif
                        @can('delete leave')
                        <form action="{{ route('hris.leave.destroy', $req) }}" method="POST" class="inline"
                              data-confirm-submit="Hapus data cuti {{ $req->user->name }} ini?"
                              data-confirm-text="{{ $req->leaveType->name }}, {{ $req->total_days }} hari ({{ $req->start_date->format('d/m/Y') }} — {{ $req->end_date->format('d/m/Y') }}), status {{ ucfirst($req->status) }}. Tindakan ini tidak bisa dibatalkan."
                              data-confirm-btn="Ya, Hapus">
                            @csrf @method('DELETE')
                            <button class="text-xs text-red-600 hover:text-red-800 ml-2">Hapus</button>
                        </form>
                        @endcan
                    </td>
                </tr>
                {{-- Reject Modal --}}
                @can('approve leave')
                <dialog id="reject-{{ $req->id }}" class="rounded-2xl p-6 shadow-xl w-full max-w-md">
                    <form action="{{ route('hris.leave.reject', $req) }}" method="POST">
                        @csrf @method('PATCH')
                        <h3 class="font-bold text-gray-900 mb-3">Tolak Cuti</h3>
                        <textarea name="rejection_reason" rows="3" required placeholder="Alasan penolakan..." class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm mb-3 focus:ring-2 focus:ring-blue-500 focus:outline-none"></textarea>
                        <div class="flex gap-2 justify-end">
                            <button type="button" onclick="document.getElementById('reject-{{ $req->id }}').close()" class="px-4 py-2 text-sm text-gray-500 border border-gray-200 rounded-xl">Batal</button>
                            <button class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-xl hover:bg-red-700">Tolak</button>
                        </div>
                    </form>
                </dialog>
                @endcan
                {{-- Edit Modal --}}
                @can('update leave')
                @if($req->status === 'pending')
                <dialog id="edit-leave-{{ $req->id }}" class="rounded-2xl p-6 shadow-xl w-full max-w-md">
                    <form action="{{ route('hris.leave.update', $req) }}" method="POST">
                        @csrf @method('PUT')
                        <h3 class="font-bold text-gray-900 mb-3">Edit Cuti — {{ $req->user->name }}</h3>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Jenis Cuti</label>
                                <select name="leave_type_id" required class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                    @foreach($leaveTypes as $type)
                                    <option value="{{ $type->id }}" @selected($req->leave_type_id == $type->id)>{{ $type->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Tanggal Mulai</label>
                                    <input type="date" name="start_date" value="{{ $req->start_date->toDateString() }}" required
                                           class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Tanggal Akhir</label>
                                    <input type="date" name="end_date" value="{{ $req->end_date->toDateString() }}" required
                                           class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Alasan</label>
                                <textarea name="reason" rows="3" required class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none resize-none">{{ $req->reason }}</textarea>
                            </div>
                        </div>
                        <div class="flex gap-2 justify-end mt-4">
                            <button type="button" onclick="document.getElementById('edit-leave-{{ $req->id }}').close()" class="px-4 py-2 text-sm text-gray-500 border border-gray-200 rounded-xl">Batal</button>
                            <button class="px-4 py-2 text-sm font-medium text-white rounded-xl" style="background:var(--hris-gradient)">Simpan</button>
                        </div>
                    </form>
                </dialog>
                @endif
                @endcan
                @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400 text-sm">
                    {{ request('status') ? 'Tidak ada pengajuan dengan status ini.' : 'Belum ada pengajuan cuti.' }}
                </td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4 flex items-center justify-between gap-3 flex-wrap">
            <x-per-page />
            {{ $requests->links() }}
        </div>
    </div>
</div>
@endsection
