@extends('layouts.app')

@section('title', 'Timesheet: ' . $project->name)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('projects.show', $project) }}"
           class="p-2 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Timesheet</h1>
            <p class="text-sm text-gray-500 mt-0.5">
                Proyek: <span class="font-medium text-gray-700">{{ $project->name }}</span>
            </p>
        </div>
    </div>

    {{-- ============================================================
         SUMMARY TABLE
    ============================================================ --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-8">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-base font-semibold text-gray-900">Ringkasan per Developer</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Developer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Total Jam</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Jumlah Log</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($summary as $row)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-semibold text-xs uppercase">
                                        {{ substr($row['user']->name ?? '?', 0, 2) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ $row['user']->name ?? '-' }}</p>
                                        <p class="text-xs text-gray-500">{{ $row['user']->email ?? '' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm font-semibold text-indigo-700">
                                    {{ number_format($row['total_hours'], 2) }} jam
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $row['logs_count'] }} log
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center text-sm text-gray-400">
                                Belum ada data timesheet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($summary) > 0)
                    <tfoot class="bg-gray-50 border-t border-gray-200">
                        <tr>
                            <td class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Total Keseluruhan</td>
                            <td class="px-6 py-3 text-sm font-bold text-indigo-700">
                                {{ number_format(collect($summary)->sum('total_hours'), 2) }} jam
                            </td>
                            <td class="px-6 py-3 text-sm font-bold text-gray-700">
                                {{ collect($summary)->sum('logs_count') }} log
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- ============================================================
         DETAIL LOGS TABLE
    ============================================================ --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-base font-semibold text-gray-900">Detail Log Waktu</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Developer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Task</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Mulai</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Selesai</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Durasi</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Catatan</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($logs as $log)
                        @php
                            $minutes = $log->duration_minutes ?? 0;
                            $hours   = floor($minutes / 60);
                            $mins    = $minutes % 60;
                            $durationLabel = $hours > 0
                                ? $hours . ' jam ' . ($mins > 0 ? $mins . ' mnt' : '')
                                : $mins . ' mnt';
                        @endphp
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-3">
                                <p class="text-sm font-medium text-gray-900">{{ $log->user->name ?? '-' }}</p>
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-700">
                                {{ $log->task->title ?? '-' }}
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-600 whitespace-nowrap">
                                {{ $log->started_at
                                    ? \Carbon\Carbon::parse($log->started_at)->format('d M Y, H:i')
                                    : '-' }}
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-600 whitespace-nowrap">
                                {{ $log->ended_at
                                    ? \Carbon\Carbon::parse($log->ended_at)->format('d M Y, H:i')
                                    : '-' }}
                            </td>
                            <td class="px-6 py-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">
                                    {{ trim($durationLabel) }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-500 max-w-xs truncate"
                                title="{{ $log->notes }}">
                                {{ $log->notes ?: '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-400">
                                Belum ada log waktu.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($logs, 'links') && $logs->hasPages())
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $logs->withQueryString()->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
