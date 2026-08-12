@extends('layouts.app')
@section('title', 'Penggajian')
@section('page-title', 'Penggajian')

@section('content')
<div class="space-y-6 pt-5">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Penggajian (Payroll)</h1>
        @can('update payroll')
        <a href="{{ route('hris.payroll.setting') }}"
           class="inline-flex items-center gap-2 text-sm text-violet-600 hover:text-violet-800 font-medium">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Pengaturan PPh 21
        </a>
        @endcan
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-xl px-4 py-3">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-xl px-4 py-3">{{ session('error') }}</div>
    @endif

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

    {{-- Generate Form (only for HR) --}}
    @can('generate payroll')
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4" x-data="{ open: false }">
        <button @click="open = !open" class="text-sm font-medium text-violet-700 hover:text-violet-900">
            + Generate Payroll untuk Karyawan
        </button>
        <div x-show="open" class="mt-4">
            <form action="{{ route('hris.payroll.generate') }}" method="POST" class="flex gap-3 flex-wrap">
                @csrf
                <select name="user_id" required class="border border-gray-200 rounded-xl px-3 py-2 text-sm">
                    <option value="">Pilih karyawan...</option>
                    @foreach($employees as $emp)
                    <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                    @endforeach
                </select>
                <input type="hidden" name="year" value="{{ $year }}">
                <input type="hidden" name="month" value="{{ $month }}">
                <button class="px-4 py-2 bg-violet-600 text-white text-sm font-semibold rounded-xl hover:bg-violet-700">Generate</button>
            </form>
        </div>
    </div>
    @endcan

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Karyawan</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Bruto</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Potongan</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Bersih</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($payrolls as $p)
                <tr>
                    <td class="px-4 py-3 font-medium text-gray-900">{{ $p->user->name }}</td>
                    <td class="px-4 py-3 text-right text-gray-700">Rp {{ number_format($p->penghasilan_bruto, 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-right text-red-600">Rp {{ number_format($p->total_potongan, 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-right font-bold text-green-700">Rp {{ number_format($p->gaji_bersih, 0, ',', '.') }}</td>
                    <td class="px-4 py-3">
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium
                            @if($p->status === 'paid') bg-green-100 text-green-700
                            @elseif($p->status === 'finalized') bg-blue-100 text-blue-700
                            @else bg-gray-100 text-gray-700 @endif">
                            {{ ucfirst($p->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center flex items-center justify-center gap-2">
                        <a href="{{ route('hris.payroll.show', $p) }}" class="text-xs text-violet-600 hover:text-violet-800">Detail</a>
                        <a href="{{ route('hris.payroll.slip', $p) }}" class="text-xs text-violet-600 hover:text-violet-800">Slip PDF</a>
                        @can('update payroll')
                        @if($p->status === 'draft')
                        <form action="{{ route('hris.payroll.finalize', $p) }}" method="POST" class="inline">
                            @csrf @method('PATCH')
                            <button class="text-xs text-green-600 hover:text-green-800">Finalize</button>
                        </form>
                        @endif
                        @endcan
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400 text-sm">Belum ada data payroll bulan ini.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $payrolls->withQueryString()->links() }}</div>
    </div>
</div>
@endsection
