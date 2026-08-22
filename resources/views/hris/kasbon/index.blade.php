@extends('layouts.app')
@section('title', 'Kasbon')
@section('page-title', 'Kasbon')

@section('content')
<div class="space-y-6 pt-5">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Kasbon</h1>
        <a href="{{ route('hris.payroll.index') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
            &larr; Kembali ke Penggajian
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-xl px-4 py-3">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-xl px-4 py-3">{{ session('error') }}</div>
    @endif

    <div class="bg-blue-50 border border-blue-200 rounded-xl px-4 py-3 text-xs text-blue-800">
        Cicilan kasbon otomatis dipotong dari gaji karyawan setiap kali payroll bulan berjalan di-generate,
        sampai sisa kasbon lunas. Setiap karyawan hanya bisa memiliki satu kasbon berjalan pada satu waktu.
    </div>

    {{-- Add Form --}}
    @can('create payroll')
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4" x-data="{ open: false }">
        <button @click="open = !open" class="text-sm font-medium text-blue-700 hover:text-blue-900">
            + Tambah Kasbon
        </button>
        <div x-show="open" class="mt-4">
            <form action="{{ route('hris.kasbon.store') }}" method="POST" class="flex gap-3 flex-wrap items-start">
                @csrf
                <select name="user_id" required class="border border-gray-200 rounded-xl px-3 py-2 text-sm">
                    <option value="">Pilih karyawan...</option>
                    @foreach($employees as $emp)
                    <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                    @endforeach
                </select>
                <input type="date" name="tanggal" required value="{{ now()->toDateString() }}"
                       class="border border-gray-200 rounded-xl px-3 py-2 text-sm">
                <input type="number" name="jumlah" required min="1" placeholder="Jumlah Kasbon (Rp)"
                       class="border border-gray-200 rounded-xl px-3 py-2 text-sm w-40">
                <input type="number" name="cicilan_per_bulan" required min="1" placeholder="Cicilan/bulan (Rp)"
                       class="border border-gray-200 rounded-xl px-3 py-2 text-sm w-40">
                <input type="text" name="keterangan" placeholder="Keterangan (opsional)"
                       class="border border-gray-200 rounded-xl px-3 py-2 text-sm flex-1 min-w-[160px]">
                <button class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-xl hover:bg-blue-700">Simpan</button>
            </form>
        </div>
    </div>
    @endcan

    <div class="flex justify-end mb-2">
        <x-per-page />
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Karyawan</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tanggal</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Jumlah</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Cicilan/bulan</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Sisa</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Keterangan</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($kasbons as $k)
                <tr>
                    <td class="px-4 py-3 font-medium text-gray-900">{{ $k->user->name }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ $k->tanggal->format('d M Y') }}</td>
                    <td class="px-4 py-3 text-right text-gray-700">Rp {{ number_format($k->jumlah, 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-right text-gray-700">Rp {{ number_format($k->cicilan_per_bulan, 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-right font-medium {{ $k->sisa > 0 ? 'text-red-600' : 'text-green-600' }}">Rp {{ number_format($k->sisa, 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $k->status === 'lunas' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                            {{ \App\Models\Kasbon::statusLabels()[$k->status] ?? $k->status }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-500">{{ $k->keterangan ?: '-' }}</td>
                    <td class="px-4 py-3 text-center">
                        @can('delete payroll')
                        @if($k->sisa == $k->jumlah)
                        <form action="{{ route('hris.kasbon.destroy', $k) }}" method="POST"
                              onsubmit="return confirm('Hapus data kasbon ini?')" class="inline">
                            @csrf @method('DELETE')
                            <button class="text-xs text-red-600 hover:text-red-800">Hapus</button>
                        </form>
                        @else
                        <span class="text-xs text-gray-300">-</span>
                        @endif
                        @endcan
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400 text-sm">Belum ada data kasbon.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($kasbons->hasPages())
        <div class="p-4">{{ $kasbons->links() }}</div>
        @endif
    </div>
</div>
@endsection
