@extends('superadmin.layout')
@section('title', 'Dashboard')
@section('page-title', 'Platform Overview')

@section('content')

{{-- Stats --}}
<div class="grid grid-cols-4 gap-4 mb-8">
    @foreach([
        ['label' => 'Total Perusahaan', 'value' => $stats['total_companies'], 'color' => 'text-amber-400', 'bg' => 'bg-amber-500/10'],
        ['label' => 'Total User', 'value' => $stats['total_users'], 'color' => 'text-blue-400', 'bg' => 'bg-blue-500/10'],
        ['label' => 'Total Project', 'value' => $stats['total_projects'], 'color' => 'text-green-400', 'bg' => 'bg-green-500/10'],
        ['label' => 'Daftar Bulan Ini', 'value' => $stats['new_this_month'], 'color' => 'text-purple-400', 'bg' => 'bg-purple-500/10'],
    ] as $s)
    <div class="bg-slate-800/60 border border-white/5 rounded-2xl p-5">
        <div class="text-xs text-slate-400 font-medium mb-3">{{ $s['label'] }}</div>
        <div class="text-3xl font-bold {{ $s['color'] }}">{{ number_format($s['value']) }}</div>
    </div>
    @endforeach
</div>

{{-- Tenant Table --}}
<div class="bg-slate-800/60 border border-white/5 rounded-2xl overflow-hidden">
    <div class="px-6 py-4 border-b border-white/5 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-white">Tenant Terbaru</h2>
        <a href="{{ route('superadmin.companies') }}" class="text-xs text-amber-400 hover:text-amber-300 transition-colors">
            Lihat semua →
        </a>
    </div>

    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-white/5 text-xs text-slate-500 uppercase tracking-wide">
                <th class="text-left px-6 py-3 font-medium">Perusahaan</th>
                <th class="text-left px-6 py-3 font-medium">Kode</th>
                <th class="text-center px-6 py-3 font-medium">Unit Organisasi</th>
                <th class="text-left px-6 py-3 font-medium">Terdaftar</th>
                <th class="text-center px-6 py-3 font-medium">Status</th>
                <th class="px-6 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-white/5">
            @forelse($companies as $company)
            <tr class="hover:bg-white/2 transition-colors">
                <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold text-white shrink-0"
                             style="background: linear-gradient(135deg, #6366f1, #8b5cf6)">
                            {{ strtoupper(substr($company->name, 0, 2)) }}
                        </div>
                        <span class="font-medium text-white">{{ $company->name }}</span>
                    </div>
                </td>
                <td class="px-6 py-4 text-slate-400 font-mono text-xs">{{ $company->code ?? '-' }}</td>
                <td class="px-6 py-4 text-center text-slate-300">{{ $company->root_organization_units_count }}</td>
                <td class="px-6 py-4 text-slate-400 text-xs">{{ $company->created_at->format('d M Y') }}</td>
                <td class="px-6 py-4 text-center">
                    @if($company->is_active)
                        <span class="bg-green-500/15 text-green-400 text-xs font-medium px-2 py-0.5 rounded-full">Aktif</span>
                    @else
                        <span class="bg-red-500/15 text-red-400 text-xs font-medium px-2 py-0.5 rounded-full">Nonaktif</span>
                    @endif
                </td>
                <td class="px-6 py-4 text-right">
                    <form method="POST" action="{{ route('superadmin.companies.toggle', $company) }}">
                        @csrf @method('PATCH')
                        <button type="submit"
                                class="text-xs text-slate-400 hover:text-white transition-colors px-3 py-1.5 rounded-lg hover:bg-white/8">
                            {{ $company->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                        </button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-6 py-12 text-center text-slate-500 text-sm">
                    Belum ada perusahaan terdaftar.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($companies->hasPages())
    <div class="px-6 py-4 border-t border-white/5">
        {{ $companies->links() }}
    </div>
    @endif
</div>

@endsection
