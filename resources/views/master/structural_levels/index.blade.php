@extends('layouts.app')
@section('title', 'Master Data — Level Struktural')
@section('page-title', 'Level Struktural')

@section('content')
<div class="py-4">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-xs text-gray-400 mb-5">
        <a href="{{ route('master.index') }}" class="hover:text-blue-600 transition-colors">Master Data</a>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-600 font-medium">Level Struktural</span>
    </div>

    {{-- Toolbar --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-6">
        <form method="GET" class="flex gap-2 flex-1">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama level..."
                   class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 w-52">
            @if(request('search'))
                <a href="{{ route('structural-levels.index') }}" class="text-sm text-gray-500 hover:text-gray-700 px-3 py-2">Reset</a>
            @endif
        </form>
        @if($hasDefaults ?? false)
        <form method="POST" action="{{ route('structural-levels.reset') }}" onsubmit="return confirm('Isi level struktural dengan set default (Staff s/d BOD)?')">
            @csrf
            <button class="inline-flex items-center gap-2 bg-white border border-amber-300 text-amber-600 hover:bg-amber-50 text-sm font-medium px-4 py-2 rounded-lg transition-colors shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Set Default
            </button>
        </form>
        @endif
        <a href="{{ route('structural-levels.create') }}" class="inline-flex items-center gap-2 bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Level
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        @if($levels->isEmpty())
            <div class="py-16 text-center">
                <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4"/></svg>
                </div>
                <p class="text-gray-500 text-sm mb-4">Belum ada level struktural.</p>
                @if($hasDefaults ?? false)
                <form method="POST" action="{{ route('structural-levels.reset') }}" class="inline" onsubmit="return confirm('Isi level struktural dengan set default (Staff s/d BOD)?')">
                    @csrf
                    <button class="inline-flex items-center gap-2 bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                        Gunakan Set Default
                    </button>
                </form>
                @endif
            </div>
        @else
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider w-12">Urutan</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Nama Level</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider w-24">Jumlah User</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider w-24">Status</th>
                        <th class="px-5 py-3 w-24"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($levels as $level)
                    @php
                        $badges = [
                            1 => ['bg' => 'bg-gray-100',    'text' => 'text-gray-600'],
                            2 => ['bg' => 'bg-blue-50',     'text' => 'text-blue-600'],
                            3 => ['bg' => 'bg-indigo-50',   'text' => 'text-indigo-600'],
                            4 => ['bg' => 'bg-violet-50',   'text' => 'text-violet-600'],
                            5 => ['bg' => 'bg-purple-50',   'text' => 'text-purple-600'],
                            6 => ['bg' => 'bg-amber-50',    'text' => 'text-amber-600'],
                            7 => ['bg' => 'bg-orange-50',   'text' => 'text-orange-600'],
                            8 => ['bg' => 'bg-red-50',      'text' => 'text-red-600'],
                        ];
                        $b = $badges[$level->sort_order] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-600'];
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-3.5 text-center">
                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-full {{ $b['bg'] }} {{ $b['text'] }} text-xs font-bold">
                                {{ $level->sort_order }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="font-medium text-gray-900">{{ $level->name }}</span>
                        </td>
                        <td class="px-5 py-3.5 text-center">
                            <span class="text-gray-600 font-medium">{{ $level->users_count }}</span>
                        </td>
                        <td class="px-5 py-3.5 text-center">
                            <span class="text-xs px-2.5 py-1 rounded-full font-medium {{ $level->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $level->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('structural-levels.edit', $level) }}" class="text-xs text-gray-500 hover:text-blue-600 font-medium transition-colors">Edit</a>
                                <form method="POST" action="{{ route('structural-levels.destroy', $level) }}"
                                      data-confirm-delete="{{ $level->name }}" data-confirm-label="Hapus Level">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs text-red-500 hover:text-red-700 transition-colors">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            @if($levels->hasPages())
                <div class="px-5 py-4 border-t border-gray-100">{{ $levels->links() }}</div>
            @endif
        @endif
    </div>
</div>
@endsection
