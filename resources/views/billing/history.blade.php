@extends('layouts.app')
@section('title', 'Riwayat Pembayaran')
@section('page-title', 'Riwayat Pembayaran')

@section('content')
<div class="py-4">
    <div class="flex items-center justify-between mb-4">
        <p class="text-sm text-gray-500">Riwayat transaksi langganan perusahaan Anda.</p>
        <a href="{{ route('billing.renew') }}" class="inline-flex items-center gap-2 bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
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
                    <td class="px-4 py-3 font-medium text-gray-800">Rp {{ number_format($order->amount, 0, ',', '.') }}</td>
                    <td class="px-4 py-3">
                        <span class="badge {{ $statusClass[$order->status] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ $statusLabel[$order->status] ?? ucfirst($order->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-500">{{ $order->created_at->translatedFormat('d M Y H:i') }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ $order->paid_at?->translatedFormat('d M Y H:i') ?? '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-400">Belum ada riwayat pembayaran.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($orders->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $orders->links() }}</div>
        @endif
    </div>
</div>
@endsection
