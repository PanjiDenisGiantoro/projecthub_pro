@extends('layouts.app')

@section('title', 'Status Pembayaran')
@section('page-title', 'Status Pembayaran')

@section('content')
<div class="max-w-lg mx-auto py-10">
    <div class="rounded-2xl border border-gray-200 bg-white p-8 text-center shadow-sm">

        @if($order && $order->isPaid())
            {{-- Sukses --}}
            <div class="w-14 h-14 rounded-full bg-green-50 flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h2 class="text-lg font-bold text-gray-900">Pembayaran Berhasil</h2>
            <p class="text-sm text-gray-500 mt-2">
                Terima kasih! Paket <span class="font-medium text-gray-700">{{ $order->package_name }}</span> Anda sudah aktif.
                Selamat menggunakan Flovig.
            </p>
        @elseif($order && $order->isFailed())
            {{-- Gagal --}}
            <div class="w-14 h-14 rounded-full bg-red-50 flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </div>
            <h2 class="text-lg font-bold text-gray-900">Pembayaran Gagal</h2>
            <p class="text-sm text-gray-500 mt-2">
                Transaksi dibatalkan, ditolak, atau kadaluarsa sebelum diselesaikan.
                Silakan coba lagi dari halaman perpanjangan.
            </p>
        @else
            {{-- Masih diproses / menunggu webhook (mis. VA yang belum di-transfer) --}}
            <div class="w-14 h-14 rounded-full bg-violet-50 flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h2 class="text-lg font-bold text-gray-900">Pembayaran Sedang Diproses</h2>
            <p class="text-sm text-gray-500 mt-2">
                Terima kasih! Status pembayaran Anda sedang diverifikasi oleh Midtrans.
                Untuk transfer Virtual Account, halaman ini akan otomatis memperbarui diri
                begitu pembayaran dikonfirmasi.
            </p>
        @endif

        @if($order)
            <div class="mt-5 text-left text-sm bg-gray-50 rounded-xl p-4 space-y-1">
                <p class="text-gray-500">No. Order: <span class="font-mono text-gray-800">{{ $order->order_number }}</span></p>
                <p class="text-gray-500">Paket: <span class="font-medium text-gray-800">{{ $order->package_name }}</span></p>
                <p class="text-gray-500">Status: <span class="font-medium text-gray-800 capitalize">{{ $order->status }}</span></p>
            </div>
        @endif

        <div class="mt-6 space-y-2">
            @if($order && $order->isFailed())
                <a href="{{ route('billing.renew') }}"
                   class="inline-flex items-center justify-center w-full py-2.5 rounded-xl bg-violet-600 hover:bg-violet-700 text-white text-sm font-semibold transition-colors">
                    Coba Lagi
                </a>
            @endif
            <a href="{{ route('dashboard') }}"
               class="inline-flex items-center justify-center w-full py-2.5 rounded-xl border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-semibold transition-colors">
                Kembali ke Dashboard
            </a>
        </div>
    </div>
</div>

@if($order && ! $order->isPaid() && ! $order->isFailed())
    @push('scripts')
    <script>
        (function () {
            var checkUrl = @json(route('billing.status', $order->order_number));
            var interval = setInterval(function () {
                fetch(checkUrl, { headers: { 'Accept': 'application/json' } })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (data.status !== 'pending') {
                            clearInterval(interval);
                            window.location.reload();
                        }
                    })
                    .catch(function () { /* diamkan, coba lagi di interval berikutnya */ });
            }, 4000);
        })();
    </script>
    @endpush
@endif
@endsection
