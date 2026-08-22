@extends('layouts.app')

@section('title', 'Marketing Dashboard')

@section('page-title', 'Marketing Dashboard')

@section('content')
<div class="space-y-6 pt-4">

    {{-- ============================================================
         STAT CARDS
    ============================================================ --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-xl">

        {{-- Active Campaigns --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Kampanye Aktif</span>
                <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-800">{{ $stats['active_campaigns'] }}</p>
            <p class="mt-2 text-xs text-gray-500">Kampanye sedang berjalan</p>
        </div>

        {{-- Pending Review --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Pending Review</span>
                <div class="w-9 h-9 rounded-lg bg-yellow-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-yellow-600">{{ $stats['pending_review'] }}</p>
            <p class="mt-2 text-xs text-gray-500">Menunggu persetujuan</p>
        </div>

    </div>

    {{-- ============================================================
         RECENT CAMPAIGNS TABLE
    ============================================================ --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between px-5 py-4 border-b border-gray-100 gap-3">
            <h2 class="font-semibold text-gray-800">Recent Campaigns</h2>
            <a href="{{ route('campaigns.index') }}"
               class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-md transition-colors">
                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Semua Kampanye
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50">
                        <th class="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">Nama Kampanye</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">Channel</th>
                        <th class="text-left px-3 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">Status</th>
                        <th class="text-left px-3 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">Budget</th>
                        <th class="text-left px-3 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">Project</th>
                        <th class="px-3 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($campaigns as $campaign)
                        @php
                            $statusClasses = [
                                'draft'     => 'bg-gray-100 text-gray-600',
                                'active'    => 'bg-green-100 text-green-700',
                                'paused'    => 'bg-yellow-100 text-yellow-700',
                                'completed' => 'bg-blue-100 text-blue-700',
                                'review'    => 'bg-purple-100 text-purple-700',
                                'cancelled' => 'bg-red-100 text-red-700',
                            ];
                            $channelIcons = [
                                'email'     => '✉',
                                'social'    => '📱',
                                'ads'       => '📢',
                                'seo'       => '🔍',
                                'content'   => '📝',
                                'event'     => '📅',
                            ];
                            $sc = $statusClasses[$campaign->status] ?? 'bg-gray-100 text-gray-600';
                            $sl = ucfirst($campaign->status ?? 'draft');
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-5 py-3">
                                <a href="{{ route('campaigns.show', $campaign->id) }}"
                                   class="font-medium text-gray-800 hover:text-blue-600 line-clamp-1">
                                    {{ $campaign->name }}
                                </a>
                                @if($campaign->start_date)
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        {{ \Carbon\Carbon::parse($campaign->start_date)->format('d M Y') }}
                                        @if($campaign->end_date)
                                            — {{ \Carbon\Carbon::parse($campaign->end_date)->format('d M Y') }}
                                        @endif
                                    </p>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center gap-1.5 text-xs text-gray-600 bg-gray-100 px-2 py-1 rounded-md font-medium">
                                    {{ $channelIcons[$campaign->channel] ?? '📌' }}
                                    {{ ucfirst($campaign->channel ?? '-') }}
                                </span>
                            </td>
                            <td class="px-3 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $sc }}">
                                    {{ $sl }}
                                </span>
                            </td>
                            <td class="px-3 py-3">
                                @if(isset($campaign->budget) && $campaign->budget)
                                    <span class="text-xs font-medium text-gray-700">
                                        Rp {{ number_format($campaign->budget, 0, ',', '.') }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                @if($campaign->project)
                                    <a href="{{ route('projects.show', $campaign->project_id) }}"
                                       class="text-xs text-blue-600 hover:text-blue-800 font-medium line-clamp-1">
                                        {{ $campaign->project->name }}
                                    </a>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('campaigns.show', $campaign->id) }}"
                                       class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                                        Detail
                                    </a>
                                    @if($campaign->status === 'review')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">
                                            Review
                                        </span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center">
                                <div class="flex flex-col items-center gap-2 text-gray-400">
                                    <svg class="w-10 h-10 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                                    </svg>
                                    <p class="text-sm">Belum ada kampanye</p>
                                    <a href="{{ route('campaigns.create') }}"
                                       class="mt-2 inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-md transition-colors">
                                        Buat Kampanye Pertama
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($campaigns->hasPages())
            <div class="px-5 py-4 border-t border-gray-100">
                {{ $campaigns->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
