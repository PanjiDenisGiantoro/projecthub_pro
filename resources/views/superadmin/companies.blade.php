@extends('superadmin.layout')
@section('title', 'Semua Perusahaan')
@section('page-title', 'Semua Perusahaan')

@section('content')

<div x-data="{ deleteTarget: null, confirmInput: '' }">

<div class="bg-slate-800/60 border border-white/5 rounded-2xl overflow-hidden">
    <div class="px-6 py-4 border-b border-white/5">
        <p class="text-sm text-slate-400">Total: <span class="text-white font-semibold">{{ $companies->total() }}</span> perusahaan</p>
    </div>

    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-white/5 text-xs text-slate-500 uppercase tracking-wide">
                <th class="text-left px-6 py-3 font-medium">Perusahaan</th>
                <th class="text-left px-6 py-3 font-medium">Kontak</th>
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
                <td class="px-6 py-4 text-center text-slate-300">{{ $company->root_organization_units_count }}</td>
                <td class="px-6 py-4 text-slate-400 text-xs">{{ $company->created_at->format('d M Y, H:i') }}</td>
                <td class="px-6 py-4 text-center">
                    @if($company->is_active)
                        <span class="bg-green-500/15 text-green-400 text-xs font-medium px-2.5 py-1 rounded-full">Aktif</span>
                    @else
                        <span class="bg-red-500/15 text-red-400 text-xs font-medium px-2.5 py-1 rounded-full">Nonaktif</span>
                    @endif
                </td>
                <td class="px-6 py-4 text-right">
                    <div class="flex items-center justify-end gap-1">
                        <form method="POST" action="{{ route('superadmin.companies.toggle', $company) }}">
                            @csrf @method('PATCH')
                            <button type="submit"
                                    class="text-xs {{ $company->is_active ? 'text-red-400 hover:text-red-300' : 'text-green-400 hover:text-green-300' }} transition-colors px-3 py-1.5 rounded-lg hover:bg-white/5">
                                {{ $company->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                            </button>
                        </form>
                        <button type="button"
                                @click="deleteTarget = { id: {{ $company->id }}, name: @js($company->name) }; confirmInput = ''"
                                class="text-xs text-red-500 hover:text-red-400 transition-colors px-3 py-1.5 rounded-lg hover:bg-white/5">
                            Hapus
                        </button>
                    </div>
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

{{-- Modal konfirmasi hapus perusahaan --}}
<div x-show="deleteTarget"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4"
     style="display: none;">
    <div @click.outside="deleteTarget = null"
         class="bg-slate-800 border border-white/10 rounded-2xl p-6 w-full max-w-md">
        <h3 class="text-white font-semibold text-base">Hapus Perusahaan?</h3>
        <p class="text-slate-400 text-sm mt-2">
            Ini akan menghapus <span class="text-white font-medium" x-text="deleteTarget?.name"></span> beserta
            <span class="text-red-400">seluruh user, project, absensi, dan payroll</span> di dalamnya secara permanen.
            Tindakan ini tidak bisa dibatalkan.
        </p>

        <label class="block text-xs text-slate-400 mt-4 mb-1.5">
            Ketik <span class="font-mono text-white" x-text="deleteTarget?.name"></span> untuk konfirmasi
        </label>
        <form method="POST" :action="'/superadmin/companies/' + deleteTarget?.id">
            @csrf @method('DELETE')
            <input type="text" name="confirm_name" x-model="confirmInput"
                   class="w-full px-3.5 py-2 bg-slate-900 border border-white/10 rounded-lg text-sm text-white focus:outline-none focus:ring-2 focus:ring-red-500">

            <div class="flex items-center justify-end gap-2 mt-5">
                <button type="button" @click="deleteTarget = null"
                        class="text-xs text-slate-400 hover:text-white px-4 py-2 rounded-lg hover:bg-white/5">
                    Batal
                </button>
                <button type="submit" :disabled="confirmInput !== deleteTarget?.name"
                        :class="confirmInput === deleteTarget?.name ? 'bg-red-600 hover:bg-red-700 text-white' : 'bg-white/5 text-slate-500 cursor-not-allowed'"
                        class="text-xs font-medium px-4 py-2 rounded-lg transition-colors">
                    Hapus Permanen
                </button>
            </div>
        </form>
    </div>
</div>

</div>

@endsection
