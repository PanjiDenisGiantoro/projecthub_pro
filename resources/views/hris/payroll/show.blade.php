@extends('layouts.app')
@section('title', 'Detail Payroll')
@section('page-title', 'Detail Payroll')

@section('content')
<div class="max-w-2xl mx-auto pt-5 space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('hris.payroll.index') }}" class="text-gray-400 hover:text-gray-700">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <h1 class="text-2xl font-bold text-gray-900">Detail Payroll</h1>
        <a href="{{ route('hris.payroll.slip', $payroll) }}" class="ml-auto text-sm text-blue-600 border border-blue-200 px-4 py-2 rounded-xl hover:bg-blue-50">Unduh Slip PDF</a>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-xl font-bold text-gray-900">{{ $payroll->user->name }}</p>
                <p class="text-sm text-gray-500">{{ \Carbon\Carbon::create($payroll->year, $payroll->month)->locale('id')->isoFormat('MMMM Y') }}</p>
            </div>
            <span class="text-xs px-3 py-1 rounded-full font-semibold
                @if($payroll->status === 'paid') bg-green-100 text-green-700
                @elseif($payroll->status === 'finalized') bg-blue-100 text-blue-700
                @else bg-gray-100 text-gray-700 @endif">
                {{ ucfirst($payroll->status) }}
            </span>
        </div>

        <hr class="border-gray-100">

        <div class="space-y-2">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Pendapatan</p>
            @foreach([
                ['Gaji Pokok', $payroll->gaji_pokok],
                ['Tunjangan Transport', $payroll->tunjangan_transport],
                ['Tunjangan Makan', $payroll->tunjangan_makan],
                ['Tunjangan Jabatan', $payroll->tunjangan_jabatan],
                ['Tunjangan Lainnya', $payroll->tunjangan_lainnya],
                ['Lembur', $payroll->lembur],
                ['Reimburse', $payroll->reimburse],
            ] as [$label, $val])
            @if($val > 0)
            <div class="flex justify-between text-sm">
                <span class="text-gray-600">{{ $label }}</span>
                <span class="font-medium text-gray-900">Rp {{ number_format($val, 0, ',', '.') }}</span>
            </div>
            @endif
            @endforeach
            <div class="flex justify-between text-sm font-bold border-t border-gray-100 pt-2">
                <span>Total Bruto</span>
                <span>Rp {{ number_format($payroll->penghasilan_bruto, 0, ',', '.') }}</span>
            </div>
        </div>

        <div class="space-y-2">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Potongan</p>
            @foreach([
                ['BPJS Kesehatan (1%)', $payroll->potongan_bpjs_kes],
                ['BPJS Ketenagakerjaan', $payroll->potongan_bpjs_tk],
                ['PPh 21', $payroll->potongan_pph21],
                ['Potongan Lainnya', $payroll->potongan_lainnya],
            ] as [$label, $val])
            @if($val > 0)
            <div class="flex justify-between text-sm">
                <span class="text-gray-600">{{ $label }}</span>
                <span class="font-medium text-red-600">- Rp {{ number_format($val, 0, ',', '.') }}</span>
            </div>
            @endif
            @endforeach
            <div class="flex justify-between text-sm font-bold border-t border-gray-100 pt-2 text-red-600">
                <span>Total Potongan</span>
                <span>- Rp {{ number_format($payroll->total_potongan, 0, ',', '.') }}</span>
            </div>
        </div>

        <div class="bg-green-50 rounded-xl px-5 py-4 flex justify-between items-center">
            <span class="font-bold text-gray-900">Gaji Bersih</span>
            <span class="text-2xl font-extrabold text-green-700">Rp {{ number_format($payroll->gaji_bersih, 0, ',', '.') }}</span>
        </div>
    </div>
</div>
@endsection
