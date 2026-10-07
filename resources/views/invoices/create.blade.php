@extends('layouts.app')

@section('title', 'Buat Invoice Baru')
@section('page-title', 'Buat Invoice Baru')

@section('content')
<div class="space-y-6 pt-5 pb-8 max-w-5xl mx-auto" x-data="invoiceForm()">

    {{-- ── Breadcrumb & Top Banner ────────────────────────────────────────── --}}
    <nav class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-2">
        <a href="{{ route('invoices.index') }}" class="hover:text-blue-600 transition">Invoice & Penagihan</a>
        <span>/</span>
        <span class="text-slate-800 dark:text-slate-200 font-semibold">Buat Invoice Baru</span>
    </nav>

    <div class="relative overflow-hidden rounded-2xl border border-slate-200/80 dark:border-gray-700/80 bg-gradient-to-r from-blue-50/80 via-indigo-50/50 to-cyan-50/60 dark:from-gray-850 dark:via-gray-850 dark:to-gray-800 px-6 py-6 shadow-xs">
        <div class="flex items-center justify-between">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-blue-100/70 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-xs font-semibold mb-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-600 dark:bg-blue-400 animate-pulse"></span>
                    Dokumen Penagihan Resmi
                </div>
                <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                    Terbitkan Invoice Baru
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Lengkapi rincian penagihan, termin proyek, dan item jasa untuk diterbitkan ke klien.
                </p>
            </div>
            <a href="{{ route('invoices.index') }}"
               class="hidden sm:inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold border border-slate-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-gray-700 transition">
                &larr; Kembali
            </a>
        </div>
    </div>

    {{-- ── Form ───────────────────────────────────────────────────────────── --}}
    <form method="POST" action="{{ route('invoices.store') }}" enctype="multipart/form-data"
          @submit="if (submitting) { $event.preventDefault(); } else { submitting = true; }"
          class="space-y-6">
        @csrf

        {{-- Section 1: Detail & Penerima --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 p-6 shadow-xs space-y-5">
            <div class="border-b border-slate-100 dark:border-gray-700 pb-3">
                <h2 class="text-sm font-bold text-slate-900 dark:text-white">1. Informasi Penerima & Entitas</h2>
                <p class="text-xs text-slate-400">Pilih jenis tagihan dan relasi proyek.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @if($companies->count() > 1)
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                        Perusahaan Penerbit <span class="text-rose-500">*</span>
                    </label>
                    <select name="company_id" required
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-xl text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 cursor-pointer shadow-2xs">
                        <option value="">— Pilih Perusahaan —</option>
                        @foreach($companies as $co)
                            <option value="{{ $co->id }}" {{ old('company_id') == $co->id ? 'selected' : '' }}>{{ $co->name }}</option>
                        @endforeach
                    </select>
                </div>
                @elseif($companies->isNotEmpty())
                    <input type="hidden" name="company_id" value="{{ $companies->first()->id }}">
                @endif

                {{-- Jenis Invoice Selector (Card Radio) --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2">
                        Jenis Tagihan
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label class="flex items-center gap-3 p-3.5 rounded-xl border cursor-pointer transition-all"
                               :class="invoiceType === 'project' ? 'border-blue-500 bg-blue-50/70 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 ring-1 ring-blue-500' : 'border-slate-200 dark:border-gray-700 hover:border-slate-300 text-slate-700 dark:text-slate-300'">
                            <input type="radio" name="invoice_type" value="project" x-model="invoiceType" class="text-blue-600 focus:ring-blue-500">
                            <div>
                                <p class="text-xs font-bold">Tagihan Proyek (Termin/Milestone)</p>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400">Terhubung ke deliverable dan klien proyek.</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 p-3.5 rounded-xl border cursor-pointer transition-all"
                               :class="invoiceType === 'internal' ? 'border-blue-500 bg-blue-50/70 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 ring-1 ring-blue-500' : 'border-slate-200 dark:border-gray-700 hover:border-slate-300 text-slate-700 dark:text-slate-300'">
                            <input type="radio" name="invoice_type" value="internal" x-model="invoiceType" class="text-blue-600 focus:ring-blue-500">
                            <div>
                                <p class="text-xs font-bold">Tagihan Internal / Non-Proyek</p>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400">Jasa umum, konsultasi, retainer mandiri.</p>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Proyek Select --}}
                <div x-show="invoiceType === 'project'">
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                        Pilih Proyek <span class="text-rose-500">*</span>
                    </label>
                    <select name="project_id" :required="invoiceType === 'project'"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-xl text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 cursor-pointer shadow-2xs">
                        <option value="">— Pilih Proyek Terkait —</option>
                        @foreach($projects as $p)
                            <option value="{{ $p->id }}" {{ old('project_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Client Select --}}
                <div :class="invoiceType === 'internal' ? 'md:col-span-2' : ''">
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                        Klien / Penerima Tagihan <span class="text-rose-500">*</span>
                    </label>
                    <select name="client_id" required
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-xl text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 cursor-pointer shadow-2xs">
                        <option value="">— Pilih Klien —</option>
                        @foreach($clients as $c)
                            <option value="{{ $c->id }}" {{ old('client_id') == $c->id ? 'selected' : '' }}>{{ $c->name }} ({{ $c->email }})</option>
                        @endforeach
                    </select>
                </div>

                {{-- Issue & Due Dates --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                        Tanggal Terbit <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" name="issue_date" value="{{ old('issue_date', date('Y-m-d')) }}" required
                           class="w-full px-3.5 py-2.5 text-xs sm:text-sm bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-xl text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-2xs">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                        Jatuh Tempo <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" name="due_date" value="{{ old('due_date') }}" required
                           class="w-full px-3.5 py-2.5 text-xs sm:text-sm bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-xl text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-2xs">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                        Pajak PPN / Tax (%)
                    </label>
                    <div class="relative">
                        <input type="number" name="tax" value="{{ old('tax', 0) }}" min="0" max="100" step="0.1" x-model="tax"
                               class="w-full pl-3.5 pr-8 py-2.5 text-xs sm:text-sm bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-xl text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-2xs">
                        <span class="absolute right-3.5 top-2.5 text-xs text-slate-400 font-bold">%</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 2: Line Items --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 p-6 shadow-xs space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-gray-700">
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">2. Rincian Item Tagihan</h2>
                    <p class="text-xs text-slate-400">Daftar layanan, pekerjaan, atau barang yang ditagihkan.</p>
                </div>
                <button type="button" @click="addItem()"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/40 hover:bg-blue-100 transition shadow-2xs">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Item
                </button>
            </div>

            <div class="border border-slate-200 dark:border-gray-700 rounded-xl overflow-hidden">
                <table class="w-full text-left border-collapse text-xs">
                    <thead class="bg-slate-50 dark:bg-gray-800 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase">
                        <tr>
                            <th class="px-4 py-3">Deskripsi Pekerjaan / Layanan</th>
                            <th class="px-3 py-3 text-center w-28">Qty</th>
                            <th class="px-3 py-3 text-right w-44">Harga Satuan (Rp)</th>
                            <th class="px-4 py-3 text-right w-44">Total (Rp)</th>
                            <th class="px-3 py-3 w-12 text-center"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-gray-800">
                        <template x-for="(item, index) in items" :key="index">
                            <tr class="align-top hover:bg-slate-50/50 dark:hover:bg-gray-800/40 transition">
                                <td class="p-3">
                                    <textarea :name="'items['+index+'][description]'" x-model="item.description" required
                                              placeholder="Contoh: Milestone 1 — Desain UI/UX & Prototipe Web..."
                                              rows="2"
                                              class="w-full px-3 py-2 text-xs bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-lg text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 resize-y"></textarea>
                                </td>
                                <td class="p-3">
                                    <input type="number" :name="'items['+index+'][quantity]'" x-model="item.quantity" @input="calcItem(item)" min="0" step="0.01"
                                           class="w-full px-2 py-2 text-xs text-center bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-lg text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                                </td>
                                <td class="p-3">
                                    <input type="number" :name="'items['+index+'][unit_price]'" x-model="item.unit_price" @input="calcItem(item)" min="0"
                                           class="w-full px-3 py-2 text-xs text-right bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-lg text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                                </td>
                                <td class="p-3 pt-4 text-right font-bold text-slate-900 dark:text-white whitespace-nowrap tabular-nums"
                                    x-text="'Rp ' + formatNumber(item.total)">
                                </td>
                                <td class="p-3 pt-3.5 text-center">
                                    <button type="button" @click="removeItem(index)" x-show="items.length > 1"
                                            class="p-1 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot class="bg-slate-50/80 dark:bg-gray-800/80 border-t border-slate-200 dark:border-gray-700 text-xs">
                        <tr>
                            <td colspan="3" class="px-4 py-2.5 text-right font-medium text-slate-500">Subtotal</td>
                            <td class="px-4 py-2.5 text-right font-bold text-slate-800 dark:text-slate-200 tabular-nums"
                                x-text="'Rp ' + formatNumber(subtotal)"></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="3" class="px-4 py-2.5 text-right font-medium text-slate-500">
                                Pajak PPN (<span x-text="tax"></span>%)
                            </td>
                            <td class="px-4 py-2.5 text-right font-semibold text-slate-700 dark:text-slate-300 tabular-nums"
                                x-text="'Rp ' + formatNumber(taxAmount)"></td>
                            <td></td>
                        </tr>
                        <tr class="border-t border-slate-200 dark:border-gray-700 bg-blue-50/30 dark:bg-blue-950/20">
                            <td colspan="3" class="px-4 py-3.5 text-right font-black text-slate-900 dark:text-white uppercase tracking-wider">
                                GRAND TOTAL
                            </td>
                            <td class="px-4 py-3.5 text-right font-black text-blue-600 dark:text-blue-400 text-base tabular-nums"
                                x-text="'Rp ' + formatNumber(total)"></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Section 3: Catatan & Lampiran --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 p-6 shadow-xs space-y-4">
            <div class="border-b border-slate-100 dark:border-gray-700 pb-3">
                <h2 class="text-sm font-bold text-slate-900 dark:text-white">3. Informasi Tambahan & Lampiran</h2>
                <p class="text-xs text-slate-400">Instruksi pembayaran rekening dan dokumen pendukung.</p>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                        Catatan Pembayaran / Rekening
                    </label>
                    <textarea name="notes" rows="3"
                              placeholder="Contoh: Mohon transfer ke rekening Bank BCA 1234567890 a/n Flovig Solusi Digital..."
                              class="w-full px-3.5 py-2.5 text-xs sm:text-sm bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-xl text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-2xs">{{ old('notes') }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                        Lampiran File <span class="text-slate-400 font-normal">(opsional)</span>
                    </label>
                    <input type="file" name="attachment"
                           class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900/40 dark:file:text-blue-300">
                    <p class="text-[11px] text-slate-400 mt-1">Format file: PDF, DOCX, ZIP, PNG, JPG (Maksimal 10MB).</p>
                    @error('attachment') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('invoices.index') }}"
               class="px-5 py-2.5 text-xs sm:text-sm font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-300 transition">
                Batal
            </a>
            <button type="submit" :disabled="submitting"
                    class="inline-flex items-center gap-2 px-6 py-2.5 text-xs sm:text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 rounded-xl transition-all shadow-sm shadow-blue-600/20 disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!submitting">Terbitkan Invoice</span>
                <span x-show="submitting" x-cloak>Menyimpan...</span>
            </button>
        </div>
    </form>

</div>

@push('scripts')
<script>
function invoiceForm() {
    return {
        invoiceType: '{{ old('invoice_type', 'project') }}',
        tax: {{ old('tax', 0) }},
        submitting: false,
        items: [{ description: '', quantity: 1, unit_price: 0, total: 0 }],
        get subtotal() { return this.items.reduce((s, i) => s + (i.total || 0), 0); },
        get taxAmount() { return this.subtotal * this.tax / 100; },
        get total() { return this.subtotal + this.taxAmount; },
        addItem() { this.items.push({ description: '', quantity: 1, unit_price: 0, total: 0 }); },
        removeItem(i) { this.items.splice(i, 1); },
        calcItem(item) { item.total = (parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0); },
        formatNumber(n) { return Math.round(n).toLocaleString('id-ID'); }
    }
}
</script>
@endpush
@endsection
