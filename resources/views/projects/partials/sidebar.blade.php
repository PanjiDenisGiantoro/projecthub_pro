{{-- Sidebar menu project; dipakai di halaman detail project & Knowledge Base. --}}
@php
$tabs = [
    ['key' => 'overview', 'label' => 'Overview', 'group' => 'PROJECT', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>'],
    ['key' => 'timesheet', 'label' => 'Timesheet', 'group' => 'PROJECT', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
    ['key' => 'tasks', 'label' => 'Tasks', 'group' => 'PLANNING', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>'],
    ['key' => 'sprints', 'label' => 'Sprints', 'group' => 'PLANNING', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>'],
    ['key' => 'milestones', 'label' => 'Milestones', 'group' => 'PLANNING', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6H9.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/>'],
    ['key' => 'team', 'label' => 'Team', 'group' => 'TEAM', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>'],
    ['key' => 'tickets', 'label' => 'Tickets', 'group' => 'ISSUES', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>'],
    ['key' => 'files', 'label' => 'Files', 'group' => 'DOCUMENTS', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>'],
    ['key' => 'kb', 'label' => 'Knowledge Base', 'group' => 'DOCUMENTS', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>'],
    ['key' => 'portal', 'label' => 'Portal', 'group' => 'TOOLS', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>'],
    ['key' => 'budget', 'label' => 'Budget', 'group' => 'TOOLS', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
    ['key' => 'notif', 'label' => 'Notifications', 'group' => 'COMMUNICATION', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>'],
    ['key' => 'chat', 'label' => 'Chat', 'group' => 'COMMUNICATION', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>'],
];
$groupedTabs = collect($tabs)->groupBy('group');
@endphp

{{-- ============================================================
1. CLEAN SIDEBAR NAVIGATION
============================================================ --}}
<aside
    class="w-full lg:w-56 shrink-0 lg:sticky lg:top-4 flex flex-col bg-white dark:bg-gray-850 rounded-2xl shadow-2xs border border-gray-200/90 dark:border-gray-700/80 p-3">
    <div class="space-y-4">


        @foreach($groupedTabs as $groupName => $groupTabs)
            <div class="space-y-1">
                <p class="px-2.5 text-[10.5px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                    {{ $groupName }}
                </p>
                <nav class="space-y-0.5">
                    @foreach($groupTabs as $t)
                        @php $isTab = $tab === $t['key']; @endphp
                        <a href="{{ $t['key'] === 'kb' ? route('kb.index', $project) : route('projects.tab', [$project, \App\Http\Controllers\Web\ProjectWebController::tabSlug($t['key'])]) }}"
                            class="relative w-full flex items-center justify-between px-2.5 py-1.5 rounded-xl border text-[13px] font-medium transition-all group {{ $isTab
                                ? 'bg-blue-50/80 dark:bg-blue-950/40 border-blue-500/30 text-blue-700 dark:text-blue-400 font-semibold shadow-2xs'
                                : 'border-transparent text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/60 hover:text-gray-900 dark:hover:text-gray-200' }}">
                            <span class="flex items-center gap-2.5 truncate">
                                <svg class="w-4 h-4 shrink-0 transition-colors {{ $isTab ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400 group-hover:text-gray-600' }}"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $t['icon'] !!}</svg>
                                <span class="truncate">{{ $t['label'] }}</span>
                            </span>
                            {{-- Active indicator dot on right --}}
                            @if($isTab)
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-600 dark:bg-blue-400 shrink-0"></span>
                            @endif
                        </a>
                    @endforeach
                </nav>
            </div>
        @endforeach
    </div>

    {{-- Sidebar Footer --}}
    <div class="mt-4 pt-3 px-2.5 border-t border-gray-100 dark:border-gray-700/80 text-[11px] text-gray-400 dark:text-gray-600 font-medium">
        Powered by ARUNIKA &copy; 2026
    </div>
</aside>
