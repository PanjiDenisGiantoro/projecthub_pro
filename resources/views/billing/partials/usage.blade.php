{{-- Kuota paket (project, user, storage) — dipakai di halaman Subscribe & Usage (billing.history). --}}
@php

    $percent = fn (int $used, ?int $max) => $max ? min(100, round($used / $max * 100)) : null;
    $barColor = fn (?float $p) => $p === null ? 'bg-blue-600' : ($p >= 100 ? 'bg-red-500' : ($p >= 80 ? 'bg-amber-500' : 'bg-blue-600'));

    $storageUsed = $limits->storageUsedBytes();
    $storageMax  = $limits->maxStorageBytes();

    $cards = [
        [
            'title'  => 'Project',
            'used'   => number_format($limits->projectCount(), 0, ',', '.'),
            'limit'  => \App\Models\Package::limitDisplay($limits->maxProjects()),
            'pct'    => $percent($limits->projectCount(), $limits->maxProjects()),
            'note'   => 'Project yang dihapus tidak dihitung.',
        ],
        [
            'title'  => 'User',
            'used'   => number_format($limits->userCount(), 0, ',', '.'),
            'limit'  => \App\Models\Package::limitDisplay($limits->maxUsers()),
            'pct'    => $percent($limits->userCount(), $limits->maxUsers()),
            'note'   => 'User aktif & nonaktif dihitung. Akun client tidak dihitung.',
        ],
        [
            'title'  => 'Storage',
            'used'   => \App\Support\PlanLimits::humanBytes($storageUsed),
            'limit'  => $limits->storageLimitLabel(),
            'pct'    => $storageMax ? min(100, round($storageUsed / $storageMax * 100)) : null,
            'note'   => 'Avatar, logo & foto wajah absensi tidak dihitung.',
        ],
    ];
    $anyFull = collect($cards)->contains(fn ($c) => $c['pct'] !== null && $c['pct'] >= 80);
@endphp

<div class="space-y-4">
    <h2 class="text-sm font-semibold text-gray-700">
        Pemakaian Kuota — Paket {{ $limits->planName() }}
    </h2>

    @if($anyFull)
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Sebagian kuota hampir atau sudah habis. Upgrade paket agar tim tetap bisa menambah data.
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach($cards as $card)
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">{{ $card['title'] }}</h3>
                    @if($card['pct'] !== null)
                        <span class="text-xs font-medium {{ $card['pct'] >= 100 ? 'text-red-600' : ($card['pct'] >= 80 ? 'text-amber-600' : 'text-gray-500') }}">{{ $card['pct'] }}%</span>
                    @endif
                </div>
                <p class="mt-2 text-2xl font-bold text-gray-900">
                    {{ $card['used'] }} <span class="text-sm font-medium text-gray-400">/ {{ $card['limit'] }}</span>
                </p>
                <div class="mt-3 h-2 w-full rounded-full bg-gray-100 overflow-hidden">
                    <div class="h-2 rounded-full {{ $barColor($card['pct']) }}" style="width: {{ $card['pct'] ?? 0 }}%"></div>
                </div>
                <p class="mt-3 text-xs text-gray-500">{{ $card['note'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700">Rincian Storage per Menu</h3>
        </div>
        @if($breakdown->isEmpty())
            <p class="px-5 py-6 text-sm text-gray-500">Belum ada file yang di-upload.</p>
        @else
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                    <tr>
                        <th class="px-5 py-3 text-left">Menu</th>
                        <th class="px-5 py-3 text-left">Pemakaian</th>
                        <th class="px-5 py-3 text-right">Ukuran</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($breakdown as $menu => $bytes)
                        @php $share = $storageUsed ? round($bytes / $storageUsed * 100) : 0; @endphp
                        <tr>
                            <td class="px-5 py-3 text-gray-700">{{ $menu }}</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="h-2 w-full rounded-full bg-gray-100 overflow-hidden">
                                        <div class="h-2 rounded-full bg-blue-600" style="width: {{ $share }}%"></div>
                                    </div>
                                    <span class="text-xs text-gray-500 w-10 text-right">{{ $share }}%</span>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-right font-medium text-gray-900 whitespace-nowrap">{{ \App\Support\PlanLimits::humanBytes($bytes) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
