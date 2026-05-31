@extends('layouts.app')
@section('title', 'Global Search')
@section('page-title', 'Global Search')

@section('content')
<div class="py-4 max-w-4xl">
    <form method="GET" action="{{ route('search.index') }}" class="mb-6">
        <div class="flex gap-3">
            <input type="text" name="q" value="{{ $query }}" autofocus placeholder="Cari task, proyek, ticket, artikel..."
                   class="flex-1 px-4 py-3 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
            <button type="submit"
                    class="bg-violet-600 hover:bg-violet-700 text-white px-6 py-3 rounded-xl text-sm font-medium transition-colors">
                Cari
            </button>
        </div>
    </form>

    @if($results === null)
    <div class="text-center py-12 text-gray-400">
        <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <p class="text-gray-500 font-medium">Ketikkan kata kunci untuk mencari.</p>
    </div>
    @elseif($totalCount === 0)
    <div class="text-center py-12 text-gray-400">
        <p class="font-medium text-gray-500">Tidak ada hasil untuk "<strong>{{ $query }}</strong>"</p>
    </div>
    @else
    <p class="text-sm text-gray-500 mb-4">Ditemukan <strong>{{ $totalCount }}</strong> hasil untuk "<strong>{{ $query }}</strong>"</p>

    {{-- Tasks --}}
    @if($results['tasks']->count())
    <div class="mb-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
            <span class="w-5 h-5 bg-blue-100 text-blue-600 rounded text-xs flex items-center justify-center font-bold">T</span>
            Tasks ({{ $results['tasks']->count() }})
        </h3>
        <div class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-100">
            @foreach($results['tasks'] as $task)
            <a href="{{ route('tasks.index', $task->project_id) }}"
               class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-colors">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800">{{ $task->title }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ $task->project?->name }} · {{ $task->assignee?->name ?? 'Unassigned' }} ·
                        <span class="capitalize">{{ $task->status }}</span>
                    </p>
                </div>
                <span class="text-xs px-2 py-0.5 rounded-full {{ match($task->priority) {
                    'critical' => 'bg-red-100 text-red-700',
                    'high' => 'bg-orange-100 text-orange-700',
                    'medium' => 'bg-yellow-100 text-yellow-700',
                    default => 'bg-gray-100 text-gray-600'
                } }}">{{ $task->priority }}</span>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Projects --}}
    @if($results['projects']->count())
    <div class="mb-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
            <span class="w-5 h-5 bg-green-100 text-green-600 rounded text-xs flex items-center justify-center font-bold">P</span>
            Proyek ({{ $results['projects']->count() }})
        </h3>
        <div class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-100">
            @foreach($results['projects'] as $proj)
            <a href="{{ route('projects.show', $proj) }}"
               class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-colors">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800">{{ $proj->name }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">Manager: {{ $proj->manager?->name }} · {{ $proj->status }}</p>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Tickets --}}
    @if($results['tickets']->count())
    <div class="mb-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
            <span class="w-5 h-5 bg-red-100 text-red-600 rounded text-xs flex items-center justify-center font-bold">B</span>
            Bug Tickets ({{ $results['tickets']->count() }})
        </h3>
        <div class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-100">
            @foreach($results['tickets'] as $ticket)
            <a href="{{ route('tickets.show', $ticket) }}"
               class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-colors">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800">{{ $ticket->title }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $ticket->project?->name }} · {{ $ticket->status }}</p>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Customer Requests --}}
    @if($results['requests']->count())
    <div class="mb-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
            <span class="w-5 h-5 bg-yellow-100 text-yellow-600 rounded text-xs flex items-center justify-center font-bold">R</span>
            Requests ({{ $results['requests']->count() }})
        </h3>
        <div class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-100">
            @foreach($results['requests'] as $req)
            <a href="{{ route('requests.show', $req) }}"
               class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-colors">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800">{{ $req->title }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $req->project?->name }} · {{ $req->status }}</p>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- KB Articles --}}
    @if($results['articles']->count())
    <div class="mb-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
            <span class="w-5 h-5 bg-purple-100 text-purple-600 rounded text-xs flex items-center justify-center font-bold">K</span>
            Knowledge Base ({{ $results['articles']->count() }})
        </h3>
        <div class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-100">
            @foreach($results['articles'] as $article)
            <a href="{{ route('kb.show', [$article->project, $article]) }}"
               class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-colors">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800">{{ $article->title }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $article->project?->name }}</p>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif
    @endif
</div>
@endsection
