@extends('layouts.app')
@section('title', 'Buat Invoice')
@section('page-title', 'Buat Invoice Baru')

@section('content')
<div class="py-4 max-w-3xl" x-data="invoiceForm()">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <form method="POST" action="{{ route('invoices.store') }}" class="space-y-6"
              @submit="if (submitting) { $event.preventDefault(); } else { submitting = true; }">
            @csrf

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Proyek <span class="text-red-500">*</span></label>
                    <select name="project_id" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                        <option value="">— Pilih Proyek —</option>
                        @foreach($projects as $p)
                            <option value="{{ $p->id }}" {{ old('project_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Client <span class="text-red-500">*</span></label>
                    <select name="client_id" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                        <option value="">— Pilih Client —</option>
                        @foreach($clients as $c)
                            <option value="{{ $c->id }}" {{ old('client_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Terbit <span class="text-red-500">*</span></label>
                    <input type="date" name="issue_date" value="{{ old('issue_date', date('Y-m-d')) }}" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jatuh Tempo <span class="text-red-500">*</span></label>
                    <input type="date" name="due_date" value="{{ old('due_date') }}" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pajak (%)</label>
                    <input type="number" name="tax" value="{{ old('tax', 0) }}" min="0" max="100" step="0.1" x-model="tax" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                </div>
            </div>

            {{-- Line Items --}}
            <div>
                <div class="flex items-center justify-between mb-3">
                    <label class="text-sm font-medium text-gray-700">Item Invoice</label>
                    <button type="button" @click="addItem()" class="text-sm text-violet-600 hover:text-violet-800 font-medium">+ Tambah Item</button>
                </div>

                <div class="border border-gray-200 rounded-lg overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-600">
                            <tr>
                                <th class="px-3 py-2 text-left w-1/2">Deskripsi</th>
                                <th class="px-3 py-2 text-center w-20">Qty</th>
                                <th class="px-3 py-2 text-right w-36">Harga Satuan</th>
                                <th class="px-3 py-2 text-right w-36">Total</th>
                                <th class="px-3 py-2 w-8"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(item, index) in items" :key="index">
                                <tr class="border-t border-gray-100">
                                    <td class="px-3 py-2">
                                        <textarea :name="'items['+index+'][description]'" x-model="item.description" required
                                               placeholder="Deskripsi item..." rows="3"
                                               class="w-full px-2 py-1.5 border border-gray-200 rounded text-sm resize-y focus:outline-none focus:ring-1 focus:ring-violet-500"></textarea>
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" :name="'items['+index+'][quantity]'" x-model="item.quantity" @input="calcItem(item)" min="0" step="0.01"
                                               class="w-full px-2 py-1.5 border border-gray-200 rounded text-sm text-center focus:outline-none focus:ring-1 focus:ring-violet-500">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" :name="'items['+index+'][unit_price]'" x-model="item.unit_price" @input="calcItem(item)" min="0"
                                               class="w-full px-2 py-1.5 border border-gray-200 rounded text-sm text-right focus:outline-none focus:ring-1 focus:ring-violet-500">
                                    </td>
                                    <td class="px-3 py-2 text-right font-medium text-gray-800" x-text="'Rp '+formatNumber(item.total)"></td>
                                    <td class="px-3 py-2 text-center">
                                        <button type="button" @click="removeItem(index)" x-show="items.length > 1" class="text-red-400 hover:text-red-600">✕</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                            <tr>
                                <td colspan="3" class="px-3 py-2 text-right text-sm text-gray-600 font-medium">Subtotal</td>
                                <td class="px-3 py-2 text-right font-semibold text-gray-800" x-text="'Rp '+formatNumber(subtotal)"></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="px-3 py-2 text-right text-sm text-gray-600">Pajak (<span x-text="tax"></span>%)</td>
                                <td class="px-3 py-2 text-right text-gray-700" x-text="'Rp '+formatNumber(taxAmount)"></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="px-3 py-3 text-right font-bold text-gray-800">TOTAL</td>
                                <td class="px-3 py-3 text-right font-bold text-blue-700 text-base" x-text="'Rp '+formatNumber(total)"></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                <textarea name="notes" rows="2" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">{{ old('notes') }}</textarea>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" :disabled="submitting" class="bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium px-6 py-2.5 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!submitting">Buat Invoice</span>
                    <span x-show="submitting" x-cloak>Menyimpan...</span>
                </button>
                <a href="{{ route('invoices.index') }}" class="text-gray-600 text-sm font-medium px-4 py-2.5 rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors">Batal</a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function invoiceForm() {
    return {
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
