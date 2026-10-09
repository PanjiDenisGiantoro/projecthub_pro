@extends('layouts.app')
@section('title', 'Riwayat Pembayaran')
@section('page-title', 'Riwayat Pembayaran')

@section('content')
<div class="py-4">
    <div class="flex items-center justify-between mb-4">
        <p class="text-sm text-gray-500">Riwayat transaksi langganan perusahaan Anda.</p>
        <a href="{{ route('billing.renew') }}" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
            Perpanjang / Upgrade Paket
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 text-left">No. Order</th>
                    <th class="px-4 py-3 text-left">Paket</th>
                    <th class="px-4 py-3 text-left">Jumlah</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Tanggal Dibuat</th>
                    <th class="px-4 py-3 text-left">Tanggal Dibayar</th>
                    <th class="px-4 py-3 text-right">Invoice</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @php
                    $statusClass = [
                        'pending' => 'bg-amber-100 text-amber-700',
                        'paid'    => 'bg-green-100 text-green-700',
                        'failed'  => 'bg-red-100 text-red-700',
                        'expired' => 'bg-red-100 text-red-700',
                    ];
                    $statusLabel = [
                        'pending' => 'Menunggu Pembayaran',
                        'paid'    => 'Lunas',
                        'failed'  => 'Gagal',
                        'expired' => 'Kedaluwarsa',
                    ];
                @endphp
                @forelse($orders as $order)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-mono font-medium text-gray-800">{{ $order->order_number }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $order->package?->name ?? $order->package_name }}</td>
                    <td class="px-4 py-3 font-medium text-gray-800">
                        Rp {{ number_format($order->amount, 0, ',', '.') }}
                        @if($order->ppn_amount > 0)
                            <p class="text-[11px] font-normal text-gray-400">
                                Rp {{ number_format($order->subtotal, 0, ',', '.') }} + PPN {{ \App\Models\PpnRate::formatRate($order->ppn_rate) }} Rp {{ number_format($order->ppn_amount, 0, ',', '.') }}
                            </p>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <span class="badge {{ $statusClass[$order->status] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ $statusLabel[$order->status] ?? ucfirst($order->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-500">{{ $order->created_at->translatedFormat('d M Y H:i') }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ $order->paid_at?->translatedFormat('d M Y H:i') ?? '—' }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('billing.invoice', $order->order_number) }}" target="_blank"
                           class="inline-flex items-center gap-1.5 text-xs font-medium text-blue-600 hover:text-blue-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            PDF
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-gray-400">Belum ada riwayat pembayaran.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-gray-100 flex items-center justify-between gap-3 flex-wrap">
            <x-per-page />
            @if($orders->hasPages())
            {{ $orders->links() }}
            @endif
        </div>
    </div>
</div>
@endsection
