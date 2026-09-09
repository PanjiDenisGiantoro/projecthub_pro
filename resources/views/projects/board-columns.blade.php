@extends('layouts.app')
@section('title', 'Board Columns: ' . $project->name)
@section('page-title', 'Board Columns')

@section('content')
<div class="py-4 max-w-2xl">
    <nav class="text-sm text-gray-500 mb-4">
        <a href="{{ route('projects.show', $project) }}" class="hover:text-blue-600">{{ $project->name }}</a>
        <span class="mx-2">/</span><span class="text-gray-700">Board Columns</span>
    </nav>

    <div class="flex items-center justify-between mb-4">
        <p class="text-sm text-gray-500">Columns displayed on the Kanban board in the Tasks tab for this project.</p>
        <a href="{{ route('board-column-templates.index') }}" class="text-sm text-blue-600 hover:text-blue-800">Use template &rarr;</a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-5 mb-5">
        @if($columns->count() > 1)
        <p class="text-xs text-gray-400 mb-2">Drag <svg class="w-3 h-3 inline -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/></svg> to reorder columns on the Kanban board.</p>
        @endif
        <div class="space-y-2 mb-5" id="column-list">
            @forelse($columns as $column)
            <div class="column-row" draggable="true" data-column-id="{{ $column->id }}">
                <form method="POST" action="{{ route('board-columns.update', [$project, $column]) }}"
                      class="flex items-center gap-2 px-3 py-2 rounded-lg border {{ \App\Support\BoardColumnPalette::header($column->color) }}">
                    @csrf @method('PUT')
                    <span class="text-gray-400 cursor-grab active:cursor-grabbing shrink-0" title="Drag to reorder">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/></svg>
                    </span>
                    <span class="w-2.5 h-2.5 rounded-full shrink-0 {{ \App\Support\BoardColumnPalette::dot($column->color) }}"></span>
                    <input type="text" name="name" value="{{ $column->name }}" class="flex-1 bg-transparent border-0 text-sm font-medium text-gray-700 focus:outline-none focus:ring-1 focus:ring-blue-400 rounded px-1">
                    <select name="color" class="text-xs border border-gray-300 rounded-lg px-2 py-1">
                        @foreach($colors as $color)
                        <option value="{{ $color }}" {{ $column->color === $color ? 'selected' : '' }}>{{ ucfirst($color) }}</option>
                        @endforeach
                    </select>
                    <label class="flex items-center gap-1 text-xs text-gray-500 whitespace-nowrap">
                        <input type="checkbox" name="is_done" value="1" {{ $column->is_done ? 'checked' : '' }}>
                        Done
                    </label>
                    <span class="text-xs text-gray-400 whitespace-nowrap">{{ $column->tasks_count }} task</span>
                    <button type="submit" class="text-xs text-blue-600 hover:text-blue-800 px-2">Save</button>
                </form>
                @if($column->tasks_count === 0)
                <form method="POST" action="{{ route('board-columns.destroy', [$project, $column]) }}" class="flex justify-end -mt-1 mb-1"
                      data-confirm-delete="column {{ $column->name }}" data-confirm-label="Delete Column">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-xs text-red-400 hover:text-red-600 px-2">Delete this column</button>
                </form>
                @endif
            </div>
            @empty
            <p class="text-sm text-gray-400 text-center py-6">No columns yet. Add one below or use a template.</p>
            @endforelse
        </div>

        <form method="POST" action="{{ route('board-columns.store', $project) }}" class="flex items-end gap-2 pt-4 border-t border-gray-100">
            @csrf
            <div class="flex-1">
                <label class="block text-xs font-medium text-gray-600 mb-1">New Column Name</label>
                <input type="text" name="name" required placeholder="e.g. Testing" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Color</label>
                <select name="color" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    @foreach($colors as $color)
                    <option value="{{ $color }}">{{ ucfirst($color) }}</option>
                    @endforeach
                </select>
            </div>
            <label class="flex items-center gap-1 text-xs text-gray-500 pb-2.5 whitespace-nowrap">
                <input type="checkbox" name="is_done" value="1">
                Done
            </label>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">+ Column</button>
        </form>
    </div>

    <a href="{{ route('projects.edit', $project) }}" class="text-sm text-gray-600 hover:text-gray-800">&larr; Back to project settings</a>
</div>

@push('scripts')
<script>
(function () {
    var list = document.getElementById('column-list');
    if (!list) return;

    var reorderUrl = @json(route('board-columns.reorder', $project));
    var csrf       = document.querySelector('meta[name="csrf-token"]').content;
    var dragging   = null;

    list.addEventListener('dragstart', function (e) {
        var row = e.target.closest('.column-row');
        if (!row) return;
        dragging = row;
        e.dataTransfer.effectAllowed = 'move';
        setTimeout(function () { row.classList.add('opacity-40'); }, 0);
    });

    list.addEventListener('dragend', function () {
        if (dragging) dragging.classList.remove('opacity-40');
        dragging = null;
    });

    list.addEventListener('dragover', function (e) {
        e.preventDefault();
        var row = e.target.closest('.column-row');
        if (!row || row === dragging || !dragging) return;
        var rect   = row.getBoundingClientRect();
        var before = (e.clientY - rect.top) < rect.height / 2;
        list.insertBefore(dragging, before ? row : row.nextSibling);
    });

    list.addEventListener('drop', function (e) {
        e.preventDefault();
        var order = Array.prototype.map.call(
            list.querySelectorAll('.column-row'),
            function (row) { return row.dataset.columnId; }
        );

        fetch(reorderUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ order: order }),
        }).catch(function () { /* order remains saved locally */ });
    });
})();
</script>
@endpush
@endsection
