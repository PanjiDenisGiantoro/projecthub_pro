@extends('layouts.app')
@section('title', 'GitHub Files — ' . $project->name)
@section('page-title', 'File Repository')

@section('content')
<div class="py-4">
    <nav class="text-sm text-gray-500 mb-4">
        <a href="{{ route('projects.show', $project) }}" class="hover:text-blue-600">{{ $project->name }}</a>
        <span class="mx-2">/</span>
        <a href="{{ route('github.index', $project) }}" class="hover:text-blue-600">GitHub</a>
        <span class="mx-2">/</span><span class="text-gray-700">File</span>
    </nav>

    @if(session('success'))
    <div class="mb-4 px-4 py-3 rounded-lg bg-emerald-50 text-emerald-700 text-sm font-medium">
        {{ session('success') }}
    </div>
    @endif

    <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('github.files', array_filter(['project' => $project->id, 'branch' => $activeBranch])) }}"
               class="font-mono text-gray-500 hover:text-blue-600">root</a>
            @php $crumbs = []; $acc = ''; @endphp
            @foreach(array_filter(explode('/', $path)) as $seg)
                @php $acc = trim($acc . '/' . $seg, '/'); @endphp
                <span class="text-gray-300">/</span>
                <a href="{{ route('github.files', array_filter(['project' => $project->id, 'branch' => $activeBranch, 'path' => $acc])) }}"
                   class="font-mono text-gray-500 hover:text-blue-600">{{ $seg }}</a>
            @endforeach
        </div>

        @if(!empty($branches))
        <form method="GET" action="{{ route('github.files', $project) }}" class="flex items-center gap-1.5">
            <input type="hidden" name="path" value="{{ $path }}">
            <label class="text-xs text-gray-500">Branch</label>
            <select name="branch" onchange="this.form.submit()"
                    class="rounded-lg border-gray-300 text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500">
                @foreach($branches as $b)
                <option value="{{ $b }}" @selected($activeBranch === $b)>{{ $b }}</option>
                @endforeach
            </select>
        </form>
        @endif
    </div>

    @if(!($result['ok'] ?? false))
        <div class="bg-amber-50 border border-amber-200 text-amber-700 text-sm rounded-xl p-4">
            {{ $result['message'] ?? 'Gagal memuat folder.' }}
        </div>
    @else
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="divide-y divide-gray-50">
                @forelse($result['entries'] as $e)
                    @if($e['type'] === 'dir')
                    <a href="{{ route('github.files', array_filter(['project' => $project->id, 'branch' => $activeBranch, 'path' => $e['path']])) }}"
                       class="flex items-center gap-2 px-5 py-3 hover:bg-gray-50 transition text-sm">
                        <span class="text-gray-400">📁</span>
                        <span class="text-gray-800 font-medium">{{ $e['name'] }}</span>
                    </a>
                    @else
                    <a href="{{ route('github.files.edit', array_filter(['project' => $project->id, 'branch' => $activeBranch, 'path' => $e['path']])) }}"
                       class="flex items-center gap-2 px-5 py-3 hover:bg-gray-50 transition text-sm">
                        <span class="text-gray-400">📄</span>
                        <span class="text-gray-700">{{ $e['name'] }}</span>
                    </a>
                    @endif
                @empty
                <p class="px-5 py-8 text-center text-sm text-gray-400">Folder ini kosong.</p>
                @endforelse
            </div>
        </div>
    @endif
</div>
@endsection