@extends('layouts.app')
@section('title', $campaign->name)
@section('page-title', 'Campaign Detail')

@section('content')
@php
$statusStyle = ['draft'=>'bg-gray-100 text-gray-600','active'=>'bg-green-100 text-green-700','paused'=>'bg-yellow-100 text-yellow-700','completed'=>'bg-blue-100 text-blue-700','cancelled'=>'bg-red-100 text-red-700'];
$chanIcon    = ['social_media'=>'📱','email'=>'📧','event'=>'🎪','ads'=>'📢','seo'=>'🔍','other'=>'🔗'];
$leadStatusStyle = ['lead'=>'bg-blue-100 text-blue-700','prospect'=>'bg-yellow-100 text-yellow-700','client'=>'bg-green-100 text-green-700','lost'=>'bg-gray-100 text-gray-500'];
$scoreLabel  = fn($s) => match(true) { $s>=8=>'hot', $s>=5=>'warm', $s>0=>'cold', default=>'-' };
$scoreStyle  = ['hot'=>'bg-red-100 text-red-600','warm'=>'bg-orange-100 text-orange-600','cold'=>'bg-blue-100 text-blue-500','-'=>'bg-gray-100 text-gray-400'];
$sourcLabels = ['website'=>'Website','referral'=>'Referral','ads'=>'Ads','event'=>'Event','cold_call'=>'Cold Call','other'=>'Lainnya'];
@endphp

<div class="py-4" x-data="campaignApp()" x-init="init()">

    {{-- Breadcrumb + actions --}}
    <div class="flex flex-wrap items-center justify-between mb-5 gap-3">
        <div class="flex items-center gap-2 text-sm text-gray-500">
            <a href="{{ route('campaigns.index') }}" class="hover:text-blue-600">Campaigns</a>
            <span>/</span>
            <span class="text-gray-700 font-medium">{{ $campaign->name }}</span>
        </div>
        <div class="flex gap-2">
            @can('manage campaigns')
            <a href="{{ route('campaigns.edit', $campaign) }}"
               class="inline-flex items-center gap-1.5 text-sm border border-gray-300 text-gray-600 px-3 py-1.5 rounded-lg hover:bg-gray-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Edit
            </a>
            <form method="POST" action="{{ route('campaigns.destroy', $campaign) }}" data-confirm-delete="{{ $campaign->name }}">
                @csrf @method('DELETE')
                <button type="submit" class="inline-flex items-center gap-1.5 text-sm border border-red-200 text-red-500 px-3 py-1.5 rounded-lg hover:bg-red-50">
                    Hapus
                </button>
            </form>
            @endcan
        </div>
    </div>

    {{-- Campaign header --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5 mb-5">
        <div class="flex flex-wrap items-start gap-4 justify-between">
            <div class="flex items-start gap-3">
                <span class="text-3xl">{{ $chanIcon[$campaign->channel] ?? '📌' }}</span>
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <h2 class="text-xl font-bold text-gray-800">{{ $campaign->name }}</h2>
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $statusStyle[$campaign->status] ?? '' }}">{{ ucfirst($campaign->status) }}</span>
                        @if($campaign->isOverdue())<span class="text-xs font-medium px-2 py-0.5 rounded-full bg-red-100 text-red-600">⚠ Overdue</span>@endif
                    </div>
                    @if($campaign->description)
                    <p class="text-sm text-gray-500">{{ $campaign->description }}</p>
                    @endif
                    <div class="flex flex-wrap gap-4 mt-2 text-xs text-gray-500">
                        <span>Channel: <strong>{{ ucfirst(str_replace('_',' ',$campaign->channel)) }}</strong></span>
                        @if($campaign->project)<span>Proyek: <strong>{{ $campaign->project->name }}</strong></span>@endif
                        @if($campaign->owner)<span>PIC: <strong>{{ $campaign->owner->name }}</strong></span>@endif
                        @if($campaign->start_date)<span>📅 {{ $campaign->start_date->format('d M Y') }} – {{ $campaign->end_date?->format('d M Y') ?? '∞' }}</span>@endif
                    </div>
                </div>
            </div>

            {{-- KPI chips --}}
            <div class="flex flex-wrap gap-3">
                @php
                $kpis = [
                    ['v'=>$campaign->leads_count.($campaign->goal_leads ? '/'.$campaign->goal_leads : ''), 'l'=>'Leads', 'c'=>'text-blue-600'],
                    ['v'=>$campaign->conversion_rate.'%', 'l'=>'Konversi', 'c'=>'text-emerald-600'],
                    ['v'=>number_format($campaign->impressions), 'l'=>'Impressions', 'c'=>'text-gray-700'],
                    ['v'=>$campaign->ctr.'%', 'l'=>'CTR', 'c'=>'text-violet-600'],
                    ['v'=>$campaign->budget ? 'Rp '.number_format($campaign->budget,0,',','.') : '—', 'l'=>'Budget', 'c'=>'text-gray-700'],
                    ['v'=>$campaign->actual_spend > 0 ? 'Rp '.number_format($campaign->actual_spend,0,',','.') : '—', 'l'=>'Spent', 'c'=>'text-orange-600'],
                ];
                @endphp
                @foreach($kpis as $k)
                <div class="bg-gray-50 rounded-lg px-3 py-2 text-center min-w-[72px]">
                    <p class="text-sm font-bold {{ $k['c'] }}">{{ $k['v'] }}</p>
                    <p class="text-xs text-gray-400">{{ $k['l'] }}</p>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Lead goal progress --}}
        @if($campaign->goal_leads > 0)
        <div class="mt-4">
            <div class="flex justify-between text-xs text-gray-500 mb-1">
                <span>Progress Lead ({{ $campaign->leads_count }}/{{ $campaign->goal_leads }})</span>
                <span>{{ $campaign->progress_percent }}%</span>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                <div class="h-2 rounded-full {{ $campaign->progress_percent >= 100 ? 'bg-green-500' : 'bg-blue-500' }} transition-all"
                     style="width:{{ $campaign->progress_percent }}%"></div>
            </div>
        </div>
        @endif
    </div>

    {{-- ── Tabs ────────────────────────────────────────────────────────────────── --}}
    <div x-data="{ tab: 'leads' }">
        <div class="flex gap-1 mb-4 border-b border-gray-200">
            @foreach(['leads'=>'Leads ('.$campaign->leads->count().')','funnel'=>'Funnel','metrics'=>'Metrik & Performa'] as $t=>$label)
            <button @click="tab = '{{ $t }}'"
                    :class="tab === '{{ $t }}' ? 'border-violet-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors -mb-px">
                {{ $label }}
            </button>
            @endforeach
        </div>

        {{-- ── TAB: LEADS ──────────────────────────────────────────────────────── --}}
        <div x-show="tab === 'leads'" x-cloak>

            {{-- Toolbar --}}
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <div class="flex items-center gap-2">
                    <input type="text" x-model="search" placeholder="Cari lead…"
                           class="text-sm border border-gray-300 rounded-lg px-3 py-2 w-44 focus:outline-none focus:ring-2 focus:ring-violet-500">
                    <select x-model="filterStatus"
                            class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-500">
                        <option value="">Semua Status</option>
                        <option value="lead">Lead</option>
                        <option value="prospect">Prospect</option>
                        <option value="client">Client</option>
                        <option value="lost">Lost</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <template x-if="selected.length > 0">
                        <div class="flex items-center gap-2 bg-blue-50 border border-blue-200 rounded-lg px-3 py-1.5">
                            <span class="text-xs text-blue-700 font-medium" x-text="selected.length + ' dipilih'"></span>
                            <form method="POST" action="{{ route('campaigns.leads.bulk', $campaign) }}" id="bulk-form">
                                @csrf
                                <template x-for="id in selected" :key="id">
                                    <input type="hidden" name="lead_ids[]" :value="id">
                                </template>
                                <select name="status" @change="$el.closest('form').submit()"
                                        class="text-xs border border-blue-300 rounded px-2 py-1 bg-white focus:outline-none">
                                    <option value="">Ubah status…</option>
                                    @foreach(['lead','prospect','client','lost'] as $s)
                                    <option value="{{ $s }}">→ {{ ucfirst($s) }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </div>
                    </template>
                    @can('manage campaigns')
                    <button @click="openLeadModal(null)"
                            class="inline-flex items-center gap-1.5 bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium px-4 py-2 rounded-lg">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Tambah Lead
                    </button>
                    @endcan
                </div>
            </div>

            {{-- Follow-up warning --}}
            @if($followUpDue > 0)
            <div class="mb-4 flex items-center gap-2 bg-orange-50 border border-orange-200 text-orange-700 rounded-lg px-4 py-2.5 text-sm">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <strong>{{ $followUpDue }} lead</strong> memiliki jadwal follow-up yang sudah lewat.
            </div>
            @endif

            {{-- Kanban --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach(['lead'=>['label'=>'Lead','color'=>'bg-blue-500'],
                          'prospect'=>['label'=>'Prospect','color'=>'bg-yellow-500'],
                          'client'=>['label'=>'Client','color'=>'bg-green-500'],
                          'lost'=>['label'=>'Lost','color'=>'bg-gray-400']] as $statusKey => $sc)
                @php $colLeads = $campaign->leads->where('status', $statusKey); @endphp
                <div class="bg-gray-50 rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-4 py-3 {{ $sc['color'] }} flex items-center justify-between">
                        <span class="text-sm font-semibold text-white">{{ $sc['label'] }}</span>
                        <span class="text-xs bg-white/20 text-white px-2 py-0.5 rounded-full">{{ $colLeads->count() }}</span>
                    </div>
                    <div class="p-3 space-y-2 min-h-[120px]">
                        @forelse($colLeads as $lead)
                        @php $sl = $scoreLabel($lead->score); @endphp
                        <div class="bg-white rounded-lg border border-gray-200 p-3 hover:shadow-sm transition-shadow cursor-pointer"
                             x-show="matchesFilter('{{ addslashes($lead->name) }}', '{{ $statusKey }}')"
                             @click="openLeadModal({{ $lead->id }})">
                            <div class="flex items-start justify-between mb-1">
                                <div class="flex items-center gap-1.5">
                                    <input type="checkbox" :value="{{ $lead->id }}" x-model="selected"
                                           @click.stop class="w-3.5 h-3.5 rounded border-gray-300 text-blue-600 focus:ring-violet-500">
                                    <p class="text-xs font-semibold text-gray-800 leading-snug">{{ $lead->name }}</p>
                                </div>
                                @if($lead->score > 0)
                                <span class="text-xs px-1.5 py-0.5 rounded-full font-medium {{ $scoreStyle[$sl] }}">{{ $sl }}</span>
                                @endif
                            </div>
                            @if($lead->company)
                            <p class="text-xs text-gray-400 mb-1">{{ $lead->company }}</p>
                            @endif
                            <p class="text-xs text-gray-500">{{ $lead->contact }}</p>
                            @if($lead->value)
                            <p class="text-xs text-emerald-600 font-medium mt-1">Rp {{ number_format($lead->value,0,',','.') }}</p>
                            @endif
                            @if($lead->isFollowUpDue())
                            <p class="text-xs text-orange-500 mt-1">⏰ Follow-up: {{ $lead->follow_up_at->format('d M') }}</p>
                            @elseif($lead->follow_up_at && $lead->status !== 'client')
                            <p class="text-xs text-gray-400 mt-1">📅 Follow-up: {{ $lead->follow_up_at->format('d M') }}</p>
                            @endif
                            @if($lead->assignee)
                            <p class="text-xs text-gray-400 mt-1">👤 {{ $lead->assignee->name }}</p>
                            @endif
                        </div>
                        @empty
                        <p class="text-xs text-gray-400 text-center py-4">Kosong</p>
                        @endforelse
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Total value --}}
            @if($totalValue > 0)
            <div class="mt-4 text-sm text-right text-gray-500">
                Total nilai client: <strong class="text-emerald-600">Rp {{ number_format($totalValue,0,',','.') }}</strong>
            </div>
            @endif
        </div>

        {{-- ── TAB: FUNNEL ─────────────────────────────────────────────────────── --}}
        <div x-show="tab === 'funnel'" x-cloak>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                {{-- Funnel visual --}}
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <h4 class="text-sm font-semibold text-gray-700 mb-4">Lead Funnel</h4>
                    @php
                    $funnelSteps = [
                        ['label'=>'Lead',     'count'=>$funnel['lead'],     'color'=>'bg-blue-500',  'pct'=> $campaign->leads->count() > 0 ? round($funnel['lead']/$campaign->leads->count()*100) : 0],
                        ['label'=>'Prospect', 'count'=>$funnel['prospect'], 'color'=>'bg-yellow-400','pct'=> $campaign->leads->count() > 0 ? round($funnel['prospect']/$campaign->leads->count()*100) : 0],
                        ['label'=>'Client',   'count'=>$funnel['client'],   'color'=>'bg-green-500', 'pct'=> $campaign->leads->count() > 0 ? round($funnel['client']/$campaign->leads->count()*100) : 0],
                        ['label'=>'Lost',     'count'=>$funnel['lost'],     'color'=>'bg-gray-400',  'pct'=> $campaign->leads->count() > 0 ? round($funnel['lost']/$campaign->leads->count()*100) : 0],
                    ];
                    @endphp
                    <div class="space-y-3">
                        @foreach($funnelSteps as $step)
                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span class="text-gray-600 font-medium">{{ $step['label'] }}</span>
                                <span class="text-gray-500">{{ $step['count'] }} ({{ $step['pct'] }}%)</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-5 overflow-hidden">
                                <div class="{{ $step['color'] }} h-5 rounded-full flex items-center justify-end pr-2 transition-all"
                                     style="width: max({{ $step['pct'] }}%, {{ $step['count'] > 0 ? '20px' : '0' }})">
                                    @if($step['count'] > 0)
                                    <span class="text-white text-xs font-bold">{{ $step['count'] }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100 grid grid-cols-2 gap-3 text-sm">
                        <div class="text-center">
                            <p class="text-xs text-gray-400">Conversion Rate</p>
                            <p class="font-bold text-emerald-600 text-lg">{{ $campaign->conversion_rate }}%</p>
                        </div>
                        <div class="text-center">
                            <p class="text-xs text-gray-400">Total Leads</p>
                            <p class="font-bold text-blue-600 text-lg">{{ $campaign->leads->count() }}</p>
                        </div>
                    </div>
                </div>

                {{-- Source breakdown --}}
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <h4 class="text-sm font-semibold text-gray-700 mb-4">Sumber Lead</h4>
                    @php
                    $sources = $campaign->leads->groupBy('source');
                    $maxSrc  = $sources->max(fn($g) => $g->count()) ?: 1;
                    @endphp
                    @if($sources->isEmpty())
                    <p class="text-xs text-gray-400">Belum ada data sumber.</p>
                    @else
                    <div class="space-y-2.5">
                        @foreach($sources->sortByDesc(fn($g) => $g->count()) as $src => $group)
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-gray-600 w-20 shrink-0">{{ $sourcLabels[$src] ?? ucfirst($src ?: 'Lainnya') }}</span>
                            <div class="flex-1 bg-gray-100 rounded-full h-2 overflow-hidden">
                                <div class="h-2 bg-blue-500 rounded-full" style="width:{{ round($group->count()/$maxSrc*100) }}%"></div>
                            </div>
                            <span class="text-xs text-gray-500 w-5 text-right">{{ $group->count() }}</span>
                        </div>
                        @endforeach
                    </div>
                    @endif

                    {{-- Score distribution --}}
                    <h4 class="text-sm font-semibold text-gray-700 mt-5 mb-3">Distribusi Score</h4>
                    @php
                    $hot  = $campaign->leads->where('score', '>=', 8)->count();
                    $warm = $campaign->leads->whereBetween('score', [5, 7])->count();
                    $cold = $campaign->leads->where('score', '>', 0)->where('score', '<', 5)->count();
                    $ns   = $campaign->leads->where('score', 0)->count();
                    @endphp
                    <div class="flex gap-2 flex-wrap">
                        @foreach([['hot','Hot','bg-red-100 text-red-600',$hot],['warm','Warm','bg-orange-100 text-orange-600',$warm],['cold','Cold','bg-blue-100 text-blue-500',$cold],['-','No Score','bg-gray-100 text-gray-400',$ns]] as [$k,$l,$c,$n])
                        <div class="flex-1 min-w-[60px] text-center {{ $c }} rounded-lg px-3 py-2">
                            <p class="text-sm font-bold">{{ $n }}</p>
                            <p class="text-xs">{{ $l }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- ── TAB: METRICS ────────────────────────────────────────────────────── --}}
        <div x-show="tab === 'metrics'" x-cloak>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

                {{-- Metric cards --}}
                <div class="lg:col-span-2 grid grid-cols-2 sm:grid-cols-3 gap-3">
                    @php
                    $cpl = $campaign->cpl;
                    $roi = ($campaign->actual_spend > 0 && $totalValue > 0) ? round((($totalValue - $campaign->actual_spend) / $campaign->actual_spend) * 100, 1) : null;
                    $metricCards = [
                        ['label'=>'Impressions',   'value'=> number_format($campaign->impressions),   'icon'=>'👁',  'color'=>'text-gray-700'],
                        ['label'=>'Reach',         'value'=> number_format($campaign->reach),         'icon'=>'📡',  'color'=>'text-blue-600'],
                        ['label'=>'Clicks',        'value'=> number_format($campaign->clicks),        'icon'=>'🖱',  'color'=>'text-violet-600'],
                        ['label'=>'CTR',           'value'=> $campaign->ctr.'%',                      'icon'=>'📊',  'color'=>'text-indigo-600'],
                        ['label'=>'Budget',        'value'=> $campaign->budget ? 'Rp '.number_format($campaign->budget,0,',','.') : '—', 'icon'=>'💰', 'color'=>'text-gray-700'],
                        ['label'=>'Actual Spend',  'value'=> $campaign->actual_spend > 0 ? 'Rp '.number_format($campaign->actual_spend,0,',','.') : '—', 'icon'=>'💸', 'color'=>'text-orange-600'],
                        ['label'=>'Cost per Lead', 'value'=> $cpl > 0 ? 'Rp '.number_format($cpl,0,',','.') : '—', 'icon'=>'🎯', 'color'=>'text-teal-600'],
                        ['label'=>'ROI',           'value'=> $roi !== null ? $roi.'%' : '—',          'icon'=>'📈',  'color'=>$roi > 0 ? 'text-emerald-600' : 'text-red-500'],
                        ['label'=>'Total Value',   'value'=> $totalValue > 0 ? 'Rp '.number_format($totalValue,0,',','.') : '—', 'icon'=>'💎', 'color'=>'text-emerald-600'],
                    ];
                    @endphp
                    @foreach($metricCards as $m)
                    <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                        <span class="text-2xl">{{ $m['icon'] }}</span>
                        <p class="text-sm font-bold {{ $m['color'] }} mt-1">{{ $m['value'] }}</p>
                        <p class="text-xs text-gray-400">{{ $m['label'] }}</p>
                    </div>
                    @endforeach
                </div>

                {{-- Update metrics form --}}
                @can('manage campaigns')
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <h4 class="text-sm font-semibold text-gray-700 mb-4">Update Metrik</h4>
                    <form method="POST" action="{{ route('campaigns.metrics', $campaign) }}" class="space-y-3">
                        @csrf @method('PATCH')
                        @foreach(['impressions'=>'Impressions','reach'=>'Reach','clicks'=>'Clicks','actual_spend'=>'Actual Spend (Rp)'] as $field=>$label)
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ $label }}</label>
                            <input type="number" name="{{ $field }}" value="{{ $campaign->$field ?? '' }}" min="0"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                        </div>
                        @endforeach
                        <button type="submit" class="w-full bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium py-2 rounded-lg transition-colors mt-1">
                            Simpan Metrik
                        </button>
                    </form>
                </div>
                @endcan

            </div>

            {{-- Budget vs Spend bar --}}
            @if($campaign->budget > 0)
            <div class="mt-4 bg-white rounded-xl border border-gray-200 p-5">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">Budget vs Pengeluaran</h4>
                @php $spendPct = min(100, round($campaign->actual_spend / $campaign->budget * 100)); @endphp
                <div class="flex justify-between text-xs text-gray-500 mb-1">
                    <span>Rp {{ number_format($campaign->actual_spend,0,',','.') }}</span>
                    <span>/ Rp {{ number_format($campaign->budget,0,',','.') }} ({{ $spendPct }}%)</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden">
                    <div class="h-3 rounded-full {{ $spendPct >= 100 ? 'bg-red-500' : ($spendPct >= 80 ? 'bg-orange-400' : 'bg-blue-500') }}"
                         style="width:{{ $spendPct }}%"></div>
                </div>
            </div>
            @endif
        </div>

    </div>{{-- end tabs --}}
</div>

{{-- ── Lead Modal ────────────────────────────────────────────────────────────── --}}
<div x-data="campaignApp()" x-init="init()" style="display:none"></div>

@push('modals')
<div x-data="leadModal()" x-init="init()"
     @open-lead.window="open($event.detail)"
     x-show="show" x-cloak
     class="fixed inset-0 z-50 flex items-start justify-center p-4 pt-16 bg-black/40 overflow-y-auto">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg" @click.stop>
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800" x-text="lead.id ? 'Edit Lead' : 'Tambah Lead'"></h3>
            <button @click="show = false" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form :action="lead.id ? '/leads/'+lead.id : '{{ route('campaigns.leads.store', $campaign) }}'"
              method="POST" class="px-6 py-5 space-y-4">
            @csrf
            <template x-if="lead.id"><input type="hidden" name="_method" value="PUT"></template>

            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Nama *</label>
                    <input type="text" name="name" :value="lead.name" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Kontak *</label>
                    <input type="text" name="contact" :value="lead.contact" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Email</label>
                    <input type="email" name="email" :value="lead.email"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Phone</label>
                    <input type="text" name="phone" :value="lead.phone"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Perusahaan</label>
                    <input type="text" name="company" :value="lead.company"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Sumber</label>
                    <select name="source" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                        <option value="">—</option>
                        @foreach(['website','referral','ads','event','cold_call','other'] as $src)
                        <option value="{{ $src }}" :selected="lead.source === '{{ $src }}'">{{ ucfirst(str_replace('_',' ',$src)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Score (1–10)</label>
                    <input type="number" name="score" :value="lead.score" min="0" max="10"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Nilai Potensial (Rp)</label>
                    <input type="number" name="value" :value="lead.value" min="0"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                </div>
                <template x-if="lead.id">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                        @foreach(['lead','prospect','client','lost'] as $s)
                        <option value="{{ $s }}" :selected="lead.status === '{{ $s }}'">{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
                </template>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Assignee</label>
                    <select name="assigned_to" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                        <option value="">—</option>
                        @foreach($users as $u)
                        <option value="{{ $u->id }}" :selected="lead.assigned_to == {{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Follow-up Date</label>
                    <input type="date" name="follow_up_at" :value="lead.follow_up_at"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Catatan</label>
                    <textarea name="notes" rows="2" x-text="lead.notes"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 resize-none"></textarea>
                </div>
                <template x-if="lead.id && lead.status === 'lost'">
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Alasan Lost</label>
                    <input type="text" name="lost_reason" :value="lead.lost_reason"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                </div>
                </template>
            </div>

            <div class="flex gap-2 pt-1">
                <button type="submit" class="flex-1 bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium py-2.5 rounded-lg">
                    Simpan
                </button>
                <template x-if="lead.id">
                <form :action="'/leads/'+lead.id" method="POST" class="inline" data-confirm-delete="lead ini">
                    @csrf
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="text-red-500 border border-red-200 text-sm font-medium px-4 py-2.5 rounded-lg hover:bg-red-50">
                        Hapus
                    </button>
                </form>
                </template>
                <button type="button" @click="show = false"
                        class="px-4 py-2.5 text-sm text-gray-500 border border-gray-300 rounded-lg hover:bg-gray-50">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>
@endpush

@push('scripts')
<script>
const leadsData = @json($campaign->leads->keyBy('id'));

function campaignApp() {
    return {
        selected: [],
        search: '',
        filterStatus: '',
        init() {},
        matchesFilter(name, status) {
            const matchSearch = !this.search || name.toLowerCase().includes(this.search.toLowerCase());
            const matchStatus = !this.filterStatus || status === this.filterStatus;
            return matchSearch && matchStatus;
        },
        openLeadModal(id) {
            this.$dispatch('open-lead', id ? leadsData[id] : null);
        },
    };
}

function leadModal() {
    return {
        show: false,
        lead: {},
        init() {},
        open(data) {
            this.lead = data || {};
            this.show = true;
        },
    };
}
</script>
@endpush
@endsection
