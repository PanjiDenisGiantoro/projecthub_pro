@extends('layouts.app')
@section('title', 'Buat Template Kolom')
@section('page-title', 'Template Kolom Baru')

@section('content')
<div class="py-4 max-w-2xl" x-data="boardColumnTemplateBuilder()">
    <nav class="text-sm text-gray-500 mb-4">
        <a href="{{ route('board-column-templates.index') }}" class="hover:text-blue-600">Template Kolom</a>
        <span class="mx-2">/</span><span class="text-gray-700">Baru</span>
    </nav>

    <form method="POST" action="{{ route('board-column-templates.store') }}" class="space-y-5">
        @csrf
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Info Template</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Nama Template *</label>
                    <input type="text" name="name" required placeholder="e.g. Scrum, Bug Tracking..." class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Deskripsi</label>
                    <textarea name="description" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Kategori</label>
                    <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">- Tanpa kategori -</option>
                        @foreach($categories as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Columns --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-sm font-semibold text-gray-700">Kolom</h3>
                <button type="button" @click="addItem()" class="text-sm text-blue-600 hover:text-blue-800 font-medium">+ Kolom</button>
            </div>

            <template x-for="(item, i) in items" :key="i">
                <div class="flex gap-2 mb-2 items-center">
                    <input type="text" :name="'items['+i+'][name]'" x-model="item.name" placeholder="Nama kolom" required class="flex-1 px-2 py-1.5 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <select :name="'items['+i+'][color]'" x-model="item.color" class="px-2 py-1.5 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-blue-500">
                        @foreach($colors as $color)
                        <option value="{{ $color }}">{{ ucfirst($color) }}</option>
                        @endforeach
                    </select>
                    <label class="flex items-center gap-1 text-xs text-gray-500 whitespace-nowrap">
                        <input type="checkbox" :name="'items['+i+'][is_done]'" value="1" x-model="item.is_done">
                        Selesai
                    </label>
                    <button type="button" @click="items.splice(i,1)" class="text-red-400 hover:text-red-600 text-xs">✕</button>
                </div>
            </template>

            <template x-if="items.length === 0">
                <p class="text-sm text-gray-400 text-center py-4">Klik "+ Kolom" untuk menambahkan.</p>
            </template>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-6 py-2.5 rounded-lg transition-colors">Simpan Template</button>
            <a href="{{ route('board-column-templates.index') }}" class="text-sm text-gray-600 hover:text-gray-800 px-6 py-2.5 rounded-lg border border-gray-300">Batal</a>
        </div>
    </form>
</div>

@push('scripts')
<script>
function boardColumnTemplateBuilder() {
    return {
        items: [
            { name: 'To Do', color: 'gray', is_done: false },
            { name: 'In Progress', color: 'blue', is_done: false },
            { name: 'Done', color: 'green', is_done: true },
        ],
        addItem() {
            this.items.push({ name: '', color: 'gray', is_done: false });
        }
    }
}
</script>
@endpush
@endsection
