@extends('superadmin.layout')
@section('title', 'Semua Perusahaan')
@section('page-title', 'Semua Perusahaan')

@section('content')

<div class="bg-slate-800/60 border border-white/5 rounded-2xl overflow-hidden">
    <div class="px-6 py-4 border-b border-white/5">
        <p class="text-sm text-slate-400">Total: <span class="text-white font-semibold">{{ $companies->total() }}</span> perusahaan</p>
    </div>

    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-white/5 text-xs text-slate-500 uppercase tracking-wide">
                <th class="text-left px-6 py-3 font-medium">Perusahaan</th>
                <th class="text-left px-6 py-3 font-medium">Kontak</th>
                <th class="text-center px-6 py-3 font-medium">Cabang</th>
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
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center text-xs font-bold text-white shrink-0"
                             style="background: linear-gradient(135deg, #6366f1, #8b5cf6)">
                            {{ strtoupper(substr($company->name, 0, 2)) }}
                        </div>
                        <div>
                            <p class="font-semibold text-white">{{ $company->name }}</p>
                            <p class="text-xs text-slate-500 font-mono">{{ $company->code ?? '-' }}</p>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <p class="text-slate-300 text-xs">{{ $company->email ?? '-' }}</p>
                    <p class="text-slate-500 text-xs">{{ $company->phone ?? '-' }}</p>
                </td>
                <td class="px-6 py-4 text-center text-slate-300">{{ $company->branches_count }}</td>
                <td class="px-6 py-4 text-slate-400 text-xs">{{ $company->created_at->format('d M Y, H:i') }}</td>
                <td class="px-6 py-4 text-center">
                    @if($company->is_active)
                        <span class="bg-green-500/15 text-green-400 text-xs font-medium px-2.5 py-1 rounded-full">Aktif</span>
                    @else
                        <span class="bg-red-500/15 text-red-400 text-xs font-medium px-2.5 py-1 rounded-full">Nonaktif</span>
                    @endif
                </td>
                <td class="px-6 py-4 text-right">
                    <form method="POST" action="{{ route('superadmin.companies.toggle', $company) }}">
                        @csrf @method('PATCH')
                        <button type="submit"
                                class="text-xs {{ $company->is_active ? 'text-red-400 hover:text-red-300' : 'text-green-400 hover:text-green-300' }} transition-colors px-3 py-1.5 rounded-lg hover:bg-white/5">
                            {{ $company->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                        </button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-6 py-12 text-center text-slate-500">Belum ada perusahaan.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($companies->hasPages())
    <div class="px-6 py-4 border-t border-white/5 text-slate-400">
        {{ $companies->links() }}
    </div>
    @endif
</div>

@endsection
