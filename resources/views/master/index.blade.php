@extends('layouts.app')
@section('title', 'Master Data')
@section('page-title', 'Master Data Organisasi')

@section('content')
<div class="py-4">

    {{-- Stats overview --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
        <a href="{{ route('companies.index') }}"
           class="group bg-white rounded-xl border border-gray-200 p-4 hover:border-blue-300 hover:shadow-sm transition-all">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-9 h-9 bg-blue-50 rounded-lg flex items-center justify-center group-hover:bg-blue-100 transition-colors">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
                <p class="text-2xl font-bold text-gray-900">{{ $stats['companies'] }}</p>
            </div>
            <p class="text-xs text-gray-500 font-medium">Perusahaan</p>
        </a>

        <a href="{{ route('branches.index') }}"
           class="group bg-white rounded-xl border border-gray-200 p-4 hover:border-violet-300 hover:shadow-sm transition-all">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-9 h-9 bg-violet-50 rounded-lg flex items-center justify-center group-hover:bg-violet-100 transition-colors">
                    <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <p class="text-2xl font-bold text-gray-900">{{ $stats['branches'] }}</p>
            </div>
            <p class="text-xs text-gray-500 font-medium">Branch</p>
        </a>

        <a href="{{ route('divisions.index') }}"
           class="group bg-white rounded-xl border border-gray-200 p-4 hover:border-indigo-300 hover:shadow-sm transition-all">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-9 h-9 bg-indigo-50 rounded-lg flex items-center justify-center group-hover:bg-indigo-100 transition-colors">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                </div>
                <p class="text-2xl font-bold text-gray-900">{{ $stats['divisions'] }}</p>
            </div>
            <p class="text-xs text-gray-500 font-medium">Divisi</p>
        </a>

        <a href="{{ route('departments.index') }}"
           class="group bg-white rounded-xl border border-gray-200 p-4 hover:border-teal-300 hover:shadow-sm transition-all">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-9 h-9 bg-teal-50 rounded-lg flex items-center justify-center group-hover:bg-teal-100 transition-colors">
                    <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <p class="text-2xl font-bold text-gray-900">{{ $stats['departments'] }}</p>
            </div>
            <p class="text-xs text-gray-500 font-medium">Departemen</p>
        </a>

        <a href="{{ route('structural-levels.index') }}"
           class="group bg-white rounded-xl border border-gray-200 p-4 hover:border-amber-300 hover:shadow-sm transition-all">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-9 h-9 bg-amber-50 rounded-lg flex items-center justify-center group-hover:bg-amber-100 transition-colors">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4"/></svg>
                </div>
                <p class="text-2xl font-bold text-gray-900">{{ $stats['structural_levels'] }}</p>
            </div>
            <p class="text-xs text-gray-500 font-medium">Level Struktural</p>
        </a>
    </div>

    {{-- Org Tree --}}
    @if($companies->isNotEmpty())
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-800 text-sm">Struktur Organisasi</h2>
            <div class="flex items-center gap-4 text-xs text-gray-400">
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded bg-blue-400 inline-block"></span> Perusahaan</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded bg-violet-400 inline-block"></span> Branch</span>
                <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-indigo-300 inline-block"></span> Divisi</span>
                <span class="flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-teal-300 inline-block"></span> Departemen</span>
            </div>
        </div>

        <div class="p-6 overflow-x-auto">
            <div class="space-y-6" style="min-width: 520px;">
                @foreach($companies as $company)
                @php
                    $palette = ['blue','indigo','violet','emerald','rose','amber','cyan','teal'];
                    $color   = $palette[$company->id % count($palette)];
                    $colorMap = [
                        'blue'    => ['bg'=>'bg-blue-500',   'light'=>'bg-blue-50',    'text'=>'text-blue-700',   'hex'=>'#3b82f6'],
                        'indigo'  => ['bg'=>'bg-indigo-500', 'light'=>'bg-indigo-50',  'text'=>'text-indigo-700', 'hex'=>'#6366f1'],
                        'violet'  => ['bg'=>'bg-violet-500', 'light'=>'bg-violet-50',  'text'=>'text-violet-700', 'hex'=>'#8b5cf6'],
                        'emerald' => ['bg'=>'bg-emerald-500','light'=>'bg-emerald-50', 'text'=>'text-emerald-700','hex'=>'#10b981'],
                        'rose'    => ['bg'=>'bg-rose-500',   'light'=>'bg-rose-50',    'text'=>'text-rose-700',   'hex'=>'#f43f5e'],
                        'amber'   => ['bg'=>'bg-amber-500',  'light'=>'bg-amber-50',   'text'=>'text-amber-700',  'hex'=>'#f59e0b'],
                        'cyan'    => ['bg'=>'bg-cyan-500',   'light'=>'bg-cyan-50',    'text'=>'text-cyan-700',   'hex'=>'#06b6d4'],
                        'teal'    => ['bg'=>'bg-teal-500',   'light'=>'bg-teal-50',    'text'=>'text-teal-700',   'hex'=>'#14b8a6'],
                    ];
                    $c = $colorMap[$color];
                    $totalBranches    = $company->branches->count();
                    $totalDivisions   = $company->branches->sum(fn($b) => $b->divisions->count());
                    $totalDepartments = $company->branches->sum(fn($b) => $b->divisions->sum(fn($d) => $d->departments->count()));
                @endphp

                {{-- Company node --}}
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-xl {{ $c['light'] }} {{ $c['text'] }} flex items-center justify-center font-bold text-sm flex-shrink-0 ring-2 ring-offset-1"
                             style="ring-color: {{ $c['hex'] }}40">
                            {{ strtoupper(substr($company->name, 0, 2)) }}
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-semibold text-gray-900 text-sm">{{ $company->name }}</span>
                                @if($company->code)
                                    <span class="text-xs px-1.5 py-0.5 {{ $c['light'] }} {{ $c['text'] }} rounded font-mono">{{ $company->code }}</span>
                                @endif
                                <span class="text-xs px-2 py-0.5 rounded-full {{ $company->is_active ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-400' }}">
                                    {{ $company->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-400">{{ $totalBranches }} branch &bull; {{ $totalDivisions }} divisi &bull; {{ $totalDepartments }} departemen</p>
                        </div>
                        <a href="{{ route('companies.edit', $company) }}" class="text-xs text-gray-400 hover:text-blue-600 transition-colors font-medium flex-shrink-0">Edit</a>
                    </div>

                    {{-- Branches --}}
                    @if($company->branches->isNotEmpty())
                    <div class="ml-5 pl-5 border-l-2 border-gray-100 space-y-4">
                        @foreach($company->branches as $branch)
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-2.5 h-2.5 rounded bg-violet-300 flex-shrink-0"></div>
                                <div class="flex items-center gap-2 flex-1 min-w-0">
                                    <span class="font-medium text-gray-700 text-sm">{{ $branch->name }}</span>
                                    @if($branch->code)
                                        <span class="text-xs text-gray-400 font-mono">{{ $branch->code }}</span>
                                    @endif
                                    @if(!$branch->is_active)
                                        <span class="text-xs px-1.5 py-0.5 bg-gray-100 text-gray-400 rounded">Nonaktif</span>
                                    @endif
                                    @if($branch->phone || $branch->email)
                                        <span class="text-xs text-gray-400 truncate max-w-32">{{ $branch->phone ?: $branch->email }}</span>
                                    @endif
                                    <span class="text-xs text-gray-400 ml-auto">{{ $branch->divisions->count() }} div</span>
                                </div>
                                <a href="{{ route('branches.edit', $branch) }}" class="text-xs text-gray-400 hover:text-blue-600 transition-colors flex-shrink-0">Edit</a>
                            </div>

                            {{-- Divisions --}}
                            @if($branch->divisions->isNotEmpty())
                            <div class="ml-4 pl-4 border-l border-gray-100 space-y-2">
                                @foreach($branch->divisions as $division)
                                <div>
                                    <div class="flex items-center gap-2 mb-1.5">
                                        <div class="w-2 h-2 rounded-full bg-indigo-300 flex-shrink-0"></div>
                                        <div class="flex items-center gap-2 flex-1 min-w-0">
                                            <span class="font-medium text-gray-600 text-sm">{{ $division->name }}</span>
                                            @if($division->code)
                                                <span class="text-xs text-gray-400 font-mono">{{ $division->code }}</span>
                                            @endif
                                            @if(!$division->is_active)
                                                <span class="text-xs px-1.5 py-0.5 bg-gray-100 text-gray-400 rounded">Nonaktif</span>
                                            @endif
                                            <span class="text-xs text-gray-400 ml-auto">{{ $division->departments->count() }} dept</span>
                                        </div>
                                        <a href="{{ route('divisions.edit', $division) }}" class="text-xs text-gray-400 hover:text-blue-600 transition-colors flex-shrink-0">Edit</a>
                                    </div>

                                    {{-- Departments --}}
                                    @if($division->departments->isNotEmpty())
                                    <div class="ml-4 pl-4 border-l border-gray-50 space-y-1">
                                        @foreach($division->departments as $dept)
                                        <div class="flex items-center gap-2 group py-0.5">
                                            <div class="w-1.5 h-1.5 rounded-full bg-teal-300 flex-shrink-0"></div>
                                            <span class="text-sm text-gray-600 flex-1">{{ $dept->name }}</span>
                                            @if($dept->code)
                                                <span class="text-xs text-gray-400 font-mono">{{ $dept->code }}</span>
                                            @endif
                                            @if($dept->head)
                                                <div class="flex items-center gap-1 text-xs text-gray-400">
                                                    <div class="w-4 h-4 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold">
                                                        {{ strtoupper(substr($dept->head->name, 0, 1)) }}
                                                    </div>
                                                    <span>{{ explode(' ', $dept->head->name)[0] }}</span>
                                                </div>
                                            @endif
                                            <span class="text-xs text-gray-400">{{ $dept->users_count }} user</span>
                                            <a href="{{ route('departments.edit', $dept) }}" class="text-xs text-gray-400 hover:text-blue-600 transition-colors opacity-0 group-hover:opacity-100">Edit</a>
                                        </div>
                                        @endforeach
                                        <div class="pl-3.5">
                                            <a href="{{ route('departments.create', ['branch_id' => $branch->id]) }}"
                                               class="text-xs text-teal-500 hover:text-teal-700 transition-colors">+ Tambah departemen</a>
                                        </div>
                                    </div>
                                    @else
                                    <div class="ml-4 pl-4">
                                        <a href="{{ route('departments.create', ['branch_id' => $branch->id]) }}"
                                           class="text-xs text-teal-500 hover:text-teal-700 transition-colors">+ Tambah departemen</a>
                                    </div>
                                    @endif
                                </div>
                                @endforeach
                                <div class="pl-3.5">
                                    <a href="{{ route('divisions.create') }}" class="text-xs text-indigo-500 hover:text-indigo-700 transition-colors">+ Tambah divisi</a>
                                </div>
                            </div>
                            @else
                            <div class="ml-4 pl-4">
                                <a href="{{ route('divisions.create') }}" class="text-xs text-indigo-500 hover:text-indigo-700 transition-colors">+ Tambah divisi</a>
                            </div>
                            @endif
                        </div>
                        @endforeach
                        <div class="pl-3.5">
                            <a href="{{ route('branches.create') }}" class="text-xs text-violet-500 hover:text-violet-700 transition-colors">+ Tambah branch</a>
                        </div>
                    </div>
                    @else
                    <div class="ml-5 pl-5">
                        <a href="{{ route('branches.create') }}" class="text-xs text-violet-500 hover:text-violet-700 transition-colors">+ Tambah branch</a>
                    </div>
                    @endif
                </div>

                @if(!$loop->last)<div class="border-t border-gray-100"></div>@endif
                @endforeach
            </div>
        </div>

        <div class="px-6 py-3 bg-gray-50 border-t border-gray-100">
            <a href="{{ route('companies.create') }}" class="text-xs text-blue-600 hover:text-blue-800 font-medium transition-colors">+ Tambah Perusahaan Baru</a>
        </div>
    </div>
    @else
    <div class="bg-white rounded-xl border border-gray-200 border-dashed py-16 text-center">
        <div class="w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
        </div>
        <p class="text-gray-700 font-medium mb-1">Mulai dengan menambahkan perusahaan</p>
        <p class="text-gray-400 text-sm mb-4">Kemudian tambahkan branch, divisi, dan departemen.</p>
        <a href="{{ route('companies.create') }}" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2.5 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Perusahaan
        </a>
    </div>
    @endif
</div>
@endsection
