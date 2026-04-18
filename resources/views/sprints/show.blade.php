@extends('layouts.app')
@section('title', $sprint->name)
@section('page-title', 'Sprint Board')

@section('content')
<div class="py-4">
    <nav class="text-sm text-gray-500 mb-4 flex items-center gap-1.5 flex-wrap">
        <a href="{{ route('projects.show', $project) }}" class="hover:text-blue-600">{{ $project->name }}</a>
        <span>/</span>
        <a href="{{ route('sprints.index', $project) }}" class="hover:text-blue-600">Sprints</a>
        <span>/</span>
        <span class="text-gray-700">{{ $sprint->name }}</span>
    </nav>

    {{-- Sprint header --}}
    @php $pct = $sprint->totalPoints() > 0 ? round($sprint->completedPoints() / $sprint->totalPoints() * 100) : 0; @endphp
    <div class="bg-white rounded-xl border border-gray-200 p-5 mb-5">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-lg font-semibold text-gray-800">{{ $sprint->name }}</h2>
                    @php $sc = match($sprint->status){'active'=>'bg-green-100 text-green-700','completed'=>'bg-gray-100 text-gray-600',default=>'bg-blue-100 text-blue-700'}; @endphp
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $sc }}">{{ ucfirst($sprint->status) }}</span>
                </div>
                @if($sprint->goal)<p class="text-sm text-gray-500 mt-1">{{ $sprint->goal }}</p>@endif
                <p class="text-xs text-gray-400 mt-1">{{ $sprint->start_date?->format('d M Y') }} → {{ $sprint->end_date?->format('d M Y') }}</p>
            </div>
            <div class="text-right">
                <p class="text-2xl font-bold text-blue-600">{{ $pct }}%</p>
                <p class="text-xs text-gray-400">{{ $sprint->completedPoints() }}/{{ $sprint->totalPoints() }} pts</p>
            </div>
        </div>
        <div class="mt-3 bg-gray-100 rounded-full h-2">
            <div class="h-2 bg-blue-500 rounded-full" style="width:{{ $pct }}%"></div>
        </div>
    </div>

    {{-- Kanban board --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($statuses as $key => $label)
        @php $colTasks = $sprint->tasks->where('status', $key); @endphp
        <div class="bg-gray-50 rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                <span class="text-sm font-semibold text-gray-700">{{ $label }}</span>
                <span class="text-xs bg-white border border-gray-200 text-gray-600 rounded-full px-2 py-0.5">{{ $colTasks->count() }}</span>
            </div>
            <div class="p-3 space-y-2 min-h-[200px]"
                 data-status="{{ $key }}"
                 ondragover="event.preventDefault(); this.classList.add('bg-blue-50')"
                 ondragleave="this.classList.remove('bg-blue-50')"
                 ondrop="handleSprintDrop(event,'{{ $key }}','{{ $project->id }}')">
                @foreach($colTasks as $task)
                <div draggable="true"
                     data-task-id="{{ $task->id }}"
                     ondragstart="event.dataTransfer.setData('taskId',this.dataset.taskId); this.classList.add('opacity-50')"
                     ondragend="this.classList.remove('opacity-50')"
                     class="bg-white rounded-lg border border-gray-200 p-3 cursor-grab active:cursor-grabbing hover:shadow-sm">
                    <p class="text-sm font-medium text-gray-800 leading-snug">{{ $task->title }}</p>
                    <div class="flex items-center gap-2 mt-2">
                        @if($task->story_points)
                        <span class="text-xs bg-blue-50 text-blue-600 rounded-full px-2 py-0.5">{{ $task->story_points }} pts</span>
                        @endif
                        @if($task->assignee)
                        <span class="text-xs text-gray-400 truncate">{{ $task->assignee->name }}</span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</div>

@push('scripts')
<script>
async function handleSprintDrop(e, status, projectId) {
    e.currentTarget.classList.remove('bg-blue-50');
    const taskId = e.dataTransfer.getData('taskId');
    if (!taskId) return;

    const card = document.querySelector(`[data-task-id="${taskId}"]`);
    const target = e.currentTarget;

    try {
        const res = await fetch(`/projects/${projectId}/tasks/${taskId}/move`, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ status })
        });
        if (res.ok) {
            target.appendChild(card);
            // Update counts
            document.querySelectorAll('[data-status]').forEach(col => {
                const cnt = col.querySelectorAll('[data-task-id]').length;
                col.previousElementSibling.querySelector('span:last-child').textContent = cnt;
            });
        }
    } catch(err) {
        console.error(err);
    }
}
</script>
@endpush
@endsection
