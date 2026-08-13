@extends('layouts.app')
@section('title', 'Bonus / THR')
@section('page-title', 'Bonus / THR')

@section('content')
<div class="space-y-6 pt-5">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Bonus / THR</h1>
        <a href="{{ route('hris.payroll.index') }}" class="text-sm text-violet-600 hover:text-violet-800 font-medium">
            &larr; Kembali ke Penggajian
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-xl px-4 py-3">{{ session('success') }}</div>
    @endif

    <div class="bg-blue-50 border border-blue-200 rounded-xl px-4 py-3 text-xs text-blue-800">
        Bonus/THR yang ditambahkan di sini otomatis ikut dihitung sebagai penghasilan kena pajak (PPh 21)
        saat payroll periode yang sama di-generate.
    </div>

    {{-- Filter --}}
    <form method="GET" class="flex gap-3">
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

    {{-- Add Form --}}
    @can('create payroll')
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4" x-data="{ open: false }">
        <button @click="open = !open" class="text-sm font-medium text-violet-700 hover:text-violet-900">
            + Tambah Bonus/THR
        </button>
        <div x-show="open" class="mt-4">
            <form action="{{ route('hris.bonus.store') }}" method="POST" class="flex gap-3 flex-wrap items-start">
                @csrf
                <select name="user_id" required class="border border-gray-200 rounded-xl px-3 py-2 text-sm">
                    <option value="">Pilih karyawan...</option>
                    @foreach($employees as $emp)
                    <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                    @endforeach
                </select>
                <select name="type" required class="border border-gray-200 rounded-xl px-3 py-2 text-sm">
                    @foreach(\App\Models\Bonus::typeLabels() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <input type="number" name="amount" required min="0" placeholder="Nominal (Rp)"
                       class="border border-gray-200 rounded-xl px-3 py-2 text-sm w-40">
                <input type="text" name="description" placeholder="Keterangan (opsional)"
                       class="border border-gray-200 rounded-xl px-3 py-2 text-sm flex-1 min-w-[160px]">
                <input type="hidden" name="year" value="{{ $year }}">
                <input type="hidden" name="month" value="{{ $month }}">
                <button class="px-4 py-2 bg-violet-600 text-white text-sm font-semibold rounded-xl hover:bg-violet-700">Simpan</button>
            </form>
        </div>
    </div>
    @endcan

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Karyawan</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Jenis</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Nominal</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Keterangan</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($bonuses as $b)
                <tr>
                    <td class="px-4 py-3 font-medium text-gray-900">{{ $b->user->name }}</td>
                    <td class="px-4 py-3 text-gray-700">{{ \App\Models\Bonus::typeLabels()[$b->type] ?? $b->type }}</td>
                    <td class="px-4 py-3 text-right text-gray-700">Rp {{ number_format($b->amount, 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ $b->description ?: '-' }}</td>
                    <td class="px-4 py-3 text-center">
                        @can('delete payroll')
                        <form action="{{ route('hris.bonus.destroy', $b) }}" method="POST"
                              onsubmit="return confirm('Hapus data bonus ini?')" class="inline">
                            @csrf @method('DELETE')
                            <button class="text-xs text-red-600 hover:text-red-800">Hapus</button>
                        </form>
                        @endcan
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400 text-sm">Belum ada bonus/THR periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
