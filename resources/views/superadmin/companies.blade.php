@extends('superadmin.layout')
@section('title', 'Semua Perusahaan')
@section('page-title', 'Semua Perusahaan')

@section('content')

<div x-data="companyStorage()">

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
                <th class="text-left px-6 py-3 font-medium">Storage</th>
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
                @php
                    $limits     = $storage[$company->id];
                    $usedBytes  = $limits->storageUsedBytes();
                    $maxBytes   = $limits->maxStorageBytes();
                    $usedPct    = $maxBytes ? min(100, $usedBytes / $maxBytes * 100) : null;
                    $barColor   = $usedPct === null ? 'bg-slate-500' : ($usedPct >= 90 ? 'bg-red-500' : ($usedPct >= 70 ? 'bg-amber-500' : 'bg-blue-500'));
                @endphp
                <td class="px-6 py-4 min-w-[160px]">
                    <p class="text-xs text-slate-300">
                        {{ \App\Support\PlanLimits::humanBytes($usedBytes) }}
                        <span class="text-slate-500">/ {{ $limits->storageLimitLabel() }}</span>
                    </p>
                    <div class="mt-1.5 h-1.5 w-full rounded-full bg-white/5 overflow-hidden">
                        <div class="h-full rounded-full {{ $barColor }}" style="width: {{ $usedPct === null ? 0 : max(1, $usedPct) }}%"></div>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1">{{ $limits->plan() ? $limits->planName() : 'Internal' }}{{ $usedPct !== null ? ' · ' . round($usedPct, 1) . '%' : '' }}</p>
                </td>
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
                        <button type="button" @click="openStorage(@js(route('superadmin.companies.storage', $company)))"
                                class="text-xs text-blue-400 hover:text-blue-300 transition-colors px-3 py-1.5 rounded-lg hover:bg-white/5">
                            Storage
                        </button>
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
                <td colspan="7" class="px-6 py-12 text-center text-slate-500">Belum ada perusahaan.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="px-6 py-4 border-t border-white/5 text-slate-400 flex items-center justify-between gap-3 flex-wrap">
        <x-per-page />
        @if($companies->hasPages())
        {{ $companies->links() }}
        @endif
    </div>
</div>

{{-- Modal monitoring storage perusahaan --}}
<div x-show="storageOpen"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4"
     style="display: none;">
    <div @click.outside="storageOpen = false"
         class="bg-slate-800 border border-white/10 rounded-2xl p-6 w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h3 class="text-white font-semibold text-base">Monitoring Storage</h3>
                <p class="text-slate-400 text-sm mt-0.5" x-text="storage?.company ?? 'Memuat…'"></p>
            </div>
            <button type="button" @click="storageOpen = false" class="text-slate-500 hover:text-white text-lg leading-none px-1">&times;</button>
        </div>

        <template x-if="storageLoading">
            <p class="text-slate-500 text-sm py-10 text-center">Menghitung pemakaian storage…</p>
        </template>
        <template x-if="storageError">
            <p class="text-red-400 text-sm py-10 text-center" x-text="storageError"></p>
        </template>

        <template x-if="storage && !storageLoading">
            <div>
                <div class="mt-5 rounded-xl bg-slate-900/60 border border-white/5 p-4">
                    <div class="flex items-end justify-between gap-3">
                        <div>
                            <p class="text-2xl font-semibold text-white" x-text="storage.used"></p>
                            <p class="text-xs text-slate-500 mt-0.5">dari <span x-text="storage.limit"></span> · paket <span x-text="storage.plan"></span></p>
                        </div>
                        <p class="text-sm font-medium" :class="barText(storage.percent)" x-text="storage.percent === null ? 'Tanpa batas' : storage.percent + '%'"></p>
                    </div>
                    <div class="mt-3 h-2 w-full rounded-full bg-white/5 overflow-hidden">
                        <div class="h-full rounded-full" :class="barBg(storage.percent)" :style="'width:' + (storage.percent ?? 0) + '%'"></div>
                    </div>
                    <p class="text-xs text-slate-500 mt-2" x-show="storage.remaining">Sisa: <span class="text-slate-300" x-text="storage.remaining"></span></p>
                </div>

                <p class="text-xs text-slate-500 uppercase tracking-wide mt-5 mb-2">Rincian per menu</p>
                <div class="divide-y divide-white/5">
                    <template x-for="item in storage.items" :key="item.label">
                        <div class="py-2.5">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-slate-300" x-text="item.label"></span>
                                <span class="text-slate-400 tabular-nums"><span x-text="item.size"></span> <span class="text-slate-600 text-xs" x-text="'(' + item.percent + '%)'"></span></span>
                            </div>
                            <div class="mt-1.5 h-1 w-full rounded-full bg-white/5 overflow-hidden">
                                <div class="h-full rounded-full bg-blue-400" :style="'width:' + item.percent + '%'"></div>
                            </div>
                        </div>
                    </template>
                </div>
                <p class="text-[11px] text-slate-600 mt-4">Avatar, logo perusahaan & foto wajah absensi tidak dihitung. Data yang sudah dihapus tidak masuk hitungan.</p>
            </div>
        </template>
    </div>
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

<script>
    function companyStorage() {
        return {
            deleteTarget: null,
            confirmInput: '',
            storageOpen: false,
            storageLoading: false,
            storageError: null,
            storage: null,
            async openStorage(url) {
                this.storageOpen = true;
                this.storageLoading = true;
                this.storageError = null;
                this.storage = null;
                try {
                    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    this.storage = await res.json();
                } catch (e) {
                    this.storageError = 'Gagal memuat data storage (' + e.message + ').';
                } finally {
                    this.storageLoading = false;
                }
            },
            barBg(pct) { return pct === null ? 'bg-slate-500' : pct >= 90 ? 'bg-red-500' : pct >= 70 ? 'bg-amber-500' : 'bg-blue-500'; },
            barText(pct) { return pct === null ? 'text-slate-400' : pct >= 90 ? 'text-red-400' : pct >= 70 ? 'text-amber-400' : 'text-blue-400'; },
        };
    }
</script>

@endsection
