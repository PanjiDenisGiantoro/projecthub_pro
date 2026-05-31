@extends('layouts.app')
@section('title', 'Edit Level Struktural')
@section('page-title', 'Edit Level Struktural')

@section('content')
<div class="py-4 max-w-md">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-xs text-gray-400 mb-5">
        <a href="{{ route('master.index') }}" class="hover:text-blue-600 transition-colors">Master Data</a>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('structural-levels.index') }}" class="hover:text-blue-600 transition-colors">Level Struktural</a>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-600 font-medium">{{ $structuralLevel->name }}</span>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <form method="POST" action="{{ route('structural-levels.update', $structuralLevel) }}" class="space-y-5"
              data-confirm-submit="Perbarui level struktural?" data-confirm-btn="Ya, Perbarui">
            @csrf @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Level <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $structuralLevel->name) }}" required
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 @error('name') border-red-400 @enderror">
                @error('name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Urutan <span class="text-red-500">*</span></label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $structuralLevel->sort_order) }}" required min="0"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 @error('sort_order') border-red-400 @enderror">
                <p class="text-xs text-gray-400 mt-1">Angka lebih kecil = level lebih rendah (Staff = 1, BOD = 8)</p>
                @error('sort_order')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_active" value="1" id="is_active"
                       {{ old('is_active', $structuralLevel->is_active) ? 'checked' : '' }} class="w-4 h-4 text-violet-600 rounded">
                <label for="is_active" class="text-sm text-gray-700">Level Aktif</label>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium px-6 py-2.5 rounded-lg transition-colors">
                    Perbarui
                </button>
                <a href="{{ route('structural-levels.index') }}" class="text-gray-600 text-sm font-medium px-4 py-2.5 rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection