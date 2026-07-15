@extends('layouts.app')
@section('title', 'Edit Tarif PPh 21')
@section('page-title', 'Edit Tarif PPh 21')

@section('content')
<div class="max-w-lg mx-auto pt-5 space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('hris.master.index', ['tab' => 'tax-brackets']) }}" class="text-gray-400 hover:text-gray-700">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <h1 class="text-2xl font-bold text-gray-900">Edit Lapisan Tarif PPh 21</h1>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <form action="{{ route('hris.master.tax-brackets.update', $taxBracket) }}" method="POST" class="space-y-5">
            @csrf @method('PUT')

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">PKP Dari (Rp) *</label>
                    <input type="number" name="income_from" value="{{ old('income_from', $taxBracket->income_from) }}" required min="0"
                           class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-violet-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">PKP Sampai (Rp, 0 = tak terbatas)</label>
                    <input type="number" name="income_to" value="{{ old('income_to', $taxBracket->income_to) }}" min="0"
                           class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-violet-500 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tarif (desimal, contoh: 0.05 untuk 5%) *</label>
                <input type="number" name="rate" value="{{ old('rate', $taxBracket->rate) }}" required step="0.01" min="0" max="1"
                       class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-violet-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Label</label>
                <input type="text" name="label" value="{{ old('label', $taxBracket->label) }}"
                       class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-violet-500 focus:outline-none">
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="flex-1 py-2.5 rounded-xl font-semibold text-white text-sm"
                        style="background:linear-gradient(135deg,#7c3aed,#6d28d9)">
                    Simpan
                </button>
                <a href="{{ route('hris.master.index', ['tab' => 'tax-brackets']) }}"
                   class="px-6 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
