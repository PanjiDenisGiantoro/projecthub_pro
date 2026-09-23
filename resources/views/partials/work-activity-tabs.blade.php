@php
    $waActive   = 'border-blue-600 text-blue-600';
    $waInactive = 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300';

    $waUser = auth()->user();
    $waCompanyId = $waUser->company_id;
    $waCompanyScope = function ($q) use ($waUser, $waCompanyId) {
        if (! $waUser->is_super_admin && $waCompanyId) {
            $q->whereHas('project', fn ($p) => $p->where('company_id', $waCompanyId));
        }
    };

    $waTaskCount = \App\Models\Task::query()->tap($waCompanyScope)
        ->when($waUser->hasRole('client'), fn ($q) => $q->whereHas('project', fn ($p) => $p->where('client_id', $waUser->id)))
        ->count();
    $waSprintActiveCount = \App\Models\Sprint::query()->tap($waCompanyScope)
        ->where('status', 'active')
        ->when($waUser->hasRole('client'), fn ($q) => $q->whereHas('project', fn ($p) => $p->where('client_id', $waUser->id)))
        ->count();
    $waTicketCount = \App\Models\BugTicket::query()->tap($waCompanyScope)
        ->when($waUser->hasRole('client'), fn ($q) => $q->where('reporter_id', $waUser->id))
        ->count();

    $waCompanyName = $waUser->company->name ?? config('app.name', 'Flovig');
@endphp
<div class="mb-4">
    <p class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-gray-400 mb-1.5">
        <span>{{ $waCompanyName }}</span>
        <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span>Manajemen Pekerjaan</span>
    </p>

    <div class="flex items-center justify-between flex-wrap gap-3 mb-3">
        <h1 class="text-xl font-semibold text-gray-800">Aktivitas Kerja</h1>

        @isset($headerStats)
            <div class="flex items-center gap-2 flex-wrap">
                @foreach($headerStats as $stat)
                    <span class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-full px-3 py-1.5">
                        <span class="w-1.5 h-1.5 rounded-full {{ $stat['dot'] ?? 'bg-blue-500' }}"></span>
                        {{ $stat['label'] }}: <strong class="text-gray-800">{{ $stat['value'] }}</strong>
                    </span>
                @endforeach
            </div>
        @endisset
    </div>

    <nav class="flex gap-6 border-b border-gray-200">
        <a href="{{ route('tasks.all') }}"
           class="flex items-center gap-1.5 px-1 pb-3 text-sm font-medium border-b-2 {{ request()->routeIs('tasks.all') ? $waActive : $waInactive }}">
            Task
            <span class="text-[11px] font-semibold px-1.5 py-0.5 rounded-full {{ request()->routeIs('tasks.all') ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500' }}">{{ $waTaskCount }}</span>
        </a>
        @can('access sprints')
        <a href="{{ route('sprints.all') }}"
           class="flex items-center gap-1.5 px-1 pb-3 text-sm font-medium border-b-2 {{ request()->routeIs('sprints.all') ? $waActive : $waInactive }}">
            Sprint
            <span class="text-[11px] font-semibold px-1.5 py-0.5 rounded-full {{ request()->routeIs('sprints.all') ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500' }}">{{ $waSprintActiveCount }} Active</span>
        </a>
        @endcan
        <a href="{{ route('recurring.all') }}"
           class="px-1 pb-3 text-sm font-medium border-b-2 {{ request()->routeIs('recurring.all') ? $waActive : $waInactive }}">
            Recurring
        </a>
        @can('access tickets')
        <a href="{{ route('tickets.all') }}"
           class="flex items-center gap-1.5 px-1 pb-3 text-sm font-medium border-b-2 {{ request()->routeIs('tickets.*') ? $waActive : $waInactive }}">
            Ticket
            <span class="text-[11px] font-semibold px-1.5 py-0.5 rounded-full {{ request()->routeIs('tickets.*') ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500' }}">{{ $waTicketCount }}</span>
        </a>
        @endcan
    </nav>
</div>
