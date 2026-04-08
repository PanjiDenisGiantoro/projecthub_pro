@extends('layouts.app')

@section('title', 'Daftar Proyek')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Proyek</h1>
        @if(auth()->user()->role !== 'customer')
            <a href="{{ route('projects.create') }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg shadow hover:bg-indigo-700 transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Buat Proyek
            </a>
        @endif
    </div>

    {{-- Filter Bar --}}
    <form method="GET" action="{{ route('projects.index') }}" class="flex flex-col sm:flex-row gap-3 mb-6">
        <div class="flex-1">
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Cari nama proyek..."
                class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
        </div>
        <div>
            <select name="status"
                    class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">Semua Status</option>
                <option value="draft"       {{ request('status') === 'draft'       ? 'selected' : '' }}>Draft</option>
                <option value="active"      {{ request('status') === 'active'      ? 'selected' : '' }}>Aktif</option>
                <option value="on_hold"     {{ request('status') === 'on_hold'     ? 'selected' : '' }}>On Hold</option>
                <option value="completed"   {{ request('status') === 'completed'   ? 'selected' : '' }}>Selesai</option>
                <option value="cancelled"   {{ request('status') === 'cancelled'   ? 'selected' : '' }}>Dibatalkan</option>
            </select>
        </div>
        <button type="submit"
                class="px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-200 transition">
            Filter
        </button>
        @if(request('search') || request('status'))
            <a href="{{ route('projects.index') }}"
               class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50 transition">
                Reset
            </a>
        @endif
    </form>

    {{-- Project Grid --}}
    @if($projects->isEmpty())
        <div class="text-center py-20 text-gray-400">
            <svg class="mx-auto w-12 h-12 mb-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z"/>
            </svg>
            <p class="text-lg font-medium">Belum ada proyek</p>
            <p class="text-sm mt-1">Mulai dengan membuat proyek baru.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($projects as $project)
                @php
                    $statusConfig = [
                        'draft'     => ['label' => 'Draft',      'class' => 'bg-gray-100 text-gray-700'],
                        'active'    => ['label' => 'Aktif',      'class' => 'bg-green-100 text-green-700'],
                        'on_hold'   => ['label' => 'On Hold',    'class' => 'bg-yellow-100 text-yellow-700'],
                        'completed' => ['label' => 'Selesai',    'class' => 'bg-blue-100 text-blue-700'],
                        'cancelled' => ['label' => 'Dibatalkan', 'class' => 'bg-red-100 text-red-700'],
                    ];
                    $sc = $statusConfig[$project->status] ?? ['label' => ucfirst($project->status), 'class' => 'bg-gray-100 text-gray-700'];
                    $progress = $project->progress ?? 0;
                @endphp
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 flex flex-col hover:shadow-md transition">
                    <div class="p-5 flex-1">
                        {{-- Name & Status --}}
                        <div class="flex items-start justify-between gap-2 mb-3">
                            <h2 class="text-base font-semibold text-gray-900 leading-tight">{{ $project->name }}</h2>
                            <span class="shrink-0 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $sc['class'] }}">
                                {{ $sc['label'] }}
                            </span>
                        </div>

                        {{-- Client --}}
                        <div class="flex items-center text-sm text-gray-500 mb-1">
                            <svg class="w-4 h-4 mr-1.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                            </svg>
                            <span class="truncate">Client: <span class="font-medium text-gray-700">{{ $project->client->name ?? '-' }}</span></span>
                        </div>

                        {{-- Manager --}}
                        <div class="flex items-center text-sm text-gray-500 mb-3">
                            <svg class="w-4 h-4 mr-1.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span class="truncate">Manager: <span class="font-medium text-gray-700">{{ $project->manager->name ?? '-' }}</span></span>
                        </div>

                        {{-- Progress Bar --}}
                        <div class="mb-3">
                            <div class="flex justify-between text-xs text-gray-500 mb-1">
                                <span>Progress</span>
                                <span>{{ $progress }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="h-2 rounded-full transition-all
                                    {{ $progress >= 100 ? 'bg-blue-500' : ($progress >= 50 ? 'bg-indigo-500' : 'bg-indigo-400') }}"
                                     style="width: {{ min($progress, 100) }}%">
                                </div>
                            </div>
                        </div>

                        {{-- Dates --}}
                        <div class="text-xs text-gray-400">
                            {{ $project->start_date ? \Carbon\Carbon::parse($project->start_date)->format('d M Y') : '-' }}
                            &mdash;
                            {{ $project->end_date ? \Carbon\Carbon::parse($project->end_date)->format('d M Y') : '-' }}
                        </div>
                    </div>

                    <div class="px-5 py-3 border-t border-gray-100">
                        <a href="{{ route('projects.show', $project) }}"
                           class="text-sm font-medium text-indigo-600 hover:text-indigo-800 transition">
                            Lihat Detail &rarr;
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-8">
            {{ $projects->withQueryString()->links() }}
        </div>
    @endif

</div>
@endsection
