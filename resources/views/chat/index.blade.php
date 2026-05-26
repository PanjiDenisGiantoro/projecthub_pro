@extends('layouts.app')

@section('title', 'Chat')
@section('page-title', 'Chat')
@section('main-class', 'flex-1 overflow-hidden p-0')

@section('content')
@php
$projectsData = $projects->map(fn($p) => [
    'id'           => $p->id,
    'name'         => $p->name,
    'initials'     => strtoupper(mb_substr($p->name, 0, 2)),
    'unread_count' => $p->unread_count,
    'last_message' => $p->messages->first() ? [
        'body'      => $p->messages->first()->body ?: '[file]',
        'user_name' => $p->messages->first()->user?->name ?? 'User',
        'time'      => $p->messages->first()->created_at->diffForHumans(),
        'timestamp' => $p->messages->first()->created_at->toIso8601String(),
    ] : null,
])->values();
@endphp

<div class="flex h-full overflow-hidden"
     x-data="chatHubApp({{ json_encode($projectsData) }})">

    {{-- ═══════════════════════════════════════
         LEFT PANEL — Project List
    ═══════════════════════════════════════ --}}
    <div class="w-72 shrink-0 flex flex-col border-r border-gray-200 bg-white"
         :class="mobileChat ? 'hidden lg:flex' : 'flex'">

        {{-- Header --}}
        <div class="px-4 pt-5 pb-3 border-b border-gray-100 shrink-0">
            <div class="flex items-center justify-between mb-3">
                <h1 class="text-base font-bold text-gray-900">Chat</h1>
                <span class="text-xs text-gray-400" x-text="projects.length + ' proyek'"></span>
            </div>
            {{-- Search --}}
            <div class="relative">
                <input type="text"
                       x-model="search"
                       placeholder="Cari proyek…"
                       class="w-full pl-8 pr-3 py-2 text-sm border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:ring-2 focus:ring-indigo-300 focus:border-transparent outline-none transition">
                <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>

        {{-- Project list --}}
        <div class="flex-1 overflow-y-auto py-1">

            {{-- Empty --}}
            <div x-show="filteredProjects.length === 0" class="px-4 py-8 text-center">
                <p class="text-sm text-gray-400">Tidak ada proyek.</p>
            </div>

            <template x-for="p in filteredProjects" :key="p.id">
                <button @click="selectProject(p)"
                        :class="activeId === p.id
                            ? 'bg-indigo-50 border-r-[3px] border-indigo-500'
                            : 'hover:bg-gray-50 border-r-[3px] border-transparent'"
                        class="w-full text-left px-4 py-3.5 flex items-center gap-3 transition-colors group">

                    {{-- Avatar --}}
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-white text-sm shrink-0"
                         :style="activeId === p.id
                             ? 'background: linear-gradient(135deg,#6366f1,#8b5cf6)'
                             : 'background: linear-gradient(135deg,#64748b,#475569)'">
                        <span x-text="p.initials"></span>
                    </div>

                    {{-- Info --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-1 mb-0.5">
                            <span class="text-sm font-semibold text-gray-800 truncate" x-text="p.name"></span>
                            <template x-if="p.unread_count > 0">
                                <span class="shrink-0 bg-indigo-600 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full min-w-[18px] text-center"
                                      x-text="p.unread_count > 99 ? '99+' : p.unread_count"></span>
                            </template>
                        </div>
                        <template x-if="p.last_message">
                            <p class="text-xs text-gray-400 truncate">
                                <span class="text-gray-500 font-medium" x-text="p.last_message.user_name + ': '"></span>
                                <span x-text="p.last_message.body"></span>
                            </p>
                        </template>
                        <template x-if="!p.last_message">
                            <p class="text-xs text-gray-300 italic">Belum ada pesan</p>
                        </template>
                    </div>
                </button>
            </template>
        </div>
    </div>

    {{-- ═══════════════════════════════════════
         RIGHT PANEL — Chat Window
    ═══════════════════════════════════════ --}}
    <div class="flex-1 flex flex-col overflow-hidden bg-gray-50"
         :class="!mobileChat && activeId ? 'hidden lg:flex' : 'flex'">

        {{-- Empty state --}}
        <div x-show="!activeId" class="flex-1 flex items-center justify-center">
            <div class="text-center px-6">
                <div class="w-16 h-16 bg-white rounded-2xl shadow-sm border border-gray-200 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>
                <p class="text-sm font-semibold text-gray-500">Pilih proyek untuk mulai chat</p>
                <p class="text-xs text-gray-400 mt-1">Pilih dari daftar proyek di sebelah kiri</p>
            </div>
        </div>

        {{-- Chat UI --}}
        <div x-show="activeId" class="flex-1 flex flex-col overflow-hidden">

            {{-- Chat header --}}
            <div class="px-5 py-3.5 bg-white border-b border-gray-200 flex items-center gap-3 shrink-0">
                {{-- Mobile back button --}}
                <button @click="mobileChat = false; activeId = null"
                        class="lg:hidden p-1.5 text-gray-400 hover:text-indigo-600 rounded-lg hover:bg-indigo-50 transition mr-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>

                {{-- Project avatar --}}
                <div class="w-9 h-9 rounded-xl flex items-center justify-center font-bold text-white text-sm shrink-0"
                     style="background: linear-gradient(135deg,#6366f1,#8b5cf6)">
                    <span x-text="activeProject?.initials ?? ''"></span>
                </div>

                <div class="flex-1 min-w-0">
                    <h2 class="text-sm font-bold text-gray-900 truncate" x-text="activeProject?.name ?? ''"></h2>
                    <p class="text-xs text-gray-400" x-text="members.length + ' anggota'"></p>
                </div>

                {{-- Message count --}}
                <span class="text-xs text-gray-400" x-text="messages.filter(m => !m.deleted).length + ' pesan'"></span>
            </div>

            {{-- Loading --}}
            <div x-show="loading" class="flex-1 flex items-center justify-center">
                <svg class="animate-spin w-6 h-6 text-indigo-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
            </div>

            {{-- Messages area --}}
            <div x-show="!loading"
                 x-ref="msgArea"
                 class="flex-1 overflow-y-auto px-5 py-4 space-y-1">

                <div x-show="messages.length === 0" class="flex flex-col items-center justify-center h-full text-center py-10">
                    <div class="w-12 h-12 bg-white rounded-2xl shadow-sm border border-gray-200 flex items-center justify-center mb-3">
                        <svg class="w-6 h-6 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                    </div>
                    <p class="text-sm text-gray-400 font-medium">Belum ada pesan</p>
                    <p class="text-xs text-gray-300 mt-0.5">Jadilah yang pertama mengirim pesan!</p>
                </div>

                <template x-for="(msg, idx) in messages" :key="msg.id">
                    <div>
                        {{-- Date separator --}}
                        <template x-if="idx === 0 || messages[idx-1].date_label !== msg.date_label">
                            <div class="flex items-center gap-3 py-2">
                                <div class="flex-1 h-px bg-gray-200"></div>
                                <span class="text-xs text-gray-400 font-medium px-2 bg-gray-50 rounded-full border border-gray-200" x-text="msg.date_label"></span>
                                <div class="flex-1 h-px bg-gray-200"></div>
                            </div>
                        </template>

                        {{-- Deleted --}}
                        <template x-if="msg.deleted">
                            <div class="flex items-center gap-2 py-0.5 px-1 text-xs text-gray-300 italic select-none">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                Pesan ini telah dihapus
                            </div>
                        </template>

                        {{-- Normal message --}}
                        <template x-if="!msg.deleted">
                            <div class="group flex gap-3 px-2 py-1.5 rounded-xl hover:bg-white hover:shadow-sm transition-all">

                                {{-- Avatar --}}
                                <div class="w-8 h-8 rounded-full shrink-0 flex items-center justify-center text-white text-xs font-bold mt-0.5 overflow-hidden"
                                     :style="msg.is_mine ? 'background:linear-gradient(135deg,#6366f1,#8b5cf6)' : 'background:linear-gradient(135deg,#10b981,#059669)'">
                                    <img x-show="msg.user.avatar" :src="msg.user.avatar" class="w-full h-full object-cover">
                                    <span x-show="!msg.user.avatar" x-text="msg.user.initials"></span>
                                </div>

                                {{-- Content --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-baseline gap-2 mb-0.5">
                                        <span class="text-sm font-semibold text-gray-800" x-text="msg.user.name"></span>
                                        <span class="text-xs text-gray-400" x-text="msg.time_label"></span>
                                        <span x-show="msg.edited_at" class="text-[10px] text-gray-300 italic">(diedit)</span>
                                    </div>

                                    <template x-if="msg.parent">
                                        <div class="flex items-start gap-1.5 mb-1.5 pl-2 border-l-2 border-indigo-200">
                                            <div>
                                                <span class="text-xs font-semibold text-indigo-500" x-text="msg.parent.user"></span>
                                                <p class="text-xs text-gray-400 truncate max-w-xs" x-text="msg.parent.body"></p>
                                            </div>
                                        </div>
                                    </template>

                                    <template x-if="editingId !== msg.id">
                                        <p class="text-sm text-gray-800 leading-relaxed whitespace-pre-wrap break-words" x-html="msg.formatted_body"></p>
                                    </template>
                                    <template x-if="editingId === msg.id">
                                        <div class="mt-1">
                                            <textarea x-model="editBody"
                                                      @keydown.enter="if(!$event.shiftKey){$event.preventDefault();editSave(msg);}"
                                                      @keydown.escape="cancelEdit()"
                                                      class="w-full border border-indigo-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 outline-none resize-none"
                                                      rows="2"></textarea>
                                            <div class="flex gap-2 mt-1">
                                                <button @click="editSave(msg)" class="text-xs px-3 py-1 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">Simpan</button>
                                                <button @click="cancelEdit()" class="text-xs px-3 py-1 text-gray-500 hover:text-gray-700 transition">Batal</button>
                                            </div>
                                        </div>
                                    </template>

                                    {{-- Attachments --}}
                                    <template x-if="msg.attachments && msg.attachments.length > 0">
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            <template x-for="att in msg.attachments" :key="att.id">
                                                <div>
                                                    <a x-show="att.is_image" :href="att.url" target="_blank">
                                                        <img :src="att.url" class="max-w-[220px] max-h-[160px] rounded-lg border border-gray-200 object-cover hover:opacity-90 transition cursor-pointer">
                                                    </a>
                                                    <a x-show="!att.is_image" :href="att.url" target="_blank"
                                                       class="inline-flex items-center gap-2 text-xs bg-gray-100 hover:bg-indigo-50 border border-gray-200 rounded-lg px-3 py-2 text-gray-600 hover:text-indigo-600 transition max-w-[240px]">
                                                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                        <span class="truncate" x-text="att.name"></span>
                                                        <span class="text-gray-400 shrink-0" x-text="att.size"></span>
                                                    </a>
                                                </div>
                                            </template>
                                        </div>
                                    </template>

                                    {{-- Reactions --}}
                                    <template x-if="msg.reactions && msg.reactions.length > 0">
                                        <div class="mt-1.5 flex flex-wrap gap-1">
                                            <template x-for="r in msg.reactions" :key="r.emoji">
                                                <button @click="react(msg, r.emoji)"
                                                        :class="r.reacted ? 'bg-indigo-100 border-indigo-300 text-indigo-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'"
                                                        class="inline-flex items-center gap-1 text-xs px-1.5 py-0.5 rounded-full border transition"
                                                        :title="r.users.join(', ')">
                                                    <span x-text="r.emoji"></span>
                                                    <span x-text="r.count"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </template>
                                </div>

                                {{-- Action buttons --}}
                                <div class="invisible group-hover:visible shrink-0 flex items-start gap-0.5 pt-0.5">
                                    {{-- Emoji picker --}}
                                    <div class="relative" x-data="{ open: false }">
                                        <button @click="open = !open"
                                                class="p-1.5 rounded-lg text-gray-300 hover:text-yellow-500 hover:bg-gray-100 transition text-base leading-none"
                                                title="Reaksi">😊</button>
                                        <div x-show="open" @click.outside="open = false" x-cloak
                                             class="absolute right-0 top-8 bg-white border border-gray-200 rounded-xl shadow-lg z-20 p-1.5 flex gap-0.5">
                                            <template x-for="emoji in ['👍','❤️','😂','😮','😢','😡','🎉']" :key="emoji">
                                                <button @click="react(msg, emoji); open = false"
                                                        class="text-lg p-1 rounded-lg hover:bg-gray-100 transition"
                                                        x-text="emoji"></button>
                                            </template>
                                        </div>
                                    </div>
                                    <button @click="setReply(msg)" class="p-1.5 rounded-lg text-gray-300 hover:text-indigo-500 hover:bg-gray-100 transition" title="Balas">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                                    </button>
                                    <button x-show="msg.is_mine && editingId !== msg.id" @click="startEdit(msg)" class="p-1.5 rounded-lg text-gray-300 hover:text-indigo-500 hover:bg-gray-100 transition" title="Edit">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    </button>
                                    <button x-show="msg.is_mine" @click="deleteMessage(msg)" class="p-1.5 rounded-lg text-gray-300 hover:text-red-500 hover:bg-red-50 transition" title="Hapus">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            {{-- Reply indicator --}}
            <div x-show="replyTo" x-cloak class="px-5 py-2 bg-indigo-50 border-t border-indigo-100 flex items-center gap-3 shrink-0">
                <svg class="w-3.5 h-3.5 text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                </svg>
                <div class="flex-1 min-w-0">
                    <span class="text-xs text-indigo-600 font-semibold" x-text="'Membalas ' + (replyTo?.user?.name ?? '')"></span>
                    <p class="text-xs text-gray-500 truncate" x-text="replyTo?.body || '[file]'"></p>
                </div>
                <button @click="replyTo = null" class="text-gray-400 hover:text-red-500 transition text-lg leading-none ml-2">&times;</button>
            </div>

            {{-- File preview --}}
            <div x-show="files.length > 0" x-cloak class="px-5 py-2 border-t border-gray-100 flex gap-2 flex-wrap shrink-0 bg-gray-50">
                <template x-for="(f, i) in files" :key="i">
                    <div class="flex items-center gap-1.5 bg-white border border-gray-200 rounded-lg px-2 py-1.5 text-xs shadow-sm">
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        <span class="max-w-[120px] truncate text-gray-600" x-text="f.name"></span>
                        <button @click="removeFile(i)" class="text-red-400 hover:text-red-600 font-bold ml-0.5">&times;</button>
                    </div>
                </template>
            </div>

            {{-- Input area --}}
            <div class="px-5 py-3 border-t border-gray-200 shrink-0 bg-white">
                {{-- @mention dropdown --}}
                <div class="relative">
                    <div x-show="showMentions" x-cloak
                         class="absolute bottom-full left-0 mb-1 bg-white border border-gray-200 rounded-xl shadow-lg z-20 w-56 overflow-hidden">
                        <template x-for="(m, i) in mentionResults" :key="m.id">
                            <button @click="selectMention(m)"
                                    :class="i === mentionIndex ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50'"
                                    class="w-full text-left px-3 py-2 text-sm flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 text-[10px] font-bold shrink-0"
                                     x-text="m.name.substring(0,2).toUpperCase()"></div>
                                <span x-text="m.name"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <div class="flex gap-2 items-end">
                    <label class="cursor-pointer shrink-0 p-2 text-gray-400 hover:text-indigo-500 hover:bg-indigo-50 rounded-lg transition mb-0.5" title="Lampiran">
                        <input type="file" multiple class="hidden" @change="addFiles($event)">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                    </label>

                    <div class="flex-1 relative">
                        <textarea x-model="newBody"
                                  x-ref="inputArea"
                                  @input="handleInput($event)"
                                  @keydown="handleKeydown($event)"
                                  @keydown.escape="showMentions = false; replyTo = null"
                                  rows="1"
                                  placeholder="Tulis pesan… (Enter kirim, Shift+Enter baris baru)"
                                  class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-300 focus:border-transparent outline-none resize-none leading-relaxed transition"
                                  style="max-height:120px; overflow-y:auto"></textarea>
                    </div>

                    <button @click="send()"
                            :disabled="sending || (!newBody.trim() && files.length === 0)"
                            class="shrink-0 bg-indigo-600 text-white px-4 py-2.5 rounded-xl text-sm font-medium hover:bg-indigo-700 disabled:opacity-40 disabled:cursor-not-allowed transition flex items-center gap-1.5 mb-0.5">
                        <svg x-show="!sending" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        <svg x-show="sending" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        <span class="hidden sm:inline">Kirim</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('chatHubApp', (projectsData) => ({
        projects: projectsData,
        search: '',
        activeId: null,
        activeProject: null,
        members: [],
        messages: [],
        newBody: '',
        replyTo: null,
        editingId: null,
        editBody: '',
        files: [],
        mentionResults: [],
        showMentions: false,
        mentionIndex: 0,
        mentionStart: 0,
        lastId: 0,
        loading: false,
        sending: false,
        mobileChat: false,
        pollingTimer: null,
        csrf: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',

        get filteredProjects() {
            const q = this.search.trim().toLowerCase();
            return q ? this.projects.filter(p => p.name.toLowerCase().includes(q)) : this.projects;
        },

        async selectProject(p) {
            if (this.activeId === p.id) return;

            clearInterval(this.pollingTimer);
            this.messages  = [];
            this.lastId    = 0;
            this.replyTo   = null;
            this.editingId = null;
            this.newBody   = '';
            this.files     = [];
            this.activeId  = p.id;
            this.activeProject = p;
            this.mobileChat = true;

            // Clear unread
            const pi = this.projects.findIndex(pr => pr.id === p.id);
            if (pi !== -1) this.projects[pi] = { ...this.projects[pi], unread_count: 0 };

            this.loading = true;
            try {
                const [mRes] = await Promise.all([
                    fetch(`/projects/${p.id}/chat/members`),
                ]);
                this.members = await mRes.json();
            } catch (_) { this.members = []; }

            await this.loadMessages();
            this.markRead();
            this.startPolling();
        },

        async loadMessages() {
            this.loading = true;
            try {
                const res  = await fetch(`/projects/${this.activeId}/chat/messages`);
                const data = await res.json();
                this.messages = data.messages.map(m => this.addLabels(m));
                this.lastId   = this.messages.at(-1)?.id ?? 0;
                this.$nextTick(() => this.scrollBottom());
            } finally {
                this.loading = false;
            }
        },

        startPolling() {
            clearInterval(this.pollingTimer);
            this.pollingTimer = setInterval(async () => {
                if (!document.hidden && this.activeId && this.lastId > 0) {
                    await this.pollNew();
                }
            }, 5000);
        },

        async pollNew() {
            try {
                const res  = await fetch(`/projects/${this.activeId}/chat/messages?after=${this.lastId}`);
                const data = await res.json();
                if (data.messages.length > 0) {
                    const atBottom = this.isNearBottom();
                    data.messages.forEach(m => this.messages.push(this.addLabels(m)));
                    this.lastId = data.messages.at(-1).id;

                    // Update sidebar last message
                    const pi = this.projects.findIndex(p => p.id === this.activeId);
                    if (pi !== -1) {
                        const last = data.messages.at(-1);
                        this.projects[pi] = { ...this.projects[pi], last_message: {
                            body: last.body || '[file]',
                            user_name: last.user.name,
                            time: 'Baru saja',
                        }};
                    }

                    if (atBottom) this.$nextTick(() => this.scrollBottom());
                    this.markRead();
                }
            } catch (_) {}
        },

        addLabels(msg) {
            const d = new Date(msg.created_at.replace(',',''));
            msg.date_label = isNaN(d) ? msg.created_at : d.toLocaleDateString('id-ID', { day:'numeric', month:'long', year:'numeric' });
            msg.time_label = msg.created_at.includes(',') ? msg.created_at.split(',')[1]?.trim() : msg.created_at;
            return msg;
        },

        isNearBottom() {
            const el = this.$refs.msgArea;
            return el ? (el.scrollHeight - el.scrollTop - el.clientHeight < 80) : true;
        },

        scrollBottom() {
            const el = this.$refs.msgArea;
            if (el) el.scrollTop = el.scrollHeight;
        },

        async send() {
            if (this.sending || !this.activeId) return;
            const body = this.newBody.trim();
            if (!body && this.files.length === 0) return;

            this.sending = true;
            const fd = new FormData();
            if (body) fd.append('body', body);
            if (this.replyTo) fd.append('parent_id', this.replyTo.id);
            this.files.forEach(f => fd.append('files[]', f));
            fd.append('_token', this.csrf);

            try {
                const res = await fetch(`/projects/${this.activeId}/chat`, { method: 'POST', body: fd });
                if (res.ok) {
                    const data = await res.json();
                    this.messages.push(this.addLabels(data.message));
                    this.lastId  = data.message.id;
                    this.newBody = '';
                    this.replyTo = null;
                    this.files   = [];
                    this.$nextTick(() => this.scrollBottom());

                    // Update sidebar
                    const pi = this.projects.findIndex(p => p.id === this.activeId);
                    if (pi !== -1) {
                        this.projects[pi] = { ...this.projects[pi], last_message: {
                            body: data.message.body || '[file]',
                            user_name: data.message.user.name,
                            time: 'Baru saja',
                        }};
                    }
                }
            } finally {
                this.sending = false;
            }
        },

        async editSave(msg) {
            if (!this.editBody.trim()) return;
            const res = await fetch(`/projects/${this.activeId}/chat/${msg.id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                body: JSON.stringify({ body: this.editBody }),
            });
            if (res.ok) {
                const data = await res.json();
                const idx  = this.messages.findIndex(m => m.id === msg.id);
                if (idx !== -1) this.messages[idx] = this.addLabels(data.message);
            }
            this.editingId = null;
            this.editBody  = '';
        },

        async deleteMessage(msg) {
            if (!confirm('Hapus pesan ini?')) return;
            const res = await fetch(`/projects/${this.activeId}/chat/${msg.id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': this.csrf },
            });
            if (res.ok) {
                const idx = this.messages.findIndex(m => m.id === msg.id);
                if (idx !== -1) this.messages[idx] = { ...this.messages[idx], deleted: true, body: '', formatted_body: '' };
            }
        },

        async react(msg, emoji) {
            const res = await fetch(`/projects/${this.activeId}/chat/${msg.id}/react`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                body: JSON.stringify({ emoji }),
            });
            if (res.ok) {
                const data = await res.json();
                const idx  = this.messages.findIndex(m => m.id === msg.id);
                if (idx !== -1) this.messages[idx] = { ...this.messages[idx], reactions: data.reactions };
            }
        },

        async markRead() {
            if (!this.activeId) return;
            try {
                await fetch(`/projects/${this.activeId}/chat/read`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrf },
                });
            } catch (_) {}
        },

        setReply(msg) {
            this.replyTo = msg;
            this.$nextTick(() => this.$refs.inputArea?.focus());
        },

        startEdit(msg) {
            this.editingId = msg.id;
            this.editBody  = msg.body;
        },

        cancelEdit() {
            this.editingId = null;
            this.editBody  = '';
        },

        handleKeydown(e) {
            if (this.showMentions) {
                if (e.key === 'ArrowDown')  { e.preventDefault(); this.mentionIndex = Math.min(this.mentionIndex + 1, this.mentionResults.length - 1); return; }
                if (e.key === 'ArrowUp')    { e.preventDefault(); this.mentionIndex = Math.max(this.mentionIndex - 1, 0); return; }
                if (e.key === 'Enter' || e.key === 'Tab') { e.preventDefault(); if (this.mentionResults[this.mentionIndex]) this.selectMention(this.mentionResults[this.mentionIndex]); return; }
            }
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); this.send(); }
        },

        handleInput(e) {
            e.target.style.height = 'auto';
            e.target.style.height = Math.min(e.target.scrollHeight, 120) + 'px';
            const val    = this.newBody;
            const cursor = e.target.selectionStart;
            const before = val.substring(0, cursor);
            const match  = before.match(/@(\w*)$/);
            if (match) {
                const query = match[1].toLowerCase();
                this.mentionResults = this.members.filter(m => m.name.toLowerCase().includes(query)).slice(0, 6);
                this.showMentions   = this.mentionResults.length > 0;
                this.mentionIndex   = 0;
                this.mentionStart   = cursor - match[0].length;
            } else {
                this.showMentions = false;
            }
        },

        selectMention(member) {
            const before = this.newBody.substring(0, this.mentionStart);
            const after  = this.newBody.substring(this.$refs.inputArea?.selectionStart ?? this.newBody.length);
            this.newBody = before + '@' + member.name + ' ' + after;
            this.showMentions = false;
            this.$nextTick(() => {
                const el  = this.$refs.inputArea;
                const pos = (before + '@' + member.name + ' ').length;
                if (el) { el.focus(); el.setSelectionRange(pos, pos); }
            });
        },

        addFiles(e) {
            this.files = [...this.files, ...Array.from(e.target.files)];
            e.target.value = '';
        },

        removeFile(idx) {
            this.files.splice(idx, 1);
        },
    }));
});
</script>
@endpush
