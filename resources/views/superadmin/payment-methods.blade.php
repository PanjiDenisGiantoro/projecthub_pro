@extends('superadmin.layout')
@section('title', 'Metode Bayar & Fee')
@section('page-title', 'Metode Bayar & Fee')

@section('content')

@php
    $methodJson = $methods->mapWithKeys(fn ($m) => [$m->id => [
        'id'          => $m->id,
        'code'        => $m->code,
        'name'        => $m->name,
        'group'       => $m->group,
        'fee_flat'    => $m->fee_flat,
        'fee_percent' => $m->fee_percent,
        'sort_order'  => $m->sort_order,
        'is_active'   => $m->is_active,
    ]]);
    $blank = ['id' => null, 'code' => '', 'name' => '', 'group' => 'Virtual Account', 'fee_flat' => 0, 'fee_percent' => 0, 'sort_order' => 0, 'is_active' => true];
    $exampleBase = \App\Models\PpnRate::breakdown(150000)['total'];
@endphp

<div x-data="{
        modalOpen: {{ $errors->any() ? 'true' : 'false' }},
        mode: @js(old('_method') === 'PUT' ? 'edit' : 'create'),
        form: @js($errors->any() ? array_merge($blank, old()) : $blank),
        openCreate() { this.mode = 'create'; this.form = { ...@js($blank) }; this.modalOpen = true; },
        openEdit(m) { this.mode = 'edit'; this.form = { ...m }; this.modalOpen = true; },
    }">

    <div class="flex items-center justify-between mb-5">
        <div>
            <p class="text-sm text-slate-400">Metode yang aktif muncul di halaman checkout customer. Biaya layanan ditambahkan ke total tagihan.</p>
            <p class="text-xs text-slate-500 mt-0.5">
                Biaya = flat + (persen × harga termasuk PPN), dibulatkan ke atas. Kode harus sama dengan kode metode di DOKU, dan metodenya harus sudah aktif di akun DOKU Anda.
            </p>
        </div>
        <button type="button" @click="openCreate()"
                class="shrink-0 inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold bg-blue-600 hover:bg-blue-500 text-white rounded-xl transition-all">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Tambah Metode
        </button>
    </div>

    <div class="bg-slate-800/60 border border-white/5 rounded-2xl overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-white/5 text-xs text-slate-500 uppercase tracking-wide">
                    <th class="text-left px-6 py-3 font-medium">Metode</th>
                    <th class="text-left px-6 py-3 font-medium">Kelompok</th>
                    <th class="text-left px-6 py-3 font-medium">Biaya Layanan</th>
                    <th class="text-left px-6 py-3 font-medium">Contoh (Rp {{ number_format($exampleBase, 0, ',', '.') }})</th>
                    <th class="text-center px-6 py-3 font-medium">Status</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @forelse($methods as $m)
                <tr class="hover:bg-white/2 transition-colors">
                    <td class="px-6 py-4">
                        <p class="font-semibold text-white">{{ $m->name }}</p>
                        <p class="text-xs text-slate-500 font-mono">{{ $m->code }}</p>
                    </td>
                    <td class="px-6 py-4 text-slate-300">{{ $m->group }}</td>
                    <td class="px-6 py-4 text-slate-200">{{ $m->feeLabel() }}</td>
                    <td class="px-6 py-4 text-slate-300">Rp {{ number_format($m->feeFor($exampleBase), 0, ',', '.') }}</td>
                    <td class="px-6 py-4 text-center">
                        @if($m->is_active)
                            <span class="bg-green-500/15 text-green-400 text-xs font-medium px-2.5 py-1 rounded-full">Aktif</span>
                        @else
                            <span class="bg-red-500/15 text-red-400 text-xs font-medium px-2.5 py-1 rounded-full">Nonaktif</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-1">
                            <button type="button" @click="openEdit(@js($methodJson[$m->id]))"
                                    class="text-xs text-slate-400 hover:text-white transition-colors px-3 py-1.5 rounded-lg hover:bg-white/5">
                                Edit
                            </button>
                            <form method="POST" action="{{ route('superadmin.payment-methods.toggle', $m) }}">
                                @csrf @method('PATCH')
                                <button type="submit"
                                        class="text-xs {{ $m->is_active ? 'text-red-400 hover:text-red-300' : 'text-green-400 hover:text-green-300' }} transition-colors px-3 py-1.5 rounded-lg hover:bg-white/5">
                                    {{ $m->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('superadmin.payment-methods.destroy', $m) }}"
                                  onsubmit="return confirm(@js('Hapus metode bayar ' . $m->name . '?'))">
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
                    <td colspan="6" class="px-6 py-12 text-center text-slate-500">Belum ada metode bayar.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Modal Form (Tambah / Edit Metode) --}}
    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4 py-8" style="display:none;">
        <div @click.outside="modalOpen = false"
             class="bg-slate-900 border border-white/10 rounded-2xl w-full max-w-lg shadow-2xl max-h-[90vh] overflow-y-auto">

            <div class="flex items-center justify-between px-6 py-4 border-b border-white/5">
                <div>
                    <h3 class="font-semibold text-white text-sm" x-text="mode === 'edit' ? 'Edit Metode Bayar' : 'Tambah Metode Bayar'"></h3>
                    <p class="text-xs text-slate-500 mt-0.5">Sesuaikan biaya dengan MDR di kontrak DOKU Anda</p>
                </div>
                <button type="button" @click="modalOpen = false" class="text-slate-500 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form method="POST" :action="mode === 'edit' ? ('/superadmin/payment-methods/' + form.id) : '{{ route('superadmin.payment-methods.store') }}'" class="px-6 py-5 space-y-4">
                @csrf
                <template x-if="mode === 'edit'">
                    <input type="hidden" name="_method" value="PUT">
                </template>
                <input type="hidden" name="id" :value="form.id">

                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-slate-400 uppercase tracking-wide">Nama</label>
                        <input type="text" name="name" x-model="form.name" required maxlength="100" placeholder="BCA Virtual Account"
                               class="w-full bg-slate-800 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-blue-500/60">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-slate-400 uppercase tracking-wide">Kelompok</label>
                        <select name="group" x-model="form.group"
                                class="w-full bg-slate-800 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500/60">
                            @foreach(\App\Models\PaymentMethod::GROUPS as $g)
                                <option value="{{ $g }}">{{ $g }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-slate-400 uppercase tracking-wide">Kode DOKU</label>
                    <input type="text" name="code" x-model="form.code" required maxlength="64" placeholder="VIRTUAL_ACCOUNT_BCA"
                           @input="form.code = form.code.toUpperCase()"
                           class="w-full bg-slate-800 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white font-mono placeholder-slate-600 focus:outline-none focus:border-blue-500/60">
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-slate-400 uppercase tracking-wide">Biaya Flat (Rp)</label>
                        <input type="number" name="fee_flat" x-model="form.fee_flat" min="0" required
                               class="w-full bg-slate-800 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500/60">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-slate-400 uppercase tracking-wide">Biaya (%)</label>
                        <input type="number" name="fee_percent" x-model="form.fee_percent" min="0" max="100" step="0.01" required
                               class="w-full bg-slate-800 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500/60">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-slate-400 uppercase tracking-wide">Urutan</label>
                        <input type="number" name="sort_order" x-model="form.sort_order" min="0"
                               class="w-full bg-slate-800 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500/60">
                    </div>
                </div>

                <label class="flex items-center gap-2 text-xs font-medium text-slate-300">
                    <input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="accent-blue-500">
                    Aktif (tampil di halaman checkout)
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
