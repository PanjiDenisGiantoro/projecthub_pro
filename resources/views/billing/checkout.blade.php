@extends('layouts.app')

@section('title', 'Pembayaran')
@section('page-title', 'Pembayaran')

@section('content')
@php
    $rp = fn ($v) => 'Rp ' . number_format((int) $v, 0, ',', '.');
    $initial = old('payment_method', $methods->firstWhere('code', request('method'))?->code ?? $methods->first()?->code);
@endphp

<div class="max-w-4xl mx-auto py-6"
     x-data="{
        method: @js($initial),
        fees: @js($fees),
        names: @js($methods->pluck('name', 'code')),
        base: {{ $quote['total'] }},
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
        <input type="hidden" name="users" value="{{ $quote['users'] }}">
        <input type="hidden" name="cycle" value="{{ $quote['cycle'] }}">
        <input type="hidden" name="payment_method" :value="method">

        <div class="lg:col-span-2 space-y-5">
        {{-- Jumlah user & siklus tagihan (GET — rincian dihitung ulang di server) --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <h2 class="text-base font-bold text-gray-900">Paket {{ $package->name }}</h2>
            <p class="text-sm text-gray-500 mt-1">
                {{ $rp($package->pricePerUser('monthly')) }} / user / bulan
                @if($package->price_yearly)
                    · {{ $rp($package->pricePerUser('yearly') * 12) }} / user / tahun
                @endif
                · {{ $package->usersRangeDisplay() }} user
            </p>

            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Siklus Tagihan</label>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach(['monthly' => 'Bulanan', 'yearly' => 'Tahunan'] as $cycle => $label)
                            <a :href="@js(route('billing.checkout.show', [$package, 'users' => $quote['users'], 'cycle' => $cycle])) + '&method=' + encodeURIComponent(method ?? '')"
                               class="rounded-xl border-2 px-3 py-2 text-center text-sm font-semibold transition-all {{ $quote['cycle'] === $cycle ? 'border-blue-600 bg-blue-50 text-blue-700' : 'border-gray-200 text-gray-700 hover:border-gray-300' }}">
                                {{ $label }}
                                @if($cycle === 'yearly' && $package->price_yearly && $package->price_yearly < $package->price)
                                    <span class="block text-[11px] font-medium text-emerald-600">hemat {{ round((1 - $package->price_yearly / $package->price) * 100) }}%</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
                <div>
                    <label for="users-input" class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Jumlah User</label>
                    <div class="flex gap-2">
                        <input id="users-input" type="number" form="quote-form" name="users" value="{{ $quote['users'] }}"
                               min="{{ $package->min_users ?? 1 }}" @if($package->max_users) max="{{ $package->max_users }}" @endif
                               class="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button type="submit" form="quote-form" class="px-3 py-2 rounded-xl border border-gray-300 text-sm font-semibold text-gray-700 hover:bg-gray-50">Hitung</button>
                    </div>
                    <p class="text-[11px] text-gray-400 mt-1">Paket {{ $package->name }} untuk {{ $package->usersRangeDisplay() }} user.</p>
                </div>
            </div>
        </div>

        {{-- Pilih metode bayar — biaya layanan ditanggung customer --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <h2 class="text-base font-bold text-gray-900">Pilih Metode Pembayaran</h2>
            <p class="text-sm text-gray-500 mt-1">Biaya layanan berbeda per metode dan ditambahkan ke total pembayaran.</p>

            @forelse($methods->groupBy('group') as $group => $items)
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mt-5 mb-2">{{ $group }}</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    @foreach($items as $m)
                        <button type="button" @click="method = @js($m->code)"
                                class="flex items-center justify-between gap-3 rounded-xl border-2 px-4 py-3 text-left transition-all"
                                :class="method === @js($m->code) ? 'border-blue-600 bg-blue-50' : 'border-gray-200 hover:border-gray-300 bg-white'">
                            <div>
                                <p class="text-sm font-semibold text-gray-900">{{ $m->name }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    Biaya layanan: {{ $fees[$m->code] > 0 ? $rp($fees[$m->code]) : 'Gratis' }}
                                </p>
                            </div>
                            <svg x-show="method === @js($m->code)" class="w-5 h-5 text-blue-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                        </button>
                    @endforeach
                </div>
            @empty
                <p class="text-sm text-gray-500 mt-4">Belum ada metode pembayaran yang aktif. Silakan hubungi administrator.</p>
            @endforelse
        </div>
        </div>

        {{-- Ringkasan --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 self-start lg:sticky lg:top-4">
            <h2 class="text-base font-bold text-gray-900">Ringkasan</h2>
            <p class="text-sm text-gray-500 mt-1">Paket {{ $package->name }} · {{ $quote['cycle'] === 'yearly' ? 'Tahunan' : 'Bulanan' }} · {{ $quote['duration_days'] }} hari</p>

            <div class="mt-4 text-sm space-y-1">
                <div class="flex justify-between gap-3 text-gray-500">
                    <span>{{ $rp($quote['price_per_user'] * $quote['months']) }} × {{ $quote['users'] }} user ({{ $quote['cycle'] === 'yearly' ? '1 tahun' : '1 bulan' }})</span>
                    <span class="text-gray-800 whitespace-nowrap">{{ $rp($quote['gross']) }}</span>
                </div>
                @if($quote['discount'] > 0)
                    <div class="flex justify-between gap-3 text-emerald-600">
                        <span>Promo {{ $quote['promo']->name }} ({{ $quote['promo']->valueLabel() }})</span>
                        <span class="whitespace-nowrap">−{{ $rp($quote['discount']) }}</span>
                    </div>
                @endif
                <div class="flex justify-between text-gray-500">
                    <span>PPN {{ \App\Models\PpnRate::formatRate($quote['rate']) }}</span>
                    <span class="text-gray-800">{{ $rp($quote['ppn']) }}</span>
                </div>
                <div class="flex justify-between gap-3 text-gray-500">
                    <span>Biaya layanan <span class="text-gray-400" x-text="'(' + (names[method] ?? '-') + ')'">({{ $methods->firstWhere('code', $initial)?->name ?? '-' }})</span></span>
                    <span class="text-gray-800 whitespace-nowrap" x-text="fee > 0 ? rp(fee) : 'Gratis'">{{ ($fees[$initial] ?? 0) > 0 ? $rp($fees[$initial]) : 'Gratis' }}</span>
                </div>
                <div class="flex justify-between font-semibold text-gray-900 border-t border-gray-200 pt-2 mt-2 text-base">
                    <span>Total</span>
                    <span x-text="rp(total)">{{ $rp($quote['total'] + ($fees[$initial] ?? 0)) }}</span>
                </div>
            </div>

            <button type="submit" :disabled="!method"
                    class="mt-5 w-full py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-sm font-semibold transition-colors">
                Bayar dengan DOKU
            </button>
            <p class="text-xs text-gray-400 mt-2 text-center">Anda akan diarahkan ke halaman pembayaran DOKU.</p>
        </div>
    </form>

    <form id="quote-form" method="GET" action="{{ route('billing.checkout.show', $package) }}" class="hidden">
        <input type="hidden" name="cycle" value="{{ $quote['cycle'] }}">
        <input type="hidden" name="method" :value="method">
    </form>
</div>
@endsection
