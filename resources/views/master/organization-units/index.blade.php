@extends('layouts.app')
@section('title', 'Master Data — Organisasi')
@section('page-title', 'Unit Organisasi')

@push('head')
<style>
.org-chart-wrapper { width: 100%; overflow-x: auto; padding-bottom: 24px; }
.org-chart, .org-chart ul {
    display: flex;
    justify-content: center;
    padding-top: 28px;
    position: relative;
}
.org-chart li {
    display: flex;
    flex-direction: column;
    align-items: center;
    list-style: none;
    position: relative;
    padding: 28px 14px 0 14px;
}
.org-chart li::before, .org-chart li::after {
    content: '';
    position: absolute; top: 0; right: 50%;
    border-top: 2px solid #cbd5e1;
    width: 50%; height: 28px;
}
.org-chart li::after { right: auto; left: 50%; border-left: 2px solid #cbd5e1; }
.org-chart li:only-child::after, .org-chart li:only-child::before { display: none; }
.org-chart li:only-child { padding-top: 0; }
.org-chart li:first-child::before, .org-chart li:last-child::after { border: 0 none; }
.org-chart li:last-child::before { border-right: 2px solid #cbd5e1; border-radius: 0 5px 0 0; }
.org-chart li:first-child::after { border-radius: 5px 0 0 0; }
.org-chart ul ul::before {
    content: '';
    position: absolute; top: 0; left: 50%;
    border-left: 2px solid #cbd5e1;
    width: 0; height: 28px;
}

.org-chart-box {
    width: 172px; background: #fff;
    border: 2px solid #e5e7eb; border-radius: 8px;
    overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.06);
}
.org-chart-box__header {
    padding: 6px 8px; color: #fff; font-size: 0.7rem; font-weight: 700;
    text-align: center; line-height: 1.25;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.org-chart-box__body { padding: 8px; text-align: center; }
.org-chart-box__meta {
    font-size: 0.74rem; font-weight: 600; color: #1f2937;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.org-chart-box__code { font-size: 0.6rem; color: #9ca3af; font-family: monospace; margin-top: 2px; }
.org-chart-box__badges { display: flex; align-items: center; justify-content: center; gap: 4px; margin-top: 5px; }
.org-chart-box__badges span:not(.org-chart-box__status) {
    font-size: 0.58rem; background: #f3f4f6; color: #4b5563; padding: 1px 6px; border-radius: 9999px; white-space: nowrap;
}
.org-chart-box__status { width: 8px; height: 8px; border-radius: 9999px; display: inline-block; }
.org-chart-box__status.is-active { background: #22c55e; }
.org-chart-box__status.is-inactive { background: #9ca3af; }
.org-chart-box__actions {
    display: flex; justify-content: center; gap: 10px;
    padding: 6px 8px; border-top: 1px solid #f3f4f6;
}
.org-chart-box__actions a, .org-chart-box__actions button { color: #9ca3af; }
.org-chart-box__actions a:hover { color: #7c3aed; }
.org-chart-box__actions button:hover { color: #ef4444; }
.org-chart-box--wide { width: auto; min-width: 172px; display: inline-block; }

/* Bagan Vertikal: root (L1, L2, dst) ditumpuk ke bawah (baris masing-masing),
   tapi anak-anak dalam satu cabang disusun menyamping (wrap), bukan ditumpuk ke bawah satu-satu. */
.org-tree-wrapper { width: 100%; overflow-x: auto; }
.org-tree, .org-tree ul { list-style: none; margin: 0; padding: 0; }
.org-tree--root { margin-top: 16px; }
.org-tree--root > li { margin-bottom: 20px; }
.org-tree--root > li:last-child { margin-bottom: 0; }
.org-tree:not(.org-tree--root) {
    display: flex; flex-wrap: wrap; gap: 14px;
    margin-left: 21px; padding-left: 24px; padding-top: 4px; padding-bottom: 2px;
    border-left: 2px solid #cbd5e1; margin-top: 12px;
}
.org-tree:not(.org-tree--root) > li { position: relative; }
.org-tree:not(.org-tree--root) > li::before {
    content: ''; position: absolute; left: -24px; top: 24px;
    width: 24px; height: 0; border-top: 2px solid #cbd5e1;
}
</style>
@endpush

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

    @php $viewMode = request('view', 'table'); @endphp
    <div class="flex items-center gap-1.5 mb-4">
        <a href="{{ route('organization-units.index', array_merge(request()->except('view'), ['view' => 'table'])) }}"
           class="text-sm font-medium px-3 py-1.5 rounded-lg transition-colors {{ $viewMode === 'table' ? 'bg-violet-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
            Tabel
        </a>
        <a href="{{ route('organization-units.index', array_merge(request()->except('view'), ['view' => 'chart'])) }}"
           class="text-sm font-medium px-3 py-1.5 rounded-lg transition-colors {{ $viewMode === 'chart' ? 'bg-violet-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
            Bagan Horizontal
        </a>
        <a href="{{ route('organization-units.index', array_merge(request()->except('view'), ['view' => 'chart-vertical'])) }}"
           class="text-sm font-medium px-3 py-1.5 rounded-lg transition-colors {{ $viewMode === 'chart-vertical' ? 'bg-violet-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
            Bagan Vertikal
        </a>
    </div>

    @if($viewMode === 'chart' || $viewMode === 'chart-vertical')
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            @if($units->isEmpty())
                <p class="text-center text-gray-400 py-10">
                    @if(! $selectedCompany)
                        Belum ada perusahaan.
                    @else
                        Belum ada unit organisasi.
                        <a href="{{ route('organization-units.create', ['company_id' => $selectedCompany]) }}" class="text-violet-600 hover:underline ml-1">Tambah sekarang</a>
                    @endif
                </p>
            @elseif($viewMode === 'chart-vertical')
                <x-org-chart-vertical :units="$units" :company="$companies->firstWhere('id', (int) $selectedCompany)" />
            @else
                <x-org-chart :units="$units" :company="$companies->firstWhere('id', (int) $selectedCompany)" />
            @endif
        </div>
    @else
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
    @endif
</div>
@endsection
