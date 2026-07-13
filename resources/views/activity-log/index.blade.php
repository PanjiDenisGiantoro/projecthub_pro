@extends('layouts.app')
@section('title', 'Activity Log')
@section('page-title', 'Activity Log')

@section('content')
<div class="py-4">
    <p class="text-sm text-gray-500 mb-4">Riwayat aktivitas (buat/ubah/hapus) pada Proyek, Task, Tiket, dan Customer Request di company Anda.</p>

    {{-- Filter --}}
    <form method="GET" class="flex gap-2 flex-wrap mb-4">
        <select name="log_name" onchange="this.form.submit()" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-500">
            <option value="">Semua Tipe</option>
            @foreach($logNames as $val => $label)
                <option value="{{ $val }}" {{ request('log_name') === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari deskripsi..."
               class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-500 flex-1 min-w-[200px]">
        <button type="submit" class="bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">Cari</button>
        @if(request('log_name') || request('search'))
        <a href="{{ route('activity-log.index') }}" class="text-gray-500 hover:text-gray-700 text-sm px-4 py-2">Reset</a>
        @endif
    </form>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 text-left">Waktu</th>
                    <th class="px-4 py-3 text-left">User</th>
                    <th class="px-4 py-3 text-left">Tipe</th>
                    <th class="px-4 py-3 text-left">Event</th>
                    <th class="px-4 py-3 text-left">Deskripsi</th>
                    <th class="px-4 py-3 text-left">Perubahan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($activities as $activity)
                <tr class="hover:bg-gray-50 align-top">
                    <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $activity->created_at->format('d M Y H:i') }}</td>
                    <td class="px-4 py-3 text-gray-700">{{ $activity->causer->name ?? 'System' }}</td>
                    <td class="px-4 py-3">
                        <span class="badge bg-violet-100 text-violet-700">{{ $logNames[$activity->log_name] ?? $activity->log_name ?? '-' }}</span>
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ ucfirst($activity->event ?? '-') }}</td>
                    <td class="px-4 py-3 text-gray-700 max-w-sm truncate">{{ $activity->description }}</td>
                    <td class="px-4 py-3">
                        @if($activity->properties && $activity->properties->isNotEmpty())
                        <div x-data="{ open: false }">
                            <button type="button" @click="open = !open" class="text-violet-600 hover:text-violet-800 text-xs font-medium">
                                <span x-text="open ? 'Sembunyikan' : 'Lihat detail'"></span>
                            </button>
                            <pre x-show="open" x-cloak class="mt-2 text-[11px] bg-gray-50 rounded-lg p-2 max-w-md overflow-x-auto">{{ json_encode($activity->properties, JSON_PRETTY_PRINT) }}</pre>
                        </div>
                        @else
                        <span class="text-gray-300">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">Belum ada activity log.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($activities->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $activities->links() }}</div>
        @endif
    </div>
</div>
@endsection
