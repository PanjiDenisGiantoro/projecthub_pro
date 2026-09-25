@extends('layouts.app')
@section('title', 'Buat Invoice')
@section('page-title', 'Buat Invoice Baru')

@section('content')
<div class="py-4 w-full" x-data="invoiceForm()">
    <form method="POST" action="{{ route('invoices.store') }}" enctype="multipart/form-data" class="fl-form"
          @submit="if (submitting) { $event.preventDefault(); } else { submitting = true; }">
        @csrf

        <section class="fl-section">
            <div>
                <h3 class="fl-section-title">Detail Invoice</h3>
                <p class="fl-section-desc">Jenis invoice, penerima tagihan, dan periode pembayaran.</p>
            </div>
            <div class="fl-fields">
                @if($companies->count() > 1)
                <div class="fl-span-2">
                    <label class="fl-label">Perusahaan <span class="fl-req">*</span></label>
                    <select name="company_id" required class="fl-input">
                        <option value="">— Pilih Perusahaan —</option>
                        @foreach($companies as $co)
                            <option value="{{ $co->id }}" {{ old('company_id') == $co->id ? 'selected' : '' }}>{{ $co->name }}</option>
                        @endforeach
                    </select>
                </div>
                @elseif($companies->isNotEmpty())
                    <input type="hidden" name="company_id" value="{{ $companies->first()->id }}">
                @endif

                {{-- Jenis Invoice --}}
                <div class="fl-span-2">
                    <label class="fl-label">Jenis Invoice</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex items-center gap-2.5 border rounded-lg px-3.5 h-[2.375rem] cursor-pointer transition-colors"
                               :class="invoiceType==='project' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-gray-200 text-gray-600 hover:border-gray-300'">
                            <input type="radio" name="invoice_type" value="project" x-model="invoiceType" class="text-blue-600">
                            <span class="text-sm font-medium">Untuk Proyek</span>
                        </label>
                        <label class="flex items-center gap-2.5 border rounded-lg px-3.5 h-[2.375rem] cursor-pointer transition-colors"
                               :class="invoiceType==='internal' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-gray-200 text-gray-600 hover:border-gray-300'">
                            <input type="radio" name="invoice_type" value="internal" x-model="invoiceType" class="text-blue-600">
                            <span class="text-sm font-medium">Internal (Non-Proyek)</span>
                        </label>
                    </div>
                </div>

                <div x-show="invoiceType==='project'">
                    <label class="fl-label">Proyek <span class="fl-req">*</span></label>
                    <select name="project_id" :required="invoiceType==='project'" class="fl-input">
                        <option value="">— Pilih Proyek —</option>
                        @foreach($projects as $p)
                            <option value="{{ $p->id }}" {{ old('project_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="fl-label">Client <span class="fl-req">*</span></label>
                    <select name="client_id" required class="fl-input">
                        <option value="">— Pilih Client —</option>
                        @foreach($clients as $c)
                            <option value="{{ $c->id }}" {{ old('client_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="fl-label">Tanggal Terbit <span class="fl-req">*</span></label>
                    <input type="date" name="issue_date" value="{{ old('issue_date', date('Y-m-d')) }}" required class="fl-input">
                </div>
                <div>
                    <label class="fl-label">Jatuh Tempo <span class="fl-req">*</span></label>
                    <input type="date" name="due_date" value="{{ old('due_date') }}" required class="fl-input">
                </div>
                <div>
                    <label class="fl-label">Pajak (%)</label>
                    <input type="number" name="tax" value="{{ old('tax', 0) }}" min="0" max="100" step="0.1" x-model="tax" class="fl-input">
                </div>
            </div>
        </section>

        {{-- Line Items --}}
        <section class="fl-section fl-section-stack">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <h3 class="fl-section-title">Item Invoice</h3>
                    <p class="fl-section-desc">Rincian barang/jasa yang ditagihkan.</p>
                </div>
                <button type="button" @click="addItem()" class="fl-btn fl-btn-secondary">+ Tambah Item</button>
            </div>

            <div class="border border-gray-200 rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-3 py-2.5 text-left font-medium">Deskripsi</th>
                            <th class="px-3 py-2.5 text-center font-medium w-24">Qty</th>
                            <th class="px-3 py-2.5 text-right font-medium w-40">Harga Satuan</th>
                            <th class="px-3 py-2.5 text-right font-medium w-40">Total</th>
                            <th class="px-3 py-2.5 w-10"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, index) in items" :key="index">
                            <tr class="border-t border-gray-100 align-top">
                                <td class="px-3 py-2">
                                    <textarea :name="'items['+index+'][description]'" x-model="item.description" required
                                           placeholder="Deskripsi item..." rows="2" class="fl-input fl-input-sm resize-y"></textarea>
                                </td>
                                <td class="px-3 py-2">
                                    <input type="number" :name="'items['+index+'][quantity]'" x-model="item.quantity" @input="calcItem(item)" min="0" step="0.01"
                                           class="fl-input fl-input-sm text-center">
                                </td>
                                <td class="px-3 py-2">
                                    <input type="number" :name="'items['+index+'][unit_price]'" x-model="item.unit_price" @input="calcItem(item)" min="0"
                                           class="fl-input fl-input-sm text-right">
                                </td>
                                <td class="px-3 py-2 pt-3 text-right font-medium text-gray-800 whitespace-nowrap" x-text="'Rp '+formatNumber(item.total)"></td>
                                <td class="px-3 py-2 pt-2.5 text-center">
                                    <button type="button" @click="removeItem(index)" x-show="items.length > 1" class="text-gray-400 hover:text-red-600" title="Hapus item">✕</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot class="bg-gray-50 border-t border-gray-200">
                        <tr>
                            <td colspan="3" class="px-3 py-2 text-right text-sm text-gray-500">Subtotal</td>
                            <td class="px-3 py-2 text-right font-semibold text-gray-800" x-text="'Rp '+formatNumber(subtotal)"></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="3" class="px-3 py-2 text-right text-sm text-gray-500">Pajak (<span x-text="tax"></span>%)</td>
                            <td class="px-3 py-2 text-right text-gray-700" x-text="'Rp '+formatNumber(taxAmount)"></td>
                            <td></td>
                        </tr>
                        <tr class="border-t border-gray-200">
                            <td colspan="3" class="px-3 py-3 text-right font-semibold text-gray-800">TOTAL</td>
                            <td class="px-3 py-3 text-right font-bold text-blue-700 text-base" x-text="'Rp '+formatNumber(total)"></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>

        <section class="fl-section">
            <div>
                <h3 class="fl-section-title">Catatan &amp; Lampiran</h3>
                <p class="fl-section-desc">Informasi tambahan untuk client dan dokumen pendukung.</p>
            </div>
            <div class="fl-fields">
                <div class="fl-span-2">
                    <label class="fl-label">Catatan</label>
                    <textarea name="notes" rows="2" placeholder="cth. Pembayaran via transfer ke rekening BCA ..." class="fl-input">{{ old('notes') }}</textarea>
                </div>
                <div class="fl-span-2">
                    <label class="fl-label">Lampiran <span class="text-gray-400 font-normal">(opsional)</span></label>
                    <input type="file" name="attachment" class="fl-file">
                    <p class="fl-help">Mis. kontrak, bukti pendukung, dsb. Maks 10MB.</p>
                    @error('attachment') <p class="fl-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <div class="fl-actions">
            <a href="{{ route('invoices.index') }}" class="fl-btn fl-btn-secondary">Batal</a>
            <button type="submit" :disabled="submitting" class="fl-btn fl-btn-primary disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!submitting">Buat Invoice</span>
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
