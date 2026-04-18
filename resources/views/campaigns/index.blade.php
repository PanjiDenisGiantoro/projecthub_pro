@extends('layouts.app')
@section('title', 'Marketing Campaigns')
@section('page-title', 'Marketing Campaigns')

@section('content')
<div class="py-4 space-y-5">

    {{-- ── Stats Bar ──────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        @php
        $statCards = [
            ['label'=>'Total Campaign',  'value'=> $stats['total'],           'color'=>'text-gray-800'],
            ['label'=>'Aktif',           'value'=> $stats['active'],          'color'=>'text-green-600'],
            ['label'=>'Total Leads',     'value'=> number_format($stats['total_leads']), 'color'=>'text-blue-600'],
            ['label'=>'Converted',       'value'=> number_format($stats['converted']),  'color'=>'text-emerald-600'],
            ['label'=>'Conversion Rate', 'value'=> $stats['conversion_rate'].'%',       'color'=>'text-violet-600'],
            ['label'=>'Total Spend',     'value'=> 'Rp '.number_format($stats['total_spend'],0,',','.'), 'color'=>'text-orange-600'],
        ];
        @endphp
        @foreach($statCards as $s)
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3 text-center">
            <p class="text-xs text-gray-400 mb-1">{{ $s['label'] }}</p>
            <p class="text-lg font-bold {{ $s['color'] }}">{{ $s['value'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- ── Filter + Search + Create ──────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center gap-3 justify-between">
        <form method="GET" class="flex flex-wrap gap-2 items-center">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari campaign…"
                   class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 w-48">
            <select name="status" onchange="this.form.submit()"
                    class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Semua Status</option>
                @foreach(['draft','active','paused','completed','cancelled'] as $s)
                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <select name="channel" onchange="this.form.submit()"
                    class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Semua Channel</option>
                @foreach(['social_media','email','event','ads','seo','other'] as $c)
                <option value="{{ $c }}" {{ request('channel') === $c ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$c)) }}</option>
                @endforeach
            </select>
            @if(request()->hasAny(['search','status','channel']))
            <a href="{{ route('campaigns.index') }}" class="text-xs text-gray-400 hover:text-gray-600">× Reset</a>
            @endif
        </form>
        @can('manage campaigns')
        <a href="{{ route('campaigns.create') }}"
           class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Buat Campaign
        </a>
        @endcan
    </div>

    {{-- ── Campaign Cards ─────────────────────────────────────────────────────── --}}
    @php
    $statusStyle = ['draft'=>'bg-gray-100 text-gray-600','active'=>'bg-green-100 text-green-700','paused'=>'bg-yellow-100 text-yellow-700','completed'=>'bg-blue-100 text-blue-700','cancelled'=>'bg-red-100 text-red-700'];
    $chanIcon    = ['social_media'=>'📱','email'=>'📧','event'=>'🎪','ads'=>'📢','seo'=>'🔍','other'=>'🔗'];
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @forelse($campaigns as $c)
        @php
            $pct = $c->progress_percent;
            $overdue = $c->isOverdue();
        @endphp
        <div class="bg-white rounded-xl border {{ $overdue ? 'border-red-300' : 'border-gray-200' }} hover:shadow-md transition-shadow flex flex-col">
            {{-- Card header --}}
            <div class="p-5 flex-1">
                <div class="flex items-start justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">{{ $chanIcon[$c->channel] ?? '📌' }}</span>
                        <span class="text-xs font-medium text-gray-400 uppercase">{{ str_replace('_',' ',$c->channel) }}</span>
                    </div>
                    <span class="inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full {{ $statusStyle[$c->status] ?? 'bg-gray-100 text-gray-600' }}">
                        {{ ucfirst($c->status) }}
                    </span>
                </div>

                <h3 class="font-semibold text-gray-800 mb-1 leading-snug">{{ $c->name }}</h3>
                @if($c->description)
                <p class="text-xs text-gray-400 mb-3 line-clamp-2">{{ $c->description }}</p>
                @endif

                {{-- Meta --}}
                <div class="grid grid-cols-2 gap-x-3 gap-y-1.5 text-xs text-gray-500 mb-4">
                    <div class="flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Budget: <span class="font-medium text-gray-700">{{ $c->budget ? 'Rp '.number_format($c->budget,0,',','.') : '—' }}</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Leads: <span class="font-medium text-gray-700">{{ $c->leads_count }}{{ $c->goal_leads ? '/'.$c->goal_leads : '' }}</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        {{ $c->start_date?->format('d M') ?? '—' }} – {{ $c->end_date?->format('d M Y') ?? '—' }}
                    </div>
                    <div class="flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        Conv: <span class="font-medium text-emerald-600">{{ $c->conversion_rate }}%</span>
                    </div>
                </div>

                {{-- Lead progress bar --}}
                @if($c->goal_leads > 0)
                <div class="mb-1 flex justify-between text-xs text-gray-400">
                    <span>Progress Lead</span><span>{{ $pct }}%</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-1.5 mb-3 overflow-hidden">
                    <div class="h-1.5 rounded-full {{ $pct >= 100 ? 'bg-green-500' : 'bg-blue-500' }}" style="width:{{ $pct }}%"></div>
                </div>
                @endif

                {{-- Owner --}}
                @if($c->owner)
                <p class="text-xs text-gray-400">PIC: <span class="font-medium text-gray-600">{{ $c->owner->name }}</span></p>
                @endif
            </div>

            {{-- Card footer --}}
            <div class="px-5 py-3 border-t border-gray-100 flex items-center justify-between">
                @if($overdue)
                <span class="text-xs text-red-500 font-medium">⚠ Overdue</span>
                @elseif($c->days_remaining !== null)
                <span class="text-xs text-gray-400">{{ $c->days_remaining >= 0 ? $c->days_remaining.' hari lagi' : 'Selesai' }}</span>
                @else
                <span></span>
                @endif
                <a href="{{ route('campaigns.show', $c) }}"
                   class="text-xs font-semibold text-blue-600 hover:text-blue-800">Lihat →</a>
            </div>
        </div>
        @empty
        <div class="col-span-3 text-center py-16 text-gray-400">
            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
            Belum ada campaign.
        </div>
        @endforelse
    </div>

    @if($campaigns->hasPages())
    <div class="mt-2">{{ $campaigns->links() }}</div>
    @endif

</div>
@endsection
