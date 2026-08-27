@extends('layouts.app')
@section('title', $template->name)
@section('page-title', 'Detail Template Kolom')

@section('content')
<div class="py-4 max-w-2xl">
    <nav class="text-sm text-gray-500 mb-4">
        <a href="{{ route('board-column-templates.index') }}" class="hover:text-blue-600">Template Kolom</a>
        <span class="mx-2">/</span><span class="text-gray-700">{{ $template->name }}</span>
    </nav>

    <div class="bg-white rounded-xl border border-gray-200 p-5 mb-5">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-xl font-semibold text-gray-800">{{ $template->name }}</h2>
                    @if($template->category)
                    <span class="text-[11px] font-medium px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700">{{ \App\Models\BoardColumnTemplate::CATEGORIES[$template->category] ?? $template->category }}</span>
                    @endif
                </div>
                @if($template->description)
                <p class="text-sm text-gray-500 mt-1">{{ $template->description }}</p>
                @endif
                <p class="text-xs text-gray-400 mt-2">Dibuat oleh {{ $template->creator?->name ?? 'Sistem' }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('board-column-templates.apply', $template) }}"
                   class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg">Terapkan ke Proyek</a>
                @if(!auth()->user()->hasRole('client'))
                <form method="POST" action="{{ route('board-column-templates.destroy', $template) }}"
                      data-confirm-delete="{{ $template->name }}">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-sm text-red-500 hover:text-red-700 border border-red-300 px-4 py-2 rounded-lg">Hapus</button>
                </form>
                @endif
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-sm font-semibold text-gray-700 mb-3">Kolom ({{ $template->items->count() }})</h3>
        <div class="space-y-2">
            @foreach($template->items as $item)
            <div class="flex items-center gap-2 px-3 py-2 rounded-lg {{ \App\Support\BoardColumnPalette::header($item->color) }} border">
                <span class="w-2.5 h-2.5 rounded-full {{ \App\Support\BoardColumnPalette::dot($item->color) }}"></span>
                <span class="text-sm font-medium text-gray-700">{{ $item->name }}</span>
                @if($item->is_done)
                <span class="text-xs text-gray-400 ml-auto">Menandakan selesai</span>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
