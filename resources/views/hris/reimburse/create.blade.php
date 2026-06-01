@extends('layouts.app')
@section('title', 'Ajukan Reimburse')
@section('page-title', 'Ajukan Reimburse')

@section('content')
<div class="max-w-xl mx-auto pt-5 space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('hris.reimburse.index') }}" class="text-gray-400 hover:text-gray-700">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <h1 class="text-2xl font-bold text-gray-900">Ajukan Reimburse</h1>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <form action="{{ route('hris.reimburse.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Kategori *</label>
                <select name="category" required class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-violet-500 focus:outline-none">
                    <option value="">Pilih kategori...</option>
                    @foreach(['transport' => 'Transport', 'makan' => 'Makan', 'akomodasi' => 'Akomodasi', 'medis' => 'Medis', 'pulsa' => 'Pulsa', 'lainnya' => 'Lainnya'] as $val => $label)
                    <option value="{{ $val }}" @selected(old('category') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Judul *</label>
                <input type="text" name="title" value="{{ old('title') }}" required placeholder="Contoh: Tiket KRL kantor - Klien"
                       class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-violet-500 focus:outline-none">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Pengeluaran *</label>
                    <input type="date" name="expense_date" value="{{ old('expense_date') }}" required
                           class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-violet-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah (Rp) *</label>
                    <input type="number" name="amount" value="{{ old('amount') }}" required min="1"
                           class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-violet-500 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan</label>
                <textarea name="description" rows="2"
                          class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-violet-500 focus:outline-none resize-none">{{ old('description') }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Bukti / Nota <span class="text-gray-400">(opsional)</span></label>
                <input type="file" name="receipt" accept=".pdf,.jpg,.jpeg,.png"
                       class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:bg-violet-50 file:text-violet-700 file:font-medium">
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="flex-1 py-2.5 rounded-xl font-semibold text-white text-sm"
                        style="background:linear-gradient(135deg,#7c3aed,#6d28d9)">
                    Kirim Pengajuan
                </button>
                <a href="{{ route('hris.reimburse.index') }}"
                   class="px-6 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
