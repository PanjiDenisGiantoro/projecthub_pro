@extends('layouts.app')
@section('title', 'Timesheet: ' . $project->name)

@section('content')
<div class="py-4">

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('projects.show', $project) }}"
           class="p-2 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-gray-900">Timesheet</h1>
            <p class="text-sm text-gray-500">{{ $project->name }}</p>
        </div>
    </div>

    @include('projects.partials.timesheet-content')

</div>
@endsection
