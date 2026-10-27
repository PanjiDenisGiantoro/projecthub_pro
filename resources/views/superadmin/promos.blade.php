@extends('superadmin.layout')
@section('title', 'Promo')
@section('page-title', 'Promo')

@section('content')

@php
    $promoJson = $promos->mapWithKeys(fn ($p) => [$p->id => [
        'id'             => $p->id,
        'name'           => $p->name,
        'company_id'     => $p->company_id ? (string) $p->company_id : '',
        'discount_type'  => $p->discount_type,
        'discount_value' => $p->discount_type === 'percent' ? $p->discount_value : (int) $p->discount_value,
        'start_date'     => $p->start_date->toDateString(),
        'end_date'       => $p->end_date?->toDateString() ?? '',
        'is_active'      => $p->is_active,
        'notes'          => $p->notes ?? '',
    ]]);
    $statusBadge = [
        'berlaku'     => ['Berlaku', 'bg-green-500/15 text-green-400'],
        'akan_datang' => ['Akan Datang', 'bg-blue-500/15 text-blue-400'],
        'berakhir'    => ['Berakhir', 'bg-slate-500/15 text-slate-300'],
        'nonaktif'    => ['Nonaktif', 'bg-red-500/15 text-red-400'],
    ];
    $blank = ['id' => null, 'name' => '', 'company_id' => '', 'discount_type' => 'percent', 'discount_value' => '', 'start_date' => '', 'end_date' => '', 'is_active' => true, 'notes' => ''];
    $oldForm = $errors->any()
        ? ['id' => old('id'), 'name' => old('name', ''), 'company_id' => (string) old('company_id', ''), 'discount_type' => old('discount_type', 'percent'),
           'discount_value' => old('discount_value', ''), 'start_date' => old('start_date', ''), 'end_date' => old('end_date', ''),
           'is_active' => (bool) old('is_active'), 'notes' => old('notes', '')]
        : $blank;
@endphp

<div x-data="{
        modalOpen: {{ $errors->any() ? 'true' : 'false' }},
        mode: @js(old('_method') === 'PUT' ? 'edit' : 'create'),
        form: @js($oldForm),
        openCreate() { this.mode = 'create'; this.form = @js($blank); this.modalOpen = true; },
        openEdit(p) { this.mode = 'edit'; this.form = { ...p }; this.modalOpen = true; },
    }">

    <div class="flex items-center justify-between mb-5">
        <div>
            <p class="text-sm text-slate-400">Potongan harga langganan dalam periode tertentu, untuk semua perusahaan atau satu perusahaan.</p>
            <p class="text-xs text-slate-500 mt-0.5">Dipotong dari harga paket sebelum PPN. Kalau beberapa promo berlaku sekaligus, dipakai yang potongannya terbesar (tidak digabung).</p>
        </div>
        <button type="button" @click="openCreate()"
                class="shrink-0 inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold bg-blue-600 hover:bg-blue-500 text-white rounded-xl transition-all">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Tambah Promo
        </button>
    </div>

    <div class="bg-slate-800/60 border border-white/5 rounded-2xl overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-white/5 text-xs text-slate-500 uppercase tracking-wide">
                    <th class="text-left px-6 py-3 font-medium">Nama Promo</th>
                    <th class="text-left px-6 py-3 font-medium">Potongan</th>
                    <th class="text-left px-6 py-3 font-medium">Berlaku Untuk</th>
                    <th class="text-left px-6 py-3 font-medium">Periode</th>
                    <th class="text-center px-6 py-3 font-medium">Status</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @forelse($promos as $p)
                    @php [$label, $cls] = $statusBadge[$p->status()]; @endphp
                <tr class="hover:bg-white/2 transition-colors">
                    <td class="px-6 py-4">
                        <p class="font-semibold text-white">{{ $p->name }}</p>
                        @if($p->notes)<p class="text-xs text-slate-500 mt-0.5">{{ $p->notes }}</p>@endif
                    </td>
                    <td class="px-6 py-4 font-semibold text-white">{{ $p->valueLabel() }}</td>
                    <td class="px-6 py-4 text-slate-300">{{ $p->company?->name ?? 'Semua perusahaan' }}</td>
                    <td class="px-6 py-4 text-slate-300 whitespace-nowrap">
                        {{ $p->start_date->translatedFormat('d M Y') }} – {{ $p->end_date?->translatedFormat('d M Y') ?? 'seterusnya' }}
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="{{ $cls }} text-xs font-medium px-2.5 py-1 rounded-full">{{ $label }}</span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-1">
                            <button type="button" @click="openEdit(@js($promoJson[$p->id]))"
                                    class="text-xs text-slate-400 hover:text-white transition-colors px-3 py-1.5 rounded-lg hover:bg-white/5">
                                Edit
                            </button>
                            <form method="POST" action="{{ route('superadmin.promos.toggle', $p) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="text-xs text-slate-400 hover:text-white transition-colors px-3 py-1.5 rounded-lg hover:bg-white/5">
                                    {{ $p->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('superadmin.promos.destroy', $p) }}"
                                  onsubmit="return confirm(@js('Hapus promo ' . $p->name . '?'))">
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
                    <td colspan="6" class="px-6 py-12 text-center text-slate-500">Belum ada promo.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Modal Form (Tambah / Edit Promo) --}}
    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4 py-8" style="display:none;">
        <div @click.outside="modalOpen = false"
             class="bg-slate-900 border border-white/10 rounded-2xl w-full max-w-lg shadow-2xl max-h-[90vh] overflow-y-auto">

            <div class="flex items-center justify-between px-6 py-4 border-b border-white/5">
                <div>
                    <h3 class="font-semibold text-white text-sm" x-text="mode === 'edit' ? 'Edit Promo' : 'Tambah Promo'"></h3>
                    <p class="text-xs text-slate-500 mt-0.5">Kosongkan tanggal berakhir jika promo berlaku sampai dinonaktifkan</p>
                </div>
                <button type="button" @click="modalOpen = false" class="text-slate-500 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            @if($errors->any())
                <div class="mx-6 mt-4 bg-red-500/10 border border-red-500/20 text-red-300 rounded-xl px-4 py-3 text-xs">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" :action="mode === 'edit' ? ('/superadmin/promos/' + form.id) : '{{ route('superadmin.promos.store') }}'" class="px-6 py-5 space-y-4">
                @csrf
                <template x-if="mode === 'edit'">
                    <input type="hidden" name="_method" value="PUT">
                </template>
                <input type="hidden" name="id" :value="form.id">

                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-slate-400 uppercase tracking-wide">Nama Promo</label>
                    <input type="text" name="name" x-model="form.name" required maxlength="255" placeholder="mis. Promo Akhir Tahun"
                           class="w-full bg-slate-800 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-blue-500/60">
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-slate-400 uppercase tracking-wide">Berlaku Untuk</label>
                    <select name="company_id" x-model="form.company_id"
                            class="w-full bg-slate-800 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500/60">
                        <option value="">Semua perusahaan</option>
                        @foreach($companies as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-slate-400 uppercase tracking-wide">Tipe Potongan</label>
                        <select name="discount_type" x-model="form.discount_type"
                                class="w-full bg-slate-800 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500/60">
                            @foreach(\App\Models\Promo::TYPES as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-slate-400 uppercase tracking-wide" x-text="form.discount_type === 'percent' ? 'Potongan (%)' : 'Potongan (Rp)'"></label>
                        <input type="number" name="discount_value" x-model="form.discount_value" required min="0.01"
                               :max="form.discount_type === 'percent' ? 100 : null" :step="form.discount_type === 'percent' ? '0.01' : '1'"
                               :placeholder="form.discount_type === 'percent' ? '20' : '50000'"
                               class="w-full bg-slate-800 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-blue-500/60">
                    </div>
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
                    <input type="text" name="notes" x-model="form.notes" maxlength="255"
                           class="w-full bg-slate-800 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-blue-500/60">
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-300">
                    <input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="rounded border-white/20 bg-slate-800">
                    Aktif
                </label>

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
