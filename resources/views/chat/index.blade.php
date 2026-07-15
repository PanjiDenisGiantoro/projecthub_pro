@extends('layouts.app')

@section('title', 'Chat')
@section('page-title', 'Chat')
@section('main-class', 'flex-1 overflow-hidden p-0')

@section('content')
<div class="flex h-full overflow-hidden"
     x-data="chatHubApp(
        {{ json_encode($projects) }},
        {{ json_encode($dms) }},
        {{ json_encode($forums) }},
        {{ json_encode($allPeers) }}
     )">

    {{-- ═══════════════════════════════════════
         LEFT PANEL — Tabs + List
    ═══════════════════════════════════════ --}}
    <div class="w-72 shrink-0 flex flex-col border-r border-gray-200 bg-white"
         :class="mobileChat ? 'hidden lg:flex' : 'flex'">

        {{-- Header --}}
        <div class="px-4 pt-5 pb-3 border-b border-gray-100 shrink-0">
            <div class="flex items-center justify-between mb-3">
                <h1 class="text-base font-bold text-gray-900">Chat</h1>
                <button x-show="tab === 'forum'" @click="openCreateForum()"
                        class="text-xs font-semibold text-indigo-600 hover:text-indigo-700 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Forum
                </button>
            </div>

            {{-- Tabs --}}
            <div class="flex gap-1 mb-3 bg-gray-100 rounded-xl p-1">
                <button @click="tab = 'project'"
                        :class="tab === 'project' ? 'bg-white shadow-sm text-indigo-600' : 'text-gray-500 hover:text-gray-700'"
                        class="flex-1 text-xs font-semibold py-1.5 rounded-lg transition relative">
                    Proyek
                    <span x-show="unreadByTab.project > 0" class="ml-1 text-[10px] bg-indigo-600 text-white rounded-full px-1.5" x-text="unreadByTab.project"></span>
                </button>
                <button @click="tab = 'dm'"
                        :class="tab === 'dm' ? 'bg-white shadow-sm text-indigo-600' : 'text-gray-500 hover:text-gray-700'"
                        class="flex-1 text-xs font-semibold py-1.5 rounded-lg transition relative">
                    Pesan
                    <span x-show="unreadByTab.dm > 0" class="ml-1 text-[10px] bg-indigo-600 text-white rounded-full px-1.5" x-text="unreadByTab.dm"></span>
                </button>
                <button @click="tab = 'forum'"
                        :class="tab === 'forum' ? 'bg-white shadow-sm text-indigo-600' : 'text-gray-500 hover:text-gray-700'"
                        class="flex-1 text-xs font-semibold py-1.5 rounded-lg transition relative">
                    Forum
                    <span x-show="unreadByTab.forum > 0" class="ml-1 text-[10px] bg-indigo-600 text-white rounded-full px-1.5" x-text="unreadByTab.forum"></span>
                </button>
            </div>

            {{-- Search --}}
            <div class="relative">
                <input type="text"
                       x-model="search"
                       :placeholder="tab === 'project' ? 'Cari proyek…' : (tab === 'dm' ? 'Cari orang…' : 'Cari forum…')"
                       class="w-full pl-8 pr-3 py-2 text-sm border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:ring-2 focus:ring-indigo-300 focus:border-transparent outline-none transition">
                <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>

        {{-- List --}}
        <div class="flex-1 overflow-y-auto py-1">

            <div x-show="filteredList.length === 0" class="px-4 py-8 text-center">
                <p class="text-sm text-gray-400" x-show="tab !== 'forum'">Tidak ada yang ditemukan.</p>
                <template x-if="tab === 'forum'">
                    <div>
                        <p class="text-sm text-gray-400 mb-2">Belum ada forum.</p>
                        <button @click="openCreateForum()" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">+ Buat forum baru</button>
                    </div>
                </template>
            </div>

            <template x-for="item in filteredList" :key="item.type + '-' + item.id">
                <button @click="selectItem(item)"
                        :class="isActive(item)
                            ? 'bg-indigo-50 border-r-[3px] border-indigo-500'
                            : 'hover:bg-gray-50 border-r-[3px] border-transparent'"
                        class="w-full text-left px-4 py-3.5 flex items-center gap-3 transition-colors group">

                    <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-white text-sm shrink-0 overflow-hidden"
                         :style="isActive(item)
                             ? 'background: linear-gradient(135deg,#6366f1,#8b5cf6)'
                             : (item.type === 'forum' ? 'background: linear-gradient(135deg,#f59e0b,#d97706)' : 'background: linear-gradient(135deg,#64748b,#475569)')">
                        <img x-show="item.avatar" :src="item.avatar" class="w-full h-full object-cover">
                        <span x-show="!item.avatar" x-text="item.initials"></span>
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-1 mb-0.5">
                            <span class="text-sm font-semibold text-gray-800 truncate" x-text="item.name"></span>
                            <template x-if="item.unread_count > 0">
                                <span class="shrink-0 bg-indigo-600 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full min-w-[18px] text-center"
                                      x-text="item.unread_count > 99 ? '99+' : item.unread_count"></span>
                            </template>
                        </div>
                        <template x-if="item.last_message">
                            <p class="text-xs text-gray-400 truncate">
                                <span x-show="item.last_message.is_mine" class="text-gray-400">Anda: </span>
                                <span x-show="!item.last_message.is_mine && item.type !== 'dm'" class="text-gray-500 font-medium" x-text="(item.last_message.user_name || '') + ': '"></span>
                                <span x-text="item.last_message.body"></span>
                            </p>
                        </template>
                        <template x-if="!item.last_message">
                            <p class="text-xs text-gray-300 italic" x-text="item.type === 'forum' ? (item.member_count + ' anggota') : 'Belum ada pesan'"></p>
                        </template>
                    </div>
                </button>
            </template>
        </div>
    </div>

    {{-- ═══════════════════════════════════════
         RIGHT PANEL — Thread Window
    ═══════════════════════════════════════ --}}
    <div class="flex-1 flex flex-col overflow-hidden bg-gray-50"
         :class="!mobileChat && activeItem ? 'hidden lg:flex' : 'flex'">

        {{-- Empty state --}}
        <div x-show="!activeItem" class="flex-1 flex items-center justify-center">
            <div class="text-center px-6">
                <div class="w-16 h-16 bg-white rounded-2xl shadow-sm border border-gray-200 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>
                <p class="text-sm font-semibold text-gray-500">Pilih proyek, orang, atau forum untuk mulai chat</p>
                <p class="text-xs text-gray-400 mt-1">Pilih dari daftar di sebelah kiri</p>
            </div>
        </div>

        {{-- Thread UI --}}
        <div x-show="activeItem" class="flex-1 flex flex-col overflow-hidden">

            {{-- Thread header --}}
            <div class="px-5 py-3.5 bg-white border-b border-gray-200 flex items-center gap-3 shrink-0">
                <button @click="mobileChat = false; activeItem = null"
                        class="lg:hidden p-1.5 text-gray-400 hover:text-indigo-600 rounded-lg hover:bg-indigo-50 transition mr-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>

                <div class="w-9 h-9 rounded-xl flex items-center justify-center font-bold text-white text-sm shrink-0 overflow-hidden"
                     :style="activeItem?.type === 'forum' ? 'background: linear-gradient(135deg,#f59e0b,#d97706)' : 'background: linear-gradient(135deg,#6366f1,#8b5cf6)'">
                    <img x-show="activeItem?.avatar" :src="activeItem?.avatar" class="w-full h-full object-cover">
                    <span x-show="!activeItem?.avatar" x-text="activeItem?.initials ?? ''"></span>
                </div>

                <div class="flex-1 min-w-0">
                    <h2 class="text-sm font-bold text-gray-900 truncate" x-text="activeItem?.name ?? ''"></h2>
                    <p x-show="activeItem?.type === 'forum'" class="text-xs text-gray-400" x-text="members.length + ' anggota'"></p>
                </div>

                <button x-show="activeItem?.type === 'forum'" @click="openMembers()"
                        class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition" title="Anggota">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </button>

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
                        <template x-if="idx === 0 || messages[idx-1].date_label !== msg.date_label">
                            <div class="flex items-center gap-3 py-2">
                                <div class="flex-1 h-px bg-gray-200"></div>
                                <span class="text-xs text-gray-400 font-medium px-2 bg-gray-50 rounded-full border border-gray-200" x-text="msg.date_label"></span>
                                <div class="flex-1 h-px bg-gray-200"></div>
                            </div>
                        </template>

                        <template x-if="msg.deleted">
                            <div class="flex items-center gap-2 py-0.5 px-1 text-xs text-gray-300 italic select-none">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                Pesan ini telah dihapus
                            </div>
                        </template>

                        <template x-if="!msg.deleted">
                            <div class="group flex gap-3 px-2 py-1.5 rounded-xl hover:bg-white hover:shadow-sm transition-all">

                                <div class="w-8 h-8 rounded-full shrink-0 flex items-center justify-center text-white text-xs font-bold mt-0.5 overflow-hidden"
                                     :style="msg.is_mine ? 'background:linear-gradient(135deg,#6366f1,#8b5cf6)' : 'background:linear-gradient(135deg,#10b981,#059669)'">
                                    <img x-show="msg.user.avatar" :src="msg.user.avatar" class="w-full h-full object-cover">
                                    <span x-show="!msg.user.avatar" x-text="msg.user.initials"></span>
                                </div>

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
                                        <p class="text-sm text-gray-800 leading-relaxed whitespace-pre-wrap break-words" x-text="msg.body"></p>
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
                                </div>

                                <div class="invisible group-hover:visible shrink-0 flex items-start gap-0.5 pt-0.5">
                                    <button @click="setReply(msg)" class="p-1.5 rounded-lg text-gray-300 hover:text-indigo-500 hover:bg-gray-100 transition" title="Balas">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                                    </button>
                                    <button x-show="msg.is_mine && editingId !== msg.id" @click="startEdit(msg)" class="p-1.5 rounded-lg text-gray-300 hover:text-indigo-500 hover:bg-gray-100 transition" title="Edit">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    </button>
                                    <button x-show="msg.is_mine || (activeItem?.type !== 'dm')" @click="deleteMessage(msg)" class="p-1.5 rounded-lg text-gray-300 hover:text-red-500 hover:bg-red-50 transition" title="Hapus">
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
                    <p class="text-xs text-gray-500 truncate" x-text="replyTo?.body || '[pesan dihapus]'"></p>
                </div>
                <button @click="replyTo = null" class="text-gray-400 hover:text-red-500 transition text-lg leading-none ml-2">&times;</button>
            </div>

            {{-- Input area --}}
            <div class="px-5 py-3 border-t border-gray-200 shrink-0 bg-white">
                <div class="flex gap-2 items-end">
                    <div class="flex-1 relative">
                        <textarea x-model="newBody"
                                  x-ref="inputArea"
                                  @input="autoGrow($event)"
                                  @keydown.enter="if(!$event.shiftKey){$event.preventDefault();send();}"
                                  @keydown.escape="replyTo = null"
                                  rows="1"
                                  placeholder="Tulis pesan… (Enter kirim, Shift+Enter baris baru)"
                                  class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-300 focus:border-transparent outline-none resize-none leading-relaxed transition"
                                  style="max-height:120px; overflow-y:auto"></textarea>
                    </div>

                    <button @click="send()"
                            :disabled="sending || !newBody.trim()"
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

    {{-- ═══════════════════════════════════════
         Modal — Buat Forum
    ═══════════════════════════════════════ --}}
    <div x-show="createForumOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background:rgba(0,0,0,0.5)">
        <div class="relative w-full max-w-md rounded-2xl overflow-hidden shadow-2xl bg-white" @click.stop>
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <p class="font-bold text-gray-900">Buat Forum Baru</p>
                <button @click="createForumOpen = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1.5">Nama Forum</label>
                    <input type="text" x-model="newForumName" placeholder="cth: Diskusi Umum"
                           class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:ring-2 focus:ring-indigo-300 outline-none transition">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1.5">Deskripsi (opsional)</label>
                    <textarea x-model="newForumDesc" rows="2" placeholder="Tentang apa forum ini…"
                              class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:ring-2 focus:ring-indigo-300 outline-none transition resize-none"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1.5">Undang Anggota</label>
                    <div class="border border-gray-200 rounded-xl max-h-48 overflow-y-auto divide-y divide-gray-100">
                        <template x-for="p in allPeers" :key="p.id">
                            <label class="flex items-center gap-3 px-3 py-2.5 cursor-pointer hover:bg-gray-50">
                                <input type="checkbox" :value="p.id" x-model="newForumMembers" class="rounded accent-indigo-600">
                                <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-[10px] font-bold shrink-0 overflow-hidden" style="background:linear-gradient(135deg,#64748b,#475569)">
                                    <img x-show="p.avatar" :src="p.avatar" class="w-full h-full object-cover">
                                    <span x-show="!p.avatar" x-text="p.name.substring(0,2).toUpperCase()"></span>
                                </div>
                                <span class="text-sm text-gray-700" x-text="p.name"></span>
                            </label>
                        </template>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex gap-2 justify-end">
                <button @click="createForumOpen = false" class="px-4 py-2 text-sm text-gray-500 hover:text-gray-700 transition">Batal</button>
                <button @click="createForum()" :disabled="!newForumName.trim() || creatingForum"
                        class="px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 disabled:opacity-40 transition">
                    <span x-show="!creatingForum">Buat Forum</span>
                    <span x-show="creatingForum">Membuat…</span>
                </button>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════
         Modal — Anggota Forum
    ═══════════════════════════════════════ --}}
    <div x-show="membersOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background:rgba(0,0,0,0.5)">
        <div class="relative w-full max-w-sm rounded-2xl overflow-hidden shadow-2xl bg-white" @click.stop>
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <p class="font-bold text-gray-900">Anggota Forum</p>
                <button @click="membersOpen = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-4 max-h-96 overflow-y-auto divide-y divide-gray-100">
                <template x-for="m in members" :key="m.id">
                    <div class="flex items-center gap-3 px-2 py-2.5">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0 overflow-hidden" style="background:linear-gradient(135deg,#64748b,#475569)">
                            <img x-show="m.avatar" :src="m.avatar" class="w-full h-full object-cover">
                            <span x-show="!m.avatar" x-text="m.name.substring(0,2).toUpperCase()"></span>
                        </div>
                        <span class="flex-1 text-sm text-gray-700" x-text="m.name"></span>
                        <button @click="removeForumMember(m)" class="text-xs text-red-500 hover:text-red-700 transition">Keluarkan</button>
                    </div>
                </template>
            </div>
            <div class="p-4 border-t border-gray-100">
                <label class="block text-xs font-semibold text-gray-500 mb-1.5">Tambah Anggota</label>
                <select x-model="addMemberId" @change="addForumMember()"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-xl bg-gray-50 outline-none">
                    <option value="">Pilih orang…</option>
                    <template x-for="p in allPeers.filter(p => !members.some(m => m.id === p.id))" :key="p.id">
                        <option :value="p.id" x-text="p.name"></option>
                    </template>
                </select>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('chatHubApp', (projectsData, dmsData, forumsData, allPeers) => ({
        tab: 'project',
        search: '',
        projects: projectsData,
        dms: dmsData,
        forums: forumsData,
        allPeers: allPeers,

        activeItem: null,
        members: [],
        messages: [],
        newBody: '',
        replyTo: null,
        editingId: null,
        editBody: '',
        lastId: 0,
        loading: false,
        sending: false,
        mobileChat: false,
        pollingTimer: null,
        csrf: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',

        createForumOpen: false,
        newForumName: '',
        newForumDesc: '',
        newForumMembers: [],
        creatingForum: false,

        membersOpen: false,
        addMemberId: '',

        get list() {
            return this.tab === 'project' ? this.projects : (this.tab === 'dm' ? this.dms : this.forums);
        },

        get filteredList() {
            const q = this.search.trim().toLowerCase();
            const l = this.list;
            return q ? l.filter(i => i.name.toLowerCase().includes(q)) : l;
        },

        get unreadByTab() {
            const sum = arr => arr.reduce((s, i) => s + (i.unread_count || 0), 0);
            return { project: sum(this.projects), dm: sum(this.dms), forum: sum(this.forums) };
        },

        isActive(item) {
            return this.activeItem && this.activeItem.type === item.type && this.activeItem.id === item.id;
        },

        endpoints(item) {
            if (item.type === 'project') return {
                thread: `/projects/${item.id}/chat/messages`,
                send:   `/projects/${item.id}/chat`,
                update: (mid) => `/projects/${item.id}/chat/${mid}`,
                destroy:(mid) => `/projects/${item.id}/chat/${mid}`,
                read:   `/projects/${item.id}/chat/read`,
            };
            if (item.type === 'dm') return {
                thread: `/messages/${item.id}/thread`,
                send:   `/messages/${item.id}`,
                update: (mid) => `/messages/${item.id}/${mid}`,
                destroy:(mid) => `/messages/${item.id}/${mid}`,
                read:   `/messages/${item.id}/read`,
            };
            return {
                thread: `/forums/${item.id}/messages`,
                send:   `/forums/${item.id}/messages`,
                update: (mid) => `/forums/${item.id}/messages/${mid}`,
                destroy:(mid) => `/forums/${item.id}/messages/${mid}`,
                read:   `/forums/${item.id}/read`,
                members:`/forums/${item.id}/members`,
            };
        },

        async selectItem(item) {
            if (this.isActive(item)) return;

            clearInterval(this.pollingTimer);
            this.messages   = [];
            this.members    = [];
            this.lastId     = 0;
            this.replyTo    = null;
            this.editingId  = null;
            this.newBody    = '';
            this.activeItem = item;
            this.mobileChat = true;

            const list = this.tab === 'project' ? this.projects : (this.tab === 'dm' ? this.dms : this.forums);
            const idx  = list.findIndex(i => i.id === item.id);
            if (idx !== -1) list[idx] = { ...list[idx], unread_count: 0 };

            if (item.type === 'forum') await this.loadMembers();

            await this.loadMessages();
            this.markRead();
            this.startPolling();
        },

        async loadMembers() {
            const eps = this.endpoints(this.activeItem);
            if (!eps.members) { this.members = []; return; }
            try {
                const res = await fetch(eps.members);
                this.members = await res.json();
            } catch (_) { this.members = []; }
        },

        async loadMessages() {
            this.loading = true;
            try {
                const eps  = this.endpoints(this.activeItem);
                const res  = await fetch(eps.thread);
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
                if (!document.hidden && this.activeItem) await this.pollNew();
            }, 5000);
        },

        async pollNew() {
            try {
                const eps  = this.endpoints(this.activeItem);
                const res  = await fetch(`${eps.thread}?after=${this.lastId}`);
                const data = await res.json();
                if (data.messages.length > 0) {
                    const atBottom = this.isNearBottom();
                    data.messages.forEach(m => this.messages.push(this.addLabels(m)));
                    this.lastId = data.messages.at(-1).id;

                    const list = this.tab === 'project' ? this.projects : (this.tab === 'dm' ? this.dms : this.forums);
                    const idx  = list.findIndex(i => i.id === this.activeItem.id);
                    if (idx !== -1) {
                        const last = data.messages.at(-1);
                        list[idx] = { ...list[idx], last_message: {
                            body: last.body || '[pesan dihapus]',
                            is_mine: last.is_mine,
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

        autoGrow(e) {
            e.target.style.height = 'auto';
            e.target.style.height = Math.min(e.target.scrollHeight, 120) + 'px';
        },

        async send() {
            if (this.sending || !this.activeItem) return;
            const body = this.newBody.trim();
            if (!body) return;

            this.sending = true;
            try {
                const eps = this.endpoints(this.activeItem);
                const res = await fetch(eps.send, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                    body: JSON.stringify({ body, parent_id: this.replyTo?.id ?? null }),
                });
                if (res.ok) {
                    const data = await res.json();
                    this.messages.push(this.addLabels(data.message));
                    this.lastId  = data.message.id;
                    this.newBody = '';
                    this.replyTo = null;
                    this.$nextTick(() => this.scrollBottom());

                    const list = this.tab === 'project' ? this.projects : (this.tab === 'dm' ? this.dms : this.forums);
                    const idx  = list.findIndex(i => i.id === this.activeItem.id);
                    if (idx !== -1) {
                        list[idx] = { ...list[idx], last_message: {
                            body: data.message.body,
                            is_mine: true,
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
            const eps = this.endpoints(this.activeItem);
            const res = await fetch(eps.update(msg.id), {
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
            const eps = this.endpoints(this.activeItem);
            const res = await fetch(eps.destroy(msg.id), {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': this.csrf },
            });
            if (res.ok) {
                const idx = this.messages.findIndex(m => m.id === msg.id);
                if (idx !== -1) this.messages[idx] = { ...this.messages[idx], deleted: true, body: '' };
            }
        },

        async markRead() {
            if (!this.activeItem) return;
            try {
                const eps = this.endpoints(this.activeItem);
                await fetch(eps.read, { method: 'POST', headers: { 'X-CSRF-TOKEN': this.csrf } });
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

        // ── Forum: create ────────────────────────────────────────────────
        openCreateForum() {
            this.newForumName    = '';
            this.newForumDesc    = '';
            this.newForumMembers = [];
            this.createForumOpen = true;
        },

        async createForum() {
            if (!this.newForumName.trim() || this.creatingForum) return;
            this.creatingForum = true;
            try {
                const res = await fetch('{{ route('forums.store') }}', {
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
                    const forum = {
                        type: 'forum',
                        id: data.forum.id,
                        name: data.forum.name,
                        avatar: null,
                        initials: data.forum.name.substring(0,2).toUpperCase(),
                        unread_count: 0,
                        member_count: data.forum.member_count,
                        last_message: null,
                    };
                    this.forums.unshift(forum);
                    this.createForumOpen = false;
                    this.tab = 'forum';
                    this.selectItem(forum);
                }
            } finally {
                this.creatingForum = false;
            }
        },

        // ── Forum: members ───────────────────────────────────────────────
        openMembers() {
            this.addMemberId = '';
            this.membersOpen = true;
        },

        async addForumMember() {
            if (!this.addMemberId || !this.activeItem) return;
            try {
                const res = await fetch(`/forums/${this.activeItem.id}/members`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                    body: JSON.stringify({ user_id: this.addMemberId }),
                });
                if (res.ok) await this.loadMembers();
            } finally {
                this.addMemberId = '';
            }
        },

        async removeForumMember(member) {
            if (!this.activeItem) return;
            if (!confirm(`Keluarkan ${member.name} dari forum?`)) return;
            const res = await fetch(`/forums/${this.activeItem.id}/members/${member.id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': this.csrf },
            });
            if (res.ok) this.members = this.members.filter(m => m.id !== member.id);
        },
    }));
});
</script>
@endpush
