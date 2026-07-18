@extends('layouts.app')
@section('title', 'Master Data — Organisasi')
@section('page-title', 'Unit Organisasi')

@section('content')
<div class="py-4">

    <div class="flex items-center gap-2 text-xs text-gray-400 mb-5">
        <a href="{{ route('master.index') }}" class="hover:text-blue-600 transition-colors">Master Data</a>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-600 font-medium">Organisasi</span>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-6">
        <form method="GET" class="flex gap-2 flex-1 flex-wrap">
            @if($companies->count() > 1)
                <select name="company_id" onchange="this.form.submit()" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-500">
                    @foreach($companies as $comp)
                        <option value="{{ $comp->id }}" {{ (string) $selectedCompany === (string) $comp->id ? 'selected' : '' }}>{{ $comp->name }}</option>
                    @endforeach
                </select>
            @endif
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama / kode..."
                   class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 w-44">
            <select name="is_active" onchange="this.form.submit()" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-500">
                <option value="">Semua Status</option>
                <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Aktif</option>
                <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Nonaktif</option>
            </select>
            @if(request()->hasAny(['search','is_active']))
                <a href="{{ route('organization-units.index', ['company_id' => $selectedCompany]) }}" class="text-sm text-gray-500 hover:text-gray-700 px-3 py-2">Reset</a>
            @endif
        </form>
        <a href="{{ route('organization-units.create', ['company_id' => $selectedCompany]) }}" class="inline-flex items-center gap-2 bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Unit
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wide">
                <tr>
                    <th class="px-4 py-3 text-left">Unit</th>
                    <th class="px-4 py-3 text-left">Kepala Unit</th>
                    <th class="px-4 py-3 text-center">Turunan</th>
                    <th class="px-4 py-3 text-center">Anggota</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($units as $unit)
                    <x-org-unit-row :unit="$unit" />
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-10 text-center text-gray-400">
                        @if(! $selectedCompany)
                            Belum ada perusahaan.
                        @else
                            Belum ada unit organisasi.
                            <a href="{{ route('organization-units.create', ['company_id' => $selectedCompany]) }}" class="text-violet-600 hover:underline ml-1">Tambah sekarang</a>
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
