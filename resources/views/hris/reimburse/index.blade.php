@extends('layouts.app')
@section('title', 'Reimburse')
@section('page-title', 'Reimburse')

@section('content')
<div class="space-y-6 pt-5">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Reimburse</h1>
        <a href="{{ route('hris.reimburse.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white rounded-xl"
           style="background:var(--hris-gradient)">
            + Ajukan Reimburse
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-xl px-4 py-3">{{ session('success') }}</div>
    @endif

    <form method="GET" class="flex gap-2">
        <select name="status" onchange="this.form.submit()" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500 bg-white">
            <option value="">Semua Status</option>
            @foreach(['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected','cancelled'=>'Cancelled'] as $s => $sl)
                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ $sl }}</option>
            @endforeach
        </select>
        @if(request('status'))
            <a href="{{ route('hris.reimburse.index') }}" class="text-sm text-gray-400 hover:text-gray-600 px-2 py-2">✕ Reset</a>
        @endif
    </form>

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
                        <span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 font-medium capitalize">{{ $item->category }}</span>
                    </td>
                    <td class="px-4 py-3 text-gray-700">{{ $item->title }}</td>
                    <td class="px-4 py-3 text-gray-600 text-xs">{{ $item->expense_date->format('d/m/Y') }}</td>
                    <td class="px-4 py-3 text-right font-semibold text-gray-900">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
                    <td class="px-4 py-3">
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium
                            @if($item->status === 'approved') bg-green-100 text-green-700
                            @elseif($item->status === 'rejected') bg-red-100 text-red-700
                            @elseif($item->status === 'cancelled') bg-gray-100 text-gray-500
                            @else bg-yellow-100 text-yellow-700 @endif">
                            {{ ucfirst($item->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($item->status === 'pending')
                            @can('approve reimbursement')
                            <div x-data="{ open: false }" class="relative inline-block">
                                <button @click="open=!open" class="text-xs text-gray-500 hover:text-gray-700">Aksi ▾</button>
                                <div x-show="open" @click.away="open=false" class="absolute right-0 mt-1 w-40 bg-white border border-gray-100 rounded-xl shadow-lg z-10">
                                    <form action="{{ route('hris.reimburse.approve', $item) }}" method="POST"
                                          data-confirm-submit="Setujui reimbursement {{ $item->user->name }}?"
                                          data-confirm-text="{{ $item->title }} · Rp {{ number_format($item->amount, 0, ',', '.') }}. Persetujuan tidak bisa dibatalkan dari sini."
                                          data-confirm-btn="Ya, Setujui">
                                        @csrf @method('PATCH')
                                        <button class="w-full text-left px-4 py-2 text-sm text-green-700 hover:bg-green-50">Setujui</button>
                                    </form>
                                    <button @click="open=false; document.getElementById('reject-reimburse-{{ $item->id }}').showModal()"
                                            class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">Tolak</button>
                                </div>
                            </div>
                            @endcan
                            @can('update reimbursement')
                            <button onclick="document.getElementById('edit-reimburse-{{ $item->id }}').showModal()"
                                    class="text-xs text-blue-500 hover:text-blue-700 ml-2">Edit</button>
                            @endcan
                            @if($item->user_id === auth()->id())
                            <form action="{{ route('hris.reimburse.destroy', $item) }}" method="POST" class="inline"
                                  data-confirm-submit="Batalkan pengajuan reimburse ini?" data-confirm-btn="Ya, Batalkan">
                                @csrf @method('DELETE')
                                <button class="text-xs text-red-500 hover:text-red-700 ml-2">Batal</button>
                            </form>
                            @endif
                        @endif
                        @can('delete reimbursement')
                        <form action="{{ route('hris.reimburse.destroy', $item) }}" method="POST" class="inline"
                              data-confirm-submit="Hapus data reimburse {{ $item->user->name }} ini?"
                              data-confirm-text="{{ $item->title }} · Rp {{ number_format($item->amount, 0, ',', '.') }}, status {{ ucfirst($item->status) }}. Tindakan ini tidak bisa dibatalkan."
                              data-confirm-btn="Ya, Hapus">
                            @csrf @method('DELETE')
                            <button class="text-xs text-red-600 hover:text-red-800 ml-2">Hapus</button>
                        </form>
                        @endcan
                    </td>
                </tr>
                {{-- Reject Modal --}}
                @can('approve reimbursement')
                <dialog id="reject-reimburse-{{ $item->id }}" class="rounded-2xl p-6 shadow-xl w-full max-w-md">
                    <form action="{{ route('hris.reimburse.reject', $item) }}" method="POST">
                        @csrf @method('PATCH')
                        <h3 class="font-bold text-gray-900 mb-3">Tolak Reimburse</h3>
                        <textarea name="rejection_reason" rows="3" required placeholder="Alasan penolakan..." class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm mb-3 focus:ring-2 focus:ring-blue-500 focus:outline-none"></textarea>
                        <div class="flex gap-2 justify-end">
                            <button type="button" onclick="document.getElementById('reject-reimburse-{{ $item->id }}').close()" class="px-4 py-2 text-sm text-gray-500 border border-gray-200 rounded-xl">Batal</button>
                            <button class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-xl hover:bg-red-700">Tolak</button>
                        </div>
                    </form>
                </dialog>
                @endcan
                {{-- Edit Modal --}}
                @can('update reimbursement')
                @if($item->status === 'pending')
                <dialog id="edit-reimburse-{{ $item->id }}" class="rounded-2xl p-6 shadow-xl w-full max-w-md">
                    <form action="{{ route('hris.reimburse.update', $item) }}" method="POST">
                        @csrf @method('PUT')
                        <h3 class="font-bold text-gray-900 mb-3">Edit Reimburse — {{ $item->user->name }}</h3>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Kategori</label>
                                <select name="category" required class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                    @foreach(['transport' => 'Transport', 'makan' => 'Makan', 'akomodasi' => 'Akomodasi', 'medis' => 'Medis', 'pulsa' => 'Pulsa', 'lainnya' => 'Lainnya'] as $val => $label)
                                    <option value="{{ $val }}" @selected($item->category === $val)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Judul</label>
                                <input type="text" name="title" value="{{ $item->title }}" required
                                       class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Tanggal</label>
                                    <input type="date" name="expense_date" value="{{ $item->expense_date->toDateString() }}" required
                                           class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Jumlah (Rp)</label>
                                    <input type="number" name="amount" value="{{ $item->amount }}" required min="1"
                                           class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Keterangan</label>
                                <textarea name="description" rows="2" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none resize-none">{{ $item->description }}</textarea>
                            </div>
                        </div>
                        <div class="flex gap-2 justify-end mt-4">
                            <button type="button" onclick="document.getElementById('edit-reimburse-{{ $item->id }}').close()" class="px-4 py-2 text-sm text-gray-500 border border-gray-200 rounded-xl">Batal</button>
                            <button class="px-4 py-2 text-sm font-medium text-white rounded-xl" style="background:var(--hris-gradient)">Simpan</button>
                        </div>
                    </form>
                </dialog>
                @endif
                @endcan
                @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400 text-sm">
                    {{ request('status') ? 'Tidak ada data dengan status ini.' : 'Belum ada data reimburse.' }}
                </td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4 flex items-center justify-between gap-3 flex-wrap">
            <x-per-page />
            {{ $items->links() }}
        </div>
    </div>
</div>
@endsection
