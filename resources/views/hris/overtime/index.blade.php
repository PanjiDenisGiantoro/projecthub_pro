@extends('layouts.app')
@section('title', 'Lembur')
@section('page-title', 'Lembur')

@section('content')
<div class="space-y-6 pt-5">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Lembur</h1>
        <a href="{{ route('hris.overtime.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white rounded-xl"
           style="background:var(--hris-gradient)">
            + Ajukan Lembur
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
            <a href="{{ route('hris.overtime.index') }}" class="text-sm text-gray-400 hover:text-gray-600 px-2 py-2">✕ Reset</a>
        @endif
    </form>

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
                            @elseif($ot->status === 'cancelled') bg-gray-100 text-gray-500
                            @else bg-yellow-100 text-yellow-700 @endif">
                            {{ ucfirst($ot->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($ot->status === 'pending')
                            @can('approve overtime')
                            <div x-data="{ open: false }" class="relative inline-block">
                                <button @click="open=!open" class="text-xs text-gray-500 hover:text-gray-700">Aksi ▾</button>
                                <div x-show="open" @click.away="open=false" class="absolute right-0 mt-1 w-40 bg-white border border-gray-100 rounded-xl shadow-lg z-10">
                                    <form action="{{ route('hris.overtime.approve', $ot) }}" method="POST"
                                          data-confirm-submit="Setujui lembur {{ $ot->user->name }}?"
                                          data-confirm-text="{{ $ot->date->locale('id')->isoFormat('ddd, D MMM Y') }} · {{ $ot->total_hours }} jam{{ $ot->total_amount > 0 ? ' · Rp ' . number_format($ot->total_amount, 0, ',', '.') : '' }}. Persetujuan tidak bisa dibatalkan dari sini."
                                          data-confirm-btn="Ya, Setujui">
                                        @csrf @method('PATCH')
                                        <button class="w-full text-left px-4 py-2 text-sm text-green-700 hover:bg-green-50">Setujui</button>
                                    </form>
                                    <button @click="open=false; document.getElementById('reject-overtime-{{ $ot->id }}').showModal()"
                                            class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">Tolak</button>
                                </div>
                            </div>
                            @endcan
                            @can('update overtime')
                            <button onclick="document.getElementById('edit-overtime-{{ $ot->id }}').showModal()"
                                    class="text-xs text-blue-500 hover:text-blue-700 ml-2">Edit</button>
                            @endcan
                            @if($ot->user_id === auth()->id())
                            <form action="{{ route('hris.overtime.destroy', $ot) }}" method="POST" class="inline"
                                  data-confirm-submit="Batalkan pengajuan lembur ini?" data-confirm-btn="Ya, Batalkan">
                                @csrf @method('DELETE')
                                <button class="text-xs text-red-500 hover:text-red-700 ml-2">Batal</button>
                            </form>
                            @endif
                        @endif
                        @can('delete overtime')
                        <form action="{{ route('hris.overtime.destroy', $ot) }}" method="POST" class="inline"
                              data-confirm-submit="Hapus data lembur {{ $ot->user->name }} ini?"
                              data-confirm-text="{{ $ot->date->locale('id')->isoFormat('ddd, D MMM Y') }} · {{ $ot->total_hours }} jam, status {{ ucfirst($ot->status) }}. Tindakan ini tidak bisa dibatalkan."
                              data-confirm-btn="Ya, Hapus">
                            @csrf @method('DELETE')
                            <button class="text-xs text-red-600 hover:text-red-800 ml-2">Hapus</button>
                        </form>
                        @endcan
                    </td>
                </tr>
                {{-- Reject Modal --}}
                @can('approve overtime')
                <dialog id="reject-overtime-{{ $ot->id }}" class="rounded-2xl p-6 shadow-xl w-full max-w-md">
                    <form action="{{ route('hris.overtime.reject', $ot) }}" method="POST">
                        @csrf @method('PATCH')
                        <h3 class="font-bold text-gray-900 mb-3">Tolak Lembur</h3>
                        <textarea name="rejection_reason" rows="3" required placeholder="Alasan penolakan..." class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm mb-3 focus:ring-2 focus:ring-blue-500 focus:outline-none"></textarea>
                        <div class="flex gap-2 justify-end">
                            <button type="button" onclick="document.getElementById('reject-overtime-{{ $ot->id }}').close()" class="px-4 py-2 text-sm text-gray-500 border border-gray-200 rounded-xl">Batal</button>
                            <button class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-xl hover:bg-red-700">Tolak</button>
                        </div>
                    </form>
                </dialog>
                @endcan
                {{-- Edit Modal --}}
                @can('update overtime')
                @if($ot->status === 'pending')
                <dialog id="edit-overtime-{{ $ot->id }}" class="rounded-2xl p-6 shadow-xl w-full max-w-md">
                    <form action="{{ route('hris.overtime.update', $ot) }}" method="POST">
                        @csrf @method('PUT')
                        <h3 class="font-bold text-gray-900 mb-3">Edit Lembur — {{ $ot->user->name }}</h3>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Tanggal</label>
                                <input type="date" name="date" value="{{ $ot->date->toDateString() }}" required
                                       class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Jam Mulai</label>
                                    <input type="time" name="start_time" value="{{ $ot->start_time }}" required
                                           class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Jam Selesai</label>
                                    <input type="time" name="end_time" value="{{ $ot->end_time }}" required
                                           class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Deskripsi</label>
                                <textarea name="description" rows="3" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none resize-none">{{ $ot->description }}</textarea>
                            </div>
                        </div>
                        <div class="flex gap-2 justify-end mt-4">
                            <button type="button" onclick="document.getElementById('edit-overtime-{{ $ot->id }}').close()" class="px-4 py-2 text-sm text-gray-500 border border-gray-200 rounded-xl">Batal</button>
                            <button class="px-4 py-2 text-sm font-medium text-white rounded-xl" style="background:var(--hris-gradient)">Simpan</button>
                        </div>
                    </form>
                </dialog>
                @endif
                @endcan
                @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400 text-sm">
                    {{ request('status') ? 'Tidak ada data dengan status ini.' : 'Belum ada data lembur.' }}
                </td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4 flex items-center justify-between gap-3 flex-wrap">
            <x-per-page />
            {{ $overtimes->links() }}
        </div>
    </div>
</div>
@endsection
