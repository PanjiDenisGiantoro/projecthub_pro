@extends('layouts.app')
@section('title', 'Calendar')
@section('page-title', 'Calendar')
@section('main-class', 'flex-1 overflow-y-auto px-4 sm:px-6 lg:px-8 py-6 bg-slate-50 text-slate-800 min-h-screen')

@section('content')
    <div class="max-w-7xl mx-auto space-y-4 font-sans select-none"
        style="font-family: 'Plus Jakarta Sans', Inter, system-ui, sans-serif;" x-data="calendarManager({
                             initialEventsUrl: '{{ route('calendar.events') }}',
                             storeUrl: '{{ route('calendar.events.store') }}',
                             updateUrlTemplate: '{{ url('/calendar/events') }}/__ID__',
                             deleteUrlTemplate: '{{ url('/calendar/events') }}/__ID__',
                             csrfToken: '{{ csrf_token() }}',
                             members: {{ json_encode($members) }},
                             categories: {{ json_encode($categories) }},
                             colors: {{ json_encode($colors) }},
                             availableTags: {{ json_encode($availableTags) }},
                             hasGoogleConnected: {{ json_encode($hasGoogleConnected) }},
                             googleConnectUrl: '{{ $googleConnectUrl }}',
                             initialView: '{{ request('view', 'month') }}',
                             initialCategory: '{{ request('category', '') }}'
                         })" x-init="init()" x-cloak>

        {{-- ── 1. Top Header: Navigation, View Switcher & New Event ───────────────── --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-1">
            {{-- Left: Current Date Header & Navigation --}}
            <div class="flex items-center gap-3">
                <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 min-w-[180px]" x-text="headerTitle">
                </h2>
                <div class="flex items-center gap-1.5">
                    <button @click="navigateDate('prev')"
                        class="p-2 rounded-xl bg-slate-200/80 hover:bg-slate-300 text-slate-700 transition"
                        title="Previous">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <button @click="goToToday()"
                        class="px-3.5 py-1.5 text-xs font-semibold rounded-xl bg-slate-200/80 hover:bg-slate-300 text-slate-800 transition">
                        Today
                    </button>
                    <button @click="navigateDate('next')"
                        class="p-2 rounded-xl bg-slate-200/80 hover:bg-slate-300 text-slate-700 transition" title="Next">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Right: Views (Month, Week, Day, List) + New Event Button --}}
            <div class="flex items-center gap-2.5 flex-wrap">
                <div class="inline-flex items-center p-1 rounded-2xl bg-slate-200/80 gap-1 text-xs font-medium">
                    <button @click="view = 'month'"
                        :class="view === 'month' ? 'bg-white text-blue-600 font-bold' : 'text-slate-600 hover:text-slate-900'"
                        class="px-3.5 py-1.5 rounded-xl flex items-center gap-1.5 transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Month
                    </button>
                    <button @click="view = 'week'"
                        :class="view === 'week' ? 'bg-white text-blue-600 font-bold' : 'text-slate-600 hover:text-slate-900'"
                        class="px-3.5 py-1.5 rounded-xl flex items-center gap-1.5 transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                        Week
                    </button>
                    <button @click="view = 'day'"
                        :class="view === 'day' ? 'bg-white text-blue-600 font-bold' : 'text-slate-600 hover:text-slate-900'"
                        class="px-3.5 py-1.5 rounded-xl flex items-center gap-1.5 transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Day
                    </button>
                    <button @click="view = 'list'"
                        :class="view === 'list' ? 'bg-white text-blue-600 font-bold' : 'text-slate-600 hover:text-slate-900'"
                        class="px-3.5 py-1.5 rounded-xl flex items-center gap-1.5 transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h7" />
                        </svg>
                        List
                    </button>
                </div>

                <template x-if="hasGoogleConnected">
                    <a href="{{ route('profile') }}"
                        class="hidden sm:inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-semibold transition"
                        title="Google Calendar Terhubung">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span>Google Calendar</span>
                        <span
                            class="text-[10px] font-bold bg-emerald-200/70 text-emerald-800 px-1.5 py-0.5 rounded-md">Connected</span>
                    </a>
                </template>
                <template x-if="!hasGoogleConnected">
                    <a :href="googleConnectUrl" target="_blank"
                        class="hidden sm:inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-700 text-xs font-semibold transition"
                        title="Klik untuk menghubungkan Google Calendar">
                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                        <span>Google Calendar</span>
                        <span
                            class="text-[10px] font-bold bg-amber-200/70 text-amber-800 px-1.5 py-0.5 rounded-md flex items-center gap-1">Connect
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg></span>
                    </a>
                </template>

                <button @click="openCreateModal()"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs rounded-xl flex items-center gap-1.5 transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    New Event
                </button>
            </div>
        </div>

        {{-- ── 2. Search & Filter Bar ─────────────────────────────────────────────── --}}
        <div class="space-y-2">
            {{-- Full-width Search Bar --}}
            <div class="relative w-full">
                <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="text" x-model="searchQuery" placeholder="Search events..."
                    class="w-full bg-white rounded-2xl pl-10 pr-9 py-2.5 text-xs sm:text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition">
                <button x-show="searchQuery" @click="searchQuery = ''"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Filter Pills: Colors, Tags, Categories --}}
            <div class="flex items-center gap-2 flex-wrap text-xs">
                {{-- Colors Dropdown --}}
                <div class="relative" x-data="{ open: false }" @click.away="open = false">
                    <button @click="open = !open"
                        class="px-3.5 py-1.5 rounded-xl bg-white hover:bg-slate-100 text-slate-700 flex items-center gap-1.5 transition">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        Colors
                        <span x-show="selectedColors.length > 0"
                            class="ml-0.5 px-1.5 py-0.2 bg-blue-600 text-white rounded-full text-[10px] font-bold"
                            x-text="selectedColors.length"></span>
                    </button>
                    <div x-show="open" x-cloak class="absolute left-0 mt-2 w-44 rounded-2xl bg-white z-30 p-2 space-y-1">
                        <div class="px-2 py-1 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Filter by
                            Color</div>
                        <div class="h-px bg-slate-100 my-1"></div>
                        <template x-for="c in colors" :key="c.value">
                            <label
                                class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-slate-50 cursor-pointer text-slate-700">
                                <input type="checkbox" :value="c.value" x-model="selectedColors"
                                    class="w-3.5 h-3.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                <span class="w-2.5 h-2.5 rounded-full" :class="c.bg"></span>
                                <span class="text-xs" x-text="c.name"></span>
                            </label>
                        </template>
                    </div>
                </div>

                {{-- Tags Dropdown --}}
                <div class="relative" x-data="{ open: false }" @click.away="open = false">
                    <button @click="open = !open"
                        class="px-3.5 py-1.5 rounded-xl bg-white hover:bg-slate-100 text-slate-700 flex items-center gap-1.5 transition">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                        Tags
                        <span x-show="selectedTags.length > 0"
                            class="ml-0.5 px-1.5 py-0.2 bg-blue-600 text-white rounded-full text-[10px] font-bold"
                            x-text="selectedTags.length"></span>
                    </button>
                    <div x-show="open" x-cloak class="absolute left-0 mt-2 w-44 rounded-2xl bg-white z-30 p-2 space-y-1">
                        <div class="px-2 py-1 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Filter by
                            Tag</div>
                        <div class="h-px bg-slate-100 my-1"></div>
                        <template x-for="t in availableTags" :key="t">
                            <label
                                class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-slate-50 cursor-pointer text-slate-700">
                                <input type="checkbox" :value="t" x-model="selectedTags"
                                    class="w-3.5 h-3.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                <span class="text-xs" x-text="t"></span>
                            </label>
                        </template>
                    </div>
                </div>

                {{-- Categories Dropdown --}}
                <div class="relative" x-data="{ open: false }" @click.away="open = false">
                    <button @click="open = !open"
                        class="px-3.5 py-1.5 rounded-xl bg-white hover:bg-slate-100 text-slate-700 flex items-center gap-1.5 transition">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        Categories
                        <span x-show="selectedCategories.length > 0"
                            class="ml-0.5 px-1.5 py-0.2 bg-blue-600 text-white rounded-full text-[10px] font-bold"
                            x-text="selectedCategories.length"></span>
                    </button>
                    <div x-show="open" x-cloak class="absolute left-0 mt-2 w-48 rounded-2xl bg-white z-30 p-2 space-y-1">
                        <div class="px-2 py-1 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Filter by
                            Category</div>
                        <div class="h-px bg-slate-100 my-1"></div>
                        <template x-for="cat in categories" :key="cat">
                            <label
                                class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-slate-50 cursor-pointer text-slate-700">
                                <input type="checkbox" :value="cat" x-model="selectedCategories"
                                    class="w-3.5 h-3.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                <span class="text-xs" x-text="cat"></span>
                            </label>
                        </template>
                    </div>
                </div>

                {{-- Clear Filters Button --}}
                <button x-show="hasActiveFilters" @click="clearFilters()"
                    class="px-2.5 py-1.5 text-xs text-slate-500 hover:text-rose-600 transition flex items-center gap-1 font-medium">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Clear
                </button>
            </div>

            {{-- Active Filter Tags Row --}}
            <div x-show="hasActiveFilters" class="flex items-center gap-1.5 flex-wrap pt-1">
                <span class="text-[11px] text-slate-500 font-medium">Active filters:</span>
                {{-- Active Colors --}}
                <template x-for="cVal in selectedColors" :key="'ac-'+cVal">
                    <span
                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg bg-blue-50 text-[11px] text-blue-700 font-medium">
                        <span class="w-2 h-2 rounded-full" :class="getColorMeta(cVal).bg"></span>
                        <span x-text="getColorMeta(cVal).name"></span>
                        <button @click="selectedColors = selectedColors.filter(c => c !== cVal)"
                            class="hover:text-blue-900 ml-0.5">×</button>
                    </span>
                </template>
                {{-- Active Tags --}}
                <template x-for="tVal in selectedTags" :key="'at-'+tVal">
                    <span
                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg bg-slate-200/80 text-[11px] text-slate-700 font-medium">
                        <span x-text="tVal"></span>
                        <button @click="selectedTags = selectedTags.filter(t => t !== tVal)"
                            class="hover:text-slate-900 ml-0.5">×</button>
                    </span>
                </template>
                {{-- Active Categories --}}
                <template x-for="catVal in selectedCategories" :key="'acat-'+catVal">
                    <span
                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg bg-blue-50 text-[11px] text-blue-700 font-medium">
                        <span x-text="catVal"></span>
                        <button @click="selectedCategories = selectedCategories.filter(c => c !== catVal)"
                            class="hover:text-blue-900 ml-0.5">×</button>
                    </span>
                </template>
            </div>
        </div>

        {{-- Loading indicator --}}
        <div x-show="loading" class="py-16 flex flex-col items-center justify-center gap-2 text-slate-400">
            <svg class="w-6 h-6 animate-spin text-blue-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span class="text-xs font-medium text-slate-500">Memuat jadwal dan acara...</span>
        </div>

        {{-- ── 3. CALENDAR VIEWS CONTAINER (Borderless & Shadowless) ─────────────────── --}}
        <div x-show="!loading" class="w-full">

            {{-- ═══════════════════════════════════════════════════════════════════════
            VIEW 1: MONTH VIEW (Flat Seamless Grid)
            ═══════════════════════════════════════════════════════════════════════ --}}
            <div x-show="view === 'month'" class="rounded-3xl bg-white overflow-hidden">
                {{-- Weekdays header --}}
                <div class="grid grid-cols-7 bg-slate-50/70 text-center text-xs font-semibold text-slate-600 py-3">
                    <div>Sun</div>
                    <div>Mon</div>
                    <div>Tue</div>
                    <div>Wed</div>
                    <div>Thu</div>
                    <div>Fri</div>
                    <div>Sat</div>
                </div>

                {{-- Month days grid --}}
                <div class="grid grid-cols-7 auto-rows-fr">
                    <template x-for="day in monthDays" :key="day.dateKey">
                        <div class="min-h-[105px] sm:min-h-[120px] p-2.5 flex flex-col transition hover:bg-slate-50/80 group relative cursor-pointer"
                            @click="handleDayCellClick(day)">
                            {{-- Day number header --}}
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-xs font-medium" :class="{
                                                          'bg-blue-600 text-white font-bold rounded-full w-6 h-6 flex items-center justify-center': day.isToday,
                                                          'text-slate-800 font-semibold': !day.isToday && day.isCurrentMonth,
                                                          'text-slate-300': !day.isToday && !day.isCurrentMonth
                                                      }" x-text="day.dayNumber">
                                </span>
                                <span x-show="day.events.length > 0"
                                    class="text-[10px] text-slate-400 font-mono hidden sm:inline"
                                    x-text="day.events.length + ' event'"></span>
                            </div>

                            {{-- Event pills list --}}
                            <div class="space-y-1 flex-1 overflow-hidden">
                                <template x-for="event in day.events.slice(0, 3)" :key="event.id">
                                    <div @click.stop="handleEventClick(event)"
                                        class="text-[11px] font-semibold px-2 py-0.5 rounded-lg truncate transition hover:opacity-90 flex items-center gap-1 cursor-pointer"
                                        :class="getEventPillClass(event.color)">
                                        <span class="truncate" x-text="event.title"></span>
                                    </div>
                                </template>
                                <template x-if="day.events.length > 3">
                                    <div class="text-[10px] text-blue-600 font-semibold px-1 cursor-pointer hover:underline"
                                        @click.stop="view = 'day'; currentDate = new Date(day.dateObject)">
                                        <span x-text="'+ ' + (day.events.length - 3) + ' more'"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════════════════════════
            VIEW 2: WEEK VIEW (Flat Seamless Timetable)
            ═══════════════════════════════════════════════════════════════════════ --}}
            <div x-show="view === 'week'" class="rounded-3xl bg-white overflow-x-auto">
                <div class="min-w-[760px]">
                    {{-- Week headers --}}
                    <div class="grid grid-cols-8 bg-slate-50/70 text-center text-xs font-semibold text-slate-600">
                        <div class="py-3 text-slate-500">Time</div>
                        <template x-for="col in weekDays" :key="col.dateKey">
                            <div class="py-2.5 px-1 flex flex-col items-center justify-center"
                                :class="col.isToday ? 'bg-blue-50/60 text-blue-700 font-bold' : ''">
                                <span class="text-[11px] text-slate-400" x-text="col.dayName"></span>
                                <span class="text-xs" :class="col.isToday ? 'text-blue-700 font-bold' : 'text-slate-800'"
                                    x-text="col.formattedDate"></span>
                            </div>
                        </template>
                    </div>

                    {{-- 24 hours grid --}}
                    <div class="max-h-[680px] overflow-y-auto">
                        <template x-for="hour in 24" :key="'whour-'+(hour-1)">
                            <div class="grid grid-cols-8 min-h-[52px]">
                                {{-- Hour label --}}
                                <div class="p-2 text-center text-xs font-mono text-slate-400 bg-slate-50/40">
                                    <span x-text="formatHourLabel(hour-1)"></span>
                                </div>
                                {{-- 7 day slots --}}
                                <template x-for="col in weekDays" :key="'wslot-'+col.dateKey+'-'+(hour-1)">
                                    <div class="p-1 hover:bg-slate-50/80 transition relative cursor-pointer"
                                        @click="openCreateModalWithTime(col.dateObject, hour-1)">
                                        <div class="space-y-1">
                                            <template x-for="event in getEventsForSlot(col.dateObject, hour-1)"
                                                :key="event.id">
                                                <div @click.stop="handleEventClick(event)"
                                                    class="text-[11px] font-semibold p-1.5 rounded-lg transition hover:opacity-90 truncate flex items-center gap-1 cursor-pointer"
                                                    :class="getEventPillClass(event.color)">
                                                    <span class="truncate" x-text="event.title"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════════════════════════
            VIEW 3: DAY VIEW (Flat Seamless Timeline)
            ═══════════════════════════════════════════════════════════════════════ --}}
            <div x-show="view === 'day'" class="rounded-3xl bg-white overflow-hidden">
                <div class="max-h-[720px] overflow-y-auto">
                    <template x-for="hour in 24" :key="'dhour-'+(hour-1)">
                        <div class="flex min-h-[64px] hover:bg-slate-50/60 transition"
                            @click="openCreateModalWithTime(currentDate, hour-1)">
                            {{-- Hour Column (00:00, 01:00...) --}}
                            <div
                                class="w-16 sm:w-20 shrink-0 p-3 text-xs font-mono text-slate-400 bg-slate-50/40 flex items-start justify-center">
                                <span x-text="formatHourLabel(hour-1)"></span>
                            </div>
                            {{-- Slot events container --}}
                            <div class="flex-1 p-2 space-y-1.5 cursor-pointer">
                                <template x-for="event in getEventsForSlot(currentDate, hour-1)" :key="event.id">
                                    <div @click.stop="handleEventClick(event)"
                                        class="p-3 rounded-2xl bg-slate-50 hover:bg-blue-50/50 transition flex items-center justify-between gap-3 cursor-pointer">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <span class="w-2.5 h-2.5 rounded-full shrink-0"
                                                :class="getColorMeta(event.color).bg"></span>
                                            <div class="min-w-0">
                                                <p class="text-xs sm:text-sm font-bold text-slate-800 truncate"
                                                    x-text="event.title"></p>
                                                <p x-show="event.description" class="text-[11px] text-slate-500 truncate"
                                                    x-text="event.description"></p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <span
                                                class="px-2.5 py-0.5 rounded-full text-[10px] bg-blue-100/70 text-blue-700 font-semibold"
                                                x-text="event.category"></span>
                                            <span class="text-[11px] text-slate-500 font-mono"
                                                x-text="formatTimeWindow(event.startTime, event.endTime)"></span>
                                            <template x-if="event.google_meet_link">
                                                <a :href="event.google_meet_link" target="_blank" @click.stop
                                                    class="px-2.5 py-1 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs flex items-center gap-1 font-semibold"
                                                    title="Join Google Meet">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                    </svg>
                                                    Meet
                                                </a>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════════════════════════
            VIEW 4: LIST VIEW (Flat Clean Cards)
            ═══════════════════════════════════════════════════════════════════════ --}}
            <div x-show="view === 'list'" class="rounded-3xl bg-white p-4 sm:p-6">
                <template x-if="groupedListEvents.length === 0">
                    <div class="py-16 text-center">
                        <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <p class="text-sm font-medium text-slate-500">Tidak ada event atau jadwal ditemukan.</p>
                        <button @click="openCreateModal()"
                            class="mt-3 text-xs text-blue-600 hover:text-blue-700 underline font-semibold">
                            + Tambah Event Baru
                        </button>
                    </div>
                </template>

                <div class="space-y-6">
                    <template x-for="group in groupedListEvents" :key="group.dateLabel">
                        <div class="space-y-3">
                            {{-- Date header e.g. Monday, October 20, 2025 --}}
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                                <h3 class="text-xs sm:text-sm font-bold text-slate-800" x-text="group.dateLabel"></h3>
                            </div>

                            {{-- Event cards in this date --}}
                            <div class="space-y-2.5">
                                <template x-for="event in group.events" :key="event.id">
                                    <div @click="handleEventClick(event)"
                                        class="group rounded-2xl bg-slate-50 hover:bg-blue-50/40 p-4 transition cursor-pointer">
                                        <div class="flex items-start gap-3.5">
                                            {{-- Colored dot --}}
                                            <div class="mt-1 w-2.5 h-2.5 rounded-full shrink-0"
                                                :class="getColorMeta(event.color).bg"></div>

                                            {{-- Main content --}}
                                            <div class="flex-1 min-w-0">
                                                <div
                                                    class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-1">
                                                    <div class="min-w-0">
                                                        <h4 class="font-bold text-sm sm:text-base text-slate-900 group-hover:text-blue-600 transition truncate"
                                                            x-text="event.title"></h4>
                                                        <p x-show="event.description"
                                                            class="mt-1 text-xs sm:text-sm text-slate-500 line-clamp-2"
                                                            x-text="event.description"></p>
                                                    </div>
                                                    {{-- Category Badge --}}
                                                    <div class="flex items-center gap-1.5 shrink-0">
                                                        <span
                                                            class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100/70 text-blue-700"
                                                            x-text="event.category"></span>
                                                    </div>
                                                </div>

                                                {{-- Time & Tags footer --}}
                                                <div class="mt-3 flex flex-wrap items-center gap-3 text-xs text-slate-500">
                                                    {{-- Clock and time range --}}
                                                    <div class="flex items-center gap-1.5">
                                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        <span class="font-medium text-slate-600"
                                                            x-text="formatTimeWindow(event.startTime, event.endTime)"></span>
                                                    </div>

                                                    {{-- Tag chips --}}
                                                    <template x-for="tag in (event.tags || [])" :key="tag">
                                                        <span
                                                            class="px-2.5 py-1 rounded-lg bg-white text-slate-600 text-[10px] font-semibold"
                                                            x-text="tag"></span>
                                                    </template>

                                                    {{-- Google Meet Join Button (Direct Instant) --}}
                                                    <template x-if="event.google_meet_link">
                                                        <a :href="event.google_meet_link" target="_blank" @click.stop
                                                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-[11px] font-semibold transition"
                                                            title="Masuk ke Google Meet">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                            </svg>
                                                            Join Meet
                                                        </a>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════════════════════
        MODAL: CREATE / EVENT DETAILS (Borderless & Flat)
        ═══════════════════════════════════════════════════════════════════════════ --}}
        <div x-show="modalOpen" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4 overflow-y-auto"
            @click.self="closeModal()">
            <div class="bg-white rounded-3xl w-full max-w-md p-6 relative my-8" @keydown.escape.window="closeModal()">

                {{-- Modal Header --}}
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 class="text-base font-bold text-slate-900"
                            x-text="isCreating ? 'Create Event' : 'Event Details'"></h3>
                        <p class="text-xs text-slate-500 mt-0.5"
                            x-text="isCreating ? 'Add a new event to your calendar' : 'View and edit event details'"></p>
                    </div>
                    <button @click="closeModal()"
                        class="text-slate-400 hover:text-slate-600 p-1.5 rounded-xl hover:bg-slate-100 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Form Body --}}
                <form @submit.prevent="saveEvent()" class="space-y-4 text-xs">
                    {{-- Title --}}
                    <div class="space-y-1.5">
                        <label class="block font-semibold text-slate-700">Title <span class="text-red-500">*</span></label>
                        <input type="text" x-model="form.title" :disabled="!isCreating && !form.can_edit"
                            placeholder="Event title" required
                            class="w-full bg-slate-100 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 disabled:opacity-60 transition">
                    </div>

                    {{-- Description --}}
                    <div class="space-y-1.5">
                        <label class="block font-semibold text-slate-700">Description</label>
                        <textarea x-model="form.description" :disabled="!isCreating && !form.can_edit" rows="3"
                            placeholder="Event description..."
                            class="w-full bg-slate-100 rounded-xl px-3.5 py-2 text-xs sm:text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 disabled:opacity-60 transition"></textarea>
                    </div>

                    {{-- Start Time & End Time (Row 2 cols) --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <label class="block font-semibold text-slate-700">Start Time <span
                                    class="text-red-500">*</span></label>
                            <input type="datetime-local" x-model="form.start_time" :disabled="!isCreating && !form.can_edit"
                                required
                                class="w-full bg-slate-100 rounded-xl px-3 py-2 text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 disabled:opacity-60 transition">
                        </div>
                        <div class="space-y-1.5">
                            <label class="block font-semibold text-slate-700">End Time <span
                                    class="text-red-500">*</span></label>
                            <input type="datetime-local" x-model="form.end_time" :disabled="!isCreating && !form.can_edit"
                                required
                                class="w-full bg-slate-100 rounded-xl px-3 py-2 text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 disabled:opacity-60 transition">
                        </div>
                    </div>

                    {{-- Category & Color (Row 2 cols) --}}
                    <div class="grid grid-cols-2 gap-3">
                        {{-- Category (Read-only, auto value: Meeting) --}}
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <label class="block font-semibold text-slate-700">Category</label>
                                <span class="text-[10px] font-semibold flex items-center gap-1"
                                    :class="hasGoogleConnected ? 'text-emerald-600' : 'text-amber-600'">
                                    <span class="w-1.5 h-1.5 rounded-full"
                                        :class="hasGoogleConnected ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                                    <span
                                        x-text="hasGoogleConnected ? 'Google Connected' : 'Google Belum Terhubung'"></span>
                                </span>
                            </div>
                            <div
                                class="w-full bg-slate-100 rounded-xl px-3.5 py-2 text-xs text-slate-800 flex items-center justify-between select-none">
                                <span class="flex items-center gap-2 font-semibold text-slate-800">
                                    <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    <span>Meeting</span>
                                </span>
                                <span class="text-[10px] bg-blue-100/80 text-blue-700 font-bold px-2 py-0.5 rounded-md">
                                    Auto GMeet
                                </span>
                            </div>
                            <input type="hidden" x-model="form.category" value="Meeting">
                        </div>

                        {{-- Color Select (Custom popover dropdown matching task styling) --}}
                        <div class="space-y-1.5" x-data="{ openColorPicker: false }">
                            <label class="block font-semibold text-slate-700">Color</label>
                            <div class="relative">
                                <button type="button"
                                    @click="if (isCreating || form.can_edit) openColorPicker = !openColorPicker"
                                    :disabled="!isCreating && !form.can_edit"
                                    class="w-full bg-slate-100 hover:bg-slate-200/70 rounded-xl px-3.5 py-2 text-xs text-slate-800 flex items-center justify-between transition focus:outline-none focus:ring-2 focus:ring-blue-500/20 disabled:opacity-60 text-left cursor-pointer">
                                    <span class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full shrink-0 shadow-2xs"
                                            :class="getColorMeta(form.color).bg"></span>
                                        <span class="font-medium text-slate-800"
                                            x-text="getColorMeta(form.color).name"></span>
                                    </span>
                                    <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-150 shrink-0"
                                        :class="openColorPicker ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>

                                {{-- Dropdown popover panel --}}
                                <div x-show="openColorPicker" @click.outside="openColorPicker = false" x-cloak
                                    x-transition:enter="transition ease-out duration-150"
                                    x-transition:enter-start="opacity-0 scale-95"
                                    x-transition:enter-end="opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-100"
                                    x-transition:leave-start="opacity-100 scale-100"
                                    x-transition:leave-end="opacity-0 scale-95"
                                    class="absolute left-0 right-0 top-full mt-1.5 bg-white rounded-2xl shadow-xl border border-slate-100 p-1.5 z-40 space-y-0.5 max-h-52 overflow-y-auto">
                                    <div class="px-2.5 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                        Color Tag
                                    </div>
                                    <template x-for="c in colors" :key="c.value">
                                        <button type="button" @click="form.color = c.value; openColorPicker = false"
                                            class="w-full text-left px-2.5 py-1.5 text-xs flex items-center justify-between rounded-xl transition cursor-pointer"
                                            :class="form.color === c.value ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-700 hover:bg-slate-50'">
                                            <span class="flex items-center gap-2.5">
                                                <span class="w-2.5 h-2.5 rounded-full shrink-0" :class="c.bg"></span>
                                                <span x-text="c.name"></span>
                                            </span>
                                            <svg x-show="form.color === c.value" class="w-4 h-4 text-blue-600 shrink-0"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Alert Validasi: Google Calendar Belum Terhubung --}}
                    <div x-show="!form.google_meet_link && !hasGoogleConnected" x-transition
                        class="p-3.5 rounded-2xl bg-amber-50 border border-amber-200/80 text-amber-900 space-y-2">
                        <div class="flex items-start gap-2.5">
                            <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <div class="flex-1 text-xs">
                                <p class="font-bold text-amber-900">Google Calendar Belum Terhubung</p>
                                <p class="mt-0.5 text-amber-700 leading-relaxed text-[11px]">
                                    Event meeting ini otomatis terintegrasi dengan Google Meet. Akun Anda belum terhubung ke
                                    Google Calendar sehingga link Google Meet belum bisa dibuat otomatis. Silakan hubungkan
                                    Google Calendar Anda terlebih dahulu.
                                </p>
                                <div class="mt-2.5 flex items-center gap-2">
                                    <a :href="googleConnectUrl" target="_blank"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs transition">
                                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor">
                                            <path
                                                d="M12.48 10.92v3.28h7.84c-.24 1.84-.853 3.187-1.787 4.133-1.147 1.147-2.933 2.4-6.053 2.4-4.827 0-8.6-3.893-8.6-8.72s3.773-8.72 8.6-8.72c2.6 0 4.507 1.027 5.907 2.347l2.307-2.307C18.747 1.44 16.133 0 12.48 0 5.867 0 .307 5.387.307 12s5.56 12 12.173 12c3.573 0 6.267-1.173 8.373-3.36 2.16-2.16 2.84-5.213 2.84-7.667 0-.76-.053-1.467-.173-2.053H12.48z" />
                                        </svg>
                                        Hubungkan Google Calendar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ── Auto Google Meet Box ── --}}
                    <template x-if="!form.google_meet_link && hasGoogleConnected">
                        <div class="p-3.5 rounded-2xl bg-blue-50/70 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="flex items-center gap-1.5 text-xs font-bold text-blue-900">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    Google Meet Link
                                </span>
                                <template x-if="form.google_meet_link">
                                    <span
                                        class="text-[10px] text-emerald-700 font-bold bg-emerald-100 px-2 py-0.5 rounded-full">Ready</span>
                                </template>
                                <template x-if="!form.google_meet_link && hasGoogleConnected">
                                    <span
                                        class="text-[10px] text-blue-700 font-bold bg-blue-100 px-2 py-0.5 rounded-full">Auto-Create</span>
                                </template>
                            </div>
                        </div>
                    </template>

                    <template x-if="form.google_meet_link">
                        <div class="p-3.5 rounded-2xl bg-blue-50/70 space-y-2">
                            <div class="flex items-center justify-between gap-2 pt-1">
                                <input type="text" readonly :value="form.google_meet_link"
                                    class="bg-white rounded-xl px-2.5 py-1.5 text-xs text-blue-700 font-mono flex-1 select-all">
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <a :href="form.google_meet_link" target="_blank"
                                        class="px-3 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold transition flex items-center gap-1">
                                        Join
                                    </a>
                                    <button type="button" @click="copyMeetLink(form.google_meet_link)"
                                        class="px-3 py-1.5 rounded-xl bg-white hover:bg-slate-50 text-slate-700 text-xs font-medium transition">
                                        <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template x-if="!form.google_meet_link && hasGoogleConnected">
                        <div class="p-3.5 rounded-2xl bg-blue-50/70 space-y-2">
                            <p class="text-[11px] text-blue-800 font-medium leading-relaxed">
                                <span class="font-bold">Auto-Generated:</span> Link Google Meet akan otomatis dibuat melalui
                                akun Google Calendar Anda setelah event disimpan.
                            </p>
                        </div>
                    </template>

                    {{-- Interactive Tag Chips --}}
                    <div class="space-y-1.5">
                        <label class="block font-semibold text-slate-700">Tags</label>
                        <div class="flex flex-wrap gap-1.5">
                            <template x-for="tag in availableTags" :key="tag">
                                <button type="button" :disabled="!isCreating && !form.can_edit" @click="toggleTag(tag)"
                                    class="px-3 py-1 rounded-full text-xs font-semibold transition cursor-pointer"
                                    :class="form.tags.includes(tag) ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'">
                                    <span x-text="tag"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- Attendees / Member Invite --}}
                    <div class="space-y-1.5" x-data="{ memberOpen: false }">
                        <label class="block font-semibold text-slate-700">Invite Members</label>
                        <div class="relative">
                            <button type="button" :disabled="!isCreating && !form.can_edit"
                                @click="memberOpen = !memberOpen"
                                class="w-full bg-slate-100 rounded-xl px-3 py-2 text-xs text-slate-700 flex items-center justify-between hover:bg-slate-200/80 transition text-left">
                                <span
                                    x-text="form.attendees.length > 0 ? form.attendees.length + ' anggota di-invite' : 'Pilih anggota tim...'"></span>
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div x-show="memberOpen" x-cloak @click.away="memberOpen = false"
                                class="absolute left-0 right-0 mt-1 max-h-48 overflow-y-auto rounded-2xl bg-white p-2 z-30 space-y-1 shadow-lg">
                                <template x-for="m in members" :key="m.id">
                                    <label
                                        class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg hover:bg-slate-50 cursor-pointer text-slate-700">
                                        <input type="checkbox" :value="m.id" x-model="form.attendees"
                                            class="w-3.5 h-3.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                        <span class="text-xs truncate font-medium"
                                            x-text="m.name + ' (' + m.email + ')'"></span>
                                    </label>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="flex items-center justify-between pt-3">
                        <div>
                            {{-- Delete button on the left (only when editing and can_edit) --}}
                            <template x-if="!isCreating && form.can_edit">
                                <button type="button" @click="deleteEvent(form.raw_id)" :disabled="submitting"
                                    class="px-4 py-2 bg-rose-50 hover:bg-rose-100 text-rose-600 font-semibold text-xs rounded-xl transition cursor-pointer">
                                    Delete
                                </button>
                            </template>
                        </div>

                        <div class="flex items-center gap-2">
                            <button type="button" @click="closeModal()"
                                class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl transition cursor-pointer">
                                Cancel
                            </button>
                            <template x-if="isCreating || form.can_edit">
                                <button type="submit"
                                    :disabled="submitting || (!form.google_meet_link && !hasGoogleConnected)"
                                    class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs rounded-xl transition cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                                    <span x-show="!submitting" x-text="isCreating ? 'Create' : 'Save'"></span>
                                    <span x-show="submitting">Saving...</span>
                                </button>
                            </template>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ── 4. Alpine.js Calendar Controller Logic ──────────────────────────────── --}}
    <script>
        function calendarManager(config) {
            return {
                // Data & state
                events: [],
                currentDate: new Date(),
                view: config.initialView || 'month',
                loading: false,
                submitting: false,
                copied: false,

                // Filters
                searchQuery: '',
                selectedColors: [],
                selectedTags: [],
                selectedCategories: config.initialCategory ? [config.initialCategory] : [],

                // Metadata
                members: config.members || [],
                categories: config.categories || [],
                colors: config.colors || [],
                availableTags: config.availableTags || [],
                hasGoogleConnected: !!config.hasGoogleConnected,
                googleConnectUrl: config.googleConnectUrl || '/google-calendar/connect',

                // Modal
                modalOpen: false,
                isCreating: false,
                form: {
                    id: null,
                    raw_id: null,
                    source: 'custom',
                    title: '',
                    description: '',
                    start_time: '',
                    end_time: '',
                    category: 'Meeting',
                    color: 'blue',
                    tags: [],
                    attendees: [],
                    google_meet_link: '',
                    can_edit: true,
                },

                init() {
                    this.fetchEvents();
                },

                fetchEvents() {
                    this.loading = true;
                    fetch(config.initialEventsUrl, {
                        headers: { 'Accept': 'application/json' }
                    })
                        .then(res => res.json())
                        .then(data => {
                            this.events = data.map(item => ({
                                ...item,
                                startDate: new Date(item.startTime),
                                endDate: new Date(item.endTime),
                            }));
                            this.loading = false;
                        })
                        .catch(err => {
                            console.error('Error fetching calendar events:', err);
                            this.loading = false;
                        });
                },

                // Filtered events
                get filteredEvents() {
                    return this.events.filter(event => {
                        // Search query
                        if (this.searchQuery) {
                            const q = this.searchQuery.toLowerCase();
                            const matchTitle = (event.title || '').toLowerCase().includes(q);
                            const matchDesc = (event.description || '').toLowerCase().includes(q);
                            const matchCat = (event.category || '').toLowerCase().includes(q);
                            const matchTag = (event.tags || []).some(t => t.toLowerCase().includes(q));
                            if (!matchTitle && !matchDesc && !matchCat && !matchTag) return false;
                        }

                        // Colors
                        if (this.selectedColors.length > 0 && !this.selectedColors.includes(event.color)) {
                            return false;
                        }

                        // Tags
                        if (this.selectedTags.length > 0) {
                            const hasTag = (event.tags || []).some(t => this.selectedTags.includes(t));
                            if (!hasTag) return false;
                        }

                        // Categories
                        if (this.selectedCategories.length > 0 && !this.selectedCategories.includes(event.category)) {
                            return false;
                        }

                        return true;
                    });
                },

                get hasActiveFilters() {
                    return this.selectedColors.length > 0 || this.selectedTags.length > 0 || this.selectedCategories.length > 0;
                },

                clearFilters() {
                    this.selectedColors = [];
                    this.selectedTags = [];
                    this.selectedCategories = [];
                    this.searchQuery = '';
                },

                // Navigation
                navigateDate(dir) {
                    const next = dir === 'next' ? 1 : -1;
                    const d = new Date(this.currentDate);

                    if (this.view === 'month') {
                        d.setMonth(d.getMonth() + next);
                    } else if (this.view === 'week') {
                        d.setDate(d.getDate() + (next * 7));
                    } else if (this.view === 'day') {
                        d.setDate(d.getDate() + next);
                    } else if (this.view === 'list') {
                        d.setMonth(d.getMonth() + next);
                    }

                    this.currentDate = d;
                },

                goToToday() {
                    this.currentDate = new Date();
                },

                get headerTitle() {
                    const d = this.currentDate;
                    if (this.view === 'month') {
                        return d.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
                    } else if (this.view === 'week') {
                        const weekStart = this.getWeekStart(d);
                        return 'Week of ' + weekStart.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                    } else if (this.view === 'day') {
                        return d.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
                    } else {
                        return 'All Events';
                    }
                },

                // Month Days Grid Math
                get monthDays() {
                    const year = this.currentDate.getFullYear();
                    const month = this.currentDate.getMonth();
                    const today = new Date();

                    const firstDayOfMonth = new Date(year, month, 1);
                    const startDayOfWeek = firstDayOfMonth.getDay(); // 0 is Sunday
                    const totalDaysInMonth = new Date(year, month + 1, 0).getDate();

                    const days = [];

                    // Leading days from previous month
                    const prevMonthDays = new Date(year, month, 0).getDate();
                    for (let i = startDayOfWeek - 1; i >= 0; i--) {
                        const d = new Date(year, month - 1, prevMonthDays - i);
                        days.push(this.buildDayMeta(d, false, today));
                    }

                    // Current month days
                    for (let i = 1; i <= totalDaysInMonth; i++) {
                        const d = new Date(year, month, i);
                        days.push(this.buildDayMeta(d, true, today));
                    }

                    // Trailing days from next month to complete 35 or 42 cells
                    const totalCells = days.length <= 35 ? 35 : 42;
                    const remaining = totalCells - days.length;
                    for (let i = 1; i <= remaining; i++) {
                        const d = new Date(year, month + 1, i);
                        days.push(this.buildDayMeta(d, false, today));
                    }

                    return days;
                },

                buildDayMeta(dateObj, isCurrentMonth, today) {
                    const dateKey = this.formatDateKey(dateObj);
                    const isToday = this.formatDateKey(today) === dateKey;

                    const dayStart = new Date(dateObj.getFullYear(), dateObj.getMonth(), dateObj.getDate(), 0, 0, 0);
                    const dayEnd = new Date(dateObj.getFullYear(), dateObj.getMonth(), dateObj.getDate(), 23, 59, 59);

                    const dayEvents = this.filteredEvents.filter(e => {
                        // Di Calendar Month View: default hanya task dan meeting yang tampil (tanpa sprint dan milestone dll)
                        if (this.selectedCategories.length === 0) {
                            const isTaskOrMeeting = e.category === 'Task' || e.category === 'Meeting';
                            if (!isTaskOrMeeting) return false;
                        }
                        return e.startDate <= dayEnd && e.endDate >= dayStart;
                    });

                    return {
                        dateKey: dateKey,
                        dateObject: dateObj,
                        dayNumber: dateObj.getDate(),
                        isCurrentMonth: isCurrentMonth,
                        isToday: isToday,
                        events: dayEvents
                    };
                },

                // Week Days Grid
                get weekDays() {
                    const weekStart = this.getWeekStart(this.currentDate);
                    const todayKey = this.formatDateKey(new Date());
                    const cols = [];

                    for (let i = 0; i < 7; i++) {
                        const d = new Date(weekStart);
                        d.setDate(d.getDate() + i);
                        const k = this.formatDateKey(d);

                        cols.push({
                            dateKey: k,
                            dateObject: d,
                            dayName: d.toLocaleDateString('en-US', { weekday: 'short' }),
                            formattedDate: d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }),
                            isToday: k === todayKey
                        });
                    }

                    return cols;
                },

                getWeekStart(d) {
                    const date = new Date(d);
                    const day = date.getDay(); // 0 is Sunday
                    date.setDate(date.getDate() - day);
                    date.setHours(0, 0, 0, 0);
                    return date;
                },

                getEventsForSlot(slotDate, hour) {
                    return this.filteredEvents.filter(e => {
                        return (
                            e.startDate.getFullYear() === slotDate.getFullYear() &&
                            e.startDate.getMonth() === slotDate.getMonth() &&
                            e.startDate.getDate() === slotDate.getDate() &&
                            e.startDate.getHours() === hour
                        );
                    });
                },

                // List View Grouping
                get groupedListEvents() {
                    const sorted = [...this.filteredEvents].sort((a, b) => a.startDate - b.startDate);
                    const groups = {};

                    sorted.forEach(ev => {
                        const key = ev.startDate.toLocaleDateString('en-US', {
                            weekday: 'long',
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                        });
                        if (!groups[key]) groups[key] = [];
                        groups[key].push(ev);
                    });

                    return Object.keys(groups).map(k => ({
                        dateLabel: k,
                        events: groups[k]
                    }));
                },

                // Formatting utilities
                formatDateKey(d) {
                    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
                },

                formatHourLabel(hour) {
                    return String(hour).padStart(2, '0') + ':00';
                },

                formatTimeWindow(startIso, endIso) {
                    if (!startIso) return 'All Day';
                    const s = new Date(startIso);
                    const e = endIso ? new Date(endIso) : null;

                    const sStr = s.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
                    if (!e) return sStr;
                    const eStr = e.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
                    return sStr + ' - ' + eStr;
                },

                formatDateTimeLocal(dateObj) {
                    const pad = n => String(n).padStart(2, '0');
                    return dateObj.getFullYear() + '-' +
                        pad(dateObj.getMonth() + 1) + '-' +
                        pad(dateObj.getDate()) + 'T' +
                        pad(dateObj.getHours()) + ':' +
                        pad(dateObj.getMinutes());
                },

                getColorMeta(cVal) {
                    const found = this.colors.find(c => c.value === cVal);
                    return found || { name: 'Blue', value: 'blue', bg: 'bg-blue-600', text: 'text-blue-600', hex: '#2563EB' };
                },

                getEventPillClass(colorValue) {
                    switch (colorValue) {
                        case 'blue': return 'bg-blue-600 text-white';
                        case 'green': return 'bg-emerald-600 text-white';
                        case 'purple': return 'bg-purple-600 text-white';
                        case 'orange': return 'bg-amber-500 text-white';
                        case 'pink': return 'bg-pink-500 text-white';
                        case 'red': return 'bg-rose-500 text-white';
                        default: return 'bg-blue-600 text-white';
                    }
                },

                // Event Click Handler:
                // Jika task/sprint/milestone punya link meet -> buka modal Event Details
                // Jika tidak punya link meet -> direct ke halaman detail entitas
                // Jika custom event -> buka modal Event Details/Edit
                handleEventClick(event) {
                    if (event.source !== 'custom') {
                        // Ada link meet -> buka modal detail acara meeting
                        if (event.google_meet_link) {
                            this.openDetailModal(event);
                            return;
                        }

                        // Tidak ada link meet -> direct ke halaman aslinya (task, sprint, milestone, ticket)
                        if (event.url) {
                            window.location.href = event.url;
                            return;
                        }
                    }

                    this.openDetailModal(event);
                },

                // Modal triggers
                openCreateModal() {
                    const now = new Date();
                    const start = new Date(now.getFullYear(), now.getMonth(), now.getDate(), now.getHours() + 1, 0);
                    const end = new Date(start.getTime() + 60 * 60 * 1000);

                    this.isCreating = true;
                    this.form = {
                        id: null,
                        raw_id: null,
                        source: 'custom',
                        title: '',
                        description: '',
                        start_time: this.formatDateTimeLocal(start),
                        end_time: this.formatDateTimeLocal(end),
                        category: 'Meeting',
                        color: 'blue',
                        tags: ['Work'],
                        attendees: [],
                        google_meet_link: '',
                        can_edit: true
                    };
                    this.modalOpen = true;
                },

                openCreateModalWithTime(dateObj, hour) {
                    const start = new Date(dateObj.getFullYear(), dateObj.getMonth(), dateObj.getDate(), hour, 0);
                    const end = new Date(start.getTime() + 60 * 60 * 1000);

                    this.isCreating = true;
                    this.form = {
                        id: null,
                        raw_id: null,
                        source: 'custom',
                        title: '',
                        description: '',
                        start_time: this.formatDateTimeLocal(start),
                        end_time: this.formatDateTimeLocal(end),
                        category: 'Meeting',
                        color: 'blue',
                        tags: ['Work'],
                        attendees: [],
                        google_meet_link: '',
                        can_edit: true
                    };
                    this.modalOpen = true;
                },

                handleDayCellClick(day) {
                    this.openCreateModalWithTime(day.dateObject, 9);
                },

                openDetailModal(event) {
                    this.isCreating = false;
                    this.form = {
                        id: event.id,
                        raw_id: event.raw_id,
                        source: event.source,
                        title: event.title,
                        description: event.description || '',
                        start_time: this.formatDateTimeLocal(new Date(event.startTime)),
                        end_time: this.formatDateTimeLocal(new Date(event.endTime)),
                        category: event.category || 'Meeting',
                        color: event.color || 'blue',
                        tags: Array.isArray(event.tags) ? [...event.tags] : [],
                        attendees: Array.isArray(event.attendee_ids) ? [...event.attendee_ids] : [],
                        google_meet_link: event.google_meet_link || '',
                        can_edit: !!event.can_edit
                    };
                    this.modalOpen = true;
                },

                closeModal() {
                    this.modalOpen = false;
                    this.copied = false;
                },

                toggleTag(tag) {
                    if (!this.isCreating && !this.form.can_edit) return;
                    if (this.form.tags.includes(tag)) {
                        this.form.tags = this.form.tags.filter(t => t !== tag);
                    } else {
                        this.form.tags.push(tag);
                    }
                },

                copyMeetLink(link) {
                    if (!link) return;
                    navigator.clipboard.writeText(link).then(() => {
                        this.copied = true;
                        setTimeout(() => { this.copied = false; }, 2000);
                    });
                },

                // API Actions
                saveEvent() {
                    if (this.submitting) return;

                    // Client-side validation: Meeting membutuhkan koneksi Google Calendar
                    if (!this.form.google_meet_link && !this.hasGoogleConnected) {
                        if (confirm('Akun Anda belum terhubung dengan Google Calendar sehingga link Google Meet tidak dapat dibuat.\n\nApakah Anda ingin membuka halaman untuk menghubungkan Google Calendar sekarang?')) {
                            window.open(this.googleConnectUrl, '_blank');
                        }
                        return;
                    }

                    this.submitting = true;

                    const payload = {
                        title: this.form.title,
                        description: this.form.description,
                        start_time: this.form.start_time,
                        end_time: this.form.end_time,
                        category: 'Meeting',
                        color: this.form.color,
                        tags: this.form.tags,
                        attendees: this.form.attendees
                    };

                    const url = this.isCreating
                        ? config.storeUrl
                        : config.updateUrlTemplate.replace('__ID__', this.form.raw_id);

                    const method = this.isCreating ? 'POST' : 'PUT';

                    fetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': config.csrfToken
                        },
                        body: JSON.stringify(payload)
                    })
                        .then(async res => {
                            const data = await res.json().catch(() => ({}));
                            this.submitting = false;

                            if (!res.ok || !data.ok) {
                                if (data.need_google_connect) {
                                    if (confirm((data.message || 'Akun Anda belum terhubung ke Google Calendar.') + '\n\nBuka halaman untuk menghubungkan Google Calendar sekarang?')) {
                                        window.open(data.connect_url || this.googleConnectUrl, '_blank');
                                    }
                                } else if (data.errors) {
                                    const firstErr = Object.values(data.errors)[0];
                                    alert(Array.isArray(firstErr) ? firstErr[0] : firstErr);
                                } else {
                                    alert(data.message || 'Terjadi kesalahan saat menyimpan event.');
                                }
                                return;
                            }

                            if (data.ok && data.event) {
                                const saved = {
                                    ...data.event,
                                    startDate: new Date(data.event.startTime),
                                    endDate: new Date(data.event.endTime),
                                };

                                if (this.isCreating) {
                                    this.events.push(saved);
                                } else {
                                    const idx = this.events.findIndex(e => e.id === saved.id);
                                    if (idx !== -1) {
                                        this.events.splice(idx, 1, saved);
                                    } else {
                                        this.events.push(saved);
                                    }
                                }

                                this.closeModal();

                                if (window.showToast) {
                                    window.showToast(data.message || 'Event berhasil disimpan!');
                                }
                            }
                        })
                        .catch(err => {
                            this.submitting = false;
                            console.error(err);
                            alert('Gagal menyimpan event. Silakan cek koneksi atau input Anda.');
                        });
                },

                deleteEvent(rawId) {
                    if (!confirm('Yakin ingin menghapus event ini?')) return;
                    this.submitting = true;

                    const url = config.deleteUrlTemplate.replace('__ID__', rawId);

                    fetch(url, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': config.csrfToken
                        }
                    })
                        .then(res => res.json())
                        .then(data => {
                            this.submitting = false;
                            if (data.ok) {
                                this.events = this.events.filter(e => e.raw_id !== rawId || e.source !== 'custom');
                                this.closeModal();
                                if (window.showToast) {
                                    window.showToast('Event berhasil dihapus.');
                                }
                            } else {
                                alert(data.message || 'Gagal menghapus event.');
                            }
                        })
                        .catch(err => {
                            this.submitting = false;
                            console.error(err);
                            alert('Gagal menghapus event.');
                        });
                }
            };
        }
    </script>
@endsection