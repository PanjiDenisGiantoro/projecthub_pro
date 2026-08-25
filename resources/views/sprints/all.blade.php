@extends('layouts.app')
@section('title', 'Aktivitas Kerja — Sprint')
@section('page-title', 'Aktivitas Kerja')

@section('content')
@php
    $sc = ['planned'=>'bg-gray-100 text-gray-600','active'=>'bg-blue-100 text-blue-700','completed'=>'bg-green-100 text-green-700'];
@endphp
<div class="py-4">
    @include('partials.work-activity-tabs')

    {{-- Filter --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-4">
        <form method="GET" class="flex gap-2 flex-1 flex-wrap">
            <select name="status" onchange="this.form.submit()" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Semua Status</option>
                @foreach(['planned'=>'Planned','active'=>'Active','completed'=>'Completed'] as $s => $sl)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ $sl }}</option>
                @endforeach
            </select>
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 text-left">Sprint</th>
                    <th class="px-4 py-3 text-left">Proyek</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Periode</th>
                    <th class="px-4 py-3 text-left">Progress</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($sprints as $sprint)
                @php
                    $total = $sprint->totalPoints();
                    $done  = $sprint->completedPoints();
                    $pct   = $total > 0 ? (int) round($done / $total * 100) : 0;
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-800 max-w-xs truncate">{{ $sprint->name }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $sprint->project->name ?? '-' }}</td>
                    <td class="px-4 py-3">
                        <span class="badge {{ $sc[$sprint->status] ?? '' }}">{{ ucfirst($sprint->status) }}</span>
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500">
                        {{ $sprint->start_date?->format('d M Y') ?? '?' }} — {{ $sprint->end_date?->format('d M Y') ?? '?' }}
                    </td>
                    <td class="px-4 py-3">
                        @if($total > 0)
                        <div class="flex items-center gap-2 min-w-[120px]">
                            <div class="flex-1 bg-gray-100 rounded-full h-1.5 overflow-hidden">
                                <div class="h-1.5 rounded-full bg-blue-400" style="width: {{ $pct }}%"></div>
                            </div>
                            <span class="text-xs text-gray-400 whitespace-nowrap">{{ $done }}/{{ $total }} pts</span>
                        </div>
                        @else
                        <span class="text-xs text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($sprint->project)
                        <a href="{{ route('sprints.show', [$sprint->project, $sprint]) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Detail</a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">Belum ada sprint.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-gray-100 flex items-center justify-between gap-3 flex-wrap">
            <x-per-page />
            @if($sprints->hasPages())
            {{ $sprints->links() }}
            @endif
        </div>
    </div>
</div>
@endsection
