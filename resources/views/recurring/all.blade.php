@extends('layouts.app')
@section('title', 'Aktivitas Kerja — Recurring')
@section('page-title', 'Aktivitas Kerja')

@section('content')
<div class="py-4">
    @include('partials.work-activity-tabs')

    {{-- Filter --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-4">
        <form method="GET" class="flex gap-2 flex-1 flex-wrap">
            <select name="status" onchange="this.form.submit()" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Semua Status</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
            </select>
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 text-left">Judul</th>
                    <th class="px-4 py-3 text-left">Proyek</th>
                    <th class="px-4 py-3 text-left">Frekuensi</th>
                    <th class="px-4 py-3 text-left">Assignee</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Terakhir Dibuat</th>
                    <th class="px-4 py-3 text-center">Task Dibuat</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($definitions as $def)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-800 max-w-xs truncate">{{ $def->title }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $def->project->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-gray-600 capitalize">{{ $def->frequency }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $def->assignee->name ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $def->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $def->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $def->last_generated_at?->format('d/m/Y') ?? '—' }}</td>
                    <td class="px-4 py-3 text-center text-gray-600">{{ $def->tasks_count }}</td>
                    <td class="px-4 py-3">
                        @if($def->project)
                        <a href="{{ route('recurring.index', $def->project) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Kelola</a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">Belum ada recurring task.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-gray-100 flex items-center justify-between gap-3 flex-wrap">
            <x-per-page />
            @if($definitions->hasPages())
            {{ $definitions->links() }}
            @endif
        </div>
    </div>
</div>
@endsection
