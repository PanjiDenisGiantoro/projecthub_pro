@extends('layouts.app')
@section('title', $template->name)
@section('page-title', 'Template Detail')

@section('content')
<div class="py-4 max-w-3xl">
    <nav class="text-sm text-gray-500 mb-4">
        <a href="{{ route('templates.index') }}" class="hover:text-blue-600">Templates</a>
        <span class="mx-2">/</span><span class="text-gray-700">{{ $template->name }}</span>
    </nav>

    <div class="bg-white rounded-xl border border-gray-200 p-5 mb-5">
        <div class="flex items-start justify-between gap-4">
            <div>
                @if($template->category)
                <span class="text-xs bg-blue-50 text-blue-600 rounded-full px-2 py-0.5 mb-2 inline-block">{{ $template->category }}</span>
                @endif
                <h2 class="text-xl font-semibold text-gray-800">{{ $template->name }}</h2>
                @if($template->description)
                <p class="text-sm text-gray-500 mt-1">{{ $template->description }}</p>
                @endif
                <p class="text-xs text-gray-400 mt-2">Dibuat oleh {{ $template->creator?->name }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('templates.apply', $template) }}"
                   class="bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium px-4 py-2 rounded-lg">Terapkan ke Proyek</a>
                @if(!auth()->user()->hasRole('customer'))
                <form method="POST" action="{{ route('templates.destroy', $template) }}"
                      data-confirm-delete="{{ $template->name }}">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-sm text-red-500 hover:text-red-700 border border-red-300 px-4 py-2 rounded-lg">Hapus</button>
                </form>
                @endif
            </div>
        </div>
    </div>

    {{-- Milestones --}}
    <div class="space-y-4">
        @foreach($template->milestones as $ms)
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-8 h-8 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center text-sm font-bold">{{ $loop->iteration }}</div>
                <div>
                    <p class="font-semibold text-gray-800">{{ $ms->title }}</p>
                    <p class="text-xs text-gray-400">Mulai hari ke-{{ $ms->offset_days }} · Durasi {{ $ms->duration_days }} hari</p>
                </div>
            </div>
            @if($ms->tasks->count())
            <div class="pl-4 border-l-2 border-gray-100 space-y-1">
                @foreach($ms->tasks as $task)
                <div class="flex items-center gap-2 text-sm">
                    <span class="text-gray-400">▸</span>
                    <span class="text-gray-700">{{ $task->title }}</span>
                    <span class="text-xs text-gray-400 ml-auto">{{ $task->priority }}</span>
                    @if($task->estimated_hours)<span class="text-xs text-gray-400">{{ $task->estimated_hours }}j</span>@endif
                    @if($task->story_points)<span class="text-xs bg-blue-50 text-blue-600 rounded-full px-1.5">{{ $task->story_points }}pt</span>@endif
                </div>
                @endforeach
            </div>
            @else
            <p class="text-xs text-gray-400 pl-4">Tidak ada task.</p>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endsection
