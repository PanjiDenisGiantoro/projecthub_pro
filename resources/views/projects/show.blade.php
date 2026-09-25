@extends('layouts.app')

@section('title', $project->name)
@section('page-title', $project->name)
@section('main-class', 'flex-1 px-4 sm:px-6 pb-8 overflow-y-auto overflow-x-hidden w-full max-w-full min-w-0')

@push('head')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <style>
        .select2-container--default .select2-selection--multiple {
            min-height: 42px !important;
            border: 1px solid #d1d5db !important;
            border-radius: 0.75rem !important;
            padding: 0.25rem 0.5rem !important;
        }

        .select2-container--default.select2-container--focus .select2-selection--multiple {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, .2) !important;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #eff6ff !important;
            border: 1px solid #bfdbfe !important;
            color: #1d4ed8 !important;
            border-radius: 0.375rem !important;
            padding: 1px 6px !important;
            font-size: 0.75rem !important;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: #3b82f6 !important;
            margin-right: 4px !important;
        }

        .select2-dropdown {
            border: 1px solid #d1d5db !important;
            border-radius: 0.75rem !important;
            font-size: 0.875rem !important;
        }

        .select2-results__option--highlighted {
            background-color: #3b82f6 !important;
        }

        .select2-search--dropdown .select2-search__field {
            border-radius: 0.5rem !important;
            border: 1px solid #d1d5db !important;
            padding: 0.375rem 0.625rem !important;
            font-size: 0.875rem !important;
        }

        /* Drag & Drop Visual Indicators */
        .sortable-drag-ghost {
            opacity: 0.4 !important;
            border: 2px dashed #a855f7 !important;
            background-color: #faf5ff !important;
        }

        .sortable-drag-chosen {
            transform: scale(1.02);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        }

        .list-dropzone-active {
            background-color: #fdf4ff !important;
            border: 2px dashed #d946ef !important;
            border-radius: 0.75rem;
            min-height: 48px;
        }

        /* Hide scrollbar cleanly for board horizontal scroll */
        .hide-scrollbar {
            -ms-overflow-style: none !important;
            scrollbar-width: none !important;
        }

        .hide-scrollbar::-webkit-scrollbar {
            display: none !important;
            width: 0 !important;
            height: 0 !important;
        }

        /* Bucket dropzone max height and smooth custom scrollbar */
        .project-detail-cards-dropzone {
            max-height: calc(100vh - 300px);
            min-height: 180px;
            overflow-y: auto !important;
            overflow-x: hidden;
            padding-right: 4px;
            scrollbar-width: thin;
            scrollbar-color: rgba(156, 163, 175, 0.4) transparent;
        }

        .project-detail-cards-dropzone::-webkit-scrollbar {
            width: 5px;
        }

        .project-detail-cards-dropzone::-webkit-scrollbar-track {
            background: transparent;
        }

        .project-detail-cards-dropzone::-webkit-scrollbar-thumb {
            background-color: rgba(156, 163, 175, 0.35);
            border-radius: 9999px;
        }

        .project-detail-cards-dropzone::-webkit-scrollbar-thumb:hover {
            background-color: rgba(156, 163, 175, 0.65);
        }
    </style>
@endpush

@section('content')
    <div class="py-6 w-full max-w-full min-w-0 overflow-x-hidden" x-data="projectPageData()" x-init="initProjectPage()">

        {{-- ============================================================
        PROJECT DATA INITIALIZATION
        ============================================================ --}}
        @php
            $statusConfig = [
                'draft' => ['label' => 'Draft', 'class' => 'bg-gray-100 text-gray-700 border-gray-200', 'dot' => 'bg-gray-400'],
                'active' => ['label' => 'Active', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'dot' => 'bg-emerald-500'],
                'on_hold' => ['label' => 'On Hold', 'class' => 'bg-amber-50 text-amber-700 border-amber-200', 'dot' => 'bg-amber-500'],
                'completed' => ['label' => 'Completed', 'class' => 'bg-blue-50 text-blue-700 border-blue-200', 'dot' => 'bg-blue-500'],
                'cancelled' => ['label' => 'Cancelled', 'class' => 'bg-red-50 text-red-700 border-red-200', 'dot' => 'bg-red-500'],
            ];
            $sc = $statusConfig[$project->status] ?? ['label' => ucfirst($project->status), 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'dot' => 'bg-emerald-500'];
            $progress = $project->progress ?? 0;
            $totalTasks = $project->tasks()->count();
            $doneTasks = $project->tasks()->where('status', 'done')->count();

            // Calculate progress percentage if 0
            if ($progress == 0 && $totalTasks > 0) {
                $progress = round(($doneTasks / $totalTasks) * 100);
            }

            // Sisa hari = total hari kerja dari task, sprint, dan ticket yang belum beres
            $today = now()->startOfDay();

            $openTaskDays = $project->tasks()->where('status', '!=', 'done')->whereNotNull('due_date')->pluck('due_date')
                ->sum(fn($d) => max(0, $today->diffInDays(\Carbon\Carbon::parse($d)->startOfDay(), false)));

            $openSprintDays = $project->sprints()->where('status', '!=', 'completed')->whereNotNull('end_date')->pluck('end_date')
                ->sum(fn($d) => max(0, $today->diffInDays(\Carbon\Carbon::parse($d)->startOfDay(), false)));

            $openTicketDays = $project->tickets()->whereNotIn('status', ['resolved', 'closed'])->whereNotNull('sla_due_at')->pluck('sla_due_at')
                ->sum(fn($d) => max(0, $today->diffInDays(\Carbon\Carbon::parse($d)->startOfDay(), false)));

            $hasOpenItems = $project->tasks()->where('status', '!=', 'done')->whereNotNull('due_date')->exists()
                || $project->sprints()->where('status', '!=', 'completed')->whereNotNull('end_date')->exists()
                || $project->tickets()->whereNotIn('status', ['resolved', 'closed'])->whereNotNull('sla_due_at')->exists();

            $daysLeft = $hasOpenItems ? (int) ($openTaskDays + $openSprintDays + $openTicketDays) : null;

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

        <div class="flex flex-col lg:flex-row gap-6 items-start w-full max-w-full min-w-0">
            {{-- ============================================================
            1. CLEAN SIDEBAR NAVIGATION
            ============================================================ --}}
            <aside
                class="w-full lg:w-56 shrink-0 lg:sticky lg:top-4 flex flex-col justify-between min-h-[calc(100vh-120px)]">
                <div class="space-y-4">


                    @foreach($groupedTabs as $groupName => $groupTabs)
                        <div class="space-y-1">
                            <p class="px-2.5 text-[10.5px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                                {{ $groupName }}
                            </p>
                            <nav class="space-y-0.5">
                                @foreach($groupTabs as $t)
                                    <button @click="tab = '{{ $t['key'] }}'"
                                        :class="tab === '{{ $t['key'] }}'
                                                        ? 'bg-blue-50/80 dark:bg-blue-950/40 border-blue-500/30 text-blue-700 dark:text-blue-400 font-semibold shadow-2xs'
                                                        : 'border-transparent text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/60 hover:text-gray-900 dark:hover:text-gray-200'"
                                        class="relative w-full flex items-center justify-between px-2.5 py-1.5 rounded-xl border text-[13px] font-medium transition-all group">
                                        <span class="flex items-center gap-2.5 truncate">
                                            <svg class="w-4 h-4 shrink-0 transition-colors"
                                                :class="tab === '{{ $t['key'] }}' ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400 group-hover:text-gray-600'"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $t['icon'] !!}</svg>
                                            <span class="truncate">{{ $t['label'] }}</span>
                                        </span>
                                        {{-- Active indicator dot on right --}}
                                        <span x-show="tab === '{{ $t['key'] }}'"
                                            class="w-1.5 h-1.5 rounded-full bg-blue-600 dark:bg-blue-400 shrink-0"></span>
                                    </button>
                                @endforeach
                            </nav>
                        </div>
                    @endforeach
                </div>

                {{-- Sidebar Footer --}}
                <div class="pt-6 pb-2 px-2.5 text-[11px] text-gray-400 dark:text-gray-600 font-medium">
                    Powered by ARUNIKA &copy; 2026
                </div>
            </aside>

            {{-- ============================================================
            2. MAIN CONTENT AREA
            ============================================================ --}}
            <div class="flex-1 min-w-0 w-full max-w-full space-y-5 overflow-x-hidden">

                {{-- Global Clean Breadcrumb Line across all tabs (Overview, Timesheet, Sprints, Milestones, Tasks, etc.) --}}
                <div class="flex items-center gap-1.5 text-xs text-gray-400 font-medium">
                    <a href="{{ route('projects.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition">Projects</a>
                    <span>&gt;</span>
                    <span class="text-gray-600 dark:text-gray-300 font-semibold">{{ $project->name }}</span>
                    <span>&gt;</span>
                    <span class="text-blue-600 dark:text-blue-400 font-semibold"
                        x-text="{
                            'overview': 'Overview',
                            'timesheet': 'Timesheet',
                            'tasks': 'Tasks',
                            'sprints': 'Sprints',
                            'milestones': 'Milestones',
                            'team': 'Team',
                            'tickets': 'Tickets',
                            'files': 'Files',
                            'kb': 'Knowledge Base',
                            'portal': 'Portal',
                            'budget': 'Budget',
                            'notif': 'Notifications',
                            'chat': 'Chat'
                        }[tab] || (tab.charAt(0).toUpperCase() + tab.slice(1))">
                        Overview
                    </span>
                </div>

                {{-- ============================================================
                TOP HEADER & 4 STAT CARDS (ONLY FOR OVERVIEW TAB)
                ============================================================ --}}
                <div x-show="tab === 'overview'" x-cloak class="space-y-5">
                    {{-- Top Header Card --}}
                    <div
                        class="bg-white dark:bg-gray-850 rounded-2xl shadow-2xs border border-gray-200/90 dark:border-gray-700/80 p-5 sm:p-6 space-y-4">


                        {{-- Title Row + Action Buttons --}}
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div class="flex items-center gap-3 flex-wrap">
                                <h1 class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white tracking-tight">
                                    {{ $project->name }}
                                </h1>
                                <span
                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border {{ $sc['class'] }}">
                                    <span class="w-2 h-2 rounded-full {{ $sc['dot'] }}"></span>
                                    {{ $sc['label'] }}
                                </span>
                            </div>

                            <div class="flex items-center gap-2.5 shrink-0">
                                @if($project->google_meet_link)
                                    <a href="{{ $project->google_meet_link }}" target="_blank" rel="noopener"
                                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 text-white text-xs font-semibold rounded-xl hover:bg-emerald-700 transition shadow-2xs">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                                            <path
                                                d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z" />
                                        </svg>
                                        Join Meeting
                                    </a>
                                @elseif(!auth()->user()->hasRole('client') && $project->google_meet_enabled)
                                    <form method="POST" action="{{ route('projects.meeting.create', $project) }}">
                                        @csrf
                                        <button type="submit"
                                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 text-xs font-semibold rounded-xl hover:bg-gray-50 dark:hover:bg-gray-750 transition shadow-2xs">
                                            <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                            Create Meeting
                                        </button>
                                    </form>
                                @endif

                                @if(!auth()->user()->hasRole('client'))
                                    <a href="{{ route('projects.edit', $project) }}"
                                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl transition shadow-2xs">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                        </svg>
                                        Edit Project
                                    </a>
                                @endif
                            </div>
                        </div>

                        {{-- Meta Info Line --}}
                        <div
                            class="flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-gray-500 dark:text-gray-400 pt-1">
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                Client: <strong
                                    class="text-gray-800 dark:text-gray-200 font-semibold">{{ $project->client?->name ?? 'Client One Global' }}</strong>
                            </span>
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                Lead: <strong
                                    class="text-gray-800 dark:text-gray-200 font-semibold">{{ $project->manager?->name ?? 'Admin ProjectHub' }}</strong>
                            </span>
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Timeline: <strong class="text-gray-800 dark:text-gray-200 font-semibold">
                                    {{ $project->start_date ? \Carbon\Carbon::parse($project->start_date)->format('d M Y') : '01 Sep 2026' }}
                                    –
                                    {{ $project->end_date ? \Carbon\Carbon::parse($project->end_date)->format('d M Y') : '30 Nov 2026' }}
                                </strong>
                            </span>
                        </div>

                        {{-- Sprint Progress Bar --}}
                        <div class="pt-2 space-y-1.5">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-gray-700 dark:text-gray-300">Overall Sprint Progress</span>
                                <span class="font-extrabold text-blue-600 dark:text-blue-400">{{ $progress }}%
                                    Complete</span>
                            </div>
                            <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-2.5 overflow-hidden">
                                <div class="h-2.5 rounded-full bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 transition-all duration-500 shadow-2xs"
                                    style="width: {{ min($progress, 100) }}%"></div>
                            </div>
                        </div>
                    </div>                    {{-- 4 Stat Cards (Redesigned matching Sprint Style CSS) --}}
                    <div
                        class="bg-white dark:bg-gray-850 rounded-2xl border border-gray-200/90 dark:border-gray-700/80 shadow-2xs grid grid-cols-2 lg:grid-cols-4 divide-y sm:divide-y-0 sm:divide-x divide-gray-100 dark:divide-gray-800">
                        {{-- Col 1: TOTAL TASKS --}}
                        <div class="p-5 flex flex-col justify-between">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-extrabold text-gray-500 dark:text-gray-400 uppercase tracking-wider">TOTAL TASKS</span>
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                </svg>
                            </div>
                            <p class="text-3xl font-black text-gray-900 dark:text-white leading-none my-2.5">{{ $totalTasks }}</p>
                            <div class="flex items-center gap-1.5 text-xs font-semibold">
                                <span class="text-blue-600 dark:text-blue-400">↑ {{ $totalTasks }} in scope</span>
                                <span class="text-gray-300 dark:text-gray-600">·</span>
                                <span class="text-gray-400 dark:text-gray-500 font-normal">{{ $project->tasks->sum('story_points') ?: ($totalTasks * 3) }} pts</span>
                            </div>
                        </div>

                        {{-- Col 2: COMPLETED --}}
                        <div class="p-5 flex flex-col justify-between">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-extrabold text-gray-500 dark:text-gray-400 uppercase tracking-wider">COMPLETED</span>
                                <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                            <p class="text-3xl font-black text-emerald-600 dark:text-emerald-400 leading-none my-2.5">{{ $doneTasks }}</p>
                            <div class="flex items-center gap-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>{{ $progress }}% delivered</span>
                            </div>
                        </div>

                        {{-- Col 3: SPRINT MEMBERS --}}
                        <div class="p-5 flex flex-col justify-between">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-extrabold text-gray-500 dark:text-gray-400 uppercase tracking-wider">SPRINT MEMBERS</span>
                                <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <p class="text-3xl font-black text-gray-900 dark:text-white leading-none my-2.5">
                                {{ $project->members->count() ?: 1 }}
                            </p>
                            <div class="flex items-center gap-1.5 text-xs font-semibold text-purple-600 dark:text-purple-400">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <span>{{ $project->members->count() ?: 1 }} active team</span>
                            </div>
                        </div>

                        {{-- Col 4: WORKING DAYS LEFT --}}
                        <div class="p-5 flex flex-col justify-between">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-extrabold text-gray-500 dark:text-gray-400 uppercase tracking-wider">WORKING DAYS LEFT</span>
                                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <p class="text-3xl font-black text-blue-600 dark:text-blue-400 leading-none my-2.5">
                                {{ $daysLeft !== null ? $daysLeft . 'd' : '—' }}
                            </p>
                            <div class="flex items-center gap-1.5 text-xs font-semibold text-gray-500 dark:text-gray-400">
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span>Target: {{ $project->end_date ? \Carbon\Carbon::parse($project->end_date)->format('d M Y') : 'Ongoing' }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- ============================================================
                    TAB: OVERVIEW CONTENT (DASHBOARD, DIRECT SHORTCUTS, ANALYTICS)
                    ============================================================ --}}
                    <div x-show="tab === 'overview'" x-cloak class="pt-1">
                        @include('projects.partials.overview-content')
                    </div>
                </div>

                {{-- ============================================================
                TAB: TASKS (FILTERS, BOARD & LIST VIEWS)
                ============================================================ --}}
                <div x-show="tab === 'tasks'" x-cloak class="space-y-4 w-full max-w-full min-w-0">

                    {{-- FILTER TOOLBAR & VIEW TOGGLE (MATCHING REFERENCE DESIGN) --}}
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-1">
                        {{-- Left: View Switcher (Board vs List) --}}
                        <div class="inline-flex items-center gap-1 bg-gray-100/90 dark:bg-gray-800 p-1 rounded-xl shrink-0">
                            <button type="button" @click="taskView = 'board'"
                                :class="taskView === 'board' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-xs font-bold' : 'text-gray-500 dark:text-gray-400 hover:text-gray-800'"
                                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs transition-all cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2H4V5zm0 4h16v10a1 1 0 01-1 1H5a1 1 0 01-1-1V9z" />
                                </svg>
                                Board
                            </button>
                            <button type="button" @click="taskView = 'list'"
                                :class="taskView === 'list' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-xs font-bold' : 'text-gray-500 dark:text-gray-400 hover:text-gray-800'"
                                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs transition-all cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                </svg>
                                List
                            </button>
                        </div>

                        {{-- Right: Dropdown Filters --}}
                        <div class="flex items-center gap-2 flex-wrap">
                            {{-- Assignee Filter --}}
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" @click.away="open = false" type="button"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-750 border border-gray-200/90 dark:border-gray-700 rounded-xl text-xs font-medium text-gray-700 dark:text-gray-300 shadow-2xs transition">
                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <span>Assignee: <strong class="font-semibold"
                                            x-text="filterAssigneeName || 'All'"></strong></span>
                                    <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <div x-show="open" x-cloak
                                    class="absolute right-0 top-full mt-1.5 w-48 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg p-1.5 z-40 space-y-0.5">
                                    <button
                                        @click="filterAssignee = ''; filterAssigneeName = 'All'; open = false; applyFilters()"
                                        class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs hover:bg-gray-50 dark:hover:bg-gray-700"
                                        :class="filterAssignee === '' ? 'text-blue-600 font-bold' : 'text-gray-700 dark:text-gray-200'">All
                                        Assignees</button>
                                    @foreach($assignableUsers as $u)
                                        <button
                                            @click="filterAssignee = '{{ $u->id }}'; filterAssigneeName = '{{ addslashes($u->name) }}'; open = false; applyFilters()"
                                            class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2"
                                            :class="filterAssignee === '{{ $u->id }}' ? 'text-blue-600 font-bold' : 'text-gray-700 dark:text-gray-200'">
                                            <span
                                                class="w-5 h-5 rounded-full bg-blue-600 text-white flex items-center justify-center text-[9px] font-bold">{{ strtoupper(substr($u->name, 0, 1)) }}</span>
                                            <span class="truncate">{{ $u->name }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Due Date Filter --}}
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" @click.away="open = false" type="button"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-750 border border-gray-200/90 dark:border-gray-700 rounded-xl text-xs font-medium text-gray-700 dark:text-gray-300 shadow-2xs transition">
                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span>Due Date: <strong class="font-semibold"
                                            x-text="filterDueDateName || 'Any time'"></strong></span>
                                    <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <div x-show="open" x-cloak
                                    class="absolute right-0 top-full mt-1.5 w-44 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg p-1.5 z-40 space-y-0.5">
                                    <button
                                        @click="filterDueDate = ''; filterDueDateName = 'Any time'; open = false; applyFilters()"
                                        class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs hover:bg-gray-50 dark:hover:bg-gray-700"
                                        :class="filterDueDate === '' ? 'text-blue-600 font-bold' : 'text-gray-700 dark:text-gray-200'">Any
                                        time</button>
                                    <button
                                        @click="filterDueDate = 'today'; filterDueDateName = 'Today'; open = false; applyFilters()"
                                        class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs hover:bg-gray-50 dark:hover:bg-gray-700"
                                        :class="filterDueDate === 'today' ? 'text-blue-600 font-bold' : 'text-gray-700 dark:text-gray-200'">Today</button>
                                    <button
                                        @click="filterDueDate = 'week'; filterDueDateName = 'This Week'; open = false; applyFilters()"
                                        class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs hover:bg-gray-50 dark:hover:bg-gray-700"
                                        :class="filterDueDate === 'week' ? 'text-blue-600 font-bold' : 'text-gray-700 dark:text-gray-200'">This
                                        Week</button>
                                    <button
                                        @click="filterDueDate = 'overdue'; filterDueDateName = 'Overdue'; open = false; applyFilters()"
                                        class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs hover:bg-gray-50 dark:hover:bg-gray-700 text-red-600 font-medium">Overdue</button>
                                    <button
                                        @click="filterDueDate = 'completed'; filterDueDateName = 'Completed'; open = false; applyFilters()"
                                        class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs hover:bg-gray-50 dark:hover:bg-gray-700 text-emerald-600 font-medium">Completed</button>
                                </div>
                            </div>

                            {{-- Label Filter --}}
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" @click.away="open = false" type="button"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-750 border border-gray-200/90 dark:border-gray-700 rounded-xl text-xs font-medium text-gray-700 dark:text-gray-300 shadow-2xs transition">
                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                    <span>Label: <strong class="font-semibold"
                                            x-text="filterLabelName || 'All'"></strong></span>
                                    <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <div x-show="open" x-cloak
                                    class="absolute right-0 top-full mt-1.5 w-48 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg p-1.5 z-40 space-y-0.5 max-h-60 overflow-y-auto">
                                    <button @click="filterLabel = ''; filterLabelName = 'All'; open = false; applyFilters()"
                                        class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs hover:bg-gray-50 dark:hover:bg-gray-700"
                                        :class="filterLabel === '' ? 'text-blue-600 font-bold' : 'text-gray-700 dark:text-gray-200'">All
                                        Labels</button>
                                    @foreach($projectLabels as $lbl)
                                        <button
                                            @click="filterLabel = '{{ $lbl->id }}'; filterLabelName = '{{ addslashes($lbl->name) }}'; open = false; applyFilters()"
                                            class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-1.5"
                                            :class="filterLabel === '{{ $lbl->id }}' ? 'text-blue-600 font-bold' : 'text-gray-700 dark:text-gray-200'">
                                            <span class="w-2 h-2 rounded-full {{ $lbl->colorClasses()['dot'] }}"></span>
                                            <span class="truncate">{{ $lbl->name }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Priority Filter --}}
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" @click.away="open = false" type="button"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-750 border border-gray-200/90 dark:border-gray-700 rounded-xl text-xs font-medium text-gray-700 dark:text-gray-300 shadow-2xs transition">
                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6H9.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
                                    </svg>
                                    <span>Priority: <strong class="font-semibold"
                                            x-text="filterPriorityName || 'All'"></strong></span>
                                    <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <div x-show="open" x-cloak
                                    class="absolute right-0 top-full mt-1.5 w-40 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg p-1.5 z-40 space-y-0.5">
                                    <button
                                        @click="filterPriority = ''; filterPriorityName = 'All'; open = false; applyFilters()"
                                        class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs hover:bg-gray-50 dark:hover:bg-gray-700"
                                        :class="filterPriority === '' ? 'text-blue-600 font-bold' : 'text-gray-700 dark:text-gray-200'">All</button>
                                    <button
                                        @click="filterPriority = 'urgent'; filterPriorityName = 'Urgent'; open = false; applyFilters()"
                                        class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs hover:bg-gray-50 dark:hover:bg-gray-700 text-rose-600 font-semibold">Urgent</button>
                                    <button
                                        @click="filterPriority = 'high'; filterPriorityName = 'Important'; open = false; applyFilters()"
                                        class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs hover:bg-gray-50 dark:hover:bg-gray-700 text-amber-600 font-semibold">Important</button>
                                    <button
                                        @click="filterPriority = 'medium'; filterPriorityName = 'Medium'; open = false; applyFilters()"
                                        class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs hover:bg-gray-50 dark:hover:bg-gray-700 text-blue-600 font-semibold">Medium</button>
                                    <button
                                        @click="filterPriority = 'low'; filterPriorityName = 'Low'; open = false; applyFilters()"
                                        class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-500 font-semibold">Low</button>
                                </div>
                            </div>

                            {{-- Milestone Filter --}}
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" @click.away="open = false" type="button"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-750 border border-gray-200/90 dark:border-gray-700 rounded-xl text-xs font-medium text-gray-700 dark:text-gray-300 shadow-2xs transition"
                                    :class="filterMilestone ? 'border-purple-300 dark:border-purple-600 bg-purple-50/50 text-purple-700 dark:text-purple-300' : ''">
                                    <svg class="w-3.5 h-3.5" :class="filterMilestone ? 'text-purple-600 dark:text-purple-400' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6H9.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
                                    </svg>
                                    <span>Milestone: <strong class="font-semibold" x-text="filterMilestoneName || 'All'"></strong></span>
                                    <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <div x-show="open" x-cloak
                                    class="absolute right-0 top-full mt-1.5 w-56 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg p-1.5 z-40 space-y-0.5 max-h-60 overflow-y-auto">
                                    <button @click="filterMilestone = ''; filterMilestoneName = 'All'; open = false; applyFilters()"
                                        class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs hover:bg-gray-50 dark:hover:bg-gray-700"
                                        :class="filterMilestone === '' ? 'text-blue-600 font-bold' : 'text-gray-700 dark:text-gray-200'">All Milestones</button>
                                    @foreach($project->milestones as $m)
                                        <button @click="filterMilestone = '{{ $m->id }}'; filterMilestoneName = '{{ addslashes($m->title) }}'; open = false; applyFilters()"
                                            class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center justify-between gap-2"
                                            :class="filterMilestone === '{{ $m->id }}' ? 'text-blue-600 font-bold' : 'text-gray-700 dark:text-gray-200'">
                                            <span class="truncate">{{ $m->title }}</span>
                                            @if($m->code)
                                                <span class="text-[10px] font-mono text-gray-400 shrink-0">{{ $m->code }}</span>
                                            @endif
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Sprint Filter --}}
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" @click.away="open = false" type="button"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-750 border border-gray-200/90 dark:border-gray-700 rounded-xl text-xs font-medium text-gray-700 dark:text-gray-300 shadow-2xs transition"
                                    :class="filterSprint ? 'border-indigo-300 dark:border-indigo-600 bg-indigo-50/50 text-indigo-700 dark:text-indigo-300' : ''">
                                    <svg class="w-3.5 h-3.5" :class="filterSprint ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                    <span>Sprint: <strong class="font-semibold" x-text="filterSprintName || 'All'"></strong></span>
                                    <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <div x-show="open" x-cloak
                                    class="absolute right-0 top-full mt-1.5 w-56 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg p-1.5 z-40 space-y-0.5 max-h-60 overflow-y-auto">
                                    <button @click="filterSprint = ''; filterSprintName = 'All'; open = false; applyFilters()"
                                        class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs hover:bg-gray-50 dark:hover:bg-gray-700"
                                        :class="filterSprint === '' ? 'text-blue-600 font-bold' : 'text-gray-700 dark:text-gray-200'">All Sprints</button>
                                    @foreach($project->sprints as $s)
                                        <button @click="filterSprint = '{{ $s->id }}'; filterSprintName = '{{ addslashes($s->name) }}'; open = false; applyFilters()"
                                            class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center justify-between gap-2"
                                            :class="filterSprint === '{{ $s->id }}' ? 'text-blue-600 font-bold' : 'text-gray-700 dark:text-gray-200'">
                                            <span class="truncate">{{ $s->name }}</span>
                                            @if($s->code)
                                                <span class="text-[10px] font-mono text-gray-400 shrink-0">{{ $s->code }}</span>
                                            @endif
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Clear Filters Button --}}
                            <button type="button"
                                x-show="filterAssignee || filterDueDate || filterLabel || filterPriority || filterMilestone || filterSprint || searchQuery"
                                @click="clearAllFilters()" x-cloak
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-gray-500 hover:text-red-500 transition cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Clear filters
                            </button>
                        </div>
                    </div>

                    {{-- ============================================================
                    EMPTY STATE (WHEN NO BUCKETS EXIST)
                    ============================================================ --}}
                    @if($columns->isEmpty())
                        <div
                            class="bg-white dark:bg-gray-850 rounded-3xl border border-gray-200/90 dark:border-gray-700/80 p-8 sm:p-12 text-center shadow-2xs space-y-6">
                            {{-- Illustration Blueprint Box --}}
                            <div
                                class="relative w-20 h-20 mx-auto rounded-3xl bg-blue-50 dark:bg-blue-950/60 border-2 border-blue-200/70 dark:border-blue-800/80 flex items-center justify-center text-blue-600 dark:text-blue-400 shadow-sm">
                                <svg class="w-9 h-9" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2H4V5zm0 4h16v10a1 1 0 01-1 1H5a1 1 0 01-1-1V9z" />
                                    <circle cx="8" cy="13" r="1" fill="currentColor" />
                                    <circle cx="12" cy="13" r="1" fill="currentColor" />
                                    <circle cx="16" cy="13" r="1" fill="currentColor" />
                                    <circle cx="8" cy="16" r="1" fill="currentColor" />
                                    <circle cx="12" cy="16" r="1" fill="currentColor" />
                                    <circle cx="16" cy="16" r="1" fill="currentColor" />
                                </svg>
                                <span
                                    class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-blue-600 ring-4 ring-white dark:ring-gray-850"></span>
                            </div>

                            <div class="space-y-2 max-w-md mx-auto">
                                <h3 class="text-xl font-black text-gray-900 dark:text-white">No Buckets in this Sprint Yet</h3>
                                <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 leading-relaxed">
                                    Organize your sprint workflow by creating custom stages or instantly bootstrap with standard
                                    agile buckets.
                                </p>
                            </div>

                            {{-- Action Buttons --}}
                            <div class="flex items-center justify-center gap-3 flex-wrap pt-2">
                                @if(!auth()->user()->hasRole('client'))
                                    <button type="button" @click="bootstrapDefaultBuckets()" :disabled="isBootstrapping"
                                        class="inline-flex items-center gap-2 px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs sm:text-sm font-bold rounded-full transition shadow-sm cursor-pointer disabled:opacity-50">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                        </svg>
                                        <span x-text="isBootstrapping ? 'Creating Buckets...' : 'Use Default Buckets'">Use Default
                                            Buckets</span>
                                    </button>
                                    <button type="button" @click="openCreateBucketModal()"
                                        class="inline-flex items-center gap-2 px-6 py-2.5 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-750 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-650 text-xs sm:text-sm font-bold rounded-full transition shadow-2xs cursor-pointer">
                                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4v16m8-8H4" />
                                        </svg>
                                        Create Custom Bucket
                                    </button>
                                @endif
                            </div>

                            {{-- Auto-Generates Preview Badge Line --}}
                            <div
                                class="pt-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-center gap-2 text-xs flex-wrap text-gray-400">
                                <span
                                    class="font-bold tracking-wider text-[10px] uppercase text-gray-400">AUTO-GENERATES:</span>
                                <span
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold text-[11px]">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> To Do
                                </span>
                                <span>&rarr;</span>
                                <span
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 font-semibold text-[11px]">
                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> IN PROGRESS
                                </span>
                                <span>&rarr;</span>
                                <span
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-purple-100 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 font-semibold text-[11px]">
                                    <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span> REVIEW
                                </span>
                                <span>&rarr;</span>
                                <span
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 font-semibold text-[11px]">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> COMPLETED
                                </span>
                            </div>
                        </div>
                    @endif

                    {{-- ============================================================
                    BOARD VIEW (KANBAN)
                    ============================================================ --}}
                    @if($columns->isNotEmpty())
                        <div x-show="taskView === 'board'" x-cloak class="pt-1 w-full max-w-full min-w-0 overflow-x-hidden">
                            <div id="project-detail-kanban-columns"
                                class="flex gap-5 overflow-x-auto pb-6 pt-1 items-start min-h-[calc(100vh-320px)] w-full max-w-full min-w-0 hide-scrollbar select-none">

                                @foreach($columns as $col)
                                    @php
                                        $colTasks = $project->tasks->where('board_column_id', $col->id)->sortBy('sort_order');
                                        $dotColor = \App\Support\BoardColumnPalette::dot($col->color);
                                        $pillBg = \App\Support\BoardColumnPalette::pill($col->color);
                                        $slug = strtolower($col->slug ?? '');
                                    @endphp
                                    <div class="kanban-column w-80 shrink-0 flex flex-col space-y-3" data-column-id="{{ $col->id }}"
                                        data-column-slug="{{ $col->slug }}" data-column-name="{{ $col->name }}"
                                        data-column-color="{{ $col->color }}" data-column-icon="{{ $col->icon }}">
                                        {{-- Column Header Row (Pixel-Perfect with Reference Image & Draggable Handle) --}}
                                        <div
                                            class="kanban-column-header flex items-center justify-between gap-2 px-1 shrink-0 cursor-grab active:cursor-grabbing">
                                            @if(in_array($slug, ['todo', 'to_do', 'backlog']) && $col->color === 'slate')
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <svg class="w-4 h-4 text-slate-700 dark:text-slate-300 shrink-0" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                    </svg>
                                                    <h3
                                                        class="font-extrabold text-xs uppercase tracking-wider text-slate-900 dark:text-slate-100 truncate">
                                                        {{ $col->name }}
                                                    </h3>
                                                    <span
                                                        class="column-counter text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 px-2 py-0.5 rounded-full shadow-2xs">{{ $colTasks->count() }}</span>
                                                </div>
                                            @else
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <span
                                                        class="column-badge inline-flex items-center gap-1.5 {{ $pillBg }} text-white font-bold text-xs uppercase tracking-wider px-3 py-1 rounded-full shadow-2xs truncate">
                                                        {!! $col->renderIcon('w-3.5 h-3.5 text-white shrink-0') !!}
                                                        <span class="truncate">{{ $col->name }}</span>
                                                        <span
                                                            class="column-counter bg-white/25 text-white text-[10.5px] px-1.5 py-0.2 rounded-full font-bold">{{ $colTasks->count() }}</span>
                                                    </span>
                                                </div>
                                            @endif

                                            {{-- Bucket 3-Dots Dropdown Trigger (Crisp Black Dots) --}}
                                            <div class="relative shrink-0" x-data="{ open: false }">
                                                <button type="button" @click="open = !open" @click.away="open = false"
                                                    class="w-7 h-7 flex items-center justify-center rounded-lg text-black hover:text-black hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700 transition cursor-pointer"
                                                    title="Bucket Options">
                                                    <svg class="w-4 h-4 text-black dark:text-white" fill="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <circle cx="5" cy="12" r="1.75" />
                                                        <circle cx="12" cy="12" r="1.75" />
                                                        <circle cx="19" cy="12" r="1.75" />
                                                    </svg>
                                                </button>

                                                {{-- 3-Dots Dropdown Menu Modal with Clean SVG Icons --}}
                                                <div x-show="open" x-cloak
                                                    class="absolute right-0 top-full mt-1.5 w-64 bg-white dark:bg-gray-850 border border-gray-200/90 dark:border-gray-700 rounded-2xl shadow-xl p-2 z-50 space-y-1">
                                                    {{-- Header with Bucket Name & Count Pill --}}
                                                    <div
                                                        class="px-2.5 py-2 flex items-center justify-between gap-2 border-b border-gray-100 dark:border-gray-800 pb-2 mb-1">
                                                        <div class="flex items-center gap-2 truncate">
                                                            <span class="w-2.5 h-2.5 rounded-full {{ $dotColor }} shrink-0"></span>
                                                            <span
                                                                class="text-xs font-bold uppercase truncate text-gray-900 dark:text-white">{{ $col->name }}</span>
                                                        </div>
                                                        <span
                                                            class="text-[10.5px] font-bold px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded-full text-gray-600 dark:text-gray-300 shrink-0">{{ $colTasks->count() }}</span>
                                                    </div>

                                                    <p
                                                        class="px-2.5 pt-1 text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                                                        BUCKET ACTIONS</p>

                                                    {{-- Edit Bucket --}}
                                                    <button type="button"
                                                        @click="open = false; openEditBucketModal({{ $col->id }}, '{{ addslashes($col->name) }}', '{{ $col->color }}', '{{ $col->icon }}')"
                                                        class="w-full text-left px-2.5 py-2 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800 transition flex items-center gap-3 cursor-pointer group">
                                                        <div
                                                            class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-950/60 border border-blue-200/60 dark:border-blue-800/60 flex items-center justify-center text-blue-600 dark:text-blue-400 shrink-0 group-hover:scale-105 transition-transform">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                            </svg>
                                                        </div>
                                                        <div class="flex-1 min-w-0">
                                                            <p
                                                                class="text-xs font-bold text-gray-800 dark:text-gray-200 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                                                Edit Bucket</p>
                                                            <p class="text-[10px] text-gray-400 truncate">Rename, change color &
                                                                icon</p>
                                                        </div>
                                                    </button>

                                                    {{-- Add Task to Bucket --}}
                                                    <button type="button" @click="open = false; openQuickAddTask({{ $col->id }})"
                                                        class="w-full text-left px-2.5 py-2 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800 transition flex items-center gap-3 cursor-pointer group">
                                                        <div
                                                            class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200/60 dark:border-emerald-800/60 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shrink-0 group-hover:scale-105 transition-transform">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2" d="M12 4v16m8-8H4" />
                                                            </svg>
                                                        </div>
                                                        <div class="flex-1 min-w-0">
                                                            <p
                                                                class="text-xs font-bold text-gray-800 dark:text-gray-200 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                                                                Add Task to Bucket</p>
                                                            <p class="text-[10px] text-gray-400 truncate">Quick create a task in
                                                                this bucket</p>
                                                        </div>
                                                    </button>

                                                    {{-- Delete Bucket --}}
                                                    <button type="button"
                                                        @click="open = false; promptDeleteBucketModal({{ $col->id }}, '{{ addslashes($col->name) }}', {{ $colTasks->count() }})"
                                                        class="w-full text-left px-2.5 py-2 rounded-xl hover:bg-red-50 dark:hover:bg-red-950/30 transition flex items-center gap-3 cursor-pointer group">
                                                        <div
                                                            class="w-8 h-8 rounded-xl bg-red-50 dark:bg-red-950/60 border border-red-200/60 dark:border-red-800/60 flex items-center justify-center text-red-600 dark:text-red-400 shrink-0 group-hover:scale-105 transition-transform">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                        </div>
                                                        <div class="flex-1 min-w-0">
                                                            <p class="text-xs font-bold text-red-600 dark:text-red-400">Delete
                                                                Bucket</p>
                                                            <p class="text-[10px] text-red-400/90 dark:text-red-400/80 truncate">
                                                                Move tasks or delete completely</p>
                                                        </div>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Full Width + Add Task Button below Header --}}
                                        @if(!auth()->user()->hasRole('client'))
                                            <div>
                                                <button type="button" @click="openQuickAddTask({{ $col->id }})"
                                                    class="w-full py-2 px-3 rounded-xl border border-dashed border-gray-300/80 dark:border-gray-700 bg-white dark:bg-gray-800 hover:bg-blue-50/50 hover:border-blue-300 dark:hover:border-blue-700 text-xs font-bold text-gray-500 hover:text-blue-600 transition flex items-center justify-center gap-1.5 shadow-2xs cursor-pointer">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M12 4v16m8-8H4" />
                                                    </svg>
                                                    Add Task
                                                </button>
                                            </div>
                                        @endif

                                        {{-- Cards Droppable Container --}}
                                        <div id="detail-cards-column-{{ $col->id }}" data-column-id="{{ $col->id }}"
                                            class="project-detail-cards-dropzone space-y-3 min-h-[160px] pb-6">
                                            @foreach($colTasks as $task)
                                                @include('sprints._kanban_card', ['task' => $task, 'col' => $col])
                                            @endforeach
                                        </div>

                                        {{-- Inline Quick Add Input (if active) --}}
                                        <div x-show="quickAddColumnId === {{ $col->id }}" x-cloak
                                            class="p-3 border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-2xl shadow-sm">
                                            <form @submit.prevent="submitQuickTask({{ $col->id }})" class="space-y-2">
                                                <textarea x-model="quickTaskTitle" x-ref="quickTaskInput_{{ $col->id }}" rows="2"
                                                    placeholder="Task title and press Enter..."
                                                    class="w-full text-xs p-2.5 rounded-xl border border-blue-300 dark:border-blue-700 bg-gray-50 dark:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20"></textarea>
                                                <div class="flex items-center justify-end gap-2">
                                                    <button type="button" @click="quickAddColumnId = null; quickTaskTitle = ''"
                                                        class="px-2.5 py-1 text-xs text-gray-500 hover:text-gray-700">Cancel</button>
                                                    <button type="submit"
                                                        class="px-3 py-1 bg-blue-600 text-white rounded-lg text-xs font-bold shadow-2xs hover:bg-blue-700">Save
                                                        Task</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach

                                {{-- + Add Another Bucket Column at the End (Matching Reference Design) --}}
                                @if(!auth()->user()->hasRole('client'))
                                    <div class="add-bucket-column-wrapper w-80 shrink-0">
                                        <button type="button" @click="openCreateBucketModal()"
                                            class="w-full h-[220px] rounded-2xl border-2 border-dashed border-gray-200 dark:border-gray-700 hover:border-blue-500 dark:hover:border-blue-500 bg-white/60 dark:bg-gray-850/40 hover:bg-blue-50/50 dark:hover:bg-blue-950/20 transition-all flex flex-col items-center justify-center gap-2.5 group cursor-pointer shadow-2xs p-6 text-center">
                                            <div
                                                class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-800 group-hover:bg-blue-600 group-hover:text-white text-gray-400 flex items-center justify-center transition-colors shadow-2xs">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                        d="M12 4v16m8-8H4" />
                                                </svg>
                                            </div>
                                            <div>
                                                <span
                                                    class="text-xs font-bold text-gray-700 dark:text-gray-300 group-hover:text-blue-600 transition-colors block">Add
                                                    Another Bucket</span>
                                                <span class="text-[11px] text-gray-400 block mt-0.5">e.g. Backlog, Blocked,
                                                    Testing</span>
                                            </div>
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- ============================================================
                    LIST VIEW (WITH DRAG & DROP & EXACT REFERENCE DESIGN)
                    ============================================================ --}}
                    @if($columns->isNotEmpty())
                        <div x-show="taskView === 'list'" x-cloak class="pt-1 w-full max-w-full min-w-0">
                            <div
                                class="bg-white dark:bg-gray-850 rounded-2xl border border-gray-200/80 dark:border-gray-700 p-6 shadow-2xs space-y-6">
                                {{-- Table Header Row --}}
                                <div
                                    class="grid grid-cols-12 gap-2 text-[12px] font-bold text-gray-400 dark:text-gray-500 pb-3 border-b border-gray-100 dark:border-gray-800 px-3 items-center">
                                    <div class="col-span-4">Name</div>
                                    <div class="col-span-1">Priority</div>
                                    <div class="col-span-1">Assignee</div>
                                    <div class="col-span-2">Labels</div>
                                    <div class="col-span-2">Due Date</div>
                                    <div class="col-span-1">Checklists</div>
                                    <div class="col-span-1 text-right">Details</div>
                                </div>

                                {{-- Buckets Accordion List --}}
                                @foreach($columns as $col)
                                    @php
                                        $colTasks = $project->tasks->where('board_column_id', $col->id)->sortBy('sort_order');
                                        $dotColor = \App\Support\BoardColumnPalette::dot($col->color);
                                        $pillBg = \App\Support\BoardColumnPalette::pill($col->color);
                                        $slug = strtolower($col->slug ?? '');
                                    @endphp
                                    <div class="space-y-2" x-data="{ accordionOpen: true }">
                                        {{-- Bucket Accordion Header --}}
                                        <div class="flex items-center gap-2.5 cursor-pointer pt-1"
                                            @click="accordionOpen = !accordionOpen">
                                            <svg class="w-4 h-4 text-gray-400 transition-transform duration-200"
                                                :class="accordionOpen ? '' : '-rotate-90'" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                    d="M19 9l-7 7-7-7" />
                                            </svg>

                                            @if(in_array($slug, ['todo', 'to_do', 'backlog']) && $col->color === 'slate')
                                                <span
                                                    class="column-badge inline-flex items-center gap-1.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 font-bold px-3 py-0.5 rounded-full text-xs shadow-2xs">
                                                    <svg class="w-3.5 h-3.5 text-gray-500 shrink-0" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                    </svg>
                                                    <span>{{ $col->name }}</span>
                                                </span>
                                            @else
                                                <span
                                                    class="column-badge inline-flex items-center gap-1.5 {{ $pillBg }} text-white font-bold px-3 py-0.5 rounded-full text-xs shadow-2xs">
                                                    {!! $col->renderIcon('w-3 h-3 text-white shrink-0') !!}
                                                    <span>{{ $col->name }}</span>
                                                </span>
                                            @endif

                                            <span class="text-xs text-gray-400 font-semibold">{{ $colTasks->count() }}</span>
                                        </div>

                                        {{-- Task Rows with SortableJS --}}
                                        <div x-show="accordionOpen" class="space-y-1 pt-1">
                                            <div id="list-tasks-column-{{ $col->id }}" data-column-id="{{ $col->id }}"
                                                data-column-name="{{ $col->name }}"
                                                class="project-detail-list-dropzone space-y-1 min-h-[44px]">
                                                @foreach($colTasks as $task)
                                                    @php
                                                        $allMembers = $task->members->isNotEmpty() ? $task->members : ($task->assignee ? collect([$task->assignee]) : collect());
                                                        $isDone = $task->isDone() || ($col->is_done ?? false) || in_array($task->status, ['done', 'completed']);
                                                        $overdue = !$isDone && $task->isOverdue();
                                                        $days = $task->daysRemaining();

                                                        $doneCount = $task->checklists->flatMap->items->where('is_done', true)->count();
                                                        $totalCount = $task->checklists->flatMap->items->count();
                                                        $pctChecklist = $totalCount > 0 ? round(($doneCount / $totalCount) * 100) : 0;

                                                        $pListCfg = [
                                                            'urgent' => ['class' => 'text-rose-600', 'label' => 'Urgent'],
                                                            'critical' => ['class' => 'text-rose-600', 'label' => 'Urgent'],
                                                            'high' => ['class' => 'text-amber-500', 'label' => 'High'],
                                                            'medium' => ['class' => 'text-blue-600', 'label' => 'Normal'],
                                                            'low' => ['class' => 'text-gray-400', 'label' => 'Low'],
                                                        ];
                                                        $plc = $pListCfg[$task->priority] ?? $pListCfg['medium'];

                                                        $lblPalette = [
                                                            'ux stages' => 'bg-rose-100/80 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 font-bold text-[10.5px] uppercase tracking-wider',
                                                            'research' => 'bg-amber-100/80 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 font-bold text-[10.5px] uppercase tracking-wider',
                                                            'it frontend' => 'bg-sky-100/80 text-sky-800 dark:bg-sky-950/60 dark:text-sky-300 font-bold text-[10.5px] uppercase tracking-wider',
                                                            'core' => 'bg-purple-100/80 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300 font-bold text-[10.5px] uppercase tracking-wider',
                                                            'design system' => 'bg-purple-100/80 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300 font-bold text-[10.5px] uppercase tracking-wider',
                                                            'backend' => 'bg-emerald-100/80 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 font-bold text-[10.5px] uppercase tracking-wider',
                                                            'api' => 'bg-amber-100/80 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 font-bold text-[10.5px] uppercase tracking-wider',
                                                            'frontend' => 'bg-teal-100/80 text-teal-800 dark:bg-teal-950/60 dark:text-teal-300 font-bold text-[10.5px] uppercase tracking-wider',
                                                            'qa' => 'bg-purple-100/80 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300 font-bold text-[10.5px] uppercase tracking-wider',
                                                            'performance' => 'bg-rose-100/80 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 font-bold text-[10.5px] uppercase tracking-wider',
                                                            'auth' => 'bg-amber-100/80 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 font-bold text-[10.5px] uppercase tracking-wider',
                                                            'data' => 'bg-rose-100/80 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 font-bold text-[10.5px] uppercase tracking-wider',
                                                            'infra' => 'bg-purple-100/80 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300 font-bold text-[10.5px] uppercase tracking-wider',
                                                        ];
                                                    @endphp
                                                    <div class="list-task-row grid grid-cols-12 gap-2 py-2.5 px-3 hover:bg-gray-50/90 dark:hover:bg-gray-800/60 rounded-xl transition-all items-center group cursor-pointer border border-transparent hover:border-gray-100 dark:hover:border-gray-750"
                                                        data-task-id="{{ $task->id }}" data-priority="{{ $task->priority }}"
                                                        data-assignee-ids="{{ $allMembers->pluck('id')->join(',') }}"
                                                        data-labels="{{ $task->labels->pluck('id')->join(',') }}"
                                                        data-due="{{ $task->due_date ? $task->due_date->format('Y-m-d') : '' }}"
                                                        data-is-done="{{ $isDone ? '1' : '0' }}"
                                                        data-sprint-id="{{ $task->sprint_id ?? '' }}"
                                                        data-milestone-id="{{ $task->milestone_id ?? '' }}"
                                                        @click="openTask({{ $task->id }})">
                                                        {{-- 1. Name + Drag handle + Checkbox (col-span-4) --}}
                                                        <div class="col-span-4 flex items-center gap-2.5 min-w-0 pr-2">
                                                            <span
                                                                class="list-drag-handle cursor-grab active:cursor-grabbing text-gray-300 group-hover:text-gray-500 p-0.5 shrink-0"
                                                                @click.stop>
                                                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                                                                    <path
                                                                        d="M9 5a2 2 0 11-4 0 2 2 0 014 0zM9 12a2 2 0 11-4 0 2 2 0 014 0zM9 19a2 2 0 11-4 0 2 2 0 014 0zM19 5a2 2 0 11-4 0 2 2 0 014 0zM19 12a2 2 0 11-4 0 2 2 0 014 0zM19 19a2 2 0 11-4 0 2 2 0 014 0z" />
                                                                </svg>
                                                            </span>
                                                            <button type="button"
                                                                @click.stop="toggleTaskComplete({{ $task->id }}, {{ $isDone ? 'false' : 'true' }})"
                                                                class="w-4 h-4 rounded-full flex items-center justify-center transition-colors shrink-0 {{ $isDone ? 'bg-emerald-500 text-white' : 'border-2 border-gray-300 hover:border-emerald-500 text-transparent' }}">
                                                                <svg class="w-2.5 h-2.5 stroke-[3]" fill="none" stroke="currentColor"
                                                                    viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        d="M5 13l4 4L19 7" />
                                                                </svg>
                                                            </button>
                                                            <span
                                                                class="text-xs font-bold text-gray-800 dark:text-gray-100 group-hover:text-blue-600 truncate {{ $isDone ? 'line-through text-gray-400 dark:text-gray-500' : '' }}">
                                                                {{ $task->title }}
                                                            </span>
                                                        </div>

                                                        {{-- 2. Priority (col-span-1) --}}
                                                        <div
                                                            class="col-span-1 list-priority-container flex items-center gap-1.5 text-xs font-bold {{ $plc['class'] }}">
                                                            <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                                                <path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                                                            </svg>
                                                            <span class="truncate">{{ $plc['label'] }}</span>
                                                        </div>

                                                        {{-- 3. Assignee (col-span-1) --}}
                                                        <div class="col-span-1 list-assignee-container flex items-center">
                                                            @if($allMembers->isNotEmpty())
                                                                @php $assignee = $allMembers->first(); @endphp
                                                                @if($assignee->avatar)
                                                                    <img src="{{ Storage::url($assignee->avatar) }}" alt="{{ $assignee->name }}"
                                                                        title="{{ $assignee->name }}"
                                                                        class="w-6 h-6 rounded-full object-cover shadow-2xs">
                                                                @else
                                                                    <div class="w-6 h-6 rounded-full ring-2 ring-white dark:ring-gray-800 text-white flex items-center justify-center text-[10px] font-bold shadow-2xs"
                                                                        style="background-color: {{ $assignee->avatarColor() }};"
                                                                        title="{{ $assignee->name }}">
                                                                        {{ $assignee->initials() }}
                                                                    </div>
                                                                @endif
                                                            @else
                                                                <span class="text-xs text-gray-300">—</span>
                                                            @endif
                                                        </div>

                                                        {{-- 4. Labels (col-span-2) --}}
                                                        <div
                                                            class="col-span-2 list-labels-container flex items-center gap-1.5 flex-wrap">
                                                            @if($task->labels->isNotEmpty())
                                                                @foreach($task->labels->take(2) as $lbl)
                                                                    @php
                                                                        $lKey = strtolower(trim($lbl->name));
                                                                        $lStyle = $lblPalette[$lKey] ?? null;
                                                                        if (!$lStyle) {
                                                                            $c = $lbl->colorClasses();
                                                                            $lStyle = $c['bg'] . ' ' . $c['text'];
                                                                        }
                                                                    @endphp
                                                                    <span
                                                                        class="inline-flex items-center px-2 py-0.5 rounded-md text-[10.5px] font-bold {{ $lStyle }} truncate max-w-[120px]">
                                                                        {{ $lbl->name }}
                                                                    </span>
                                                                @endforeach
                                                                @if($task->labels->count() > 2)
                                                                    <span
                                                                        class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] font-bold bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 shrink-0">
                                                                        +{{ $task->labels->count() - 2 }}
                                                                    </span>
                                                                @endif
                                                            @else
                                                                <span class="text-xs text-gray-300">—</span>
                                                            @endif
                                                        </div>

                                                        {{-- 5. Due Date (col-span-2) --}}
                                                        <div class="col-span-2 list-due-container flex items-center text-xs">
                                                            @if($isDone)
                                                                <span
                                                                    class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400">
                                                                    <svg class="w-3 h-3 shrink-0 text-emerald-500" fill="none"
                                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                                            stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                                    </svg>
                                                                    Completed {{ $task->updated_at?->format('d M') ?? '' }}
                                                                </span>
                                                            @elseif($task->due_date)
                                                                @if($overdue)
                                                                    <span
                                                                        class="inline-flex items-center gap-1 text-[11px] font-bold text-red-600 dark:text-red-400">
                                                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500 shrink-0"></span>
                                                                        {{ $task->due_date->format('d M') }} (Overdue)
                                                                    </span>
                                                                @elseif($days !== null && $days <= 2)
                                                                    <span
                                                                        class="inline-flex items-center gap-1 text-[11px] font-bold text-red-500 dark:text-red-400">
                                                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500 shrink-0"></span>
                                                                        Due: {{ $task->due_date->format('d M') }}
                                                                    </span>
                                                                @else
                                                                    <span
                                                                        class="inline-flex items-center gap-1 text-[11px] font-medium text-gray-500 dark:text-gray-400">
                                                                        <svg class="w-3 h-3 shrink-0 text-gray-400" fill="none"
                                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                                stroke-width="2"
                                                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                                        </svg>
                                                                        {{ $task->due_date->format('d M') }}
                                                                    </span>
                                                                @endif
                                                            @else
                                                                <span class="text-[11px] text-gray-300 dark:text-gray-600">—</span>
                                                            @endif
                                                        </div>

                                                        {{-- 6. Checklists (col-span-1) --}}
                                                        <div class="col-span-1 list-checklist-container flex items-center">
                                                            @if($totalCount > 0)
                                                                <span
                                                                    class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[11px] font-semibold {{ $pctChecklist === 100 ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400' : 'bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400' }}">
                                                                    <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor"
                                                                        viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                                            stroke-width="2"
                                                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                                                    </svg>
                                                                    <span>{{ $doneCount }}/{{ $totalCount }}</span>
                                                                </span>
                                                            @else
                                                                <span class="text-[11px] text-gray-300 dark:text-gray-600">—</span>
                                                            @endif
                                                        </div>

                                                        {{-- 7. Details (col-span-1: Comments, Attachments, Link Arrow) --}}
                                                        <div
                                                            class="col-span-1 list-details-container flex items-center justify-end gap-2 text-xs">
                                                            {{-- Comments count --}}
                                                            <span
                                                                class="inline-flex items-center gap-1 text-[11px] {{ $task->comments_count > 0 ? 'text-gray-500 dark:text-gray-400 font-medium' : 'text-gray-300 dark:text-gray-600' }}"
                                                                title="{{ $task->comments_count }} comments">
                                                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor"
                                                                    viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2"
                                                                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                                                </svg>
                                                                <span>{{ $task->comments_count }}</span>
                                                            </span>

                                                            {{-- Attachments count --}}
                                                            @if($task->attachments_count > 0)
                                                                <span
                                                                    class="inline-flex items-center gap-1 text-[11px] text-gray-500 dark:text-gray-400 font-medium"
                                                                    title="{{ $task->attachments_count }} attachments">
                                                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor"
                                                                        viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                                            stroke-width="2"
                                                                            d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                                                    </svg>
                                                                    <span>{{ $task->attachments_count }}</span>
                                                                </span>
                                                            @endif

                                                            {{-- Detail Link Arrow --}}
                                                            <svg class="w-4 h-4 text-gray-300 group-hover:text-gray-500 dark:text-gray-600 dark:group-hover:text-gray-400 shrink-0"
                                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                    d="M9 5l7 7-7 7" />
                                                            </svg>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>

                                            {{-- Inline Quick Add Input (if active in List View) --}}
                                            <div x-show="quickAddColumnId === {{ $col->id }}" x-cloak
                                                class="my-2 p-2.5 bg-blue-50/40 dark:bg-blue-950/20 rounded-xl border border-blue-200 dark:border-blue-800/50 shadow-2xs">
                                                <form @submit.prevent="submitQuickTask({{ $col->id }})"
                                                    class="flex items-center gap-3">
                                                    <div
                                                        class="w-4 h-4 rounded-full border-2 border-dashed border-gray-300 shrink-0 ml-1">
                                                    </div>
                                                    <input type="text" x-model="quickTaskTitle"
                                                        x-ref="quickTaskListInput_{{ $col->id }}"
                                                        @keydown.escape="quickAddColumnId = null; quickTaskTitle = ''"
                                                        placeholder="Type task title and press Enter..."
                                                        class="flex-1 text-xs font-semibold px-3.5 py-2 rounded-xl border border-blue-300 dark:border-blue-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                                    <div class="flex items-center gap-2 shrink-0">
                                                        <button type="button" @click="quickAddColumnId = null; quickTaskTitle = ''"
                                                            class="px-2.5 py-1.5 text-xs font-medium text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 cursor-pointer">
                                                            Cancel
                                                        </button>
                                                        <button type="submit"
                                                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-2xs cursor-pointer transition">
                                                            Save Task
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>

                                            {{-- Bucket Bottom Actions --}}
                                            <div class="flex items-center justify-between px-3 pt-2 text-xs">
                                                @if(!auth()->user()->hasRole('client'))
                                                    <button type="button" x-show="quickAddColumnId !== {{ $col->id }}"
                                                        @click="openQuickAddTask({{ $col->id }})"
                                                        class="inline-flex items-center gap-1.5 text-xs font-bold text-gray-400 hover:text-blue-600 transition cursor-pointer pl-6">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M12 4v16m8-8H4" />
                                                        </svg>
                                                        Add task
                                                    </button>
                                                    <div x-show="quickAddColumnId === {{ $col->id }}"></div>
                                                @else
                                                    <div></div>
                                                @endif

                                                <span class="text-[11px] font-semibold text-gray-400">Count
                                                    {{ $colTasks->count() }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- ============================================================
                CREATE & EDIT BUCKET MODAL DIALOG
                ============================================================ --}}
                <div x-show="showBucketModal" x-cloak
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
                    <div class="bg-white dark:bg-gray-850 rounded-3xl border border-gray-200 dark:border-gray-700 shadow-2xl w-full max-w-md overflow-hidden transform transition-all"
                        @click.away="showBucketModal = false">
                        {{-- Modal Header --}}
                        <div
                            class="px-6 py-5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-2xl bg-blue-50 dark:bg-blue-950/60 border border-blue-200/60 flex items-center justify-center text-blue-600 dark:text-blue-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2H4V5zm0 4h16v10a1 1 0 01-1 1H5a1 1 0 01-1-1V9z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-black text-gray-900 dark:text-white"
                                        x-text="bucketModalMode === 'edit' ? 'Edit Bucket' : 'Create New Bucket'">Create New
                                        Bucket</h3>
                                    <p class="text-[11px] text-gray-400">Define pipeline phase and cadence rules</p>
                                </div>
                            </div>
                            <button type="button" @click="showBucketModal = false"
                                class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        {{-- Form Content --}}
                        <form @submit.prevent="saveBucket()" class="p-6 space-y-5">
                            {{-- Bucket Title --}}
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">Bucket Title /
                                    Name</label>
                                <div class="relative">
                                    <input type="text" x-model="bucketForm.name"
                                        placeholder="e.g. Quality Assurance & Testing" required
                                        class="w-full text-xs font-semibold px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                                    <span x-show="bucketForm.name.trim().length > 0"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-emerald-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                    </span>
                                </div>
                            </div>

                            {{-- Bucket Icon Selector --}}
                            <div class="space-y-2">
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">Bucket Icon</label>
                                <div class="grid grid-cols-7 gap-2">
                                    <template
                                        x-for="ic in ['clipboard', 'flame', 'shield', 'check', 'bug', 'rocket', 'window']"
                                        :key="ic">
                                        <button type="button" @click="bucketForm.icon = ic"
                                            :class="bucketForm.icon === ic ? 'bg-blue-600 text-white border-blue-600 shadow-xs' : 'bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:bg-gray-100'"
                                            class="h-10 rounded-xl border flex items-center justify-center text-sm transition-all cursor-pointer">
                                            <template x-if="ic === 'clipboard'">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                </svg>
                                            </template>
                                            <template x-if="ic === 'flame'">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                                                </svg>
                                            </template>
                                            <template x-if="ic === 'shield'">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                                </svg>
                                            </template>
                                            <template x-if="ic === 'check'">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M5 13l4 4L19 7" />
                                                </svg>
                                            </template>
                                            <template x-if="ic === 'bug'">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0a8 8 0 01-8 8m0 0a8 8 0 01-8-8m8-8a8 8 0 018 8m-8-8a8 8 0 00-8 8" />
                                                </svg>
                                            </template>
                                            <template x-if="ic === 'rocket'">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                                                </svg>
                                            </template>
                                            <template x-if="ic === 'window'">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2H4V5zm0 4h16v10a1 1 0 01-1 1H5a1 1 0 01-1-1V9z" />
                                                </svg>
                                            </template>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            {{-- Theme Accent Color --}}
                            <div class="space-y-2">
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">Theme Accent
                                    Color</label>
                                <div class="flex items-center gap-3">
                                    @php
                                        $paletteOptions = [
                                            ['key' => 'blue', 'bg' => 'bg-blue-600', 'label' => 'Blue'],
                                            ['key' => 'purple', 'bg' => 'bg-purple-600', 'label' => 'Purple'],
                                            ['key' => 'teal', 'bg' => 'bg-cyan-500', 'label' => 'Cyan'],
                                            ['key' => 'green', 'bg' => 'bg-emerald-600', 'label' => 'Green'],
                                            ['key' => 'red', 'bg' => 'bg-red-600', 'label' => 'Red'],
                                            ['key' => 'slate', 'bg' => 'bg-slate-700', 'label' => 'Slate'],
                                        ];
                                    @endphp
                                    @foreach($paletteOptions as $pCol)
                                        <button type="button" @click="bucketForm.color = '{{ $pCol['key'] }}'"
                                            class="w-8 h-8 rounded-full {{ $pCol['bg'] }} flex items-center justify-center text-white transition-transform cursor-pointer"
                                            :class="bucketForm.color === '{{ $pCol['key'] }}' ? 'ring-4 ring-blue-200 scale-110' : 'hover:scale-105'">
                                            <span x-show="bucketForm.color === '{{ $pCol['key'] }}'">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                        d="M5 13l4 4L19 7" />
                                                </svg>
                                            </span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Actions --}}
                            <div
                                class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100 dark:border-gray-800">
                                <button type="button" @click="showBucketModal = false"
                                    class="px-4 py-2 text-xs font-bold text-gray-500 hover:text-gray-700 cursor-pointer">
                                    Cancel
                                </button>
                                <button type="submit" :disabled="isSavingBucket || !bucketForm.name.trim()"
                                    class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-sm transition disabled:opacity-50 cursor-pointer">
                                    <span
                                        x-text="isSavingBucket ? 'Saving...' : (bucketModalMode === 'edit' ? 'Save Changes' : 'Create Bucket')">Create
                                        Bucket</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ============================================================
                DELETE BUCKET CONFIRMATION MODAL
                ============================================================ --}}
                <div x-show="showDeleteBucketModal" x-cloak
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
                    <div class="bg-white dark:bg-gray-850 rounded-3xl border border-gray-200 dark:border-gray-700 shadow-2xl w-full max-w-md overflow-hidden transform transition-all"
                        @click.away="showDeleteBucketModal = false">
                        <div class="p-6 space-y-4">
                            {{-- Icon + Title --}}
                            <div class="flex items-center gap-3.5">
                                <div
                                    class="w-12 h-12 rounded-2xl bg-red-50 dark:bg-red-950/60 border border-red-200 dark:border-red-800/80 flex items-center justify-center text-red-600 dark:text-red-400 shrink-0">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-black text-gray-900 dark:text-white">Delete Bucket</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Bucket: <strong
                                            class="text-gray-800 dark:text-gray-200"
                                            x-text="deleteBucketData.name"></strong></p>
                                </div>
                            </div>

                            {{-- Warning text & Task handling --}}
                            <template x-if="deleteBucketData.taskCount > 0">
                                <div
                                    class="p-3.5 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/70 rounded-2xl space-y-2.5 text-xs text-amber-900 dark:text-amber-300">
                                    <p class="font-semibold flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        <span>This bucket contains <strong x-text="deleteBucketData.taskCount"></strong>
                                            task(s).</span>
                                    </p>

                                    <div class="space-y-2 pt-1">
                                        <label class="block text-[11px] font-bold text-gray-700 dark:text-gray-300">Choose
                                            what to do with the tasks:</label>

                                        <div class="space-y-2">
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="radio" value="move" x-model="deleteBucketData.action"
                                                    class="text-blue-600 focus:ring-blue-500">
                                                <span class="text-xs text-gray-700 dark:text-gray-300 font-medium">Move
                                                    tasks to another bucket:</span>
                                            </label>

                                            <div x-show="deleteBucketData.action === 'move'" class="pl-6 pt-0.5">
                                                <select x-model="deleteBucketData.moveToColumnId"
                                                    class="w-full text-xs p-2.5 rounded-xl border border-gray-300 dark:border-gray-650 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-blue-500/20">
                                                    @foreach($columns as $otherCol)
                                                        <option value="{{ $otherCol->id }}"
                                                            x-show="deleteBucketData.id !== {{ $otherCol->id }}">
                                                            {{ $otherCol->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <label class="flex items-center gap-2 cursor-pointer pt-1">
                                                <input type="radio" value="delete_all" x-model="deleteBucketData.action"
                                                    class="text-red-600 focus:ring-red-500">
                                                <span class="text-xs text-red-600 dark:text-red-400 font-medium">Permanently
                                                    delete all tasks in this bucket</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <template x-if="deleteBucketData.taskCount === 0">
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    Are you sure you want to delete this empty bucket? This action cannot be undone.
                                </div>
                            </template>
                        </div>

                        {{-- Footer Actions --}}
                        <div
                            class="px-6 py-4 bg-gray-50 dark:bg-gray-800/60 border-t border-gray-100 dark:border-gray-800 flex items-center justify-end gap-2.5">
                            <button type="button" @click="showDeleteBucketModal = false"
                                class="px-4 py-2 text-xs font-semibold text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 cursor-pointer">
                                Cancel
                            </button>
                            <button type="button" @click="executeDeleteBucket()" :disabled="isDeletingBucket"
                                class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl shadow-xs transition disabled:opacity-50 flex items-center gap-1.5 cursor-pointer">
                                <span x-text="isDeletingBucket ? 'Deleting...' : 'Delete Bucket'">Delete Bucket</span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Include Task Detail Modal --}}
                @include('sprints._task_modal')

                {{-- ============================================================
                TAB: MILESTONES
                ============================================================ --}}
                <div x-show="tab === 'milestones'" x-cloak>
                    @include('projects.partials.milestones-content')
                </div>

                {{-- ============================================================
                TAB: TICKETS
                ============================================================ --}}
                <div x-show="tab === 'tickets'" x-cloak class="space-y-4 w-full max-w-full min-w-0">
                    <div class="bg-white dark:bg-gray-850 rounded-2xl shadow-2xs border border-gray-200/90 dark:border-gray-700/80 overflow-hidden">
                        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                            <div>
                                <h2 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    Recent Tickets
                                </h2>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Daftar tiket dan issue yang dilaporkan pada proyek ini</p>
                            </div>
                            <div class="flex gap-2">
                                <a href="{{ route('tickets.create', $project) }}"
                                    class="inline-flex items-center px-3.5 py-1.5 bg-blue-600 text-white text-xs font-bold rounded-xl hover:bg-blue-700 transition shadow-2xs">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" stroke-width="2"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Create Ticket
                                </a>
                                <a href="{{ route('tickets.index', $project) }}"
                                    class="inline-flex items-center px-3.5 py-1.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 text-xs font-bold rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition shadow-2xs">
                                    View All
                                </a>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                                <thead class="bg-gray-50 dark:bg-gray-800/50">
                                    <tr>
                                        <th
                                            class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                            Title</th>
                                        <th
                                            class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                            Reporter</th>
                                        <th
                                            class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                            Priority</th>
                                        <th
                                            class="px-6 py-3.5 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                            Status</th>
                                        <th class="px-6 py-3.5"></th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-850 divide-y divide-gray-100 dark:divide-gray-800">
                                    @forelse($recentTickets as $ticket)
                                        @php
                                            $tPriorityClass = [
                                                'low' => 'bg-gray-100 text-gray-600',
                                                'medium' => 'bg-blue-100 text-blue-700',
                                                'high' => 'bg-orange-100 text-orange-700',
                                                'urgent' => 'bg-red-100 text-red-700',
                                            ][$ticket->priority ?? 'medium'] ?? 'bg-gray-100 text-gray-600';
                                            $tStatusClass = [
                                                'open' => 'bg-blue-100 text-blue-700',
                                                'in_progress' => 'bg-yellow-100 text-yellow-700',
                                                'resolved' => 'bg-green-100 text-green-700',
                                                'closed' => 'bg-gray-100 text-gray-600',
                                            ][$ticket->status ?? 'open'] ?? 'bg-gray-100 text-gray-600';
                                        @endphp
                                        <tr class="hover:bg-gray-50 transition cursor-pointer"
                                            onclick="window.location='{{ route('tickets.show', $ticket) }}'">
                                            <td class="px-6 py-3">
                                                <a href="{{ route('tickets.show', $ticket) }}"
                                                    class="text-sm font-medium text-gray-900 hover:text-blue-600 transition-colors">
                                                    {{ $ticket->title }}
                                                </a>
                                            </td>
                                            <td class="px-6 py-3 text-sm text-gray-600">{{ $ticket->reporter->name ?? '-' }}
                                            </td>
                                            <td class="px-6 py-3">
                                                <span
                                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $tPriorityClass }}">
                                                    {{ ucfirst($ticket->priority ?? '-') }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-3">
                                                <span
                                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $tStatusClass }}">
                                                    {{ ucwords(str_replace('_', ' ', $ticket->status ?? '-')) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-3 text-right">
                                                <a href="{{ route('tickets.show', $ticket) }}"
                                                    class="text-gray-400 hover:text-blue-600 transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M9 5l7 7-7 7" />
                                                    </svg>
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-400">
                                                No tickets yet.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- ============================================================
                TAB: TEAM
                ============================================================ --}}
                <div x-show="tab === 'team'" x-cloak x-data="{ showAddMember: false }">

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                        {{-- ── Member List ── --}}
                        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 overflow-hidden">
                            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                                <div>
                                    <h2 class="text-base font-semibold text-gray-900">Team Members</h2>
                                    <p class="text-xs text-gray-400 mt-0.5">{{ $project->members->count() }} active members
                                    </p>
                                </div>
                                @if(!auth()->user()->hasRole('client'))
                                    <button @click="showAddMember = !showAddMember"
                                        :class="showAddMember ? 'bg-gray-100 text-gray-700' : 'bg-blue-600 text-white hover:bg-blue-700'"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                        </svg>
                                        <span x-text="showAddMember ? 'Close Form' : 'Add Member'"></span>
                                    </button>
                                @endif
                            </div>

                            <ul class="divide-y divide-gray-100">
                                @forelse($project->members as $member)
                                    <li class="flex items-center justify-between px-6 py-4 hover:bg-gray-50 transition group">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold text-sm uppercase shrink-0">
                                                {{ strtoupper(substr($member->user->name ?? '?', 0, 2)) }}
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">{{ $member->user->name ?? '-' }}
                                                </p>
                                                <p class="text-xs text-gray-500 mt-0.5">{{ $member->user->email ?? '' }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            @if(!auth()->user()->hasRole('client'))
                                                <form method="POST"
                                                    action="{{ route('projects.members.remove', [$project, $member->user]) }}"
                                                    class="opacity-0 group-hover:opacity-100 transition-opacity"
                                                    data-confirm-delete="{{ $member->user->name }} from team"
                                                    data-confirm-label="Remove from Team">
                                                    @csrf @method('DELETE')
                                                    <button type="submit"
                                                        class="p-1.5 text-gray-300 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m2 0a1 1 0 00-1-1h-4a1 1 0 00-1 1H5" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </li>
                                @empty
                                    <li class="px-6 py-12 text-center">
                                        <svg class="w-10 h-10 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <p class="text-sm text-gray-400">No team members yet.</p>
                                        <p class="text-xs text-gray-300 mt-1">Click "Add Member" to get started.</p>
                                    </li>
                                @endforelse
                            </ul>
                        </div>

                        {{-- ── Add Member Form (sidebar) ── --}}
                        @if(!auth()->user()->hasRole('client'))
                            <div x-show="showAddMember" x-cloak x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0">
                                <div class="bg-white rounded-xl border border-gray-200 p-6 sticky top-4">
                                    <div class="flex items-center justify-between mb-5">
                                        <div>
                                            <h3 class="text-sm font-semibold text-gray-900">Add Member</h3>
                                            <p class="text-xs text-gray-400 mt-0.5">Add developers to this project team</p>
                                        </div>
                                        <button @click="showAddMember = false"
                                            class="text-gray-400 hover:text-gray-600 transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>

                                    <form action="{{ route('projects.members.add', $project) }}" method="POST"
                                        class="space-y-4">
                                        @csrf

                                        {{-- Anggota --}}
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1.5">
                                                Members <span class="text-red-500">*</span>
                                            </label>
                                            <select id="member-select" name="user_id[]" required multiple class="w-full"
                                                style="width:100%">
                                                @foreach($companyUsers as $u)
                                                    <option value="{{ $u->id }}" {{ collect(old('user_id'))->contains($u->id) ? 'selected' : '' }}>
                                                        {{ $u->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('user_id')
                                                <p class="mt-1 text-xs text-red-500 flex items-center gap-1">
                                                    <svg class="w-3 h-3 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd"
                                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                    {{ $message }}
                                                </p>
                                            @enderror
                                        </div>

                                        <button type="submit"
                                            class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2.5 rounded-lg transition-colors flex items-center justify-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                            </svg>
                                            Add to Team
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endif

                    </div>
                </div>

                {{-- ============================================================
                TAB: KB (Knowledge Base)
                ============================================================ --}}
                <div x-show="tab === 'kb'" x-cloak>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h2 class="text-base font-semibold text-gray-900 mb-3">Knowledge Base</h2>
                        <a href="{{ route('kb.index', $project) }}"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                            Open Knowledge Base &rarr;
                        </a>
                    </div>
                </div>

                {{-- ============================================================
                TAB: TIMESHEET
                ============================================================ --}}
                <div x-show="tab === 'timesheet'" x-cloak>
                    @include('projects.partials.timesheet-content')
                </div>

                {{-- ============================================================
                TAB: SPRINTS
                ============================================================ --}}
                <div x-show="tab === 'sprints'" x-cloak>
                    @include('projects.partials.sprints-content')
                </div>

                {{-- ============================================================
                TAB: FILES
                ============================================================ --}}
                <div x-show="tab === 'files'" x-cloak>
                    @include('projects.partials.files-content')
                </div>

                {{-- ============================================================
                TAB: BUDGET
                ============================================================ --}}
                <div x-show="tab === 'budget'" x-cloak>
                    @php
                        $budgetUsed = $project->totalExpenses();
                        $budgetPct = $project->budgetUsedPercent();
                    @endphp
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-start justify-between mb-4">
                            <h2 class="text-base font-semibold text-gray-900">Budget Tracking</h2>
                            <a href="{{ route('budget.index', $project) }}"
                                class="text-sm text-blue-600 hover:text-blue-800 font-medium">Manage →</a>
                        </div>
                        @if($project->budget)
                            <div class="grid grid-cols-3 gap-4 mb-4">
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <p class="text-xs text-gray-400 mb-0.5">Total Budget</p>
                                    <p class="font-semibold text-gray-800 text-sm">Rp
                                        {{ number_format($project->budget, 0, ',', '.') }}
                                    </p>
                                </div>
                                <div class="bg-red-50 rounded-lg p-3">
                                    <p class="text-xs text-gray-400 mb-0.5">Spent</p>
                                    <p class="font-semibold text-red-600 text-sm">Rp
                                        {{ number_format($budgetUsed, 0, ',', '.') }}
                                    </p>
                                </div>
                                <div class="bg-green-50 rounded-lg p-3">
                                    <p class="text-xs text-gray-400 mb-0.5">Remaining</p>
                                    <p class="font-semibold text-green-600 text-sm">Rp
                                        {{ number_format($project->budget - $budgetUsed, 0, ',', '.') }}
                                    </p>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-xs text-gray-500 mb-1">
                                    <span>Budget usage</span>
                                    <span
                                        class="{{ $budgetPct >= 90 ? 'text-red-600 font-bold' : '' }}">{{ $budgetPct }}%</span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-2">
                                    <div class="h-2 rounded-full {{ $budgetPct >= 90 ? 'bg-red-500' : ($budgetPct >= 70 ? 'bg-yellow-500' : 'bg-blue-500') }}"
                                        style="width:{{ min(100, $budgetPct) }}%"></div>
                                </div>
                            </div>
                        @else
                            <p class="text-sm text-gray-400">Budget not set. <a href="{{ route('budget.index', $project) }}"
                                    class="text-blue-600 hover:underline">Manage budget →</a></p>
                        @endif
                    </div>
                </div>

                {{-- ============================================================
                TAB: RECURRING
                ============================================================ --}}
                <div x-show="tab === 'recurring'" x-cloak>
                    @include('projects.partials.recurring-content')
                </div>

                {{-- ============================================================
                TAB: PORTAL
                ============================================================ --}}
                <div x-show="tab === 'portal'" x-cloak>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h2 class="text-base font-semibold text-gray-900 mb-4">Client Portal</h2>
                        <p class="text-sm text-gray-500 mb-4">{{ $project->portalTokens()->count() }} portal link created.
                            Share a private link with clients to view project progress.</p>
                        <a href="{{ route('portal.index', $project) }}"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                            Manage Portal Links &rarr;
                        </a>
                    </div>
                </div>

                {{-- ============================================================
                TAB: NOTIFICATIONS (SLACK/DISCORD)
                ============================================================ --}}
                <div x-show="tab === 'notif'" x-cloak>
                    @include('projects.partials.notifications-content')
                </div>

                {{-- ============================================================
                TAB: CHAT
                ============================================================ --}}
                <div x-show="tab === 'chat'" x-cloak>
                    @include('projects.partials._chat')
                </div>

            </div>
            {{-- /MAIN CONTENT --}}
        </div>
        {{-- /sidebar + main flex wrapper --}}
    </div>
@endsection

@push('scripts')
    <script>
        function projectPageData() {
            return {
                tab: new URLSearchParams(window.location.search).get('tab') || 'tasks',
                taskView: localStorage.getItem('projecthub_task_view') || 'board',
                filterAssignee: '',
                filterAssigneeName: 'All',
                filterDueDate: '',
                filterDueDateName: 'Any time',
                filterLabel: '',
                filterLabelName: 'All',
                filterPriority: '',
                filterPriorityName: 'All',
                filterSprint: new URLSearchParams(window.location.search).get('sprint') || '',
                filterSprintName: 'All',
                filterMilestone: new URLSearchParams(window.location.search).get('milestone') || '',
                filterMilestoneName: 'All',
                searchQuery: '',

                // Bucket modal
                showBucketModal: false,
                bucketModalMode: 'create', // 'create' | 'edit'
                bucketEditId: null,
                bucketForm: {
                    name: '',
                    icon: 'clipboard',
                    color: 'blue'
                },
                isSavingBucket: false,
                isBootstrapping: false,

                // Delete Bucket modal
                showDeleteBucketModal: false,
                isDeletingBucket: false,
                deleteBucketData: {
                    id: null,
                    name: '',
                    taskCount: 0,
                    action: 'move',
                    moveToColumnId: ''
                },

                // Quick add
                quickAddColumnId: null,
                quickTaskTitle: '',

                initProjectPage() {
                    const urlParams = new URLSearchParams(window.location.search);
                    const sprintParam = urlParams.get('sprint');
                    const milestoneParam = urlParams.get('milestone');

                    if (sprintParam) {
                        this.tab = 'tasks';
                        this.filterSprint = sprintParam;
                        const sList = @json($project->sprints->map(fn($s) => ['id' => (string) $s->id, 'name' => $s->name]));
                        const foundS = sList.find(s => s.id === sprintParam);
                        if (foundS) this.filterSprintName = foundS.name;
                    }

                    if (milestoneParam) {
                        this.tab = 'tasks';
                        this.filterMilestone = milestoneParam;
                        const mList = @json($project->milestones->map(fn($m) => ['id' => (string) $m->id, 'title' => $m->title]));
                        const foundM = mList.find(m => m.id === milestoneParam);
                        if (foundM) this.filterMilestoneName = foundM.title;
                    }

                    this.$watch('taskView', (val) => {
                        localStorage.setItem('projecthub_task_view', val);
                        this.$nextTick(() => {
                            this.initSortables();
                            this.applyFilters();
                        });
                    });

                    this.$watch('tab', (val) => {
                        const url = new URL(window.location);
                        url.searchParams.set('tab', val);
                        window.history.replaceState({}, '', url);
                        if (val === 'tasks') {
                            this.$nextTick(() => {
                                this.initSortables();
                                this.applyFilters();
                            });
                        }
                    });

                    this.$nextTick(() => {
                        this.initSortables();
                        this.initBoardHorizontalScroll();
                        if (sprintParam || milestoneParam) {
                            this.applyFilters();
                        }
                    });
                },

                initBoardHorizontalScroll() {
                    const container = document.getElementById('project-detail-kanban-columns');
                    if (container && !container._wheelAttached) {
                        container._wheelAttached = true;
                        container.addEventListener('wheel', (e) => {
                            if (e.deltaY !== 0 && !e.shiftKey) {
                                const cardList = e.target.closest('.project-detail-cards-dropzone');
                                if (cardList) {
                                    const canScrollUp = cardList.scrollTop > 0 && e.deltaY < 0;
                                    const canScrollDown = Math.ceil(cardList.scrollTop + cardList.clientHeight) < cardList.scrollHeight && e.deltaY > 0;
                                    if (canScrollUp || canScrollDown) return;
                                }
                                e.preventDefault();
                                container.scrollLeft += e.deltaY * 0.9;
                            }
                        }, { passive: false });
                    }
                },

                initSortables() {
                    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content;
                    if (!CSRF) return;

                    // 1. Board Kanban Cards
                    const boardDropzones = document.querySelectorAll('.project-detail-cards-dropzone');
                    boardDropzones.forEach(zone => {
                        if (zone._sortableInstance) {
                            zone._sortableInstance.destroy();
                        }
                        zone._sortableInstance = new Sortable(zone, {
                            group: 'project-detail-cards',
                            animation: 180,
                            ghostClass: 'sortable-drag-ghost',
                            chosenClass: 'sortable-drag-chosen',
                            onEnd: async (evt) => {
                                const card = evt.item;
                                const taskId = card.dataset.taskId;
                                const targetCol = evt.to;
                                const targetColumnId = targetCol.dataset.columnId;

                                const cardElements = Array.from(targetCol.querySelectorAll('.kanban-card'));
                                const order = cardElements.map(el => parseInt(el.dataset.taskId, 10));

                                if (evt.from !== evt.to) {
                                    try {
                                        await fetch(`/projects/{{ $project->id }}/tasks/${taskId}/move`, {
                                            method: 'PATCH',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'X-CSRF-TOKEN': CSRF,
                                                'Accept': 'application/json'
                                            },
                                            body: JSON.stringify({ board_column_id: targetColumnId })
                                        });
                                    } catch (err) {
                                        console.error(err);
                                    }
                                }

                                try {
                                    await fetch(`/projects/{{ $project->id }}/tasks/reorder`, {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': CSRF,
                                            'Accept': 'application/json'
                                        },
                                        body: JSON.stringify({
                                            order: order,
                                            board_column_id: targetColumnId
                                        })
                                    });
                                } catch (err) {
                                    console.error(err);
                                }

                                this.updateCounters();
                            }
                        });
                    });

                    // 2. List View Tasks Dropzones
                    const listDropzones = document.querySelectorAll('.project-detail-list-dropzone');
                    listDropzones.forEach(zone => {
                        if (zone._sortableInstance) {
                            zone._sortableInstance.destroy();
                        }
                        zone._sortableInstance = new Sortable(zone, {
                            group: 'project-detail-list-tasks',
                            handle: '.list-drag-handle',
                            animation: 180,
                            ghostClass: 'sortable-drag-ghost',
                            chosenClass: 'sortable-drag-chosen',
                            onEnd: async (evt) => {
                                const row = evt.item;
                                const taskId = row.dataset.taskId;
                                const targetCol = evt.to;
                                const targetColumnId = targetCol.dataset.columnId;

                                const rowElements = Array.from(targetCol.querySelectorAll('.list-task-row'));
                                const order = rowElements.map(el => parseInt(el.dataset.taskId, 10));

                                if (evt.from !== evt.to) {
                                    try {
                                        await fetch(`/projects/{{ $project->id }}/tasks/${taskId}/move`, {
                                            method: 'PATCH',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'X-CSRF-TOKEN': CSRF,
                                                'Accept': 'application/json'
                                            },
                                            body: JSON.stringify({ board_column_id: targetColumnId })
                                        });
                                    } catch (err) {
                                        console.error(err);
                                    }
                                }

                                try {
                                    await fetch(`/projects/{{ $project->id }}/tasks/reorder`, {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': CSRF,
                                            'Accept': 'application/json'
                                        },
                                        body: JSON.stringify({
                                            order: order,
                                            board_column_id: targetColumnId
                                        })
                                    });
                                } catch (err) {
                                    console.error(err);
                                }

                                this.updateCounters();
                            }
                        });
                    });

                    // 3. Board Column Reordering (Drag & Drop Columns Left / Right)
                    const columnsContainer = document.getElementById('project-detail-kanban-columns');
                    if (columnsContainer) {
                        if (columnsContainer._sortableInstance) {
                            columnsContainer._sortableInstance.destroy();
                        }
                        columnsContainer._sortableInstance = new Sortable(columnsContainer, {
                            draggable: '.kanban-column',
                            handle: '.kanban-column-header',
                            animation: 200,
                            ghostClass: 'sortable-drag-ghost',
                            chosenClass: 'sortable-drag-chosen',
                            filter: 'button, input, textarea, a, .kanban-card, .add-bucket-column-wrapper',
                            preventOnFilter: false,
                            onEnd: async () => {
                                const colElements = Array.from(columnsContainer.querySelectorAll('.kanban-column'));
                                const colOrder = colElements.map(el => parseInt(el.dataset.columnId, 10)).filter(id => !isNaN(id));

                                try {
                                    await fetch(`/projects/{{ $project->id }}/board-columns/reorder`, {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': CSRF,
                                            'Accept': 'application/json'
                                        },
                                        body: JSON.stringify({ order: colOrder })
                                    });
                                } catch (err) {
                                    console.error(err);
                                }
                            }
                        });
                    }
                },

                updateCounters() {
                    // Update board counters
                    document.querySelectorAll('#project-detail-kanban-columns .kanban-column').forEach(col => {
                        const count = col.querySelectorAll('.kanban-card').length;
                        const counter = col.querySelector('.column-counter');
                        if (counter) counter.textContent = count;
                    });
                    // Update list counters
                    document.querySelectorAll('.project-detail-list-dropzone').forEach(dz => {
                        const count = dz.querySelectorAll('.list-task-row').length;
                        const card = dz.closest('.space-y-2');
                        if (card) {
                            const counter = card.querySelector('.text-xs.text-gray-400.font-semibold');
                            if (counter) counter.textContent = count;
                        }
                    });
                },

                applyFilters() {
                    const todayStr = new Date().toISOString().split('T')[0];
                    const now = new Date();
                    const dayOfWeek = now.getDay() || 7;
                    const startOfWeek = new Date(now);
                    startOfWeek.setDate(now.getDate() - dayOfWeek + 1);
                    startOfWeek.setHours(0, 0, 0, 0);
                    const endOfWeek = new Date(startOfWeek);
                    endOfWeek.setDate(startOfWeek.getDate() + 6);
                    endOfWeek.setHours(23, 59, 59, 999);

                    const filterItems = (elements, isList = false) => {
                        elements.forEach(el => {
                            const assigneeIds = (el.dataset.assigneeIds || '').split(',').map(s => s.trim()).filter(Boolean);
                            const priority = (el.dataset.priority || '').toLowerCase();
                            const labels = (el.dataset.labels || '').split(',').map(s => s.trim()).filter(Boolean);
                            const due = el.dataset.due || '';
                            const isDone = el.dataset.isDone === '1';
                            const text = (el.textContent || '').toLowerCase();

                            let match = true;

                            // Assignee filter
                            if (this.filterAssignee && !assigneeIds.includes(this.filterAssignee.toString())) {
                                match = false;
                            }

                            // Priority filter
                            if (this.filterPriority && priority !== this.filterPriority.toLowerCase()) {
                                match = false;
                            }

                            // Label filter
                            if (this.filterLabel && !labels.includes(this.filterLabel.toString())) {
                                match = false;
                            }

                            // Milestone filter
                            const milestoneId = (el.dataset.milestoneId || '').trim();
                            if (this.filterMilestone && milestoneId !== this.filterMilestone.toString()) {
                                match = false;
                            }

                            // Sprint filter
                            const sprintId = (el.dataset.sprintId || '').trim();
                            if (this.filterSprint && sprintId !== this.filterSprint.toString()) {
                                match = false;
                            }

                            // Due date filter
                            if (this.filterDueDate) {
                                if (this.filterDueDate === 'today' && due !== todayStr) match = false;
                                else if (this.filterDueDate === 'week') {
                                    if (!due) match = false;
                                    else {
                                        const d = new Date(due);
                                        if (d < startOfWeek || d > endOfWeek) match = false;
                                    }
                                }
                                else if (this.filterDueDate === 'overdue') {
                                    if (!due || isDone || due >= todayStr) match = false;
                                }
                                else if (this.filterDueDate === 'completed' && !isDone) match = false;
                            }

                            // Search query
                            if (this.searchQuery && !text.includes(this.searchQuery.toLowerCase())) {
                                match = false;
                            }

                            if (match) {
                                el.classList.remove('hidden');
                                el.style.display = '';
                            } else {
                                el.classList.add('hidden');
                                el.style.display = 'none';
                            }
                        });
                    };

                    filterItems(document.querySelectorAll('.kanban-card'));
                    filterItems(document.querySelectorAll('.list-task-row'), true);
                },

                clearAllFilters() {
                    this.filterAssignee = '';
                    this.filterAssigneeName = 'All';
                    this.filterDueDate = '';
                    this.filterDueDateName = 'Any time';
                    this.filterLabel = '';
                    this.filterLabelName = 'All';
                    this.filterPriority = '';
                    this.filterPriorityName = 'All';
                    this.filterSprint = '';
                    this.filterSprintName = 'All';
                    this.filterMilestone = '';
                    this.filterMilestoneName = 'All';
                    this.searchQuery = '';
                    const url = new URL(window.location);
                    url.searchParams.delete('sprint');
                    url.searchParams.delete('milestone');
                    window.history.replaceState({}, '', url);
                    this.applyFilters();
                },

                openCreateBucketModal() {
                    this.bucketModalMode = 'create';
                    this.bucketEditId = null;
                    this.bucketForm = { name: '', icon: 'clipboard', color: 'blue' };
                    this.showBucketModal = true;
                },

                openEditBucketModal(id, name, color, icon) {
                    this.bucketModalMode = 'edit';
                    this.bucketEditId = id;
                    this.bucketForm = {
                        name: name || '',
                        icon: icon || 'clipboard',
                        color: color || 'blue'
                    };
                    this.showBucketModal = true;
                },

                getIconSvg(iconName, cls = 'w-3.5 h-3.5') {
                    const icons = {
                        'clipboard': `<svg class="${cls}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>`,
                        'flame': `<svg class="${cls}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z"/></svg>`,
                        'shield': `<svg class="${cls}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>`,
                        'check': `<svg class="${cls}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>`,
                        'bug': `<svg class="${cls}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0a8 8 0 01-8 8m0 0a8 8 0 01-8-8m8-8a8 8 0 018 8m-8-8a8 8 0 00-8 8"/></svg>`,
                        'rocket': `<svg class="${cls}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>`,
                        'window': `<svg class="${cls}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2H4V5zm0 4h16v10a1 1 0 01-1 1H5a1 1 0 01-1-1V9z"/></svg>`,
                    };
                    return icons[iconName] || icons.clipboard;
                },

                getColBgClass(color) {
                    const map = {
                        slate: 'bg-slate-700', gray: 'bg-gray-700', zinc: 'bg-zinc-700',
                        red: 'bg-red-600', orange: 'bg-orange-600', amber: 'bg-amber-600',
                        yellow: 'bg-yellow-600', lime: 'bg-lime-600', green: 'bg-emerald-600',
                        emerald: 'bg-emerald-600', teal: 'bg-teal-600', cyan: 'bg-cyan-600',
                        sky: 'bg-sky-600', blue: 'bg-blue-600', indigo: 'bg-indigo-600',
                        violet: 'bg-violet-600', purple: 'bg-purple-600', fuchsia: 'bg-fuchsia-600',
                        pink: 'bg-pink-600', rose: 'bg-rose-600'
                    };
                    return map[color] || 'bg-blue-600';
                },

                async saveBucket() {
                    if (!this.bucketForm.name.trim()) return;
                    this.isSavingBucket = true;
                    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content;

                    try {
                        const url = this.bucketModalMode === 'edit'
                            ? `/projects/{{ $project->id }}/board-columns/${this.bucketEditId}`
                            : `/projects/{{ $project->id }}/board-columns`;
                        const method = this.bucketModalMode === 'edit' ? 'PUT' : 'POST';

                        const res = await fetch(url, {
                            method: method,
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': CSRF,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(this.bucketForm)
                        });

                        if (res.ok) {
                            const data = await res.json();
                            const col = data.column;

                            if (this.bucketModalMode === 'edit' && col) {
                                // Real-time update column header in DOM
                                const colEl = document.querySelector(`.kanban-column[data-column-id="${col.id}"]`);
                                if (colEl) {
                                    colEl.dataset.columnName = col.name;
                                    colEl.dataset.columnColor = col.color;
                                    colEl.dataset.columnIcon = col.icon;

                                    const badge = colEl.querySelector('.column-badge');
                                    if (badge) {
                                        const bgClasses = ['bg-blue-600', 'bg-purple-600', 'bg-cyan-600', 'bg-teal-600', 'bg-emerald-600', 'bg-green-600', 'bg-red-600', 'bg-rose-600', 'bg-slate-700', 'bg-gray-700', 'bg-amber-600', 'bg-pink-600', 'bg-indigo-600'];
                                        badge.classList.remove(...bgClasses);
                                        badge.classList.add(this.getColBgClass(col.color));

                                        const iconSvg = badge.querySelector('svg');
                                        if (iconSvg) {
                                            iconSvg.outerHTML = this.getIconSvg(col.icon, 'w-3.5 h-3.5 text-white shrink-0');
                                        }
                                        const titleEl = badge.querySelector('span.truncate');
                                        if (titleEl) titleEl.textContent = col.name;
                                    }
                                }
                                // Update List view header
                                const listDrop = document.getElementById(`list-tasks-column-${col.id}`);
                                if (listDrop) {
                                    const listBadge = listDrop.closest('.space-y-2')?.querySelector('.column-badge');
                                    if (listBadge) {
                                        const bgClasses = ['bg-blue-600', 'bg-purple-600', 'bg-cyan-600', 'bg-teal-600', 'bg-emerald-600', 'bg-green-600', 'bg-red-600', 'bg-rose-600', 'bg-slate-700', 'bg-gray-700', 'bg-amber-600', 'bg-pink-600', 'bg-indigo-600'];
                                        listBadge.classList.remove(...bgClasses);
                                        listBadge.classList.add(this.getColBgClass(col.color));

                                        const iconSvg = listBadge.querySelector('svg');
                                        if (iconSvg) {
                                            iconSvg.outerHTML = this.getIconSvg(col.icon, 'w-3 h-3 text-white shrink-0');
                                        }
                                        const titleSpan = listBadge.querySelector('span:last-child');
                                        if (titleSpan) titleSpan.textContent = col.name;
                                    }
                                }
                            } else if (this.bucketModalMode === 'create' && col) {
                                // Dynamically append new column in Board View
                                const boardCols = document.getElementById('project-detail-kanban-columns');
                                const addBucketBtn = boardCols?.querySelector('.add-bucket-column-wrapper') || boardCols?.querySelector('.w-80.shrink-0:last-child');
                                if (boardCols && addBucketBtn) {
                                    const bgClass = this.getColBgClass(col.color);
                                    const iconSvg = this.getIconSvg(col.icon, 'w-3.5 h-3.5 text-white shrink-0');
                                    const colHtml = `
                                        <div class="kanban-column w-80 shrink-0 flex flex-col space-y-3"
                                             data-column-id="${col.id}"
                                             data-column-slug="${col.slug}"
                                             data-column-name="${col.name}"
                                             data-column-color="${col.color}"
                                             data-column-icon="${col.icon}">
                                            <div class="kanban-column-header flex items-center justify-between gap-2 px-1 shrink-0 cursor-grab active:cursor-grabbing">
                                                <div class="flex items-center gap-1.5 min-w-0">
                                                    <span class="column-badge inline-flex items-center gap-1.5 ${bgClass} text-white font-bold text-xs uppercase tracking-wider px-3 py-1 rounded-full shadow-2xs truncate">
                                                        ${iconSvg}
                                                        <span class="truncate">${col.name}</span>
                                                        <span class="column-counter bg-white/25 text-white text-[10.5px] px-1.5 py-0.2 rounded-full font-bold">0</span>
                                                    </span>
                                                </div>
                                                <div class="relative shrink-0" x-data="{ open: false }">
                                                    <button type="button" @click="open = !open" @click.away="open = false"
                                                            class="w-7 h-7 flex items-center justify-center rounded-lg text-black hover:text-black hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700 transition cursor-pointer"
                                                            title="Bucket Options">
                                                        <svg class="w-4 h-4 text-black dark:text-white" fill="currentColor" viewBox="0 0 24 24">
                                                            <circle cx="5" cy="12" r="1.75"/>
                                                            <circle cx="12" cy="12" r="1.75"/>
                                                            <circle cx="19" cy="12" r="1.75"/>
                                                        </svg>
                                                    </button>
                                                    <div x-show="open" x-cloak
                                                         class="absolute right-0 top-full mt-1.5 w-64 bg-white dark:bg-gray-850 border border-gray-200/90 dark:border-gray-700 rounded-2xl shadow-xl p-2 z-50 space-y-1">
                                                        <div class="px-2.5 py-2 flex items-center justify-between gap-2 border-b border-gray-100 dark:border-gray-800 pb-2 mb-1">
                                                            <span class="text-xs font-bold uppercase truncate text-gray-900 dark:text-white">${col.name}</span>
                                                            <span class="text-[10.5px] font-bold px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded-full text-gray-600 dark:text-gray-300 shrink-0">0</span>
                                                        </div>
                                                        <p class="px-2.5 pt-1 text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">BUCKET ACTIONS</p>
                                                        <button type="button"
                                                                @click="open = false; openEditBucketModal(${col.id}, '${col.name.replace(/'/g, "\\'")}', '${col.color}', '${col.icon}')"
                                                                class="w-full text-left px-2.5 py-2 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800 transition flex items-center gap-3 cursor-pointer group">
                                                            <div class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-950/60 border border-blue-200/60 dark:border-blue-800/60 flex items-center justify-center text-blue-600 dark:text-blue-400 shrink-0 group-hover:scale-105 transition-transform">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                            </div>
                                                            <div class="flex-1 min-w-0">
                                                                <p class="text-xs font-bold text-gray-800 dark:text-gray-200">Edit Bucket</p>
                                                                <p class="text-[10px] text-gray-400 truncate">Rename, change color & icon</p>
                                                            </div>
                                                        </button>
                                                        <button type="button"
                                                                @click="open = false; openQuickAddTask(${col.id})"
                                                                class="w-full text-left px-2.5 py-2 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800 transition flex items-center gap-3 cursor-pointer group">
                                                            <div class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200/60 dark:border-emerald-800/60 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shrink-0 group-hover:scale-105 transition-transform">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                            </div>
                                                            <div class="flex-1 min-w-0">
                                                                <p class="text-xs font-bold text-gray-800 dark:text-gray-200">Add Task to Bucket</p>
                                                                <p class="text-[10px] text-gray-400 truncate">Quick create a task</p>
                                                            </div>
                                                        </button>
                                                        <button type="button"
                                                                @click="open = false; promptDeleteBucketModal(${col.id}, '${col.name.replace(/'/g, "\\'")}', 0)"
                                                                class="w-full text-left px-2.5 py-2 rounded-xl hover:bg-red-50 dark:hover:bg-red-950/30 transition flex items-center gap-3 cursor-pointer group">
                                                            <div class="w-8 h-8 rounded-xl bg-red-50 dark:bg-red-950/60 border border-red-200/60 dark:border-red-800/60 flex items-center justify-center text-red-600 dark:text-red-400 shrink-0 group-hover:scale-105 transition-transform">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                            </div>
                                                            <div class="flex-1 min-w-0">
                                                                <p class="text-xs font-bold text-red-600 dark:text-red-400">Delete Bucket</p>
                                                                <p class="text-[10px] text-red-400/90 dark:text-red-400/80 truncate">Move tasks or delete</p>
                                                            </div>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div>
                                                <button type="button"
                                                        @click="openQuickAddTask(${col.id})"
                                                        class="w-full py-2 px-3 rounded-xl border border-dashed border-gray-300/80 dark:border-gray-700 bg-white dark:bg-gray-800 hover:bg-blue-50/50 hover:border-blue-300 text-xs font-bold text-gray-500 hover:text-blue-600 transition flex items-center justify-center gap-1.5 shadow-2xs cursor-pointer">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                    Add Task
                                                </button>
                                            </div>
                                            <div id="detail-cards-column-${col.id}"
                                                 data-column-id="${col.id}"
                                                 class="project-detail-cards-dropzone space-y-3 min-h-[160px] pb-6">
                                            </div>
                                            <div x-show="quickAddColumnId === ${col.id}" x-cloak class="p-3 border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-2xl shadow-sm">
                                                <form @submit.prevent="submitQuickTask(${col.id})" class="space-y-2">
                                                    <textarea x-model="quickTaskTitle"
                                                              x-ref="quickTaskInput_${col.id}"
                                                              rows="2"
                                                              placeholder="Task title and press Enter..."
                                                              class="w-full text-xs p-2.5 rounded-xl border border-blue-300 dark:border-blue-700 bg-gray-50 dark:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20"></textarea>
                                                    <div class="flex items-center justify-end gap-2">
                                                        <button type="button" @click="quickAddColumnId = null; quickTaskTitle = ''" class="px-2.5 py-1 text-xs text-gray-500 hover:text-gray-700">Cancel</button>
                                                        <button type="submit" class="px-3 py-1 bg-blue-600 text-white rounded-lg text-xs font-bold shadow-2xs hover:bg-blue-700">Save Task</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    `;
                                    const tempDiv = document.createElement('div');
                                    tempDiv.innerHTML = colHtml.trim();
                                    if (tempDiv.firstElementChild) {
                                        boardCols.insertBefore(tempDiv.firstElementChild, addBucketBtn);
                                    }
                                }

                                // Also append List view accordion section
                                const listWrap = document.querySelector('[x-show="taskView === \'list\'"] .space-y-6');
                                if (listWrap) {
                                    const bgClass = this.getColBgClass(col.color);
                                    const listSecHtml = `
                                        <div class="space-y-2" x-data="{ accordionOpen: true }">
                                            <div class="flex items-center gap-2.5 cursor-pointer pt-1" @click="accordionOpen = !accordionOpen">
                                                <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="accordionOpen ? '' : '-rotate-90'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                                <span class="column-badge inline-flex items-center gap-1.5 ${bgClass} text-white font-bold px-3 py-0.5 rounded-full text-xs shadow-2xs">
                                                    ${this.getIconSvg(col.icon, 'w-3 h-3 text-white shrink-0')}
                                                    <span>${col.name}</span>
                                                </span>
                                                <span class="text-xs text-gray-400 font-semibold">0</span>
                                            </div>
                                            <div x-show="accordionOpen" class="space-y-1 pt-1">
                                                <div id="list-tasks-column-${col.id}"
                                                     data-column-id="${col.id}"
                                                     data-column-name="${col.name}"
                                                     class="project-detail-list-dropzone space-y-1 min-h-[44px]">
                                                </div>
                                                <div x-show="quickAddColumnId === ${col.id}" x-cloak class="my-2 p-2.5 bg-blue-50/40 dark:bg-blue-950/20 rounded-xl border border-blue-200 dark:border-blue-800/50 shadow-2xs">
                                                    <form @submit.prevent="submitQuickTask(${col.id})" class="flex items-center gap-3">
                                                        <div class="w-4 h-4 rounded-full border-2 border-dashed border-gray-300 shrink-0 ml-1"></div>
                                                        <input type="text"
                                                               x-model="quickTaskTitle"
                                                               x-ref="quickTaskListInput_${col.id}"
                                                               @keydown.escape="quickAddColumnId = null; quickTaskTitle = ''"
                                                               placeholder="Type task title and press Enter..."
                                                               class="flex-1 text-xs font-semibold px-3.5 py-2 rounded-xl border border-blue-300 dark:border-blue-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                                        <div class="flex items-center gap-2 shrink-0">
                                                            <button type="button" @click="quickAddColumnId = null; quickTaskTitle = ''" class="px-2.5 py-1.5 text-xs font-medium text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 cursor-pointer">
                                                                Cancel
                                                            </button>
                                                            <button type="submit" class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-2xs cursor-pointer transition">
                                                                Save Task
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                                <div class="flex items-center justify-between px-3 pt-2 text-xs">
                                                    <button type="button"
                                                            x-show="quickAddColumnId !== ${col.id}"
                                                            @click="openQuickAddTask(${col.id})"
                                                            class="inline-flex items-center gap-1.5 text-xs font-bold text-gray-400 hover:text-blue-600 transition cursor-pointer pl-6">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                        Add task
                                                    </button>
                                                    <div x-show="quickAddColumnId === ${col.id}"></div>
                                                    <span class="text-[11px] font-semibold text-gray-400">Count 0</span>
                                                </div>
                                            </div>
                                        </div>
                                    `;
                                    const tempDiv = document.createElement('div');
                                    tempDiv.innerHTML = listSecHtml.trim();
                                    if (tempDiv.firstElementChild) {
                                        listWrap.appendChild(tempDiv.firstElementChild);
                                    }
                                }

                                this.initSortables();
                            }

                            this.showBucketModal = false;
                        } else {
                            const err = await res.json();
                            alert(err.error || err.message || 'Error saving bucket');
                        }
                    } catch (e) {
                        console.error(e);
                        alert('Failed to save bucket');
                    } finally {
                        this.isSavingBucket = false;
                    }
                },

                promptDeleteBucketModal(id, name, taskCount) {
                    this.deleteBucketData = {
                        id: id,
                        name: name,
                        taskCount: taskCount,
                        action: 'move',
                        moveToColumnId: ''
                    };
                    const otherCols = Array.from(document.querySelectorAll('.kanban-column'))
                        .map(el => parseInt(el.dataset.columnId, 10))
                        .filter(cId => cId && cId !== id);
                    if (otherCols.length > 0) {
                        this.deleteBucketData.moveToColumnId = otherCols[0].toString();
                    }
                    this.showDeleteBucketModal = true;
                },

                async executeDeleteBucket() {
                    if (!this.deleteBucketData.id) return;
                    this.isDeletingBucket = true;
                    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content;
                    const bucketId = this.deleteBucketData.id;
                    const action = this.deleteBucketData.action;
                    const moveToId = this.deleteBucketData.moveToColumnId;

                    try {
                        const payload = {};
                        if (action === 'move' && moveToId) {
                            payload.move_to_column_id = moveToId;
                        } else if (action === 'delete_all') {
                            payload.delete_tasks = true;
                        }

                        const res = await fetch(`/projects/{{ $project->id }}/board-columns/${bucketId}`, {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': CSRF,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(payload)
                        });

                        if (res.ok) {
                            // Move card DOM elements if move was chosen
                            if (action === 'move' && moveToId) {
                                const fromBoardDrop = document.getElementById(`detail-cards-column-${bucketId}`);
                                const toBoardDrop = document.getElementById(`detail-cards-column-${moveToId}`);
                                if (fromBoardDrop && toBoardDrop) {
                                    while (fromBoardDrop.firstElementChild) {
                                        toBoardDrop.appendChild(fromBoardDrop.firstElementChild);
                                    }
                                }
                                const fromListDrop = document.getElementById(`list-tasks-column-${bucketId}`);
                                const toListDrop = document.getElementById(`list-tasks-column-${moveToId}`);
                                if (fromListDrop && toListDrop) {
                                    while (fromListDrop.firstElementChild) {
                                        toListDrop.appendChild(fromListDrop.firstElementChild);
                                    }
                                }
                            }

                            // Remove column element from Board view
                            const colEl = document.querySelector(`.kanban-column[data-column-id="${bucketId}"]`);
                            if (colEl) colEl.remove();

                            // Remove column section from List view
                            const listDrop = document.getElementById(`list-tasks-column-${bucketId}`);
                            if (listDrop) {
                                const listSection = listDrop.closest('.space-y-2');
                                if (listSection) listSection.remove();
                            }

                            this.showDeleteBucketModal = false;
                            this.updateCounters();
                        } else {
                            const err = await res.json();
                            alert(err.error || err.message || 'Could not delete bucket.');
                        }
                    } catch (e) {
                        console.error(e);
                        alert('Failed to delete bucket.');
                    } finally {
                        this.isDeletingBucket = false;
                    }
                },

                async refreshTasksDOM() {
                    try {
                        const res = await fetch(window.location.href);
                        const html = await res.text();
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');

                        const newTasksContainer = doc.querySelector('[x-show="tab === \'tasks\'"]');
                        const currentTasksContainer = document.querySelector('[x-show="tab === \'tasks\'"]');
                        if (newTasksContainer && currentTasksContainer) {
                            currentTasksContainer.innerHTML = newTasksContainer.innerHTML;
                            this.$nextTick(() => {
                                this.initSortables();
                                this.initBoardHorizontalScroll();
                                this.applyFilters();
                            });
                        }
                    } catch (e) {
                        console.error('Failed to refresh tasks DOM:', e);
                    }
                },

                async bootstrapDefaultBuckets() {
                    this.isBootstrapping = true;
                    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content;
                    try {
                        const res = await fetch(`/projects/{{ $project->id }}/board-columns/bootstrap-default`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': CSRF,
                                'Accept': 'application/json'
                            }
                        });
                        if (res.ok) {
                            await this.refreshTasksDOM();
                        }
                    } catch (e) {
                        console.error(e);
                    } finally {
                        this.isBootstrapping = false;
                    }
                },

                openQuickAddTask(columnId) {
                    this.quickAddColumnId = columnId;
                    this.quickTaskTitle = '';
                    this.$nextTick(() => {
                        const input = this.taskView === 'list'
                            ? (this.$refs[`quickTaskListInput_${columnId}`] || this.$refs[`quickTaskInput_${columnId}`])
                            : (this.$refs[`quickTaskInput_${columnId}`] || this.$refs[`quickTaskListInput_${columnId}`]);
                        if (input) input.focus();
                    });
                },

                async submitQuickTask(columnId) {
                    const title = this.quickTaskTitle.trim();
                    if (!title) return;

                    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content;
                    try {
                        const res = await fetch(`/projects/{{ $project->id }}/tasks`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': CSRF,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                title: title,
                                board_column_id: columnId
                            })
                        });

                        if (res.ok) {
                            const data = await res.json();
                            // Append card to board column dropzone
                            const boardDropzone = document.getElementById(`detail-cards-column-${columnId}`);
                            if (boardDropzone && data.card_html) {
                                const tempDiv = document.createElement('div');
                                tempDiv.innerHTML = data.card_html.trim();
                                const newCard = tempDiv.firstElementChild;
                                if (newCard) {
                                    boardDropzone.appendChild(newCard);
                                }
                            }

                            // Append row to list dropzone
                            const listDropzone = document.getElementById(`list-tasks-column-${columnId}`);
                            if (listDropzone && data.task) {
                                const rowHtml = `
                                    <div class="list-task-row grid grid-cols-12 gap-2 py-2.5 px-3 hover:bg-gray-50/90 dark:hover:bg-gray-800/60 rounded-xl transition-all items-center group cursor-pointer border border-transparent hover:border-gray-100 dark:hover:border-gray-750"
                                         data-task-id="${data.task.id}"
                                         data-priority="${data.task.priority || 'medium'}"
                                         data-assignee-ids=""
                                         data-labels=""
                                         data-due=""
                                         data-is-done="0"
                                         data-sprint-id="${data.task.sprint_id || ''}"
                                         data-milestone-id="${data.task.milestone_id || ''}"
                                         onclick="window.openTask(${data.task.id})">
                                        <div class="col-span-4 flex items-center gap-2.5 min-w-0 pr-2">
                                            <span class="list-drag-handle cursor-grab active:cursor-grabbing text-gray-300 group-hover:text-gray-500 p-0.5 shrink-0" onclick="event.stopPropagation()">
                                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M9 5a2 2 0 11-4 0 2 2 0 014 0zM9 12a2 2 0 11-4 0 2 2 0 014 0zM9 19a2 2 0 11-4 0 2 2 0 014 0zM19 5a2 2 0 11-4 0 2 2 0 014 0zM19 12a2 2 0 11-4 0 2 2 0 014 0zM19 19a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                            </span>
                                            <button type="button"
                                                    onclick="event.stopPropagation(); window.toggleTaskFromRow(${data.task.id}, true)"
                                                    class="w-4 h-4 rounded-full flex items-center justify-center transition-colors shrink-0 border-2 border-gray-300 hover:border-emerald-500 text-transparent">
                                                <svg class="w-2.5 h-2.5 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            </button>
                                            <span class="text-xs font-bold text-gray-800 dark:text-gray-100 group-hover:text-blue-600 truncate">
                                                ${data.task.title}
                                            </span>
                                        </div>
                                        <div class="col-span-1 list-priority-container flex items-center gap-1.5 text-xs font-bold text-blue-600">
                                            <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
                                            <span class="truncate">Normal</span>
                                        </div>
                                        <div class="col-span-1 list-assignee-container flex items-center">
                                            <span class="text-xs text-gray-300">—</span>
                                        </div>
                                        <div class="col-span-2 list-labels-container flex items-center gap-1.5 flex-wrap">
                                            <span class="text-xs text-gray-300">—</span>
                                        </div>
                                        <div class="col-span-2 list-due-container flex items-center text-xs">
                                            <span class="text-[11px] text-gray-300 dark:text-gray-600">—</span>
                                        </div>
                                        <div class="col-span-1 list-checklist-container flex items-center">
                                            <span class="text-[11px] text-gray-300 dark:text-gray-600">—</span>
                                        </div>
                                        <div class="col-span-1 list-details-container flex items-center justify-end gap-2 text-xs">
                                            <span class="inline-flex items-center gap-1 text-[11px] text-gray-300 dark:text-gray-600">
                                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                                <span>0</span>
                                            </span>
                                            <svg class="w-4 h-4 text-gray-300 group-hover:text-gray-500 dark:text-gray-600 dark:group-hover:text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        </div>
                                    </div>
                                `;
                                const tempRowDiv = document.createElement('div');
                                tempRowDiv.innerHTML = rowHtml.trim();
                                if (tempRowDiv.firstElementChild) {
                                    listDropzone.appendChild(tempRowDiv.firstElementChild);
                                }
                            }

                            this.quickAddColumnId = null;
                            this.quickTaskTitle = '';
                            this.updateCounters();
                            this.applyFilters();
                        }
                    } catch (e) {
                        console.error(e);
                    }
                },

                async toggleTaskComplete(taskId, targetCompleted) {
                    // Optimistic DOM update
                    const card = document.querySelector(`.kanban-card[data-task-id="${taskId}"]`);
                    if (card) {
                        card.dataset.isDone = targetCompleted ? '1' : '0';
                        const btn = card.querySelector('button[title*="selesai"], button[title*="Selesai"]');
                        const titleEl = card.querySelector('.card-title-text, h4');
                        if (btn) {
                            if (targetCompleted) {
                                btn.className = 'mt-0.5 w-4 h-4 rounded-full shrink-0 flex items-center justify-center transition-colors bg-emerald-500 text-white';
                                btn.title = 'Tandai belum selesai';
                            } else {
                                btn.className = 'mt-0.5 w-4 h-4 rounded-full shrink-0 flex items-center justify-center transition-colors border-2 border-gray-300 hover:border-emerald-500 text-transparent';
                                btn.title = 'Tandai selesai';
                            }
                        }
                        if (titleEl) {
                            if (targetCompleted) {
                                titleEl.classList.add('line-through', 'text-gray-400', 'dark:text-gray-500');
                            } else {
                                titleEl.classList.remove('line-through', 'text-gray-400', 'dark:text-gray-500');
                            }
                        }
                    }

                    const row = document.querySelector(`.list-task-row[data-task-id="${taskId}"]`);
                    if (row) {
                        row.dataset.isDone = targetCompleted ? '1' : '0';
                        const btn = row.querySelector('button');
                        const titleEl = row.querySelector('span.truncate, span.text-xs');
                        if (btn) {
                            if (targetCompleted) {
                                btn.className = 'w-4 h-4 rounded-full flex items-center justify-center transition-colors shrink-0 bg-emerald-500 text-white';
                            } else {
                                btn.className = 'w-4 h-4 rounded-full flex items-center justify-center transition-colors shrink-0 border-2 border-gray-300 hover:border-emerald-500 text-transparent';
                            }
                        }
                        if (titleEl) {
                            if (targetCompleted) {
                                titleEl.classList.add('line-through', 'text-gray-400', 'dark:text-gray-500');
                            } else {
                                titleEl.classList.remove('line-through', 'text-gray-400', 'dark:text-gray-500');
                            }
                        }
                    }

                    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content;
                    try {
                        const res = await fetch(`/projects/{{ $project->id }}/tasks/${taskId}`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': CSRF,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                status: targetCompleted ? 'done' : 'todo'
                            })
                        });
                        if (res.ok) {
                            const data = await res.json();
                            if (data.task && window.updateCardInDOM) {
                                window.updateCardInDOM(data.task);
                            }
                            this.updateCounters();
                        }
                    } catch (e) {
                        console.error(e);
                    }
                }
            };
        }

        // Global modal trigger helper
        window.openTask = function (taskId) {
            if (window.openTaskModal) {
                window.openTaskModal(taskId);
            } else {
                window.dispatchEvent(new CustomEvent('open-task-modal', { detail: { taskId } }));
            }
        };

        window.toggleTaskFromRow = function (taskId, targetCompleted) {
            const alpineEl = document.querySelector('[x-data*="projectPageData"]');
            if (alpineEl && alpineEl._x_dataStack && alpineEl._x_dataStack[0]) {
                alpineEl._x_dataStack[0].toggleTaskComplete(taskId, targetCompleted);
            }
        };

        window.removeCardFromDOM = function (taskId) {
            if (!taskId) return;
            const card = document.querySelector(`.kanban-card[data-task-id="${taskId}"]`);
            if (card) card.remove();
            const row = document.querySelector(`.list-task-row[data-task-id="${taskId}"]`);
            if (row) row.remove();

            const alpineEl = document.querySelector('[x-data*="projectPageData"]');
            if (alpineEl && alpineEl._x_dataStack && alpineEl._x_dataStack[0]) {
                alpineEl._x_dataStack[0].updateCounters();
            }
        };

        // Direct filter triggers from Sprint or Milestone cards / tables
        window.filterTasksBySprint = function (sprintId, sprintName) {
            const alpineEl = document.querySelector('[x-data*="projectPageData"]');
            if (alpineEl && alpineEl._x_dataStack && alpineEl._x_dataStack[0]) {
                const data = alpineEl._x_dataStack[0];
                data.tab = 'tasks';
                data.filterSprint = sprintId ? sprintId.toString() : '';
                data.filterSprintName = sprintName || 'Sprint ' + sprintId;
                data.filterMilestone = '';
                data.filterMilestoneName = 'All';
                const url = new URL(window.location);
                url.searchParams.set('tab', 'tasks');
                if (sprintId) url.searchParams.set('sprint', sprintId);
                else url.searchParams.delete('sprint');
                url.searchParams.delete('milestone');
                window.history.replaceState({}, '', url);
                data.$nextTick(() => {
                    data.applyFilters();
                    const tasksSec = document.querySelector('[x-show="tab === \'tasks\'"]');
                    if (tasksSec) tasksSec.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            } else {
                window.location.href = `/projects/{{ $project->id }}?tab=tasks&sprint=${sprintId}`;
            }
        };

        window.filterTasksByMilestone = function (milestoneId, milestoneName) {
            const alpineEl = document.querySelector('[x-data*="projectPageData"]');
            if (alpineEl && alpineEl._x_dataStack && alpineEl._x_dataStack[0]) {
                const data = alpineEl._x_dataStack[0];
                data.tab = 'tasks';
                data.filterMilestone = milestoneId ? milestoneId.toString() : '';
                data.filterMilestoneName = milestoneName || 'Milestone ' + milestoneId;
                data.filterSprint = '';
                data.filterSprintName = 'All';
                const url = new URL(window.location);
                url.searchParams.set('tab', 'tasks');
                if (milestoneId) url.searchParams.set('milestone', milestoneId);
                else url.searchParams.delete('milestone');
                url.searchParams.delete('sprint');
                window.history.replaceState({}, '', url);
                data.$nextTick(() => {
                    data.applyFilters();
                    const tasksSec = document.querySelector('[x-show="tab === \'tasks\'"]');
                    if (tasksSec) tasksSec.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            } else {
                window.location.href = `/projects/{{ $project->id }}?tab=tasks&milestone=${milestoneId}`;
            }
        };
    </script>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(function () {
            $('#member-select').select2({
                placeholder: '— Select Members —',
                allowClear: true,
                width: '100%',
            });
        });
    </script>
@endpush