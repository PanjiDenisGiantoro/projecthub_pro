@extends('layouts.app')
@section('title', 'Invoice ' . $invoice->invoice_number)
@section('page-title', 'Detail Invoice')

@section('content')
@php
    $sc = ['draft'=>'bg-gray-100 text-gray-700','sent'=>'bg-blue-100 text-blue-700','paid'=>'bg-green-100 text-green-700','overdue'=>'bg-red-100 text-red-700','cancelled'=>'bg-gray-100 text-gray-500'];
    $user = auth()->user();
@endphp
<div class="py-4 max-w-3xl">
    <nav class="text-sm text-gray-500 mb-4">
        <a href="{{ route('invoices.index') }}" class="hover:text-blue-600">Invoices</a>
        <span class="mx-2">/</span>
        <span class="text-gray-700">{{ $invoice->invoice_number }}</span>
    </nav>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        {{-- Header --}}
        <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold font-mono text-gray-800">{{ $invoice->invoice_number }}</h2>
                <p class="text-sm text-gray-500 mt-1">{{ $invoice->project->name }}</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="badge {{ $sc[$invoice->status] ?? '' }} text-sm py-1 px-3">{{ ucfirst($invoice->status) }}</span>
                <a href="{{ route('invoices.pdf', $invoice) }}" class="inline-flex items-center gap-1 text-sm text-gray-600 hover:text-gray-800 border border-gray-300 rounded-lg px-3 py-1.5 hover:bg-gray-50 transition-colors">
                    ↓ PDF
                </a>
            </div>
        </div>

        {{-- Client & Date --}}
        <div class="px-6 py-4 grid grid-cols-2 gap-6 border-b border-gray-100">
            <div>
                <p class="text-xs text-gray-400 uppercase mb-1">Tagihan Kepada</p>
                <p class="font-semibold text-gray-800">{{ $invoice->client->name }}</p>
                <p class="text-sm text-gray-500">{{ $invoice->client->email }}</p>
            </div>
            <div class="text-right">
                <p class="text-xs text-gray-400 uppercase mb-1">Detail</p>
                <p class="text-sm text-gray-600">Diterbitkan: <span class="font-medium">{{ $invoice->issue_date->format('d M Y') }}</span></p>
                <p class="text-sm {{ $invoice->status === 'overdue' ? 'text-red-600 font-bold' : 'text-gray-600' }}">
                    Jatuh Tempo: <span class="font-medium">{{ $invoice->due_date->format('d M Y') }}</span>
                </p>
                @if($invoice->paid_at)
                <p class="text-sm text-green-600">Dibayar: <span class="font-medium">{{ $invoice->paid_at->format('d M Y') }}</span></p>
                @endif
            </div>
        </div>

        {{-- Items --}}
        <div class="px-6 py-4">
            <table class="w-full text-sm">
                <thead class="text-xs uppercase text-gray-500 border-b border-gray-100">
                    <tr>
                        <th class="pb-2 text-left">Deskripsi</th>
                        <th class="pb-2 text-center">Qty</th>
                        <th class="pb-2 text-right">Harga Satuan</th>
                        <th class="pb-2 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($invoice->items as $item)
                    <tr>
                        <td class="py-2.5 text-gray-700">{{ $item->description }}</td>
                        <td class="py-2.5 text-center text-gray-600">{{ $item->quantity }}</td>
                        <td class="py-2.5 text-right text-gray-600">Rp {{ number_format($item->unit_price,0,',','.') }}</td>
                        <td class="py-2.5 text-right font-medium text-gray-800">Rp {{ number_format($item->total,0,',','.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Totals --}}
            <div class="mt-4 pt-4 border-t border-gray-100 space-y-1 text-sm max-w-xs ml-auto">
                <div class="flex justify-between text-gray-600"><span>Subtotal</span><span>Rp {{ number_format($invoice->subtotal,0,',','.') }}</span></div>
                <div class="flex justify-between text-gray-600"><span>Pajak ({{ $invoice->tax }}%)</span><span>Rp {{ number_format($invoice->total - $invoice->subtotal,0,',','.') }}</span></div>
                <div class="flex justify-between font-bold text-base text-gray-800 pt-2 border-t border-gray-200"><span>TOTAL</span><span>Rp {{ number_format($invoice->total,0,',','.') }}</span></div>
            </div>

            @if($invoice->notes)
                <div class="mt-4 p-3 bg-gray-50 rounded-lg text-sm text-gray-600"><strong>Catatan:</strong> {{ $invoice->notes }}</div>
            @endif
        </div>

        {{-- Actions --}}
        @if($user->hasRole(['admin','manager']))
        <div class="px-6 py-4 border-t border-gray-100 flex gap-3">
            @if($invoice->status === 'draft')
            <form method="POST" action="{{ route('invoices.send', $invoice) }}">
                @csrf @method('PUT')
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">Kirim ke Client</button>
            </form>
            @endif
            @if(in_array($invoice->status, ['sent','overdue']))
            <form method="POST" action="{{ route('invoices.markPaid', $invoice) }}">
                @csrf @method('PUT')
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">Tandai Lunas</button>
            </form>
            @endif
        </div>
        @endif
    </div>
</div>
@endsection
