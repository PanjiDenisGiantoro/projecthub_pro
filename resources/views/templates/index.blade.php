@extends('layouts.app')
@section('title', 'Project Templates')
@section('page-title', 'Project Templates')

@section('content')
<div class="py-4">
    <div class="flex justify-between items-center mb-5">
        <p class="text-sm text-gray-500">{{ $templates->count() }} template tersedia</p>
        @if(!auth()->user()->hasRole('customer'))
        <a href="{{ route('templates.create') }}"
           class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Template Baru
        </a>
        @endif
    </div>

    @if($templates->isEmpty())
    <div class="text-center py-16 text-gray-400">
        <p class="text-4xl mb-3">📋</p>
        <p class="font-medium text-gray-500">Belum ada template.</p>
        <p class="text-sm mt-1">Buat template untuk mempercepat pembuatan proyek baru.</p>
    </div>
    @else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($templates as $template)
        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-sm transition-shadow">
            @if($template->category)
            <span class="text-xs bg-blue-50 text-blue-600 rounded-full px-2 py-0.5 mb-2 inline-block">{{ $template->category }}</span>
            @endif
            <h3 class="text-base font-semibold text-gray-800 mb-1">{{ $template->name }}</h3>
            @if($template->description)
            <p class="text-sm text-gray-500 mb-3">{{ Str::limit($template->description, 80) }}</p>
            @endif
            <div class="text-xs text-gray-400 mb-3">
                {{ $template->milestones->count() }} milestone ·
                {{ $template->milestones->sum(fn($m) => $m->tasks->count()) }} task ·
                Oleh {{ $template->creator?->name }}
            </div>
            <div class="flex gap-2">
                <a href="{{ route('templates.show', $template) }}"
                   class="flex-1 text-center text-sm text-blue-600 hover:text-blue-800 border border-blue-300 px-3 py-1.5 rounded-lg transition-colors">Detail</a>
                <a href="{{ route('templates.apply', $template) }}"
                   class="flex-1 text-center text-sm bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg transition-colors">Terapkan</a>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection
