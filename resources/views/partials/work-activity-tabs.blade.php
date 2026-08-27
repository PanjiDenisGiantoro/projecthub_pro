@php
    $waActive   = 'border-blue-600 text-blue-600';
    $waInactive = 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300';
@endphp
<div class="mb-4">
    <h1 class="text-xl font-semibold text-gray-800 mb-3">Aktivitas Kerja</h1>
    <nav class="flex gap-6 border-b border-gray-200">
        <a href="{{ route('tasks.all') }}"
           class="px-1 pb-3 text-sm font-medium border-b-2 {{ request()->routeIs('tasks.all') ? $waActive : $waInactive }}">
            Task
        </a>
        @can('access sprints')
        <a href="{{ route('sprints.all') }}"
           class="px-1 pb-3 text-sm font-medium border-b-2 {{ request()->routeIs('sprints.all') ? $waActive : $waInactive }}">
            Sprint
        </a>
        @endcan
        <a href="{{ route('recurring.all') }}"
           class="px-1 pb-3 text-sm font-medium border-b-2 {{ request()->routeIs('recurring.all') ? $waActive : $waInactive }}">
            Recurring
        </a>
        @can('access tickets')
        <a href="{{ route('tickets.all') }}"
           class="px-1 pb-3 text-sm font-medium border-b-2 {{ request()->routeIs('tickets.*') ? $waActive : $waInactive }}">
            Ticket
        </a>
        @endcan
    </nav>
</div>
