@extends('layouts.app')

@section('title', 'Pembayaran')
@section('page-title', 'Pembayaran')

@section('content')
@php
    $rp = fn ($v) => 'Rp ' . number_format((int) $v, 0, ',', '.');
    $initial = old('payment_method', $methods->firstWhere('code', 'QRIS')?->code ?? $methods->first()?->code);
@endphp

<div class="max-w-4xl mx-auto py-6"
     x-data="{
        method: @js($initial),
        fees: @js($fees),
        names: @js($methods->pluck('name', 'code')),
        base: {{ $price['total'] }},
        rp(v) { return 'Rp ' + Number(v).toLocaleString('id-ID'); },
        get fee() { return this.fees[this.method] ?? 0; },
        get total() { return this.base + this.fee; },
     }">

    <a href="{{ route('billing.renew') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 mb-4">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Kembali ke pilihan paket
    </a>

    @if($errors->any())
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('billing.checkout', $package) }}" class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        @csrf
        <input type="hidden" name="payment_method" :value="method">

        {{-- Pilih metode --}}
        <div class="lg:col-span-2 rounded-2xl border border-gray-200 bg-white p-5">
            <h2 class="text-base font-bold text-gray-900">Pilih Metode Pembayaran</h2>
            <p class="text-sm text-gray-500 mt-1">Biaya layanan berbeda per metode dan ditambahkan ke total pembayaran.</p>

            @forelse($methods->groupBy('group') as $group => $items)
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mt-5 mb-2">{{ $group }}</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    @foreach($items as $m)
                        <button type="button" @click="method = '{{ $m->code }}'"
                                class="flex items-center justify-between gap-3 rounded-xl border-2 px-4 py-3 text-left transition-all"
                                :class="method === '{{ $m->code }}' ? 'border-blue-600 bg-blue-50' : 'border-gray-200 hover:border-gray-300 bg-white'">
                            <div>
                                <p class="text-sm font-semibold text-gray-900">{{ $m->name }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    Biaya layanan: {{ $fees[$m->code] > 0 ? $rp($fees[$m->code]) : 'Gratis' }}
                                </p>
                            </div>
                            <svg x-show="method === '{{ $m->code }}'" class="w-5 h-5 text-blue-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                        </button>
                    @endforeach
                </div>
            @empty
                <p class="text-sm text-gray-500 mt-4">Belum ada metode pembayaran yang aktif. Silakan hubungi administrator.</p>
            @endforelse
        </div>

        {{-- Ringkasan --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 self-start lg:sticky lg:top-4">
            <h2 class="text-base font-bold text-gray-900">Ringkasan</h2>
            <p class="text-sm text-gray-500 mt-1">Paket {{ $package->name }} · {{ $package->duration_days }} hari</p>

            <div class="mt-4 text-sm space-y-1">
                <div class="flex justify-between text-gray-500">
                    <span>Harga paket</span>
                    <span class="text-gray-800">{{ $rp($price['subtotal']) }}</span>
                </div>
                <div class="flex justify-between text-gray-500">
                    <span>PPN {{ \App\Models\PpnRate::formatRate($price['rate']) }}</span>
                    <span class="text-gray-800">{{ $rp($price['ppn']) }}</span>
                </div>
                <div class="flex justify-between text-gray-500">
                    <span>Biaya layanan <span class="text-gray-400" x-text="'(' + (names[method] ?? '-') + ')'"></span></span>
                    <span class="text-gray-800" x-text="fee > 0 ? rp(fee) : 'Gratis'"></span>
                </div>
                <div class="flex justify-between font-semibold text-gray-900 border-t border-gray-200 pt-2 mt-2 text-base">
                    <span>Total</span>
                    <span x-text="rp(total)">{{ $rp($price['total']) }}</span>
                </div>
            </div>

            <button type="submit" :disabled="!method"
                    class="mt-5 w-full py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-sm font-semibold transition-colors">
                Bayar dengan DOKU
            </button>
            <p class="text-xs text-gray-400 mt-2 text-center">Anda akan diarahkan ke halaman pembayaran DOKU.</p>
        </div>
    </form>
</div>
@endsection
