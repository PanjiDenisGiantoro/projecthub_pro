@extends('layouts.app')
@section('title', 'Master Data — Perusahaan')
@section('page-title', 'Perusahaan')

@section('content')
<div class="py-4">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-xs text-gray-400 mb-5">
        <a href="{{ route('master.index') }}" class="hover:text-blue-600 transition-colors">Master Data</a>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-600 font-medium">Perusahaan</span>
    </div>

    {{-- Toolbar --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-6">
        <form method="GET" class="flex gap-2 flex-1 flex-wrap">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama / kode..."
                   class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 w-52">
            <select name="is_active" onchange="this.form.submit()" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-500">
                <option value="">Semua Status</option>
                <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Aktif</option>
                <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Nonaktif</option>
            </select>
            @if(request()->hasAny(['search','is_active']))
                <a href="{{ route('companies.index') }}" class="text-sm text-gray-500 hover:text-gray-700 px-3 py-2">Reset</a>
            @endif
        </form>
        <a href="{{ route('companies.create') }}" class="inline-flex items-center gap-2 bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Perusahaan
        </a>
    </div>

    @if($companies->isEmpty())
        <div class="bg-white rounded-xl border border-gray-200 py-16 text-center">
            <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
            <p class="text-gray-500 text-sm">Belum ada perusahaan.</p>
            <a href="{{ route('companies.create') }}" class="mt-3 inline-flex items-center gap-1 text-blue-600 text-sm font-medium hover:underline">
                Tambah sekarang
            </a>
        </div>
    @else
        {{-- Cards Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($companies as $company)
            @php
                $palette = ['blue','indigo','violet','emerald','rose','amber','cyan','teal'];
                $color = $palette[$company->id % count($palette)];
                $colorMap = [
                    'blue'    => ['bg' => 'bg-blue-500',   'light' => 'bg-blue-50',   'text' => 'text-blue-700',   'border' => 'border-blue-200'],
                    'indigo'  => ['bg' => 'bg-indigo-500', 'light' => 'bg-indigo-50', 'text' => 'text-indigo-700', 'border' => 'border-indigo-200'],
                    'violet'  => ['bg' => 'bg-violet-500', 'light' => 'bg-violet-50', 'text' => 'text-violet-700', 'border' => 'border-violet-200'],
                    'emerald' => ['bg' => 'bg-emerald-500','light' => 'bg-emerald-50','text' => 'text-emerald-700','border' => 'border-emerald-200'],
                    'rose'    => ['bg' => 'bg-rose-500',   'light' => 'bg-rose-50',   'text' => 'text-rose-700',   'border' => 'border-rose-200'],
                    'amber'   => ['bg' => 'bg-amber-500',  'light' => 'bg-amber-50',  'text' => 'text-amber-700',  'border' => 'border-amber-200'],
                    'cyan'    => ['bg' => 'bg-cyan-500',   'light' => 'bg-cyan-50',   'text' => 'text-cyan-700',   'border' => 'border-cyan-200'],
                    'teal'    => ['bg' => 'bg-teal-500',   'light' => 'bg-teal-50',   'text' => 'text-teal-700',   'border' => 'border-teal-200'],
                ];
                $c = $colorMap[$color];
            @endphp
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-md transition-shadow group">
                {{-- Color bar --}}
                <div class="h-1.5 {{ $c['bg'] }}"></div>

                <div class="p-5">
                    <div class="flex items-start gap-4 mb-4">
                        {{-- Avatar --}}
                        <div class="w-11 h-11 rounded-xl {{ $c['light'] }} {{ $c['text'] }} flex items-center justify-center font-bold text-base flex-shrink-0">
                            {{ strtoupper(substr($company->name, 0, 2)) }}
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="font-semibold text-gray-900 text-sm leading-tight">{{ $company->name }}</h3>
                                @if($company->code)
                                    <span class="text-xs px-1.5 py-0.5 {{ $c['light'] }} {{ $c['text'] }} rounded font-mono">{{ $company->code }}</span>
                                @endif
                            </div>
                            @if($company->email)
                                <p class="text-xs text-gray-400 mt-0.5 truncate">{{ $company->email }}</p>
                            @endif
                        </div>

                        <span class="shrink-0 text-xs px-2 py-0.5 rounded-full {{ $company->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $company->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </div>

                    {{-- Stats --}}
                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div class="bg-gray-50 rounded-lg p-3 text-center">
                            <div class="text-xl font-bold text-gray-800">{{ $company->root_organization_units_count }}</div>
                            <div class="text-xs text-gray-500 mt-0.5">Unit Level 1</div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3 text-center">
                            <div class="text-xl font-bold text-gray-800">{{ $company->organization_units_count }}</div>
                            <div class="text-xs text-gray-500 mt-0.5">Total Unit</div>
                        </div>
                    </div>

                    @if($company->website)
                        <p class="text-xs mb-1 line-clamp-1">
                            <svg class="w-3 h-3 inline mr-1 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                            <a href="{{ $company->website }}" target="_blank" rel="noopener noreferrer"
                               class="text-blue-500 hover:text-blue-700 hover:underline transition-colors">
                                {{ parse_url($company->website, PHP_URL_HOST) ?? $company->website }}
                            </a>
                        </p>
                    @endif
                    @if($company->address)
                        <p class="text-xs text-gray-400 mb-4 line-clamp-1">
                            <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            {{ $company->address }}
                        </p>
                    @endif

                    {{-- Actions --}}
                    <div class="flex items-center gap-3 pt-3 border-t border-gray-100">
                        <a href="{{ route('organization-units.index', ['company_id' => $company->id]) }}"
                           class="text-xs text-violet-600 hover:text-violet-800 font-medium transition-colors">
                            Lihat Organisasi →
                        </a>
                        <div class="flex-1"></div>
                        <a href="{{ route('companies.edit', $company) }}" class="text-xs text-gray-500 hover:text-gray-700 font-medium transition-colors">Edit</a>
                        <form method="POST" action="{{ route('companies.destroy', $company) }}"
                              data-confirm-delete="{{ $company->name }}" data-confirm-label="Hapus Perusahaan">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs text-red-500 hover:text-red-700 transition-colors">Hapus</button>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        @if($companies->hasPages())
            <div class="mt-6">{{ $companies->links() }}</div>
        @endif
    @endif
</div>
@endsection
