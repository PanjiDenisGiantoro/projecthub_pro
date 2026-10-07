@extends('layouts.app')

@section('page-title', 'Chatting & Discuss')
@section('main-class', 'flex-1 overflow-hidden p-0 h-[calc(100vh-4rem)]')

@section('content')
    <div class="flex h-full w-full overflow-hidden bg-gray-50 dark:bg-gray-900 select-none font-sans" x-data="chatHubApp(
                                                        {{ json_encode($projects) }},
                                                        {{ json_encode($dms) }},
                                                        {{ json_encode($forums) }},
                                                        {{ json_encode($inviteCandidates) }},
                                                        {{ json_encode($initialTarget) }}
                                                     )">

        {{-- ═══════════════════════════════════════════════════════════════════════════════════════
        LEFT PANEL — Search + Projects Chat + Forum + Direct Chat (3 Sections)
        ═══════════════════════════════════════════════════════════════════════════════════════ --}}
        <aside
            class="w-72 lg:w-80 shrink-0 flex flex-col border-r border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-850 transition-all duration-200 z-10"
            :class="mobileChat ? 'hidden lg:flex' : 'flex'">

            {{-- Top Bar with Search (No Pencil Icon) --}}
            <div class="p-3.5 border-b border-gray-100 dark:border-gray-800/80 shrink-0">
                <div class="relative">
                    <input type="text" x-model="search" placeholder="Search chat, project, forum..."
                        class="w-full pl-9 pr-3.5 py-2 text-sm font-medium border border-gray-200/80 dark:border-gray-700 rounded-xl bg-gray-100/80 dark:bg-gray-800 text-gray-800 dark:text-gray-200 placeholder-gray-400 focus:bg-white dark:focus:bg-gray-900 focus:border-blue-400 focus:ring-2 focus:ring-blue-100 dark:focus:ring-blue-900/40 outline-none transition-all">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <button x-show="search.length > 0" x-cloak @click="search = ''"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Scrollable List of 3 Sections --}}
            <div class="flex-1 overflow-y-auto px-2.5 py-3 space-y-4 scrollbar-thin">

                {{-- ── SECTION 1: PROJECTS CHAT (No '+' button) ────────────────────── --}}
                <div x-data="{ open: true }">
                    <div class="flex items-center justify-between px-2 py-1 text-xs font-bold tracking-wider text-gray-400 dark:text-gray-500 uppercase cursor-pointer select-none hover:text-gray-600 dark:hover:text-gray-300 transition"
                        @click="open = !open">
                        <span class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 transition-transform duration-200"
                                :class="open ? 'rotate-0' : '-rotate-90'" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                            PROJECTS CHAT
                        </span>
                        <span class="text-xs font-semibold text-gray-400" x-text="filteredProjects.length"></span>
                    </div>

                    <div x-show="open" x-collapse class="mt-1 space-y-0.5">
                        <template x-for="p in filteredProjects" :key="'project-' + p.id">
                            <button type="button" @click="selectItem(p)"
                                :class="isActive(p)
                                                                                    ? 'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-400 font-semibold shadow-2xs'
                                                                                    : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100/70 dark:hover:bg-gray-800/60 hover:text-gray-900 dark:hover:text-white'"
                                class="w-full group flex items-center justify-between px-2.5 py-1.5 rounded-xl text-sm transition-all text-left">
                                <span class="flex items-center gap-2 truncate min-w-0">
                                    <span
                                        class="text-gray-400 dark:text-gray-500 font-mono text-sm group-hover:text-blue-500 transition-colors">#</span>
                                    <span class="truncate" x-text="p.name"></span>
                                </span>
                                <div class="flex items-center shrink-0 ml-2">
                                    <template x-if="p.unread_count > 0">
                                        <span
                                            class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-blue-600 text-white min-w-[20px] text-center shadow-xs"
                                            x-text="p.unread_count"></span>
                                    </template>
                                </div>
                            </button>
                        </template>
                        <div x-show="filteredProjects.length === 0" class="px-3 py-2 text-xs text-gray-400 italic">
                            Tidak ada chat proyek ditemukan.
                        </div>
                    </div>
                </div>

                {{-- ── SECTION 2: FORUM (With '+' button) ─────────────────────────── --}}
                <div x-data="{ open: true }">
                    <div
                        class="flex items-center justify-between px-2 py-1 text-xs font-bold tracking-wider text-gray-400 dark:text-gray-500 uppercase select-none">
                        <span
                            class="flex items-center gap-1.5 cursor-pointer hover:text-gray-600 dark:hover:text-gray-300 transition"
                            @click="open = !open">
                            <svg class="w-3.5 h-3.5 transition-transform duration-200"
                                :class="open ? 'rotate-0' : '-rotate-90'" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                            FORUM
                        </span>
                        <button type="button" @click="openCreateForum()"
                            class="p-1 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition cursor-pointer"
                            title="Tambah Kanal Forum">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                        </button>
                    </div>

                    <div x-show="open" x-collapse class="mt-1 space-y-0.5">
                        <template x-for="f in filteredForums" :key="'forum-' + f.id">
                            <button type="button" @click="selectItem(f)"
                                :class="isActive(f)
                                                                                    ? 'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-400 font-semibold shadow-2xs'
                                                                                    : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100/70 dark:hover:bg-gray-800/60 hover:text-gray-900 dark:hover:text-white'"
                                class="w-full group flex items-center justify-between px-2.5 py-1.5 rounded-xl text-sm transition-all text-left">
                                <span class="flex items-center gap-2 truncate min-w-0">
                                    <span
                                        class="text-gray-400 dark:text-gray-500 font-mono text-sm group-hover:text-blue-500 transition-colors">#</span>
                                    <span class="truncate" x-text="f.name"></span>
                                </span>
                                <div class="flex items-center shrink-0 ml-2">
                                    <template x-if="f.unread_count > 0">
                                        <span
                                            class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-blue-600 text-white min-w-[20px] text-center shadow-xs"
                                            x-text="f.unread_count"></span>
                                    </template>
                                </div>
                            </button>
                        </template>
                        <div x-show="filteredForums.length === 0" class="px-3 py-2 text-xs text-gray-400 italic">
                            Tidak ada forum ditemukan.
                        </div>
                    </div>
                </div>

                {{-- ── SECTION 3: DIRECT CHAT (No '+' button, colleagues only) ──────── --}}
                <div x-data="{ open: true }">
                    <div class="flex items-center justify-between px-2 py-1 text-xs font-bold tracking-wider text-gray-400 dark:text-gray-500 uppercase cursor-pointer select-none hover:text-gray-600 dark:hover:text-gray-300 transition"
                        @click="open = !open">
                        <span class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 transition-transform duration-200"
                                :class="open ? 'rotate-0' : '-rotate-90'" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                            DIRECT CHAT
                        </span>
                        <span class="text-xs font-semibold text-gray-400" x-text="filteredDms.length"></span>
                    </div>

                    <div x-show="open" x-collapse class="mt-1 space-y-0.5">
                        <template x-for="d in filteredDms" :key="'dm-' + d.id">
                            <button type="button" @click="selectItem(d)"
                                :class="isActive(d)
                                                                                    ? 'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-400 font-semibold shadow-2xs'
                                                                                    : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100/70 dark:hover:bg-gray-800/60 hover:text-gray-900 dark:hover:text-white'"
                                class="w-full group flex items-center justify-between px-2.5 py-1.5 rounded-xl text-sm transition-all text-left">
                                <span class="flex items-center gap-2.5 truncate min-w-0">
                                    <span class="relative shrink-0">
                                        <template x-if="d.avatar">
                                            <img :src="d.avatar" class="w-6 h-6 rounded-full object-cover">
                                        </template>
                                        <template x-if="!d.avatar">
                                            <span
                                                class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold text-white shadow-2xs"
                                                :class="getUserAvatarBg(d.id, d.name)"
                                                x-text="d.initials"></span>
                                        </template>
                                    </span>
                                    <span class="truncate" x-text="d.name"></span>
                                </span>
                                <div class="flex items-center shrink-0 ml-2">
                                    <template x-if="d.unread_count > 0">
                                        <span
                                            class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-blue-600 text-white min-w-[20px] text-center shadow-xs"
                                            x-text="d.unread_count"></span>
                                    </template>
                                </div>
                            </button>
                        </template>
                        <div x-show="filteredDms.length === 0" class="px-3 py-2 text-xs text-gray-400 italic">
                            Tidak ada anggota ditemukan.
                        </div>
                    </div>
                </div>

            </div>
        </aside>

        {{-- ═══════════════════════════════════════════════════════════════════════════════════════
        CENTER AREA — Main Chat Header, Messages Feed & Bottom Editor
        ═══════════════════════════════════════════════════════════════════════════════════════ --}}
        <main class="flex-1 flex flex-col min-w-0 h-full bg-white dark:bg-gray-900 overflow-hidden relative"
            :class="!mobileChat ? 'hidden lg:flex' : 'flex'">

            <template x-if="!activeItem">
                <div
                    class="flex-1 flex flex-col items-center justify-center p-8 text-center text-gray-400 dark:text-gray-500">
                    <div
                        class="w-16 h-16 rounded-2xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center mb-3 text-gray-300 dark:text-gray-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300">Pilih Percakapan</h3>
                    <p class="text-xs max-w-sm mt-1">Pilih salah satu chat proyek, kanal forum, atau pesan langsung untuk
                        mulai berdiskusi bersama tim.</p>
                </div>
            </template>

            <template x-if="activeItem">
                <div class="flex-1 flex flex-col min-h-0 h-full overflow-hidden">

                    {{-- ── 1. Clean Chat Header (No Breadcrumbs, No 'Diskusi Proyek') ──────────────── --}}
                    <header
                        class="px-4 sm:px-6 py-3.5 border-b border-gray-100 dark:border-gray-800 shrink-0 bg-white dark:bg-gray-850">
                        <div class="flex items-center justify-between gap-3">
                            {{-- Left Info --}}
                            <div class="flex items-center gap-3 min-w-0">
                                {{-- Mobile Back Button --}}
                                <button
                                    class="lg:hidden p-1.5 -ml-1 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition cursor-pointer"
                                    @click="mobileChat = false" title="Kembali ke daftar">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 19l-7-7 7-7" />
                                    </svg>
                                </button>

                                {{-- Avatar / Identity Icon --}}
                                <div class="relative shrink-0">
                                    <template x-if="activeItem.type === 'project'">
                                        <div class="w-10 h-10 rounded-2xl bg-linear-to-tr from-blue-600 to-indigo-600 flex items-center justify-center font-bold text-white text-xs shadow-xs"
                                            x-text="activeItem.initials"></div>
                                    </template>
                                    <template x-if="activeItem.type === 'forum'">
                                        <div
                                            class="w-10 h-10 rounded-2xl bg-blue-50 dark:bg-blue-950/60 border border-blue-200 dark:border-blue-800/60 flex items-center justify-center font-mono font-bold text-blue-600 dark:text-blue-400 text-base shadow-xs">
                                            #</div>
                                    </template>
                                    <template x-if="activeItem.type === 'dm'">
                                        <div class="relative">
                                            <template x-if="activeItem.avatar">
                                                <img :src="activeItem.avatar"
                                                    class="w-10 h-10 rounded-2xl object-cover shadow-xs">
                                            </template>
                                            <template x-if="!activeItem.avatar">
                                                <div class="w-10 h-10 rounded-2xl flex items-center justify-center font-bold text-xs shadow-xs"
                                                    :class="getUserAvatarBg(activeItem.id, activeItem.name)"
                                                    x-text="activeItem.initials"></div>
                                            </template>
                                            <span
                                                class="w-2.5 h-2.5 rounded-full bg-emerald-500 absolute -bottom-0.5 -right-0.5 ring-2 ring-white dark:ring-gray-850"></span>
                                        </div>
                                    </template>
                                </div>

                                {{-- Heading text & Subtitle --}}
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h2 class="text-base sm:text-lg font-bold text-gray-900 dark:text-white truncate"
                                            x-text="activeItem.name"></h2>

                                        {{-- Project Client --}}
                                        <template x-if="activeItem.type === 'project' && activeItem.client_name">
                                            <span class="text-xs font-medium text-gray-400 dark:text-gray-500 truncate"
                                                x-text="'• ' + activeItem.client_name"></span>
                                        </template>

                                        {{-- Status Pill --}}
                                        <span
                                            class="px-2.5 py-0.5 rounded-full text-xs font-semibold flex items-center gap-1.5 shrink-0 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-800/40">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            <span x-text="activeItem.type === 'dm' ? 'Online' : 'Active'"></span>
                                        </span>
                                    </div>

                                    {{-- Subtitle (Clean: No 'Diskusi Proyek') --}}
                                    <div
                                        class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-2 truncate mt-0.5">
                                        <template x-if="activeItem.type === 'project'">
                                            <span>
                                                <span
                                                    x-text="(detailsData?.members?.length || activeItem.member_count || 1) + ' Anggota'"></span>
                                                <template x-if="activeItem.lead_name">
                                                    <span> • Lead: <strong
                                                            class="font-semibold text-gray-700 dark:text-gray-300"
                                                            x-text="activeItem.lead_name"></strong></span>
                                                </template>
                                            </span>
                                        </template>
                                        <template x-if="activeItem.type === 'forum'">
                                            <span class="truncate"
                                                x-text="activeItem.description || 'Ruang diskusi publik untuk koordinasi tim Flovig.'"></span>
                                        </template>
                                        <template x-if="activeItem.type === 'dm'">
                                            <span class="text-emerald-600 dark:text-emerald-400 font-medium">Online • Siap
                                                berdiskusi</span>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            {{-- Right Actions in Header --}}
                            <div class="flex items-center gap-1.5 shrink-0">
                                {{-- Meeting Button (For Direct Chat) --}}
                                <template x-if="activeItem.type === 'dm'">
                                    <button type="button" @click="openMeetingModal()"
                                        class="p-2 rounded-xl text-gray-500 hover:text-blue-600 hover:bg-gray-100 dark:hover:bg-gray-800 transition cursor-pointer"
                                        title="Mulai Meeting 1-on-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </button>
                                </template>

                                {{-- Pinned Messages Shortcut --}}
                                <button type="button" @click="togglePinnedShortcut()"
                                    class="p-2 rounded-xl text-gray-500 hover:text-amber-600 hover:bg-gray-100 dark:hover:bg-gray-800 transition cursor-pointer relative"
                                    title="Pesan Disematkan">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                                    </svg>
                                    <span x-show="detailsData?.pinned_messages?.length > 0"
                                        class="w-2 h-2 rounded-full bg-amber-500 absolute top-1.5 right-1.5"></span>
                                </button>

                                {{-- Toggle Right Info Drawer --}}
                                <button type="button" @click="rightDrawerOpen = !rightDrawerOpen"
                                    :class="rightDrawerOpen ? 'text-blue-600 bg-blue-50 dark:bg-blue-950/40' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800'"
                                    class="p-2 rounded-xl transition cursor-pointer" title="Detail & Informasi">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </header>

                    {{-- ── 2. WhatsApp-Style Clean Chat Feed ────────────────────────────────── --}}
                    <div class="flex-1 overflow-y-auto px-4 sm:px-6 py-5 space-y-3.5 scrollbar-thin relative bg-cover bg-center bg-no-repeat"
                        style="background-image: url('{{ asset('images/wa-bg.webp') }}'); background-size: cover; background-position: center; background-repeat: no-repeat;"
                        x-ref="msgArea" id="chat-messages-container">

                        {{-- Top Welcome Banner for Forum --}}
                        <template x-if="activeItem.type === 'forum'">
                            <div
                                class="border border-blue-100 dark:border-blue-900/50 bg-blue-50/50 dark:bg-blue-950/20 rounded-2xl p-5 mb-4 text-center">
                                <div
                                    class="w-12 h-12 mx-auto rounded-2xl bg-blue-600 text-white flex items-center justify-center font-bold text-xl shadow-xs mb-3">
                                    #
                                </div>
                                <h3 class="text-base font-bold text-gray-900 dark:text-white"
                                    x-text="'Selamat datang di kanal #' + activeItem.name + '!'"></h3>
                                <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1 max-w-md mx-auto"
                                    x-text="activeItem.description || 'Ruang diskusi publik untuk koordinasi tim Flovig. Diskusikan update, dependensi fungsional, dan hal umum di sini.'">
                                </p>
                                <div class="flex items-center justify-center gap-2.5 mt-3.5">
                                    <button type="button" @click="showGuideToast()"
                                        class="px-3.5 py-1.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-xs font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-750 transition cursor-pointer">
                                        📖 Panduan Kanal
                                    </button>
                                    <button type="button" @click="openInviteModal()"
                                        class="px-3.5 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold transition cursor-pointer">
                                        + Undang Anggota
                                    </button>
                                </div>
                            </div>
                        </template>

                        {{-- Top Welcome Banner for Direct Chat --}}
                        <template x-if="activeItem.type === 'dm'">
                            <div
                                class="border border-gray-200/70 dark:border-gray-800 bg-white dark:bg-gray-850/60 rounded-2xl p-5 mb-4 text-center shadow-2xs">
                                <div
                                    class="w-16 h-16 mx-auto rounded-2xl overflow-hidden mb-3 relative flex items-center justify-center text-white font-bold text-lg bg-amber-500 shadow-xs">
                                    <template x-if="activeItem.avatar">
                                        <img :src="activeItem.avatar" class="w-full h-full object-cover">
                                    </template>
                                    <template x-if="!activeItem.avatar">
                                        <span x-text="activeItem.initials"></span>
                                    </template>
                                </div>
                                <h3 class="text-base font-bold text-gray-900 dark:text-white" x-text="activeItem.name"></h3>
                                <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1 max-w-sm mx-auto"
                                    x-text="'Percakapan langsung dengan ' + activeItem.name + '. Pesan di sini bersifat privat antara Anda dan ' + activeItem.name + '.'">
                                </p>
                                <div class="text-xs text-gray-400 mt-2 flex items-center justify-center gap-3">
                                    <span x-text="activeItem.email"></span>
                                </div>
                            </div>
                        </template>

                        {{-- Messages List in WhatsApp Bubble Cards --}}
                        <template x-for="(msg, index) in messages" :key="msg.id">
                            <div class="w-full flex transition-all" :class="msg.is_mine ? 'justify-end' : 'justify-start'"
                                :id="'msg-' + msg.id">

                                {{-- ── CASE A: SENDER IS CURRENT USER (Align Right, Blue Bubble) ── --}}
                                <template x-if="msg.is_mine">
                                    <div
                                        class="group relative flex items-end gap-1.5 max-w-[85%] sm:max-w-[75%] md:max-w-[65%]">

                                        {{-- Left Hover Action Bar --}}
                                        <div
                                            class="opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-0.5 p-1 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-md shrink-0 mb-1 z-10">
                                            <button type="button" @click="toggleReaction(msg, '👍')"
                                                class="p-1 text-xs hover:scale-125 transition cursor-pointer"
                                                title="Suka">👍</button>
                                            <button type="button" @click="toggleReaction(msg, '❤️')"
                                                class="p-1 text-xs hover:scale-125 transition cursor-pointer"
                                                title="Cinta">❤️</button>
                                            <button type="button" @click="toggleReaction(msg, '🚀')"
                                                class="p-1 text-xs hover:scale-125 transition cursor-pointer"
                                                title="Roket">🚀</button>
                                            <div class="w-px h-3 bg-gray-200 dark:bg-gray-700 mx-0.5"></div>
                                            <button type="button" @click="togglePin(msg)"
                                                :class="msg.is_pinned ? 'text-amber-500 font-bold' : 'text-gray-400 hover:text-amber-500'"
                                                class="p-1 rounded-md transition cursor-pointer" title="Sematkan Pesan">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                                                </svg>
                                            </button>
                                            <button type="button" @click="setReply(msg)"
                                                class="p-1 rounded-md text-gray-400 hover:text-blue-500 transition cursor-pointer"
                                                title="Balas">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                                </svg>
                                            </button>
                                            <button type="button" @click="deleteMsg(msg)"
                                                class="p-1 rounded-md text-gray-400 hover:text-red-500 transition cursor-pointer"
                                                title="Hapus">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>

                                        {{-- Right Message Bubble --}}
                                        <div class="relative bg-blue-600 text-white rounded-2xl rounded-tr-xs shadow-xs px-4 py-2.5 transition-all text-left"
                                            :class="msg.is_pinned ? 'ring-2 ring-amber-400' : ''">

                                            {{-- Reply parent preview (Clean WhatsApp card) --}}
                                            <template x-if="msg.parent">
                                                <div
                                                    class="mb-2 rounded-lg bg-black/15 border-l-4 border-white/90 px-3 py-1.5 text-xs text-left">
                                                    <div class="font-bold text-white text-[11px] leading-tight"
                                                        x-text="msg.parent.user"></div>
                                                    <div class="text-blue-100/90 text-xs truncate mt-0.5"
                                                        x-text="msg.parent.body"></div>
                                                </div>
                                            </template>

                                            {{-- Message Body --}}
                                            <div class="text-sm leading-relaxed whitespace-pre-wrap break-words select-text text-white"
                                                x-html="renderMessageBody(msg.body, true)"></div>

                                            {{-- Attachments preview --}}
                                            <template x-if="msg.attachments && msg.attachments.length > 0">
                                                <div class="mt-2 flex flex-wrap gap-2">
                                                    <template x-for="att in msg.attachments" :key="att.id">
                                                        <div
                                                            class="flex items-center gap-2 p-2 rounded-xl bg-blue-700/70 border border-blue-500/60 text-xs">
                                                            <template x-if="att.is_image">
                                                                <a :href="att.url" target="_blank" class="block">
                                                                    <img :src="att.url"
                                                                        class="w-16 h-16 object-cover rounded-lg hover:opacity-90 transition">
                                                                </a>
                                                            </template>
                                                            <template x-if="!att.is_image">
                                                                <div class="flex items-center gap-2">
                                                                    <div
                                                                        class="w-7 h-7 rounded-lg bg-blue-800 text-blue-100 flex items-center justify-center font-bold text-[10px]">
                                                                        FILE</div>
                                                                    <div class="min-w-0 max-w-[140px]">
                                                                        <div class="font-medium text-white truncate"
                                                                            x-text="att.name"></div>
                                                                        <div class="text-[10px] text-blue-200"
                                                                            x-text="att.size"></div>
                                                                    </div>
                                                                    <a :href="att.url" download
                                                                        class="p-1 rounded-md text-blue-200 hover:text-white transition"
                                                                        title="Unduh">
                                                                        <svg class="w-3.5 h-3.5" fill="none"
                                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round"
                                                                                stroke-linejoin="round" stroke-width="2"
                                                                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                                        </svg>
                                                                    </a>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>

                                            {{-- Bottom Meta Row: Time, Pin, Authentic Double Checkmarks --}}
                                            <div
                                                class="flex items-center justify-end gap-1.5 mt-1.5 text-[11px] text-blue-100 select-none">
                                                <template x-if="msg.is_pinned">
                                                    <span class="flex items-center gap-0.5 text-amber-300 font-semibold"
                                                        title="Pesan Disematkan">
                                                        <svg class="w-3 h-3 fill-current" viewBox="0 0 20 20">
                                                            <path
                                                                d="M9.828 3h.344a1 1 0 01.707.293l4.828 4.828a1 1 0 01.293.707v.344a1 1 0 01-.293.707l-2.121 2.121 2.828 2.829a1 1 0 010 1.414l-1.414 1.414a1 1 0 01-1.414 0l-2.829-2.828-2.121 2.121A1 1 0 019 17h-.343a1 1 0 01-.707-.293l-4.829-4.828A1 1 0 012.828 11.17V10.83a1 1 0 01.293-.707l2.121-2.122L2.414 5.172a1 1 0 010-1.414l1.414-1.414a1 1 0 011.414 0l2.829 2.828 2.121-2.121A1 1 0 019.828 3z" />
                                                        </svg>
                                                        <span class="text-[10px]">Disematkan</span>
                                                    </span>
                                                </template>
                                                <span x-text="msg.time_str || msg.time_label"></span>
                                                {{-- WhatsApp Distinct Double Check --}}
                                                <svg class="w-4 h-3.5 text-blue-200 shrink-0 inline-block" fill="none"
                                                    stroke="currentColor" viewBox="0 0 16 11">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                                        d="M1 5.5L4 9L11 1.5 M5 5.5L8 9L15 1.5" />
                                                </svg>
                                            </div>

                                            {{-- Reactions attached to bubble --}}
                                            <div class="flex flex-wrap items-center gap-1 mt-1.5"
                                                x-show="msg.reactions && msg.reactions.length > 0">
                                                <template x-for="r in msg.reactions" :key="r.emoji">
                                                    <button type="button" @click="toggleReaction(msg, r.emoji)"
                                                        :class="r.reacted ? 'bg-blue-800 text-white border-blue-400' : 'bg-blue-700/80 text-blue-100 border-blue-500/60'"
                                                        class="px-2 py-0.5 rounded-full border text-xs font-semibold flex items-center gap-1 hover:scale-105 active:scale-95 transition cursor-pointer">
                                                        <span x-text="r.emoji"></span>
                                                        <span x-text="r.count"></span>
                                                    </button>
                                                </template>
                                            </div>

                                        </div>
                                    </div>
                                </template>

                                {{-- ── CASE B: SENDER IS ANOTHER PERSON (Align Left, White Card) ── --}}
                                <template x-if="!msg.is_mine">
                                    <div
                                        class="group relative flex items-start gap-2.5 max-w-[85%] sm:max-w-[75%] md:max-w-[65%]">

                                        {{-- Avatar --}}
                                        <div class="relative shrink-0 mt-0.5">
                                            <template x-if="msg.user.avatar">
                                                <img :src="msg.user.avatar"
                                                    class="w-8 h-8 rounded-xl object-cover shadow-2xs">
                                            </template>
                                            <template x-if="!msg.user.avatar">
                                                <div class="w-8 h-8 rounded-xl flex items-center justify-center text-xs font-bold shadow-2xs"
                                                    :class="getUserAvatarBg(msg.user.id, msg.user.name)"
                                                    x-text="msg.user.initials"></div>
                                            </template>
                                        </div>

                                        {{-- Left Message Bubble --}}
                                        <div class="relative bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-2xl rounded-tl-xs border border-gray-200/90 dark:border-gray-700 shadow-2xs px-4 py-2.5 transition-all flex-1 min-w-0 text-left"
                                            :class="msg.is_pinned ? 'ring-2 ring-amber-400 bg-amber-50/20 dark:bg-amber-950/20' : ''">

                                            {{-- Sender info row (Distinctive color per person) --}}
                                            <div class="flex items-center gap-2 mb-1 flex-wrap">
                                                <span class="text-xs font-bold transition-colors"
                                                    :class="getUserTextColor(msg.user.id, msg.user.name)"
                                                    x-text="msg.user.name"></span>
                                                <template
                                                    x-if="msg.user.role_badge === 'Project Lead' || msg.user.role_badge === 'Client'">
                                                    <span class="px-2 py-0.5 rounded-md text-xs font-semibold shrink-0"
                                                        :class="msg.user.role_badge === 'Project Lead'
                                                                                                        ? 'bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400'
                                                                                                        : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400'"
                                                        x-text="msg.user.role_badge"></span>
                                                </template>
                                            </div>

                                            {{-- Reply parent preview (Clean WhatsApp card) --}}
                                            <template x-if="msg.parent">
                                                <div
                                                    class="mb-2 rounded-lg bg-gray-100/90 dark:bg-gray-700/60 border-l-4 px-3 py-1.5 text-xs text-left"
                                                    :class="getUserBorderColor(msg.parent.user_id, msg.parent.user)">
                                                    <div class="font-bold text-[11px] leading-tight"
                                                        :class="getUserTextColor(msg.parent.user_id, msg.parent.user)"
                                                        x-text="msg.parent.user"></div>
                                                    <div class="text-gray-600 dark:text-gray-300 text-xs truncate mt-0.5"
                                                        x-text="msg.parent.body"></div>
                                                </div>
                                            </template>

                                            {{-- Message Body --}}
                                            <div class="text-sm leading-relaxed whitespace-pre-wrap break-words select-text text-gray-800 dark:text-gray-200"
                                                x-html="renderMessageBody(msg.body, false)"></div>

                                            {{-- Attachments preview --}}
                                            <template x-if="msg.attachments && msg.attachments.length > 0">
                                                <div class="mt-2 flex flex-wrap gap-2">
                                                    <template x-for="att in msg.attachments" :key="att.id">
                                                        <div
                                                            class="flex items-center gap-2 p-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-750 text-xs">
                                                            <template x-if="att.is_image">
                                                                <a :href="att.url" target="_blank" class="block">
                                                                    <img :src="att.url"
                                                                        class="w-16 h-16 object-cover rounded-lg hover:opacity-90 transition">
                                                                </a>
                                                            </template>
                                                            <template x-if="!att.is_image">
                                                                <div class="flex items-center gap-2">
                                                                    <div
                                                                        class="w-7 h-7 rounded-lg bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold text-[10px]">
                                                                        FILE</div>
                                                                    <div class="min-w-0 max-w-[140px]">
                                                                        <div class="font-medium text-gray-800 dark:text-gray-200 truncate"
                                                                            x-text="att.name"></div>
                                                                        <div class="text-[10px] text-gray-400"
                                                                            x-text="att.size"></div>
                                                                    </div>
                                                                    <a :href="att.url" download
                                                                        class="p-1 rounded-md text-gray-400 hover:text-blue-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                                                                        title="Unduh">
                                                                        <svg class="w-3.5 h-3.5" fill="none"
                                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round"
                                                                                stroke-linejoin="round" stroke-width="2"
                                                                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                                        </svg>
                                                                    </a>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>

                                            {{-- Bottom Meta Row: Time, Pin --}}
                                            <div
                                                class="flex items-center justify-end gap-1.5 mt-1 text-[11px] text-gray-400 dark:text-gray-500 select-none">
                                                <template x-if="msg.is_pinned">
                                                    <span class="flex items-center gap-0.5 text-amber-500 font-semibold"
                                                        title="Pesan Disematkan">
                                                        <svg class="w-3 h-3 fill-current" viewBox="0 0 20 20">
                                                            <path
                                                                d="M9.828 3h.344a1 1 0 01.707.293l4.828 4.828a1 1 0 01.293.707v.344a1 1 0 01-.293.707l-2.121 2.121 2.828 2.829a1 1 0 010 1.414l-1.414 1.414a1 1 0 01-1.414 0l-2.829-2.828-2.121 2.121A1 1 0 019 17h-.343a1 1 0 01-.707-.293l-4.829-4.828A1 1 0 012.828 11.17V10.83a1 1 0 01.293-.707l2.121-2.122L2.414 5.172a1 1 0 010-1.414l1.414-1.414a1 1 0 011.414 0l2.829 2.828 2.121-2.121A1 1 0 019.828 3z" />
                                                        </svg>
                                                        <span class="text-[10px]">Disematkan</span>
                                                    </span>
                                                </template>
                                                <span x-text="msg.time_str || msg.time_label"></span>
                                            </div>

                                            {{-- Reactions attached to bubble --}}
                                            <div class="flex flex-wrap items-center gap-1 mt-1.5"
                                                x-show="msg.reactions && msg.reactions.length > 0">
                                                <template x-for="r in msg.reactions" :key="r.emoji">
                                                    <button type="button" @click="toggleReaction(msg, r.emoji)"
                                                        :class="r.reacted ? 'bg-blue-50 dark:bg-blue-950/50 border-blue-300 dark:border-blue-700 text-blue-700 dark:text-blue-300' : 'bg-gray-100 dark:bg-gray-750 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400'"
                                                        class="px-2 py-0.5 rounded-full border text-xs font-semibold flex items-center gap-1 hover:scale-105 active:scale-95 transition cursor-pointer">
                                                        <span x-text="r.emoji"></span>
                                                        <span x-text="r.count"></span>
                                                    </button>
                                                </template>
                                            </div>

                                        </div>

                                        {{-- Right Hover Action Bar --}}
                                        <div
                                            class="opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-0.5 p-1 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-md shrink-0 mb-1 z-10">
                                            <button type="button" @click="toggleReaction(msg, '👍')"
                                                class="p-1 text-xs hover:scale-125 transition cursor-pointer"
                                                title="Suka">👍</button>
                                            <button type="button" @click="toggleReaction(msg, '❤️')"
                                                class="p-1 text-xs hover:scale-125 transition cursor-pointer"
                                                title="Cinta">❤️</button>
                                            <button type="button" @click="toggleReaction(msg, '🚀')"
                                                class="p-1 text-xs hover:scale-125 transition cursor-pointer"
                                                title="Roket">🚀</button>
                                            <div class="w-px h-3 bg-gray-200 dark:bg-gray-700 mx-0.5"></div>
                                            <button type="button" @click="togglePin(msg)"
                                                :class="msg.is_pinned ? 'text-amber-500 font-bold' : 'text-gray-400 hover:text-amber-500'"
                                                class="p-1 rounded-md transition cursor-pointer" title="Sematkan Pesan">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                                                </svg>
                                            </button>
                                            <button type="button" @click="setReply(msg)"
                                                class="p-1 rounded-md text-gray-400 hover:text-blue-500 transition cursor-pointer"
                                                title="Balas">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                                </svg>
                                            </button>
                                        </div>

                                    </div>
                                </template>

                            </div>
                        </template>

                        <div x-show="messages.length === 0 && !loading"
                            class="text-center py-14 text-gray-400 dark:text-gray-500 text-sm">
                            Belum ada pesan. Mulai obrolan pertama sekarang!
                        </div>

                    </div>

                    {{-- ── 3. Unified Seamless Editor Card (No Gap, Smooth Rounding) ─────── --}}
                    <div
                        class="p-3 sm:p-4 border-t border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-850 shrink-0">

                        {{-- Reply preview banner --}}
                        <div x-show="replyTo" x-cloak
                            class="flex items-center justify-between px-3 py-1.5 mb-2.5 bg-blue-50 dark:bg-blue-950/40 rounded-xl text-xs border border-blue-100 dark:border-blue-900">
                            <span class="truncate text-gray-700 dark:text-gray-300">
                                Membalas <strong :class="getUserTextColor(replyTo?.user?.id, replyTo?.user?.name)" x-text="replyTo?.user?.name"></strong>: <span class="italic"
                                    x-text="replyTo?.body"></span>
                            </span>
                            <button type="button" @click="replyTo = null"
                                class="text-blue-500 hover:text-blue-700 ml-2 cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        {{-- Attached files chips preview --}}
                        <div x-show="attachedFiles.length > 0" x-cloak class="flex flex-wrap gap-2 mb-2.5">
                            <template x-for="(file, fIdx) in attachedFiles" :key="fIdx">
                                <div
                                    class="flex items-center gap-2 px-3 py-1 rounded-xl bg-gray-100 dark:bg-gray-800 text-xs border border-gray-200 dark:border-gray-700">
                                    <span class="font-medium truncate max-w-[150px]" x-text="file.name"></span>
                                    <span class="text-gray-400" x-text="formatSize(file.size)"></span>
                                    <button type="button" @click="removeFile(fIdx)"
                                        class="text-gray-400 hover:text-red-500 cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </template>
                        </div>

                        {{-- Seamless Modern Editor Card --}}
                        <div
                            class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm focus-within:ring-2 focus-within:ring-blue-500/20 focus-within:border-blue-500 transition-all overflow-hidden">

                            {{-- Textarea (Direct flow, no gap) --}}
                            <textarea x-model="newBody" x-ref="editorInput"
                                @keydown.enter.prevent="if(!$event.shiftKey) send()" @input="autoGrow($event)" rows="2"
                                :placeholder="'Tulis pesan untuk ' + activeItem.name + '... (Gunakan @ untuk mention, shift+enter untuk baris baru)'"
                                class="w-full px-4 pt-3.5 pb-1 text-sm text-gray-900 dark:text-white placeholder-gray-400 bg-transparent outline-none border-none resize-none leading-relaxed"></textarea>

                            {{-- Integrated Toolbar (Zero dead space) --}}
                            <div class="px-3 pb-2.5 pt-0.5 flex items-center justify-between bg-white dark:bg-gray-800">

                                {{-- Formatting Tools --}}
                                <div class="flex items-center gap-0.5 sm:gap-1 text-gray-500 dark:text-gray-400">
                                    {{-- Bold --}}
                                    <button type="button" @click="wrapFormat('**', '**')"
                                        class="w-7 h-7 rounded-lg flex items-center justify-center font-bold text-xs hover:bg-gray-100 dark:hover:bg-gray-700 transition cursor-pointer"
                                        title="Bold (Tebal)">
                                        B
                                    </button>
                                    {{-- Italic --}}
                                    <button type="button" @click="wrapFormat('*', '*')"
                                        class="w-7 h-7 rounded-lg flex items-center justify-center italic text-xs font-serif hover:bg-gray-100 dark:hover:bg-gray-700 transition cursor-pointer"
                                        title="Italic (Miring)">
                                        I
                                    </button>
                                    {{-- Code --}}
                                    <button type="button" @click="wrapFormat('`', '`')"
                                        class="w-7 h-7 rounded-lg flex items-center justify-center font-mono text-xs hover:bg-gray-100 dark:hover:bg-gray-700 transition cursor-pointer"
                                        title="Code block">
                                        &lt;&gt;
                                    </button>
                                    {{-- Link --}}
                                    <button type="button" @click="insertLink()"
                                        class="w-7 h-7 rounded-lg flex items-center justify-center hover:bg-gray-100 dark:hover:bg-gray-700 transition cursor-pointer"
                                        title="Link">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                        </svg>
                                    </button>
                                    {{-- Bullets --}}
                                    <button type="button" @click="insertBullet()"
                                        class="w-7 h-7 rounded-lg flex items-center justify-center hover:bg-gray-100 dark:hover:bg-gray-700 transition cursor-pointer"
                                        title="Bullet List">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 6h16M4 12h16M4 18h16" />
                                        </svg>
                                    </button>

                                    <div class="w-px h-3.5 bg-gray-200 dark:bg-gray-700 mx-1"></div>

                                    {{-- Mention @ --}}
                                    <button type="button" @click="insertMention()"
                                        class="w-7 h-7 rounded-lg flex items-center justify-center font-semibold text-xs hover:bg-gray-100 dark:hover:bg-gray-700 transition cursor-pointer"
                                        title="Mention (@)">
                                        @
                                    </button>

                                    {{-- Attachment (Max 2MB) --}}
                                    <input type="file" x-ref="fileInput" @change="handleFileSelect($event)" multiple
                                        class="hidden">
                                    <button type="button" @click="$refs.fileInput.click()"
                                        class="w-7 h-7 rounded-lg flex items-center justify-center hover:bg-gray-100 dark:hover:bg-gray-700 transition cursor-pointer"
                                        title="Lampirkan berkas (Maks 2 MB)">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                        </svg>
                                    </button>

                                    {{-- Emoji Picker (Spacious popover, not cramped) --}}
                                    <div class="relative" x-data="{ emojiOpen: false }">
                                        <button type="button" @click="emojiOpen = !emojiOpen"
                                            class="w-7 h-7 rounded-lg flex items-center justify-center hover:bg-gray-100 dark:hover:bg-gray-700 transition cursor-pointer"
                                            title="Emoticon">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </button>
                                        <div x-show="emojiOpen" x-cloak @click.outside="emojiOpen = false"
                                            class="absolute bottom-11 left-0 p-3 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-xl w-64 z-50">
                                            <div class="text-xs font-semibold text-gray-400 dark:text-gray-500 mb-2 px-1">
                                                Pilih Emoji</div>
                                            <div class="grid grid-cols-6 gap-1.5 text-xl">
                                                <button type="button" @click="addEmoji('👍'); emojiOpen=false"
                                                    class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-gray-100 dark:hover:bg-gray-700 hover:scale-125 transition cursor-pointer">👍</button>
                                                <button type="button" @click="addEmoji('❤️'); emojiOpen=false"
                                                    class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-gray-100 dark:hover:bg-gray-700 hover:scale-125 transition cursor-pointer">❤️</button>
                                                <button type="button" @click="addEmoji('😂'); emojiOpen=false"
                                                    class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-gray-100 dark:hover:bg-gray-700 hover:scale-125 transition cursor-pointer">😂</button>
                                                <button type="button" @click="addEmoji('🎉'); emojiOpen=false"
                                                    class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-gray-100 dark:hover:bg-gray-700 hover:scale-125 transition cursor-pointer">🎉</button>
                                                <button type="button" @click="addEmoji('🚀'); emojiOpen=false"
                                                    class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-gray-100 dark:hover:bg-gray-700 hover:scale-125 transition cursor-pointer">🚀</button>
                                                <button type="button" @click="addEmoji('👀'); emojiOpen=false"
                                                    class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-gray-100 dark:hover:bg-gray-700 hover:scale-125 transition cursor-pointer">👀</button>
                                                <button type="button" @click="addEmoji('🔥'); emojiOpen=false"
                                                    class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-gray-100 dark:hover:bg-gray-700 hover:scale-125 transition cursor-pointer">🔥</button>
                                                <button type="button" @click="addEmoji('👏'); emojiOpen=false"
                                                    class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-gray-100 dark:hover:bg-gray-700 hover:scale-125 transition cursor-pointer">👏</button>
                                                <button type="button" @click="addEmoji('💡'); emojiOpen=false"
                                                    class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-gray-100 dark:hover:bg-gray-700 hover:scale-125 transition cursor-pointer">💡</button>
                                                <button type="button" @click="addEmoji('✅'); emojiOpen=false"
                                                    class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-gray-100 dark:hover:bg-gray-700 hover:scale-125 transition cursor-pointer">✅</button>
                                                <button type="button" @click="addEmoji('🙏'); emojiOpen=false"
                                                    class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-gray-100 dark:hover:bg-gray-700 hover:scale-125 transition cursor-pointer">🙏</button>
                                                <button type="button" @click="addEmoji('💯'); emojiOpen=false"
                                                    class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-gray-100 dark:hover:bg-gray-700 hover:scale-125 transition cursor-pointer">💯</button>
                                                <button type="button" @click="addEmoji('😍'); emojiOpen=false"
                                                    class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-gray-100 dark:hover:bg-gray-700 hover:scale-125 transition cursor-pointer">😍</button>
                                                <button type="button" @click="addEmoji('🤔'); emojiOpen=false"
                                                    class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-gray-100 dark:hover:bg-gray-700 hover:scale-125 transition cursor-pointer">🤔</button>
                                                <button type="button" @click="addEmoji('🥳'); emojiOpen=false"
                                                    class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-gray-100 dark:hover:bg-gray-700 hover:scale-125 transition cursor-pointer">🥳</button>
                                                <button type="button" @click="addEmoji('💪'); emojiOpen=false"
                                                    class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-gray-100 dark:hover:bg-gray-700 hover:scale-125 transition cursor-pointer">💪</button>
                                                <button type="button" @click="addEmoji('🤝'); emojiOpen=false"
                                                    class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-gray-100 dark:hover:bg-gray-700 hover:scale-125 transition cursor-pointer">🤝</button>
                                                <button type="button" @click="addEmoji('✨'); emojiOpen=false"
                                                    class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-gray-100 dark:hover:bg-gray-700 hover:scale-125 transition cursor-pointer">✨</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Right Send Action --}}
                                <div class="flex items-center gap-3">
                                    <span class="text-xs text-gray-400 hidden sm:inline">Ketik <kbd
                                            class="px-1.5 py-0.5 rounded-md border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-[11px]">Enter</kbd>
                                        untuk kirim</span>
                                    <button type="button" @click="send()"
                                        :disabled="sending || (!newBody.trim() && attachedFiles.length === 0)"
                                        class="px-4 py-2 rounded-xl font-semibold text-xs text-white flex items-center gap-1.5 shadow-sm transition-all cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed bg-blue-600 hover:bg-blue-700 active:scale-95">
                                        <span>Kirim</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                        </svg>
                                    </button>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>
            </template>
        </main>

        {{-- ═══════════════════════════════════════════════════════════════════════════════════════
        RIGHT DRAWER — Info Proyek & Diskusi / Detail Kanal / Profil Pengguna
        ═══════════════════════════════════════════════════════════════════════════════════════ --}}
        <aside x-show="activeItem && rightDrawerOpen" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-x-full opacity-0" x-transition:enter-end="translate-x-0 opacity-100"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0 opacity-100"
            x-transition:leave-end="translate-x-full opacity-0"
            class="w-72 lg:w-80 shrink-0 border-l border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-850 flex flex-col h-full z-20 overflow-y-auto scrollbar-thin">

            {{-- Drawer Header with Close 'X' --}}
            <div
                class="px-4 py-3.5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between shrink-0">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <template x-if="activeItem?.type === 'project'">
                        <span class="flex items-center gap-2">
                            <div
                                class="w-6 h-6 rounded-lg bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <span>Info Proyek &amp; Diskusi</span>
                        </span>
                    </template>
                    <template x-if="activeItem?.type === 'forum'">
                        <span class="flex items-center gap-2">
                            <div
                                class="w-6 h-6 rounded-lg bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 flex items-center justify-center font-mono font-bold text-xs">
                                #
                            </div>
                            <span>Detail Kanal</span>
                        </span>
                    </template>
                    <template x-if="activeItem?.type === 'dm'">
                        <span class="flex items-center gap-2">
                            <div
                                class="w-6 h-6 rounded-lg bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <span class="truncate" x-text="'Profil ' + activeItem.name"></span>
                        </span>
                    </template>
                </h3>
                <button type="button" @click="rightDrawerOpen = false"
                    class="p-1 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Drawer Body --}}
            <div class="p-4 space-y-5 text-sm">

                {{-- ════════════ CASE A: PROJECT DRAWER ════════════ --}}
                <template x-if="activeItem?.type === 'project'">
                    <div class="space-y-5">
                        {{-- 1. Ringkasan Proyek --}}
                        <div class="p-4 rounded-2xl bg-gray-50/90 dark:bg-gray-800/60 space-y-3">
                            <div class="flex items-center justify-between">
                                <div
                                    class="flex items-center gap-2 text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    <div
                                        class="w-6 h-6 rounded-lg bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                    <span>Ringkasan Proyek</span>
                                </div>
                                <span
                                    class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Active
                                </span>
                            </div>
                            <div>
                                <h4 class="font-bold text-sm sm:text-base text-gray-900 dark:text-white"
                                    x-text="detailsData?.project?.name || activeItem.name"></h4>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5"
                                    x-text="'Klien: ' + (detailsData?.project?.client_name || activeItem.client_name || '-')">
                                </div>
                            </div>
                            <div class="space-y-1.5 pt-1">
                                <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
                                    <span>Deadline</span>
                                    <span class="font-semibold text-gray-700 dark:text-gray-300"
                                        x-text="detailsData?.project?.deadline || activeItem.deadline"></span>
                                </div>
                                <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
                                    <span>Progress</span>
                                    <span class="font-bold text-blue-600 dark:text-blue-400"
                                        x-text="(detailsData?.project?.progress ?? activeItem.progress ?? 0) + '% Complete'"></span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 h-2 rounded-full overflow-hidden mt-1">
                                    <div class="bg-blue-600 h-full rounded-full transition-all duration-300"
                                        :style="'width:' + (detailsData?.project?.progress ?? activeItem.progress ?? 0) + '%'">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- 2. Pesan Disematkan --}}
                        <div class="space-y-2.5">
                            <div class="flex items-center justify-between">
                                <div
                                    class="flex items-center gap-2 text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                    <div
                                        class="w-6 h-6 rounded-lg bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                                        </svg>
                                    </div>
                                    <span>Pesan Disematkan</span>
                                </div>
                                <span
                                    class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300"
                                    x-text="detailsData?.pinned_messages?.length || 0"></span>
                            </div>
                            <div class="space-y-2">
                                <template x-for="pmsg in (detailsData?.pinned_messages || [])" :key="pmsg.id">
                                    <div @click="scrollToMessage(pmsg.id)"
                                        class="p-3 rounded-xl bg-amber-50/50 dark:bg-amber-950/30 hover:bg-amber-100/60 dark:hover:bg-amber-900/40 transition cursor-pointer">
                                        <div class="flex items-center justify-between text-xs text-gray-400 mb-1">
                                            <span class="font-bold truncate"
                                                :class="getUserTextColor(pmsg.user_id, pmsg.user_name)"
                                                x-text="pmsg.user_name"></span>
                                            <span x-text="pmsg.time"></span>
                                        </div>
                                        <p class="text-xs text-gray-700 dark:text-gray-300 line-clamp-2 leading-relaxed"
                                            x-text="pmsg.body"></p>
                                    </div>
                                </template>
                                <div x-show="!detailsData?.pinned_messages?.length"
                                    class="text-xs text-gray-400 dark:text-gray-500 italic p-1">
                                    Belum ada pesan disematkan.
                                </div>
                            </div>
                        </div>

                        {{-- 3. Berkas Proyek Terbagi --}}
                        <div class="space-y-2.5">
                            <div class="flex items-center justify-between">
                                <div
                                    class="flex items-center gap-2 text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                    <div
                                        class="w-6 h-6 rounded-lg bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                        </svg>
                                    </div>
                                    <span>Berkas Proyek Terbagi</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span
                                        class="px-2 py-0.5 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300"
                                        x-text="detailsData?.files?.length || 0"></span>
                                    <button type="button" x-show="(detailsData?.files?.length || 0) > 3"
                                        @click="showAllFiles = !showAllFiles"
                                        class="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline cursor-pointer ml-1">
                                        <span x-text="showAllFiles ? 'Sembunyikan' : 'Lihat Semua'"></span>
                                    </button>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <template x-for="file in visibleProjectFiles" :key="file.id">
                                    <div
                                        class="flex items-center justify-between p-2.5 rounded-xl bg-gray-50/80 dark:bg-gray-800/60 hover:bg-gray-100/80 dark:hover:bg-gray-750 transition">
                                        <div class="flex items-center gap-2.5 truncate min-w-0">
                                            <span
                                                class="w-8 h-8 rounded-lg flex items-center justify-center text-[10px] font-bold shrink-0"
                                                :class="file.extension === 'pdf' ? 'bg-red-100 text-red-600 dark:bg-red-950/50 dark:text-red-400' : (file.extension === 'xlsx' || file.extension === 'xls' ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400' : 'bg-blue-100 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400')"
                                                x-text="file.extension.toUpperCase().substring(0,3)"></span>
                                            <div class="min-w-0">
                                                <div class="font-medium text-xs text-gray-800 dark:text-gray-200 truncate max-w-[145px]"
                                                    x-text="file.name"></div>
                                                <div class="text-[11px] text-gray-400"
                                                    x-text="file.size + ' • ' + file.created_at"></div>
                                            </div>
                                        </div>
                                        <a :href="file.url" download
                                            class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-white dark:hover:bg-gray-700 transition cursor-pointer"
                                            title="Unduh berkas">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                        </a>
                                    </div>
                                </template>
                                <div x-show="!detailsData?.files?.length"
                                    class="text-xs text-gray-400 dark:text-gray-500 italic p-1">
                                    Belum ada berkas di proyek ini.
                                </div>
                            </div>
                        </div>

                        {{-- 4. Anggota Proyek --}}
                        <div class="space-y-2.5">
                            <div class="flex items-center justify-between">
                                <div
                                    class="flex items-center gap-2 text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                    <div
                                        class="w-6 h-6 rounded-lg bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                    </div>
                                    <span>Anggota Proyek</span>
                                </div>
                                <span
                                    class="px-2 py-0.5 rounded-full text-xs font-bold bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300"
                                    x-text="detailsData?.members?.length || 0"></span>
                            </div>
                            <div class="space-y-1.5">
                                <template x-for="m in (detailsData?.members || [])" :key="m.id">
                                    <div
                                        class="flex items-center justify-between p-2 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800/40 transition">
                                        <div class="flex items-center gap-2.5 truncate min-w-0">
                                            <template x-if="m.avatar">
                                                <img :src="m.avatar" class="w-7 h-7 rounded-full object-cover">
                                            </template>
                                            <template x-if="!m.avatar">
                                                <span
                                                    class="w-7 h-7 rounded-full flex items-center justify-center text-[10px] font-bold text-white shadow-2xs"
                                                    :class="getUserAvatarBg(m.id, m.name)"
                                                    x-text="m.initials"></span>
                                            </template>
                                            <span class="font-medium text-xs text-gray-800 dark:text-gray-200 truncate"
                                                x-text="m.name"></span>
                                        </div>
                                        <template
                                            x-if="m.role && (m.role.toLowerCase().includes('lead') || m.role.toLowerCase().includes('client'))">
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold shrink-0"
                                                :class="m.role.toLowerCase().includes('lead') ? 'bg-blue-50 text-blue-600 dark:bg-blue-950/40 dark:text-blue-400' : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400'"
                                                x-text="m.role"></span>
                                        </template>
                                    </div>
                                </template>
                                <div x-show="!detailsData?.members?.length"
                                    class="text-xs text-gray-400 dark:text-gray-500 italic p-1">
                                    Memuat anggota...
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- ════════════ CASE B: FORUM DRAWER ════════════ --}}
                <template x-if="activeItem?.type === 'forum'">
                    <div class="space-y-5">
                        {{-- 1. Topik Kanal --}}
                        <div class="p-4 rounded-2xl bg-gray-50/90 dark:bg-gray-800/60 space-y-2.5">
                            <div class="flex items-center justify-between">
                                <div
                                    class="flex items-center gap-2 text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    <div
                                        class="w-6 h-6 rounded-lg bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 flex items-center justify-center font-mono font-bold text-xs">
                                        #
                                    </div>
                                    <span>Topik Kanal</span>
                                </div>
                                <span
                                    class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400">•
                                    Active</span>
                            </div>
                            <h4 class="font-bold text-sm sm:text-base text-gray-900 dark:text-white"
                                x-text="'#' + activeItem.name"></h4>
                            <p class="text-xs text-gray-600 dark:text-gray-300 leading-relaxed"
                                x-text="detailsData?.forum?.description || activeItem.description"></p>
                            <div class="pt-2 space-y-1.5 text-xs text-gray-500 dark:text-gray-400">
                                <div class="flex justify-between">
                                    <span>Dibuat oleh</span>
                                    <span class="font-semibold text-gray-800 dark:text-gray-200"
                                        x-text="detailsData?.forum?.creator_name || activeItem.creator_name"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Tipe Kanal</span>
                                    <span class="font-semibold text-gray-800 dark:text-gray-200">Publik Tim</span>
                                </div>
                            </div>
                        </div>

                        {{-- 2. Pesan Disematkan --}}
                        <div class="space-y-2.5">
                            <div class="flex items-center justify-between">
                                <div
                                    class="flex items-center gap-2 text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                    <div
                                        class="w-6 h-6 rounded-lg bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                                        </svg>
                                    </div>
                                    <span>Pesan Disematkan</span>
                                </div>
                                <span
                                    class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300"
                                    x-text="detailsData?.pinned_messages?.length || 0"></span>
                            </div>
                            <div class="space-y-2">
                                <template x-for="pmsg in (detailsData?.pinned_messages || [])" :key="pmsg.id">
                                    <div @click="scrollToMessage(pmsg.id)"
                                        class="p-3 rounded-xl bg-amber-50/50 dark:bg-amber-950/30 hover:bg-amber-100/60 dark:hover:bg-amber-900/40 transition cursor-pointer">
                                        <div class="flex items-center justify-between text-xs text-gray-400 mb-1">
                                            <span class="font-bold truncate"
                                                :class="getUserTextColor(pmsg.user_id, pmsg.user_name)"
                                                x-text="pmsg.user_name"></span>
                                            <span x-text="pmsg.time"></span>
                                        </div>
                                        <p class="text-xs text-gray-700 dark:text-gray-300 line-clamp-2 leading-relaxed"
                                            x-text="pmsg.body"></p>
                                    </div>
                                </template>
                                <div x-show="!detailsData?.pinned_messages?.length"
                                    class="text-xs text-gray-400 dark:text-gray-500 italic p-1">
                                    Belum ada pesan disematkan.
                                </div>
                            </div>
                        </div>

                        {{-- 3. Berkas & Media Terbagi --}}
                        <div class="space-y-2.5">
                            <div class="flex items-center justify-between">
                                <div
                                    class="flex items-center gap-2 text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                    <div
                                        class="w-6 h-6 rounded-lg bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                        </svg>
                                    </div>
                                    <span>Berkas &amp; Media Terbagi</span>
                                </div>
                                <span
                                    class="px-2 py-0.5 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300"
                                    x-text="detailsData?.files?.length || 0"></span>
                            </div>
                            <div class="space-y-2">
                                <template x-for="f in (detailsData?.files || [])" :key="f.id">
                                    <div
                                        class="flex items-center justify-between p-2.5 rounded-xl bg-gray-50/80 dark:bg-gray-800/60 hover:bg-gray-100/80 dark:hover:bg-gray-750 transition">
                                        <div class="min-w-0">
                                            <div class="font-medium text-xs text-gray-800 dark:text-gray-200 truncate max-w-[160px]"
                                                x-text="f.name"></div>
                                            <div class="text-[11px] text-gray-400" x-text="f.size + ' • ' + f.created_at">
                                            </div>
                                        </div>
                                        <a :href="f.url" download
                                            class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 transition cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                        </a>
                                    </div>
                                </template>
                                <div x-show="!detailsData?.files?.length"
                                    class="text-xs text-gray-400 dark:text-gray-500 italic p-1">
                                    Belum ada berkas di kanal ini.
                                </div>
                            </div>
                        </div>

                        {{-- 4. Anggota Kanal --}}
                        <div class="space-y-2.5">
                            <div class="flex items-center justify-between">
                                <div
                                    class="flex items-center gap-2 text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                    <div
                                        class="w-6 h-6 rounded-lg bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                    </div>
                                    <span>Anggota Kanal</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span
                                        class="px-2 py-0.5 rounded-full text-xs font-bold bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300"
                                        x-text="detailsData?.members?.length || 0"></span>
                                    <button type="button" @click="openInviteModal()"
                                        class="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline cursor-pointer">
                                        + Undang
                                    </button>
                                </div>
                            </div>
                            <div class="space-y-1.5">
                                <template x-for="m in (detailsData?.members || [])" :key="m.id">
                                    <div
                                        class="flex items-center justify-between p-2 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800/40 transition">
                                        <div class="flex items-center gap-2.5 truncate min-w-0">
                                            <template x-if="m.avatar">
                                                <img :src="m.avatar" class="w-7 h-7 rounded-full object-cover">
                                            </template>
                                            <template x-if="!m.avatar">
                                                <span
                                                    class="w-7 h-7 rounded-full flex items-center justify-center text-[10px] font-bold text-white shadow-2xs"
                                                    :class="getUserAvatarBg(m.id, m.name)"
                                                    x-text="m.initials"></span>
                                            </template>
                                            <span class="font-medium text-xs text-gray-800 dark:text-gray-200 truncate"
                                                x-text="m.name"></span>
                                        </div>
                                        <span class="text-xs text-gray-400" x-text="m.role"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- ════════════ CASE C: DIRECT CHAT DRAWER ════════════ --}}
                <template x-if="activeItem?.type === 'dm'">
                    <div class="space-y-5">
                        {{-- 1. Profil Pengguna --}}
                        <div class="p-4 rounded-2xl bg-gray-50/90 dark:bg-gray-800/60 space-y-3">
                            <div class="flex items-center justify-between">
                                <div
                                    class="flex items-center gap-2 text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    <div
                                        class="w-6 h-6 rounded-lg bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                    <span>Profil Rekan</span>
                                </div>
                                <span
                                    class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400 flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Online
                                </span>
                            </div>
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-12 h-12 rounded-2xl text-white flex items-center justify-center font-bold text-base shrink-0 overflow-hidden shadow-xs"
                                    :class="!activeItem.avatar ? getUserAvatarBg(activeItem.id, activeItem.name) : 'bg-gray-100 dark:bg-gray-800'">
                                    <template x-if="activeItem.avatar">
                                        <img :src="activeItem.avatar" class="w-full h-full object-cover rounded-2xl">
                                    </template>
                                    <template x-if="!activeItem.avatar">
                                        <span x-text="activeItem.initials"></span>
                                    </template>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="font-bold text-sm sm:text-base text-gray-900 dark:text-white truncate"
                                        x-text="activeItem.name"></h4>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 truncate"
                                        x-text="detailsData?.peer?.role_title || activeItem.role_title || 'Member'"></div>
                                </div>
                            </div>
                            <div class="space-y-1.5 pt-2 text-xs text-gray-500 dark:text-gray-400">
                                <div class="flex justify-between">
                                    <span>Email</span>
                                    <span class="font-semibold text-gray-800 dark:text-gray-200 truncate max-w-[160px]"
                                        x-text="activeItem.email"></span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 pt-1">
                                <button type="button" @click="$refs.fileInput.click()"
                                    class="flex-1 py-2 px-3 rounded-xl bg-gray-100 dark:bg-gray-800 text-xs font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-750 transition flex items-center justify-center gap-2 cursor-pointer shadow-2xs">
                                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                    </svg>
                                    <span>Kirim Berkas</span>
                                </button>
                                <button type="button" @click="openMeetingModal()"
                                    class="flex-1 py-2 px-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold transition flex items-center justify-center gap-2 cursor-pointer shadow-2xs">
                                    <svg class="w-4 h-4 text-white shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    <span>1-on-1</span>
                                </button>
                            </div>
                        </div>

                        {{-- 2. Berkas Terbagi Bersama --}}
                        <div class="space-y-2.5">
                            <div class="flex items-center justify-between">
                                <div
                                    class="flex items-center gap-2 text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                    <div
                                        class="w-6 h-6 rounded-lg bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                        </svg>
                                    </div>
                                    <span>Berkas Terbagi</span>
                                </div>
                                <span
                                    class="px-2 py-0.5 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300"
                                    x-text="detailsData?.files?.length || 0"></span>
                            </div>
                            <div class="space-y-2">
                                <template x-for="f in (detailsData?.files || [])" :key="f.id">
                                    <div
                                        class="flex items-center justify-between p-2.5 rounded-xl bg-gray-50/80 dark:bg-gray-800/60 hover:bg-gray-100/80 dark:hover:bg-gray-750 transition">
                                        <div class="min-w-0">
                                            <div class="font-medium text-xs text-gray-800 dark:text-gray-200 truncate max-w-[160px]"
                                                x-text="f.name"></div>
                                            <div class="text-[11px] text-gray-400" x-text="f.size + ' • ' + f.created_at">
                                            </div>
                                        </div>
                                        <a :href="f.url" download
                                            class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 transition cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                        </a>
                                    </div>
                                </template>
                                <div x-show="!detailsData?.files?.length"
                                    class="text-xs text-gray-400 dark:text-gray-500 italic p-1">
                                    Belum ada berkas dikirim.
                                </div>
                            </div>
                        </div>

                        {{-- 3. Pesan Disematkan --}}
                        <div class="space-y-2.5">
                            <div class="flex items-center justify-between">
                                <div
                                    class="flex items-center gap-2 text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                    <div
                                        class="w-6 h-6 rounded-lg bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                                        </svg>
                                    </div>
                                    <span>Pesan Disematkan</span>
                                </div>
                                <span
                                    class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300"
                                    x-text="detailsData?.pinned_messages?.length || 0"></span>
                            </div>
                            <div class="space-y-2">
                                <template x-for="pmsg in (detailsData?.pinned_messages || [])" :key="pmsg.id">
                                    <div @click="scrollToMessage(pmsg.id)"
                                        class="p-3 rounded-xl bg-amber-50/50 dark:bg-amber-950/30 hover:bg-amber-100/60 transition cursor-pointer">
                                        <div class="flex items-center justify-between text-xs text-gray-400 mb-1">
                                            <span class="font-bold text-gray-800 dark:text-gray-200 truncate"
                                                x-text="pmsg.user_name"></span>
                                            <span x-text="pmsg.time"></span>
                                        </div>
                                        <p class="text-xs text-gray-700 dark:text-gray-300 line-clamp-2 leading-relaxed"
                                            x-text="pmsg.body"></p>
                                    </div>
                                </template>
                                <div x-show="!detailsData?.pinned_messages?.length"
                                    class="text-xs text-gray-400 dark:text-gray-500 italic p-1">
                                    Belum ada pesan disematkan.
                                </div>
                            </div>
                        </div>

                        {{-- 4. Proyek Bersama --}}
                        <div class="space-y-2.5">
                            <div class="flex items-center justify-between">
                                <div
                                    class="flex items-center gap-2 text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                    <div
                                        class="w-6 h-6 rounded-lg bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                        </svg>
                                    </div>
                                    <span>Proyek Bersama</span>
                                </div>
                                <span
                                    class="px-2 py-0.5 rounded-full text-xs font-bold bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300"
                                    x-text="detailsData?.peer?.shared_projects?.length || activeItem.shared_projects?.length || 0"></span>
                            </div>
                            <div class="space-y-2">
                                <template
                                    x-for="sp in (detailsData?.peer?.shared_projects || activeItem.shared_projects || [])"
                                    :key="sp.id">
                                    <button type="button" @click="switchToProject(sp.id)"
                                        class="w-full flex items-center justify-between p-2.5 rounded-xl bg-gray-50/80 dark:bg-gray-800/60 hover:bg-blue-50/80 dark:hover:bg-blue-950/40 transition text-left cursor-pointer group">
                                        <span
                                            class="font-medium text-xs text-gray-800 dark:text-gray-200 group-hover:text-blue-600 truncate"
                                            x-text="sp.name"></span>
                                        <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-blue-600 shrink-0"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                </template>
                                <div x-show="!(detailsData?.peer?.shared_projects?.length || activeItem.shared_projects?.length)"
                                    class="text-xs text-gray-400 dark:text-gray-500 italic p-1">
                                    Tidak ada proyek bersama.
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

            </div>
        </aside>

        {{-- ═══════════════════════════════════════════════════════════════════════════════════════
        MODAL 1: BUAT KANAL FORUM BARU (Task Creation Style)
        ═══════════════════════════════════════════════════════════════════════════════════════ --}}
        <div x-show="createForumOpen" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
            <div @click.outside="createForumOpen = false"
                class="bg-white dark:bg-gray-850 rounded-2xl shadow-2xl max-w-md w-full border border-gray-100 dark:border-gray-800 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <h3 class="font-bold text-gray-900 dark:text-white text-sm flex items-center gap-2">
                        <span
                            class="w-7 h-7 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-xs">#</span>
                        Buat Kanal Forum Baru
                    </h3>
                    <button type="button" @click="createForumOpen = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <form @submit.prevent="submitCreateForum()" class="p-5 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Nama
                            Kanal</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 font-mono text-gray-400">#</span>
                            <input type="text" x-model="newForumName" required placeholder="misal: sprint-auth-rbac"
                                class="w-full pl-7 pr-3 py-2 text-xs border border-gray-200 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white focus:bg-white focus:border-blue-500 outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Topik / Deskripsi
                            Kanal</label>
                        <textarea x-model="newForumDesc" rows="2"
                            placeholder="Deskripsikan tujuan atau topik diskusi kanal ini..."
                            class="w-full px-3 py-2 text-xs border border-gray-200 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white focus:bg-white focus:border-blue-500 outline-none resize-none"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Undang Rekan
                            (Hanya Rekan Proyek)</label>
                        <div
                            class="max-h-40 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-xl p-2 space-y-1 bg-gray-50/50 dark:bg-gray-800/40 divide-y divide-gray-100 dark:divide-gray-800">
                            <template x-for="p in inviteCandidates" :key="p.id">
                                <label
                                    class="flex items-center gap-2.5 p-1.5 rounded-lg hover:bg-white dark:hover:bg-gray-750 cursor-pointer">
                                    <input type="checkbox" :value="p.id" x-model="newForumMembers"
                                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span
                                        class="w-6 h-6 rounded-full flex items-center justify-center text-[9px] font-bold text-white shadow-2xs"
                                        :class="getUserAvatarBg(p.id, p.name)"
                                        x-text="p.initials"></span>
                                    <span class="text-xs text-gray-800 dark:text-gray-200 truncate" x-text="p.name"></span>
                                </label>
                            </template>
                            <div x-show="inviteCandidates.length === 0"
                                class="text-[11px] text-gray-400 p-2 italic text-center">
                                Tidak ada rekan proyek lain yang tersedia.
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                        <button type="button" @click="createForumOpen = false"
                            class="px-4 py-2 rounded-xl text-xs font-semibold text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                            Batal
                        </button>
                        <button type="submit" :disabled="creatingForum || !newForumName.trim()"
                            class="px-4 py-2 rounded-xl text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 transition disabled:opacity-50">
                            Buat Kanal
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════════════════════════════════
        MODAL 2: 1-ON-1 MEETING MODAL
        ═══════════════════════════════════════════════════════════════════════════════════════ --}}
        <div x-show="meetingModalOpen" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
            <div @click.outside="meetingModalOpen = false"
                class="bg-white dark:bg-gray-850 rounded-2xl shadow-2xl max-w-sm w-full border border-gray-100 dark:border-gray-800 p-5 text-center">
                <div
                    class="w-12 h-12 rounded-2xl bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 mx-auto flex items-center justify-center mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-gray-900 dark:text-white"
                    x-text="'Mulai Meeting 1-on-1 dengan ' + activeItem?.name"></h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Buat ruang virtual instan atau undang lewat Google
                    Meet untuk diskusi privat.</p>
                <div class="mt-4 space-y-2">
                    <a :href="'https://meet.google.com/new'" target="_blank"
                        class="w-full py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold block transition">
                        Buka Google Meet Instan
                    </a>
                    <button type="button" @click="meetingModalOpen = false"
                        class="w-full py-2 rounded-xl border border-gray-200 dark:border-gray-700 text-xs font-semibold text-gray-600 dark:text-gray-400 hover:bg-gray-50 transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('chatHubApp', (projectsData, dmsData, forumsData, inviteCandidatesData, initialTarget) => ({
                search: '',
                projects: projectsData,
                dms: dmsData,
                forums: forumsData,
                inviteCandidates: inviteCandidatesData,

                activeItem: null,
                detailsData: null,
                messages: [],
                newBody: '',
                replyTo: null,
                attachedFiles: [],
                lastId: 0,
                loading: false,
                sending: false,
                mobileChat: false,
                rightDrawerOpen: true,
                showAllFiles: false,
                pollingTimer: null,
                unreadPollingTimer: null,
                csrf: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',

                createForumOpen: false,
                newForumName: '',
                newForumDesc: '',
                newForumMembers: [],
                creatingForum: false,
                meetingModalOpen: false,

                init() {
                    if (initialTarget) {
                        const list = initialTarget.type === 'project' ? this.projects : (initialTarget.type === 'forum' ? this.forums : this.dms);
                        const found = list.find(i => i.id === initialTarget.id);
                        if (found) this.selectItem(found);
                    }
                    this.startUnreadPolling();
                },

                get filteredProjects() {
                    const q = this.search.trim().toLowerCase();
                    return q ? this.projects.filter(p => p.name.toLowerCase().includes(q)) : this.projects;
                },

                get filteredForums() {
                    const q = this.search.trim().toLowerCase();
                    return q ? this.forums.filter(f => f.name.toLowerCase().includes(q)) : this.forums;
                },

                get filteredDms() {
                    const q = this.search.trim().toLowerCase();
                    return q ? this.dms.filter(d => d.name.toLowerCase().includes(q)) : this.dms;
                },

                get visibleProjectFiles() {
                    const files = this.detailsData?.files || [];
                    return this.showAllFiles ? files : files.slice(0, 3);
                },

                isActive(item) {
                    return this.activeItem && this.activeItem.type === item.type && this.activeItem.id === item.id;
                },

                endpoints(item) {
                    if (!item) return {};
                    if (item.type === 'project') return {
                        thread: `/projects/${item.slug || item.id}/chat/messages`,
                        send: `/projects/${item.slug || item.id}/chat`,
                        pin: (mid) => `/projects/${item.slug || item.id}/chat/${mid}/pin`,
                        react: (mid) => `/projects/${item.slug || item.id}/chat/${mid}/react`,
                        delete: (mid) => `/projects/${item.slug || item.id}/chat/${mid}`,
                        details: `/projects/${item.slug || item.id}/chat/details`,
                        read: `/projects/${item.slug || item.id}/chat/read`,
                    };
                    if (item.type === 'dm') return {
                        thread: `/messages/${item.id}/thread`,
                        send: `/messages/${item.id}`,
                        pin: (mid) => `/messages/${item.id}/${mid}/pin`,
                        details: `/messages/${item.id}/details`,
                        read: `/messages/${item.id}/read`,
                    };
                    return {
                        thread: `/forums/${item.id}/messages`,
                        send: `/forums/${item.id}/messages`,
                        pin: (mid) => `/forums/${item.id}/messages/${mid}/pin`,
                        details: `/forums/${item.id}/details`,
                        read: `/forums/${item.id}/read`,
                    };
                },

                async selectItem(item) {
                    if (this.isActive(item)) return;

                    clearInterval(this.pollingTimer);
                    this.messages = [];
                    this.detailsData = null;
                    this.lastId = 0;
                    this.replyTo = null;
                    this.attachedFiles = [];
                    this.newBody = '';
                    this.showAllFiles = false;
                    this.activeItem = item;
                    this.mobileChat = true;

                    if (item.type === 'project' && (item.slug || item.id)) {
                        const url = new URL(window.location);
                        url.searchParams.delete('dm');
                        url.searchParams.delete('user');
                        url.searchParams.delete('forum');
                        url.searchParams.set('project', item.slug || item.id);
                        window.history.replaceState({}, '', url);
                    } else if (item.type === 'dm') {
                        const url = new URL(window.location);
                        url.searchParams.delete('project');
                        url.searchParams.delete('forum');
                        url.searchParams.set('dm', item.id);
                        window.history.replaceState({}, '', url);
                    } else if (item.type === 'forum') {
                        const url = new URL(window.location);
                        url.searchParams.delete('project');
                        url.searchParams.delete('dm');
                        url.searchParams.delete('user');
                        url.searchParams.set('forum', item.id);
                        window.history.replaceState({}, '', url);
                    }

                    item.unread_count = 0;

                    await Promise.all([this.loadMessages(), this.loadDetails()]);
                    this.markRead();
                    this.startPolling();
                },

                async loadMessages() {
                    this.loading = true;
                    try {
                        const eps = this.endpoints(this.activeItem);
                        const res = await fetch(eps.thread);
                        const data = await res.json();
                        this.messages = data.messages || [];
                        this.lastId = this.messages.at(-1)?.id ?? 0;
                        this.$nextTick(() => this.scrollBottom());
                    } catch (_) {
                        this.messages = [];
                    } finally {
                        this.loading = false;
                    }
                },

                async loadDetails() {
                    try {
                        const eps = this.endpoints(this.activeItem);
                        if (eps.details) {
                            const res = await fetch(eps.details);
                            if (res.ok) {
                                this.detailsData = await res.json();
                            }
                        }
                    } catch (_) { }
                },

                startPolling() {
                    clearInterval(this.pollingTimer);
                    this.pollingTimer = setInterval(async () => {
                        if (!document.hidden && this.activeItem) {
                            await this.pollNew();
                        }
                    }, 4000);
                },

                async pollNew() {
                    try {
                        const eps = this.endpoints(this.activeItem);
                        const res = await fetch(`${eps.thread}?after=${this.lastId}`);
                        const data = await res.json();
                        if (data.messages && data.messages.length > 0) {
                            const atBottom = this.isNearBottom();
                            data.messages.forEach(m => this.messages.push(m));
                            this.lastId = data.messages.at(-1).id;

                            if (atBottom) this.$nextTick(() => this.scrollBottom());
                            this.markRead();
                        }
                    } catch (_) { }
                },

                isNearBottom() {
                    const el = this.$refs.msgArea;
                    return el ? (el.scrollHeight - el.scrollTop - el.clientHeight < 120) : true;
                },

                scrollBottom() {
                    const el = this.$refs.msgArea;
                    if (el) el.scrollTop = el.scrollHeight;
                },

                scrollToMessage(messageId) {
                    const el = document.getElementById('msg-' + messageId);
                    if (el) {
                        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        el.classList.add('rounded-2xl', 'bg-yellow-50/70', 'dark:bg-blue-950/60');
                        setTimeout(() => {
                            el.classList.remove('rounded-2xl', 'bg-yellow-50/70', 'dark:bg-blue-950/60');
                        }, 2200);
                    }
                },

                togglePinnedShortcut() {
                    if (this.detailsData?.pinned_messages?.length > 0) {
                        this.scrollToMessage(this.detailsData.pinned_messages[0].id);
                    } else {
                        this.rightDrawerOpen = true;
                    }
                },

                autoGrow(e) {
                    e.target.style.height = 'auto';
                    e.target.style.height = Math.min(e.target.scrollHeight, 140) + 'px';
                },

                handleFileSelect(e) {
                    const files = Array.from(e.target.files);
                    const MAX_SIZE = 2 * 1024 * 1024; // 2MB
                    for (const file of files) {
                        if (file.size > MAX_SIZE) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Ukuran Terlalu Besar',
                                text: `Berkas "${file.name}" melebihi batas maksimal 2 MB.`,
                                confirmButtonColor: '#2563eb'
                            });
                            continue;
                        }
                        this.attachedFiles.push(file);
                    }
                    e.target.value = '';
                },

                removeFile(index) {
                    this.attachedFiles.splice(index, 1);
                },

                wrapFormat(prefix, suffix) {
                    const el = this.$refs.editorInput;
                    if (!el) return;
                    const start = el.selectionStart;
                    const end = el.selectionEnd;
                    const text = this.newBody;
                    const selected = text.substring(start, end) || 'teks';
                    this.newBody = text.substring(0, start) + prefix + selected + suffix + text.substring(end);
                    this.$nextTick(() => {
                        el.focus();
                        el.setSelectionRange(start + prefix.length, start + prefix.length + selected.length);
                    });
                },

                insertLink() {
                    const url = prompt('Masukkan tautan URL (misal: https://example.com):');
                    if (url) {
                        this.wrapFormat('[Tautan](', url + ')');
                    }
                },

                insertBullet() {
                    this.newBody += (this.newBody.endsWith('\n') || !this.newBody ? '' : '\n') + '- ';
                    this.$refs.editorInput?.focus();
                },

                insertMention() {
                    this.newBody += '@';
                    this.$refs.editorInput?.focus();
                },

                addEmoji(emoji) {
                    this.newBody += emoji;
                    this.$refs.editorInput?.focus();
                },

                setReply(msg) {
                    this.replyTo = msg;
                    this.$refs.editorInput?.focus();
                },

                async send() {
                    if (this.sending || !this.activeItem) return;
                    const body = this.newBody.trim();
                    if (!body && this.attachedFiles.length === 0) return;

                    this.sending = true;
                    try {
                        const eps = this.endpoints(this.activeItem);
                        const fd = new FormData();
                        if (body) fd.append('body', body);
                        if (this.replyTo) fd.append('parent_id', this.replyTo.id);
                        this.attachedFiles.forEach(f => fd.append('files[]', f));

                        const res = await fetch(eps.send, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': this.csrf },
                            body: fd,
                        });

                        if (res.ok) {
                            const data = await res.json();
                            if (data.message) {
                                this.messages.push(data.message);
                                this.lastId = data.message.id;
                            }
                            this.newBody = '';
                            this.replyTo = null;
                            this.attachedFiles = [];
                            if (this.$refs.editorInput) this.$refs.editorInput.style.height = 'auto';
                            this.$nextTick(() => this.scrollBottom());
                            this.loadDetails(); // Refresh details/files
                        } else {
                            let errMsg = 'Terjadi kesalahan pada server.';
                            try {
                                const err = await res.json();
                                if (err && (err.error || err.message)) {
                                    errMsg = err.error || err.message;
                                }
                            } catch (_) { }
                            Swal.fire({ icon: 'error', title: 'Gagal Mengirim', text: errMsg, confirmButtonColor: '#2563eb' });
                        }
                    } catch (err) {
                        console.error('Send error:', err);
                        Swal.fire({ icon: 'error', title: 'Gagal', text: 'Koneksi terputus atau terjadi kesalahan.', confirmButtonColor: '#2563eb' });
                    } finally {
                        this.sending = false;
                    }
                },

                async togglePin(msg) {
                    const eps = this.endpoints(this.activeItem);
                    if (!eps.pin) return;
                    try {
                        const res = await fetch(eps.pin(msg.id), {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': this.csrf },
                        });
                        if (res.ok) {
                            const data = await res.json();
                            msg.is_pinned = data.is_pinned;
                            if (this.detailsData) {
                                if (!this.detailsData.pinned_messages) {
                                    this.detailsData.pinned_messages = [];
                                }
                                if (data.is_pinned) {
                                    if (!this.detailsData.pinned_messages.some(p => p.id === msg.id)) {
                                        this.detailsData.pinned_messages.unshift({
                                            id: msg.id,
                                            user_name: msg.user?.name || 'User',
                                            body: msg.body,
                                            time: msg.time_str || msg.time_label || 'Baru saja'
                                        });
                                    }
                                } else {
                                    this.detailsData.pinned_messages = this.detailsData.pinned_messages.filter(p => p.id !== msg.id);
                                }
                            }
                            await this.loadDetails();
                        }
                    } catch (_) { }
                },

                async toggleReaction(msg, emoji) {
                    const eps = this.endpoints(this.activeItem);
                    if (!eps.react) return;
                    try {
                        const res = await fetch(eps.react(msg.id), {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                            body: JSON.stringify({ emoji }),
                        });
                        if (res.ok) {
                            const data = await res.json();
                            msg.reactions = data.reactions;
                        }
                    } catch (_) { }
                },

                async deleteMsg(msg) {
                    if (!confirm('Hapus pesan ini?')) return;
                    const eps = this.endpoints(this.activeItem);
                    if (!eps.delete) return;
                    try {
                        const res = await fetch(eps.delete(msg.id), {
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': this.csrf },
                        });
                        if (res.ok) {
                            this.messages = this.messages.filter(m => m.id !== msg.id);
                        }
                    } catch (_) { }
                },

                async markRead() {
                    const eps = this.endpoints(this.activeItem);
                    if (!eps.read) return;
                    try {
                        await fetch(eps.read, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': this.csrf },
                        });
                    } catch (_) { }
                },

                openCreateForum() {
                    this.newForumName = '';
                    this.newForumDesc = '';
                    this.newForumMembers = [];
                    this.createForumOpen = true;
                },

                async submitCreateForum() {
                    if (this.creatingForum || !this.newForumName.trim()) return;
                    this.creatingForum = true;
                    try {
                        const res = await fetch('/forums', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                            body: JSON.stringify({
                                name: this.newForumName,
                                description: this.newForumDesc,
                                member_ids: this.newForumMembers,
                            }),
                        });
                        if (res.ok) {
                            const data = await res.json();
                            this.forums.push(data.forum);
                            this.createForumOpen = false;
                            this.selectItem(data.forum);
                        }
                    } finally {
                        this.creatingForum = false;
                    }
                },

                openMeetingModal() {
                    this.meetingModalOpen = true;
                },

                openInviteModal() {
                    this.createForumOpen = true;
                },

                showGuideToast() {
                    Swal.fire({
                        icon: 'info',
                        title: 'Panduan Kanal Flovig',
                        html: '<p class="text-xs text-left">Gunakan kanal ini untuk koordinasi tim, berbagi dokumen proyek, dan mengumumkan update harian. Seluruh anggota yang ditambahkan dapat membaca riwayat diskusi.</p>',
                        confirmButtonColor: '#2563eb'
                    });
                },

                switchToProject(projectId) {
                    const p = this.projects.find(i => i.id === projectId);
                    if (p) this.selectItem(p);
                },

                formatSize(bytes) {
                    if (!bytes) return '0 B';
                    if (bytes < 1024) return bytes + ' B';
                    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
                    return (bytes / 1048576).toFixed(1) + ' MB';
                },

                getUserHue(id, name) {
                    if (typeof id === 'number' && id > 0) {
                        return (id * 7 + 3);
                    }
                    const num = parseInt(id, 10);
                    if (!isNaN(num) && num > 0) {
                        return (num * 7 + 3);
                    }
                    let hash = 0;
                    const str = String(name || id || 'flovig');
                    for (let i = 0; i < str.length; i++) {
                        hash = (hash << 5) - hash + str.charCodeAt(i);
                        hash |= 0;
                    }
                    return Math.abs(hash);
                },

                getUserTextColor(id, name) {
                    const textColors = [
                        'text-emerald-600 dark:text-emerald-400',
                        'text-blue-600 dark:text-blue-400',
                        'text-violet-600 dark:text-violet-400',
                        'text-amber-600 dark:text-amber-400',
                        'text-rose-600 dark:text-rose-400',
                        'text-teal-600 dark:text-teal-400',
                        'text-indigo-600 dark:text-indigo-400',
                        'text-cyan-600 dark:text-cyan-400',
                        'text-pink-600 dark:text-pink-400',
                        'text-orange-600 dark:text-orange-400',
                        'text-lime-600 dark:text-lime-400',
                        'text-fuchsia-600 dark:text-fuchsia-400',
                    ];
                    return textColors[this.getUserHue(id, name) % textColors.length];
                },

                getUserAvatarBg(id, name) {
                    const bgGradients = [
                        'bg-linear-to-tr from-emerald-500 to-teal-600 text-white',
                        'bg-linear-to-tr from-blue-500 to-indigo-600 text-white',
                        'bg-linear-to-tr from-violet-500 to-purple-600 text-white',
                        'bg-linear-to-tr from-amber-500 to-orange-600 text-white',
                        'bg-linear-to-tr from-rose-500 to-pink-600 text-white',
                        'bg-linear-to-tr from-teal-500 to-cyan-600 text-white',
                        'bg-linear-to-tr from-indigo-500 to-blue-600 text-white',
                        'bg-linear-to-tr from-cyan-500 to-sky-600 text-white',
                        'bg-linear-to-tr from-pink-500 to-rose-600 text-white',
                        'bg-linear-to-tr from-orange-500 to-amber-600 text-white',
                        'bg-linear-to-tr from-lime-600 to-emerald-600 text-white',
                        'bg-linear-to-tr from-fuchsia-500 to-purple-600 text-white',
                    ];
                    return bgGradients[this.getUserHue(id, name) % bgGradients.length];
                },

                getUserBorderColor(id, name) {
                    const borderColors = [
                        'border-emerald-500',
                        'border-blue-500',
                        'border-violet-500',
                        'border-amber-500',
                        'border-rose-500',
                        'border-teal-500',
                        'border-indigo-500',
                        'border-cyan-500',
                        'border-pink-500',
                        'border-orange-500',
                        'border-lime-500',
                        'border-fuchsia-500',
                    ];
                    return borderColors[this.getUserHue(id, name) % borderColors.length];
                },

                renderMessageBody(text, isMine = false) {
                    if (!text) return '';
                    let escaped = text
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;');

                    // Code blocks ```code```
                    escaped = escaped.replace(/```([\s\S]*?)```/g, '<pre class="my-1.5 p-2.5 rounded-xl bg-gray-900 text-gray-100 font-mono text-xs overflow-x-auto">$1</pre>');
                    // Inline code `code`
                    if (isMine) {
                        escaped = escaped.replace(/`([^`]+)`/g, '<code class="px-1.5 py-0.5 rounded-md bg-blue-800 font-mono text-xs text-white border border-blue-500/50">$1</code>');
                        escaped = escaped.replace(/\*\*([^*]+)\*\*/g, '<strong class="font-bold text-white">$1</strong>');
                        escaped = escaped.replace(/\*([^*]+)\*/g, '<em class="italic text-blue-100">$1</em>');
                        escaped = escaped.replace(/@([\w.]+)/g, '<span class="font-bold text-white underline underline-offset-2">@$1</span>');
                        escaped = escaped.replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g, '<a href="$2" target="_blank" class="text-white underline font-semibold hover:text-blue-200">$1</a>');
                    } else {
                        escaped = escaped.replace(/`([^`]+)`/g, '<code class="px-1.5 py-0.5 rounded-md bg-gray-100 dark:bg-gray-800 font-mono text-xs text-blue-600 dark:text-blue-400">$1</code>');
                        escaped = escaped.replace(/\*\*([^*]+)\*\*/g, '<strong class="font-bold">$1</strong>');
                        escaped = escaped.replace(/\*([^*]+)\*/g, '<em class="italic">$1</em>');
                        escaped = escaped.replace(/@([\w.]+)/g, '<span class="font-bold text-blue-600 dark:text-blue-400 underline underline-offset-2">@$1</span>');
                        escaped = escaped.replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g, '<a href="$2" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline font-medium">$1</a>');
                    }

                    return escaped;
                },

                startUnreadPolling() {
                    this.pollUnread();
                    clearInterval(this.unreadPollingTimer);
                    this.unreadPollingTimer = setInterval(async () => {
                        if (!document.hidden) {
                            await this.pollUnread();
                        }
                    }, 5000);
                },

                async pollUnread() {
                    try {
                        const res = await fetch('/chat/unread');
                        if (res.ok) {
                            const data = await res.json();
                            this.projects.forEach(p => {
                                if (!this.isActive(p)) {
                                    p.unread_count = data.projects?.[p.id] ?? 0;
                                }
                            });
                            this.forums.forEach(f => {
                                if (!this.isActive(f)) {
                                    f.unread_count = data.forums?.[f.id] ?? 0;
                                }
                            });
                            this.dms.forEach(d => {
                                if (!this.isActive(d)) {
                                    d.unread_count = data.dms?.[d.id] ?? 0;
                                }
                            });
                        }
                    } catch (_) { }
                }
            }));
        });
    </script>
@endpush