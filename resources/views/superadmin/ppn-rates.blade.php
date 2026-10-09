@extends('superadmin.layout')
@section('title', 'Master PPN')
@section('page-title', 'Master PPN')

@section('content')

@php
    $fmtRate = fn ($r) => \App\Models\PpnRate::formatRate($r);
    $rateJson = $rates->mapWithKeys(fn ($r) => [$r->id => [
        'id'         => $r->id,
        'rate'       => $r->rate,
        'start_date' => $r->start_date->toDateString(),
        'end_date'   => $r->end_date?->toDateString() ?? '',
        'notes'      => $r->notes ?? '',
    ]]);
@endphp

<div x-data="{
        modalOpen: {{ $errors->any() ? 'true' : 'false' }},
        mode: @js(old('_method') === 'PUT' ? 'edit' : 'create'),
        form: { id: @js(old('id')), rate: @js(old('rate', '')), start_date: @js(old('start_date', '')), end_date: @js(old('end_date', '')), notes: @js(old('notes', '')) },
        openCreate() { this.mode = 'create'; this.form = { id: null, rate: '', start_date: '', end_date: '', notes: '' }; this.modalOpen = true; },
        openEdit(r) { this.mode = 'edit'; this.form = { ...r }; this.modalOpen = true; },
    }">

    <div class="flex items-center justify-between mb-5">
        <div>
            <p class="text-sm text-slate-400">
                Tarif berlaku hari ini:
                @if($current)
                    <span class="text-white font-semibold">{{ $fmtRate($current->rate) }}</span>
                @else
                    <span class="text-red-400 font-semibold">tidak ada (checkout tanpa PPN)</span>
                @endif
            </p>
            <p class="text-xs text-slate-500 mt-0.5">PPN dihitung dari harga paket saat checkout, memakai tarif yang periodenya mencakup tanggal transaksi.</p>
        </div>
        <button type="button" @click="openCreate()"
                class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold bg-blue-600 hover:bg-blue-500 text-white rounded-xl transition-all">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Tambah Tarif
        </button>
    </div>

    <div class="bg-slate-800/60 border border-white/5 rounded-2xl overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-white/5 text-xs text-slate-500 uppercase tracking-wide">
                    <th class="text-left px-6 py-3 font-medium">Tarif</th>
                    <th class="text-left px-6 py-3 font-medium">Mulai</th>
                    <th class="text-left px-6 py-3 font-medium">Berakhir</th>
                    <th class="text-left px-6 py-3 font-medium">Keterangan</th>
                    <th class="text-center px-6 py-3 font-medium">Status</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @forelse($rates as $r)
                <tr class="hover:bg-white/2 transition-colors">
                    <td class="px-6 py-4 font-semibold text-white">{{ $fmtRate($r->rate) }}</td>
                    <td class="px-6 py-4 text-slate-300">{{ $r->start_date->translatedFormat('d M Y') }}</td>
                    <td class="px-6 py-4 text-slate-300">{{ $r->end_date?->translatedFormat('d M Y') ?? '—' }}</td>
                    <td class="px-6 py-4 text-slate-400">{{ $r->notes ?? '—' }}</td>
                    <td class="px-6 py-4 text-center">
                        @if($r->isCurrent())
                            <span class="bg-green-500/15 text-green-400 text-xs font-medium px-2.5 py-1 rounded-full">Berlaku</span>
                        @elseif($r->start_date->isFuture())
                            <span class="bg-blue-500/15 text-blue-400 text-xs font-medium px-2.5 py-1 rounded-full">Akan Datang</span>
                        @else
                            <span class="bg-slate-500/15 text-slate-300 text-xs font-medium px-2.5 py-1 rounded-full">Berakhir</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-1">
                            <button type="button" @click="openEdit(@js($rateJson[$r->id]))"
                                    class="text-xs text-slate-400 hover:text-white transition-colors px-3 py-1.5 rounded-lg hover:bg-white/5">
                                Edit
                            </button>
                            <form method="POST" action="{{ route('superadmin.ppn-rates.destroy', $r) }}"
                                  onsubmit="return confirm('Hapus tarif PPN {{ $fmtRate($r->rate) }} ({{ $r->start_date->format('d M Y') }})?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="text-xs text-red-500 hover:text-red-400 transition-colors px-3 py-1.5 rounded-lg hover:bg-white/5">
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-slate-500">Belum ada tarif PPN.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Modal Form (Tambah / Edit Tarif) --}}
    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4 py-8" style="display:none;">
        <div @click.outside="modalOpen = false"
             class="bg-slate-900 border border-white/10 rounded-2xl w-full max-w-lg shadow-2xl max-h-[90vh] overflow-y-auto">

            <div class="flex items-center justify-between px-6 py-4 border-b border-white/5">
                <div>
                    <h3 class="font-semibold text-white text-sm" x-text="mode === 'edit' ? 'Edit Tarif PPN' : 'Tambah Tarif PPN'"></h3>
                    <p class="text-xs text-slate-500 mt-0.5">Kosongkan tanggal berakhir jika tarif masih berlaku sampai ada perubahan</p>
                </div>
                <button type="button" @click="modalOpen = false" class="text-slate-500 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form method="POST" :action="mode === 'edit' ? ('/superadmin/ppn-rates/' + form.id) : '{{ route('superadmin.ppn-rates.store') }}'" class="px-6 py-5 space-y-4">
                @csrf
                <template x-if="mode === 'edit'">
                    <input type="hidden" name="_method" value="PUT">
                </template>
                <input type="hidden" name="id" :value="form.id">

                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-slate-400 uppercase tracking-wide">Tarif (%)</label>
                    <input type="number" name="rate" x-model="form.rate" min="0" max="100" step="0.01" required placeholder="11"
                           class="w-full bg-slate-800 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-blue-500/60">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-slate-400 uppercase tracking-wide">Tanggal Mulai</label>
                        <input type="date" name="start_date" x-model="form.start_date" required
                               class="w-full bg-slate-800 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500/60">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-slate-400 uppercase tracking-wide">Tanggal Berakhir</label>
                        <input type="date" name="end_date" x-model="form.end_date"
                               class="w-full bg-slate-800 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500/60">
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-slate-400 uppercase tracking-wide">Keterangan (opsional)</label>
                    <input type="text" name="notes" x-model="form.notes" maxlength="255" placeholder="mis. PMK / UU HPP"
                           class="w-full bg-slate-800 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-blue-500/60">
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="modalOpen = false"
                            class="px-4 py-2 text-xs font-semibold text-slate-400 hover:text-white rounded-xl hover:bg-white/5 transition-colors">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-4 py-2 text-xs font-semibold bg-blue-600 hover:bg-blue-500 text-white rounded-xl transition-all">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
