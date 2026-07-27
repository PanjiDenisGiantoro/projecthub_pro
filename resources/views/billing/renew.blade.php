@extends('layouts.app')

@section('title', 'Perpanjang Langganan')
@section('page-title', 'Perpanjang Langganan')

@section('content')
<div class="max-w-4xl mx-auto py-6 space-y-5">

    {{-- Status masa aktif --}}
    <div class="rounded-2xl border p-5 {{ $registrant?->isExpired() ? 'bg-red-50 border-red-200' : 'bg-amber-50 border-amber-200' }}">
        <h2 class="text-lg font-bold {{ $registrant?->isExpired() ? 'text-red-800' : 'text-amber-800' }}">
            @if(!$registrant || $registrant->isLifetime())
                Status Langganan
            @elseif($registrant->isExpired())
                Masa Aktif Telah Berakhir
            @else
                Masa Aktif Perusahaan Anda
            @endif
        </h2>
        <p class="text-sm mt-1 {{ $registrant?->isExpired() ? 'text-red-700' : 'text-amber-700' }}">
            @if(!$registrant || $registrant->isLifetime())
                Perusahaan Anda memiliki akses lifetime / belum ada data masa aktif.
            @elseif($registrant->isExpired())
                Berakhir sejak {{ $registrant->active_until->translatedFormat('d M Y') }}. Pilih paket di bawah untuk memperpanjang.
            @else
                Aktif hingga {{ $registrant->active_until->translatedFormat('d M Y') }}.
            @endif
        </p>
    </div>

    @if(!$isRegistrant)
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <p class="text-sm text-gray-600">
                Hanya admin pendaftar perusahaan yang dapat melakukan pembayaran perpanjangan.
                @if($registrant)
                    Silakan hubungi <span class="font-semibold text-gray-800">{{ $registrant->name }}</span>
                    ({{ $registrant->email }}) untuk memperpanjang masa aktif perusahaan.
                @else
                    Silakan hubungi admin perusahaan Anda.
                @endif
            </p>
        </div>
    @endif

    {{-- Daftar Paket --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        @forelse($packages as $package)
            @php $owned = in_array($package->slug, $ownedSlugs); @endphp
            <div class="rounded-2xl border border-gray-200 bg-white p-6 flex flex-col shadow-sm">
                <div class="flex items-start justify-between gap-2">
                    <h3 class="text-base font-bold text-gray-900">{{ $package->name }}</h3>
                    @if($owned)
                        <span class="shrink-0 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[11px] font-semibold">Aktif</span>
                    @endif
                </div>
                <p class="text-sm text-gray-500 mt-2 flex-1">{{ $package->description }}</p>

                <div class="mt-4">
                    <p class="text-2xl font-bold text-gray-900">
                        Rp{{ number_format($package->price, 0, ',', '.') }}
                    </p>
                    <p class="text-xs text-gray-400 mt-0.5">/ {{ $package->duration_days }} hari</p>
                </div>

                @if($isRegistrant)
                    <form method="POST" action="{{ route('billing.checkout', $package) }}" class="mt-5">
                        @csrf
                        <button type="submit"
                                class="w-full py-2.5 rounded-xl bg-violet-600 hover:bg-violet-700 text-white text-sm font-semibold transition-colors">
                            {{ $owned ? 'Perpanjang dengan Midtrans' : 'Berlangganan dengan Midtrans' }}
                        </button>
                    </form>
                @endif
            </div>
        @empty
            <p class="text-sm text-gray-500 col-span-2">Belum ada paket tersedia.</p>
        @endforelse
    </div>
</div>
@endsection
