@extends('layouts.app')

@section('title', 'Projects')
@section('page-title', 'Projects')
@section('main-class', 'flex-1 px-4 sm:px-6 lg:px-8 pt-2 pb-8 overflow-y-auto overflow-x-hidden w-full max-w-full min-w-0')

@section('content')
<div class="w-full max-w-[1680px] mx-auto px-2 sm:px-4 lg:px-6 py-6 sm:py-8"
     x-data="{
         view: (new URLSearchParams(window.location.search).get('view') || localStorage.getItem('flovig_projects_view') || 'card'),
         setView(v) {
             this.view = v;
             localStorage.setItem('flovig_projects_view', v);
             const url = new URL(window.location.href);
             url.searchParams.set('view', v);
             window.history.replaceState({}, '', url.toString());
         },
         setFilter(key, val) {
             const url = new URL(window.location.href);
             if (val !== undefined && val !== null && val !== '') {
                 url.searchParams.set(key, val);
             } else {
                 url.searchParams.delete(key);
             }
             url.searchParams.delete('page');
             if (this.view) {
                 url.searchParams.set('view', this.view);
             }
             window.location.href = url.toString();
         }
     }">

    {{-- ── 1. Page Header ──────────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">Projects</h1>
            <p class="text-sm text-gray-500 mt-1">Track, assign, and organize all your team's client engagements in one workspace.</p>
        </div>
        @if(!auth()->user()->hasRole('client'))
            <div class="shrink-0">
                <button type="button" @click="openCreateProjectModal('index')"
                   class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-sm font-semibold rounded-xl transition-colors duration-150 cursor-pointer shadow-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Create Project</span>
                </button>
            </div>
        @endif
    </div>

    {{-- ── 2. Metric / KPI Summary Cards (Flat, No Border, No Shadow) ────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
        {{-- Total Projects --}}
        <a href="{{ route('projects.index') }}"
           class="bg-white rounded-2xl p-4 flex items-center gap-3.5 hover:bg-gray-50/80 transition group">
            <div class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/>
                </svg>
            </div>
            <div class="min-w-0">
                <span class="text-[11px] font-bold tracking-wider text-gray-400 uppercase block truncate">TOTAL PROJECTS</span>
                <span class="text-2xl font-bold text-gray-900 leading-none mt-1 block">{{ $stats['total'] ?? 0 }}</span>
            </div>
        </a>

        {{-- Completed --}}
        <a href="{{ route('projects.index', array_merge(request()->except('page'), ['status' => 'completed'])) }}"
           class="bg-white rounded-2xl p-4 flex items-center gap-3.5 hover:bg-gray-50/80 transition group">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                </svg>
            </div>
            <div class="min-w-0">
                <span class="text-[11px] font-bold tracking-wider text-gray-400 uppercase block truncate">COMPLETED</span>
                <span class="text-2xl font-bold text-gray-900 leading-none mt-1 block">{{ $stats['completed'] ?? 0 }}</span>
            </div>
        </a>

        {{-- In Progress --}}
        <a href="{{ route('projects.index', array_merge(request()->except('page'), ['status' => 'active'])) }}"
           class="bg-white rounded-2xl p-4 flex items-center gap-3.5 hover:bg-gray-50/80 transition group">
            <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <span class="text-[11px] font-bold tracking-wider text-gray-400 uppercase block truncate">IN PROGRESS</span>
                <span class="text-2xl font-bold text-gray-900 leading-none mt-1 block">{{ $stats['in_progress'] ?? 0 }}</span>
            </div>
        </a>

        {{-- Pending --}}
        <a href="{{ route('projects.index', array_merge(request()->except('page'), ['status' => 'pending'])) }}"
           class="bg-white rounded-2xl p-4 flex items-center gap-3.5 hover:bg-gray-50/80 transition group">
            <div class="w-12 h-12 rounded-xl bg-purple-50 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0 2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71l3.114-.732a1.125 1.125 0 0 0 .864-1.096V4.46a1.125 1.125 0 0 0-1.385-1.095l-2.701.635a9 9 0 0 1-6.087-.71l-.108-.054a9 9 0 0 0-6.208-.682L3 4.5M3 15V4.5"/>
                </svg>
            </div>
            <div class="min-w-0">
                <span class="text-[11px] font-bold tracking-wider text-gray-400 uppercase block truncate">PENDING</span>
                <span class="text-2xl font-bold text-gray-900 leading-none mt-1 block">{{ $stats['pending'] ?? 0 }}</span>
            </div>
        </a>

        {{-- Overdue --}}
        <a href="{{ route('projects.index', array_merge(request()->except('page'), ['status' => 'overdue'])) }}"
           class="bg-white rounded-2xl p-4 flex items-center gap-3.5 hover:bg-gray-50/80 transition group">
            <div class="w-12 h-12 rounded-xl bg-rose-50 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <span class="text-[11px] font-bold tracking-wider text-gray-400 uppercase block truncate">OVERDUE</span>
                <span class="text-2xl font-bold text-gray-900 leading-none mt-1 block">{{ $stats['overdue'] ?? 0 }}</span>
            </div>
        </a>
    </div>

    {{-- ── 3. Filter Bar & Custom Dropdown Selects ──────────────────────── --}}
    @php
        $statusOptions = [
            '' => 'All',
            'active' => 'In Progress',
            'completed' => 'Completed',
            'pending' => 'Pending',
            'draft' => 'Draft',
            'cancelled' => 'Cancelled',
            'overdue' => 'Overdue',
        ];
        $selectedStatus = request('status', '');
        $selectedStatusLabel = $statusOptions[$selectedStatus] ?? ($selectedStatus ? ucfirst($selectedStatus) : 'All');

        $selectedManager = $managers->firstWhere('id', request('manager_id'));
        $selectedManagerName = $selectedManager ? $selectedManager->name : 'All Leads';

        $selectedClient = $clients->firstWhere('id', request('client_id'));
        $selectedClientName = $selectedClient ? $selectedClient->name : 'All';

        $deadlineOptions = [
            '' => 'Any time',
            'today' => 'Today',
            'this_week' => 'This Week',
            'this_month' => 'This Month',
            'overdue' => 'Overdue',
        ];
        $selectedDeadline = request('deadline', '');
        $selectedDeadlineLabel = $deadlineOptions[$selectedDeadline] ?? 'Any time';
    @endphp

    <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3 mb-6">
        <div class="flex-1 flex flex-wrap items-center gap-2.5">
            {{-- Search Input --}}
            <div class="relative flex-1 min-w-[220px] max-w-sm">
                <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
                <input type="text"
                       value="{{ request('search') }}"
                       placeholder="Search project name..."
                       @keydown.enter.prevent="setFilter('search', $el.value)"
                       class="w-full pl-9.5 pr-4 py-2 bg-white hover:bg-gray-50/50 border border-gray-200/90 rounded-xl text-xs sm:text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
            </div>

            {{-- Status Custom Select --}}
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" @click.away="open = false" type="button"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-gray-50 border border-gray-200/90 rounded-xl text-xs sm:text-sm font-medium text-gray-700 transition cursor-pointer">
                    <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Status: <strong class="font-semibold text-gray-900">{{ $selectedStatusLabel }}</strong></span>
                    <svg class="w-3 h-3 text-gray-400 shrink-0 transition-transform duration-150" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="open" x-cloak
                     class="absolute left-0 top-full mt-1.5 min-w-[190px] bg-white border border-gray-200/90 rounded-xl shadow-lg p-1.5 z-40 space-y-0.5">
                    @foreach($statusOptions as $val => $lbl)
                        <button type="button"
                                @click="setFilter('status', '{{ $val }}')"
                                class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs flex items-center justify-between hover:bg-gray-50 transition cursor-pointer {{ $selectedStatus === (string) $val ? 'text-blue-600 font-bold bg-blue-50/60' : 'text-gray-700' }}">
                            <span>{{ $lbl }}</span>
                            @if($selectedStatus === (string) $val)
                                <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m4.5 12.75 6 6 9-13.5"/>
                                </svg>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- PIC Custom Select --}}
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" @click.away="open = false" type="button"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-gray-50 border border-gray-200/90 rounded-xl text-xs sm:text-sm font-medium text-gray-700 transition cursor-pointer">
                    <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span>PIC: <strong class="font-semibold text-gray-900">{{ $selectedManagerName }}</strong></span>
                    <svg class="w-3 h-3 text-gray-400 shrink-0 transition-transform duration-150" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="open" x-cloak
                     class="absolute left-0 top-full mt-1.5 min-w-[210px] bg-white border border-gray-200/90 rounded-xl shadow-lg p-1.5 z-40 max-h-64 overflow-y-auto space-y-0.5">
                    <button type="button"
                            @click="setFilter('manager_id', '')"
                            class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs flex items-center justify-between hover:bg-gray-50 transition cursor-pointer {{ !request('manager_id') ? 'text-blue-600 font-bold bg-blue-50/60' : 'text-gray-700' }}">
                        <span>All Leads</span>
                        @if(!request('manager_id'))
                            <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m4.5 12.75 6 6 9-13.5"/>
                            </svg>
                        @endif
                    </button>
                    @foreach($managers as $m)
                        <button type="button"
                                @click="setFilter('manager_id', '{{ $m->id }}')"
                                class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs flex items-center gap-2 hover:bg-gray-50 transition cursor-pointer {{ (string) request('manager_id') === (string) $m->id ? 'text-blue-600 font-bold bg-blue-50/60' : 'text-gray-700' }}">
                            <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold text-white shrink-0"
                                  style="background-color: {{ $m->avatarColor() }}">
                                {{ $m->initials() }}
                            </span>
                            <span class="truncate flex-1">{{ $m->name }}</span>
                            @if((string) request('manager_id') === (string) $m->id)
                                <svg class="w-3.5 h-3.5 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m4.5 12.75 6 6 9-13.5"/>
                                </svg>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Client Custom Select --}}
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" @click.away="open = false" type="button"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-gray-50 border border-gray-200/90 rounded-xl text-xs sm:text-sm font-medium text-gray-700 transition cursor-pointer">
                    <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <span>Client: <strong class="font-semibold text-gray-900">{{ $selectedClientName }}</strong></span>
                    <svg class="w-3 h-3 text-gray-400 shrink-0 transition-transform duration-150" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="open" x-cloak
                     class="absolute left-0 top-full mt-1.5 min-w-[210px] bg-white border border-gray-200/90 rounded-xl shadow-lg p-1.5 z-40 max-h-64 overflow-y-auto space-y-0.5">
                    <button type="button"
                            @click="setFilter('client_id', '')"
                            class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs flex items-center justify-between hover:bg-gray-50 transition cursor-pointer {{ !request('client_id') ? 'text-blue-600 font-bold bg-blue-50/60' : 'text-gray-700' }}">
                        <span>All Clients</span>
                        @if(!request('client_id'))
                            <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m4.5 12.75 6 6 9-13.5"/>
                            </svg>
                        @endif
                    </button>
                    @foreach($clients as $c)
                        <button type="button"
                                @click="setFilter('client_id', '{{ $c->id }}')"
                                class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs flex items-center justify-between hover:bg-gray-50 transition cursor-pointer {{ (string) request('client_id') === (string) $c->id ? 'text-blue-600 font-bold bg-blue-50/60' : 'text-gray-700' }}">
                            <span class="truncate flex-1">{{ $c->name }}</span>
                            @if((string) request('client_id') === (string) $c->id)
                                <svg class="w-3.5 h-3.5 text-blue-600 shrink-0 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m4.5 12.75 6 6 9-13.5"/>
                                </svg>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Deadline Custom Select --}}
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" @click.away="open = false" type="button"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-gray-50 border border-gray-200/90 rounded-xl text-xs sm:text-sm font-medium text-gray-700 transition cursor-pointer">
                    <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>Deadline: <strong class="font-semibold text-gray-900">{{ $selectedDeadlineLabel }}</strong></span>
                    <svg class="w-3 h-3 text-gray-400 shrink-0 transition-transform duration-150" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="open" x-cloak
                     class="absolute left-0 top-full mt-1.5 min-w-[190px] bg-white border border-gray-200/90 rounded-xl shadow-lg p-1.5 z-40 space-y-0.5">
                    @foreach($deadlineOptions as $val => $lbl)
                        <button type="button"
                                @click="setFilter('deadline', '{{ $val }}')"
                                class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs flex items-center justify-between hover:bg-gray-50 transition cursor-pointer {{ $selectedDeadline === (string) $val ? 'text-blue-600 font-bold bg-blue-50/60' : 'text-gray-700' }}">
                            <span>{{ $lbl }}</span>
                            @if($selectedDeadline === (string) $val)
                                <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m4.5 12.75 6 6 9-13.5"/>
                                </svg>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>

            @if(request('search') || request('status') || request('manager_id') || request('client_id') || request('deadline'))
                <a href="{{ route('projects.index', ['view' => request('view', 'card')]) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-2 bg-white hover:bg-gray-50 border border-gray-200/90 text-gray-600 rounded-xl text-xs sm:text-sm font-medium transition cursor-pointer">
                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    <span>Reset</span>
                </a>
            @endif
        </div>

        {{-- View Switcher Buttons (List & Card, default Card) --}}
        <div class="flex items-center gap-1 shrink-0 bg-white p-1 rounded-xl border border-gray-200/90 self-end lg:self-auto">
            <button type="button"
                    @click="setView('list')"
                    :class="view === 'list' ? 'bg-blue-600 text-white shadow-xs font-semibold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50 font-medium'"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs transition cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                </svg>
                <span>List</span>
            </button>

            <button type="button"
                    @click="setView('card')"
                    :class="view === 'card' ? 'bg-blue-600 text-white shadow-xs font-semibold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50 font-medium'"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs transition cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"/>
                </svg>
                <span>Card</span>
            </button>
        </div>
    </div>

    {{-- Helper presets for card visual variety --}}
    @php
        $iconThemes = [
            0 => [
                'bg' => 'bg-blue-50',
                'text' => 'text-blue-600',
                'svg' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.25v4.5a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25v-4.5m16.5 0v-4.5A2.25 2.25 0 0 0 18 7.5H6a2.25 2.25 0 0 0-2.25 2.25v4.5m16.5 0h-4.5a2.25 2.25 0 0 1-2.25-2.25v-.75m-6 3H3.75m16.5 0h-3.75a2.25 2.25 0 0 1-2.25-2.25V9a2.25 2.25 0 0 0-2.25-2.25h-3A2.25 2.25 0 0 0 7.5 9v1.5a2.25 2.25 0 0 1-2.25 2.25H3.75M9 7.5V6a2.25 2.25 0 0 1 2.25-2.25h1.5A2.25 2.25 0 0 1 15 6v1.5"/></svg>',
            ],
            1 => [
                'bg' => 'bg-indigo-50',
                'text' => 'text-indigo-600',
                'svg' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/></svg>',
            ],
            2 => [
                'bg' => 'bg-cyan-50',
                'text' => 'text-cyan-600',
                'svg' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/></svg>',
            ],
            3 => [
                'bg' => 'bg-purple-50',
                'text' => 'text-purple-600',
                'svg' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.999-3.199a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"/></svg>',
            ],
            4 => [
                'bg' => 'bg-amber-50',
                'text' => 'text-amber-600',
                'svg' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/></svg>',
            ],
            5 => [
                'bg' => 'bg-emerald-50',
                'text' => 'text-emerald-600',
                'svg' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125"/></svg>',
            ],
        ];
    @endphp

    {{-- ── 4. Main Section (Card & List Views) ─────────────────────────── --}}
    @if($projects->isEmpty())
        <div class="bg-white rounded-2xl p-12 text-center text-gray-500">
            <div class="w-16 h-16 rounded-2xl bg-blue-50 text-blue-600 mx-auto flex items-center justify-center mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44z"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-1">No projects found</h3>
            <p class="text-sm text-gray-500 max-w-sm mx-auto mb-6">
                @if(request('search') || request('status') || request('manager_id') || request('client_id') || request('deadline'))
                    No projects matched your active filters. Try adjusting or resetting your filter criteria.
                @else
                    Get started by creating your first client project engagement.
                @endif
            </p>
            @if(request('search') || request('status') || request('manager_id') || request('client_id') || request('deadline'))
                <a href="{{ route('projects.index', ['view' => request('view', 'card')]) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-xl transition cursor-pointer">
                    Clear Filters
                </a>
            @elseif(!auth()->user()->hasRole('client'))
                <button type="button" @click="openCreateProjectModal('index')"
                   class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Create Project</span>
                </button>
            @endif
        </div>
    @else

        {{-- ── 4A. CARD VIEW (Default, No Border, No Shadow) ─────────────── --}}
        <div x-show="view === 'card'"
             class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($projects as $project)
                @php
                    $totalTasks = $project->total_tasks_count ?? 0;
                    $completedTasks = $project->completed_tasks_count ?? 0;

                    $progress = $project->progress > 0
                        ? (int) $project->progress
                        : ($totalTasks > 0 ? (int) round(($completedTasks / $totalTasks) * 100) : 0);

                    if ($project->status === 'completed') {
                        $sc = ['label' => 'Completed', 'class' => 'bg-emerald-50 text-emerald-700', 'dot' => 'bg-emerald-500'];
                    } elseif ($project->status === 'on_hold') {
                        $sc = ['label' => 'Pending', 'class' => 'bg-purple-50 text-purple-700', 'dot' => 'bg-purple-500'];
                    } elseif ($project->status === 'cancelled') {
                        $sc = ['label' => 'Cancelled', 'class' => 'bg-rose-50 text-rose-700', 'dot' => 'bg-rose-500'];
                    } elseif ($project->status === 'draft') {
                        $sc = ['label' => 'Draft', 'class' => 'bg-gray-100 text-gray-700', 'dot' => 'bg-gray-400'];
                    } else {
                        if ($progress > 0 && $progress < 100 && ($project->id % 2 === 1)) {
                            $sc = ['label' => 'In Progress', 'class' => 'bg-blue-50 text-blue-700', 'dot' => 'bg-blue-500'];
                        } else {
                            $sc = ['label' => 'Active', 'class' => 'bg-emerald-50 text-emerald-700', 'dot' => 'bg-emerald-500'];
                        }
                    }

                    if ($progress >= 100 || $project->status === 'completed') {
                        $progressBarColor = 'bg-emerald-500';
                        $progressTextColor = 'text-emerald-600';
                    } elseif ($project->status === 'on_hold' || $project->status === 'draft') {
                        $progressBarColor = 'bg-purple-500';
                        $progressTextColor = 'text-purple-600';
                    } elseif ($progress > 25 && $progress <= 50) {
                        $progressBarColor = 'bg-amber-500';
                        $progressTextColor = 'text-amber-600';
                    } else {
                        $progressBarColor = 'bg-blue-600';
                        $progressTextColor = 'text-blue-600';
                    }

                    $iconTheme = $iconThemes[$project->id % count($iconThemes)];
                    $projectImages = $project->imageUrls();

                    $managerRole = $project->manager?->structuralLevel?->name
                        ?? ($project->manager?->roles->first() ? ucfirst(str_replace('_', ' ', $project->manager->roles->first()->name)) : 'Senior Architect');

                    $clientName = $project->client?->name ?? 'Internal Team';
                    $clientOrg = $project->client?->organizationUnit?->name
                        ?? ($project->client?->company?->name ?? 'Enterprise B2B');

                    $projectPayload = json_encode([
                        'id' => $project->id,
                        'slug' => $project->slug,
                        'name' => $project->name,
                        'description' => $project->description,
                        'client_id' => $project->client_id,
                        'manager_id' => $project->manager_id,
                        'start_date' => $project->start_date ? $project->start_date->format('Y-m-d') : null,
                        'end_date' => $project->end_date ? $project->end_date->format('Y-m-d') : null,
                        'budget' => $project->budget,
                        'status' => $project->status,
                        'images' => $project->images ?? [],
                        'image_urls' => $project->imageUrls(),
                    ]);
                @endphp

                <div class="bg-white rounded-2xl p-6 flex flex-col justify-between group">
                    {{-- Top: Icon, Title & Status --}}
                    <div>
                        <div class="flex items-start justify-between gap-3 mb-5">
                            <div class="flex items-center gap-3 min-w-0">
                                @if(!empty($projectImages))
                                    <img src="{{ $projectImages[0] }}" alt="{{ $project->name }}"
                                         class="w-12 h-12 rounded-xl object-cover shrink-0 border border-gray-100"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="w-12 h-12 rounded-xl items-center justify-center shrink-0 {{ $iconTheme['bg'] }} {{ $iconTheme['text'] }}" style="display:none;">
                                        {!! $iconTheme['svg'] !!}
                                    </div>
                                @else
                                    <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 {{ $iconTheme['bg'] }} {{ $iconTheme['text'] }}">
                                        {!! $iconTheme['svg'] !!}
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <a href="{{ route('projects.show', $project) }}"
                                       class="font-bold text-gray-900 hover:text-blue-600 transition text-base block truncate leading-snug">
                                        {{ $project->name }}
                                    </a>
                                </div>
                            </div>

                            <span class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold {{ $sc['class'] }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $sc['dot'] }}"></span>
                                {{ $sc['label'] }}
                            </span>
                        </div>

                        {{-- Metadata Row 1: PIC & Client/Org --}}
                        <div class="grid grid-cols-2 gap-4 py-3 border-y border-gray-100/70 my-1">
                            {{-- PIC --}}
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block mb-2">PROJECT LEAD (PIC)</span>
                                <div class="flex items-center gap-2.5">
                                    @if($project->manager?->avatar)
                                        <img src="{{ Storage::url($project->manager->avatar) }}" alt="{{ $project->manager->name }}"
                                             class="w-8 h-8 rounded-full object-cover shrink-0">
                                    @else
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white shrink-0"
                                             style="background-color: {{ $project->manager ? $project->manager->avatarColor() : '#3b82f6' }}">
                                            {{ $project->manager ? $project->manager->initials() : 'NA' }}
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-gray-900 truncate">{{ $project->manager->name ?? 'Unassigned' }}</p>
                                        <p class="text-[11px] text-gray-500 truncate">{{ $managerRole }}</p>
                                    </div>
                                </div>
                            </div>

                            {{-- Client / Org --}}
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block mb-2">CLIENT / ORG</span>
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-gray-900 truncate">{{ $clientName }}</p>
                                    <p class="text-[11px] text-gray-500 truncate">{{ $clientOrg }}</p>
                                </div>
                            </div>
                        </div>

                        {{-- Metadata Row 2: Tasks & Deadline --}}
                        <div class="grid grid-cols-2 gap-4 py-2 my-1">
                            <div>
                                <span class="text-xs text-gray-400 block mb-0.5">Tasks Completed:</span>
                                <p class="text-sm font-bold text-gray-900">
                                    {{ $completedTasks }} <span class="font-normal text-gray-400">/ {{ $totalTasks }}</span>
                                </p>
                            </div>
                            <div>
                                <span class="text-xs text-gray-400 block mb-0.5">Deadline:</span>
                                <p class="text-sm font-bold text-gray-900">
                                    {{ $project->end_date ? \Carbon\Carbon::parse($project->end_date)->format('d M Y') : '-' }}
                                </p>
                            </div>
                        </div>

                        {{-- Progress Bar --}}
                        <div class="mt-2 mb-5">
                            <div class="flex items-center justify-between text-xs mb-1.5">
                                <span class="text-gray-500 font-medium">Progress</span>
                                <span class="font-bold {{ $progressTextColor }}">{{ $progress }}%</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                                <div class="h-1.5 rounded-full {{ $progressBarColor }} transition-all duration-500"
                                     style="width: {{ min($progress, 100) }}%"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Bottom Action Buttons --}}
                    <div class="flex items-center gap-3 pt-1 border-t border-gray-100/70">
                        @if(!auth()->user()->hasRole('client'))
                            <button type="button" @click="openEditProjectModal({{ $projectPayload }}, 'index')"
                               class="flex-1 py-2.5 px-3 bg-white hover:bg-gray-50 border border-gray-200/90 text-gray-700 text-xs font-semibold rounded-xl text-center transition cursor-pointer">
                                Edit Info
                            </button>
                        @endif
                        <a href="{{ route('projects.show', $project) }}"
                           class="flex-1 py-2.5 px-3 bg-blue-50 hover:bg-blue-100 active:bg-blue-200 text-blue-600 text-xs font-semibold rounded-xl text-center transition cursor-pointer inline-flex items-center justify-center gap-1.5">
                            <span>Details</span>
                            <span class="text-sm leading-none">&rarr;</span>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ── 4B. LIST VIEW (No Border, No Shadow) ────────────────────── --}}
        <div x-show="view === 'list'"
             x-cloak
             class="bg-white rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50/50">
                            <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-gray-400">PROJECT NAME</th>
                            <th class="px-4 py-4 text-[11px] font-bold uppercase tracking-wider text-gray-400">STATUS</th>
                            <th class="px-4 py-4 text-[11px] font-bold uppercase tracking-wider text-gray-400">PROJECT LEAD (PIC)</th>
                            <th class="px-4 py-4 text-[11px] font-bold uppercase tracking-wider text-gray-400">CLIENT / ORG</th>
                            <th class="px-4 py-4 text-[11px] font-bold uppercase tracking-wider text-gray-400">TASKS</th>
                            <th class="px-4 py-4 text-[11px] font-bold uppercase tracking-wider text-gray-400">PROGRESS</th>
                            <th class="px-4 py-4 text-[11px] font-bold uppercase tracking-wider text-gray-400">DEADLINE</th>
                            <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-gray-400 text-right">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($projects as $project)
                            @php
                                $totalTasks = $project->total_tasks_count ?? 0;
                                $completedTasks = $project->completed_tasks_count ?? 0;

                                $progress = $project->progress > 0
                                    ? (int) $project->progress
                                    : ($totalTasks > 0 ? (int) round(($completedTasks / $totalTasks) * 100) : 0);

                                if ($project->status === 'completed') {
                                    $sc = ['label' => 'Completed', 'class' => 'bg-emerald-50 text-emerald-700', 'dot' => 'bg-emerald-500'];
                                } elseif ($project->status === 'on_hold') {
                                    $sc = ['label' => 'Pending', 'class' => 'bg-purple-50 text-purple-700', 'dot' => 'bg-purple-500'];
                                } elseif ($project->status === 'cancelled') {
                                    $sc = ['label' => 'Cancelled', 'class' => 'bg-rose-50 text-rose-700', 'dot' => 'bg-rose-500'];
                                } elseif ($project->status === 'draft') {
                                    $sc = ['label' => 'Draft', 'class' => 'bg-gray-100 text-gray-700', 'dot' => 'bg-gray-400'];
                                } else {
                                    if ($progress > 0 && $progress < 100 && ($project->id % 2 === 1)) {
                                        $sc = ['label' => 'In Progress', 'class' => 'bg-blue-50 text-blue-700', 'dot' => 'bg-blue-500'];
                                    } else {
                                        $sc = ['label' => 'Active', 'class' => 'bg-emerald-50 text-emerald-700', 'dot' => 'bg-emerald-500'];
                                    }
                                }

                                if ($progress >= 100 || $project->status === 'completed') {
                                    $progressBarColor = 'bg-emerald-500';
                                    $progressTextColor = 'text-emerald-600';
                                } elseif ($project->status === 'on_hold' || $project->status === 'draft') {
                                    $progressBarColor = 'bg-purple-500';
                                    $progressTextColor = 'text-purple-600';
                                } elseif ($progress > 25 && $progress <= 50) {
                                    $progressBarColor = 'bg-amber-500';
                                    $progressTextColor = 'text-amber-600';
                                } else {
                                    $progressBarColor = 'bg-blue-600';
                                    $progressTextColor = 'text-blue-600';
                                }

                                $iconTheme = $iconThemes[$project->id % count($iconThemes)];
                                $projectImages = $project->imageUrls();

                                $managerRole = $project->manager?->structuralLevel?->name
                                    ?? ($project->manager?->roles->first() ? ucfirst(str_replace('_', ' ', $project->manager->roles->first()->name)) : 'Senior Architect');

                                $clientName = $project->client?->name ?? 'Internal Team';
                                $clientOrg = $project->client?->organizationUnit?->name
                                    ?? ($project->client?->company?->name ?? 'Enterprise B2B');
                            @endphp
                            <tr class="hover:bg-gray-50/60 transition-colors">
                                {{-- Project Name --}}
                                <td class="px-6 py-4.5 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        @if(!empty($projectImages))
                                            <img src="{{ $projectImages[0] }}" alt="{{ $project->name }}"
                                                 class="w-10 h-10 rounded-xl object-cover shrink-0 border border-gray-100"
                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                            <div class="w-10 h-10 rounded-xl items-center justify-center shrink-0 {{ $iconTheme['bg'] }} {{ $iconTheme['text'] }}" style="display:none;">
                                                {!! $iconTheme['svg'] !!}
                                            </div>
                                        @else
                                            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 {{ $iconTheme['bg'] }} {{ $iconTheme['text'] }}">
                                                {!! $iconTheme['svg'] !!}
                                            </div>
                                        @endif
                                        <div>
                                            <a href="{{ route('projects.show', $project) }}" class="font-bold text-gray-900 hover:text-blue-600 transition text-sm block">
                                                {{ $project->name }}
                                            </a>
                                        </div>
                                    </div>
                                </td>

                                {{-- Status --}}
                                <td class="px-4 py-4.5 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold {{ $sc['class'] }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $sc['dot'] }}"></span>
                                        {{ $sc['label'] }}
                                    </span>
                                </td>

                                {{-- Project Lead (PIC) --}}
                                <td class="px-4 py-4.5 whitespace-nowrap">
                                    <div class="flex items-center gap-2.5">
                                        @if($project->manager?->avatar)
                                            <img src="{{ Storage::url($project->manager->avatar) }}" alt="{{ $project->manager->name }}" class="w-8 h-8 rounded-full object-cover shrink-0">
                                        @else
                                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white shrink-0"
                                                 style="background-color: {{ $project->manager ? $project->manager->avatarColor() : '#3b82f6' }}">
                                                {{ $project->manager ? $project->manager->initials() : 'NA' }}
                                            </div>
                                        @endif
                                        <div>
                                            <p class="text-xs font-bold text-gray-900">{{ $project->manager->name ?? 'Unassigned' }}</p>
                                            <p class="text-[11px] text-gray-500">{{ $managerRole }}</p>
                                        </div>
                                    </div>
                                </td>

                                {{-- Client / Org --}}
                                <td class="px-4 py-4.5 whitespace-nowrap">
                                    <div>
                                        <p class="text-xs font-bold text-gray-900">{{ $clientName }}</p>
                                        <p class="text-[11px] text-gray-500">{{ $clientOrg }}</p>
                                    </div>
                                </td>

                                {{-- Tasks --}}
                                <td class="px-4 py-4.5 whitespace-nowrap">
                                    <span class="text-xs font-bold text-gray-900">{{ $completedTasks }}</span>
                                    <span class="text-xs text-gray-400">/ {{ $totalTasks }}</span>
                                </td>

                                {{-- Progress --}}
                                <td class="px-4 py-4.5 whitespace-nowrap">
                                    <div class="w-24">
                                        <span class="text-xs font-bold {{ $progressTextColor }} block mb-1">{{ $progress }}%</span>
                                        <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                                            <div class="h-1.5 rounded-full {{ $progressBarColor }}" style="width: {{ min($progress, 100) }}%"></div>
                                        </div>
                                    </div>
                                </td>

                                {{-- Deadline --}}
                                <td class="px-4 py-4.5 whitespace-nowrap text-xs font-medium text-gray-700">
                                    {{ $project->end_date ? \Carbon\Carbon::parse($project->end_date)->format('d M Y') : '-' }}
                                </td>

                                {{-- Actions --}}
                                <td class="px-6 py-4.5 whitespace-nowrap text-right">
                                    <div class="inline-flex items-center justify-end gap-2">
                                        @if(!auth()->user()->hasRole('client'))
                                            <button type="button" @click="openEditProjectModal({{ $projectPayload }}, 'index')"
                                               class="px-3 py-1.5 bg-white hover:bg-gray-50 border border-gray-200/90 text-gray-700 text-xs font-semibold rounded-lg transition cursor-pointer">
                                                Edit
                                            </button>
                                        @endif
                                        <a href="{{ route('projects.show', $project) }}"
                                           class="px-3 py-1.5 bg-blue-50 hover:bg-blue-100 active:bg-blue-200 text-blue-600 text-xs font-semibold rounded-lg transition inline-flex items-center gap-1 cursor-pointer">
                                            <span>Details</span>
                                            <span class="text-sm leading-none">&rarr;</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ── 5. Bottom Pagination & Per Page Bar (No Border, No Shadow) ─── --}}
        <div class="bg-white rounded-2xl px-6 py-4 mt-6 flex flex-col sm:flex-row items-center justify-between gap-4">
            {{-- Left: Showing entries & Show per page selector --}}
            <div class="flex items-center gap-4 text-xs text-gray-500">
                <span>
                    Showing <strong class="font-bold text-gray-900">{{ $projects->firstItem() ?? 0 }}</strong> to <strong class="font-bold text-gray-900">{{ $projects->lastItem() ?? 0 }}</strong> of <strong class="font-bold text-gray-900">{{ $projects->total() }}</strong> entries
                </span>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-400">Show:</span>
                    <div class="relative" x-data="{ open: false }">
                        <button type="button" @click="open = !open" @click.away="open = false"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-white hover:bg-gray-50 border border-gray-200/90 rounded-xl text-xs font-bold text-gray-700 transition cursor-pointer shadow-2xs">
                            <span class="text-blue-600">{{ request('per_page', 6) }}</span>
                            <span class="text-gray-400 font-normal">/ page</span>
                            <svg class="w-3 h-3 text-gray-400 transition-transform duration-150" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="open" x-cloak
                             class="absolute bottom-full mb-1.5 left-0 min-w-[110px] bg-white border border-gray-200/90 rounded-xl shadow-xl p-1 z-40 space-y-0.5">
                            @foreach([6, 12, 24, 48] as $num)
                                <button type="button"
                                        @click="setFilter('per_page', '{{ $num }}'); open = false"
                                        class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs flex items-center justify-between hover:bg-gray-50 transition cursor-pointer {{ (int) request('per_page', 6) === $num ? 'text-blue-600 font-bold bg-blue-50/60' : 'text-gray-700' }}">
                                    <span>{{ $num }} / page</span>
                                    @if((int) request('per_page', 6) === $num)
                                        <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m4.5 12.75 6 6 9-13.5"/>
                                        </svg>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right: Pagination Links --}}
            @if ($projects->hasPages())
                <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center gap-1">
                    {{-- Previous Page Link --}}
                    @if ($projects->onFirstPage())
                        <span class="px-3 py-1.5 text-xs font-semibold text-gray-300 cursor-not-allowed flex items-center gap-1">
                            &lsaquo; Previous
                        </span>
                    @else
                        <a href="{{ $projects->previousPageUrl() }}" class="px-3 py-1.5 text-xs font-semibold text-gray-600 hover:text-blue-600 transition flex items-center gap-1">
                            &lsaquo; Previous
                        </a>
                    @endif

                    {{-- Pagination Elements --}}
                    @foreach ($projects->getUrlRange(1, $projects->lastPage()) as $page => $url)
                        @if ($page == $projects->currentPage())
                            <span class="w-8 h-8 rounded-lg bg-blue-600 text-white font-bold flex items-center justify-center text-xs">
                                {{ $page }}
                            </span>
                        @elseif ($page <= 3 || $page >= $projects->lastPage() - 1 || abs($page - $projects->currentPage()) <= 1)
                            <a href="{{ $url }}" class="w-8 h-8 rounded-lg text-gray-600 hover:bg-gray-100 font-semibold flex items-center justify-center text-xs transition">
                                {{ $page }}
                            </a>
                        @elseif ($page == 4 && $projects->currentPage() > 4)
                            <span class="w-8 h-8 flex items-center justify-center text-xs text-gray-400 font-bold">&hellip;</span>
                        @elseif ($page == $projects->lastPage() - 2 && $projects->currentPage() < $projects->lastPage() - 3)
                            <span class="w-8 h-8 flex items-center justify-center text-xs text-gray-400 font-bold">&hellip;</span>
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($projects->hasMorePages())
                        <a href="{{ $projects->nextPageUrl() }}" class="px-3 py-1.5 text-xs font-semibold text-gray-600 hover:text-blue-600 transition flex items-center gap-1">
                            Next &rsaquo;
                        </a>
                    @else
                        <span class="px-3 py-1.5 text-xs font-semibold text-gray-300 cursor-not-allowed flex items-center gap-1">
                            Next &rsaquo;
                        </span>
                    @endif
                </nav>
            @endif
        </div>
    @endif

    {{-- Include Project Create & Edit Modal --}}
    @include('projects._project_modal', ['clients' => $clients, 'managers' => $managers])

</div>
@endsection
