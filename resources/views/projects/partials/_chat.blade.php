<div x-data="chatApp('{{ $project->slug }}', {{ json_encode($chatMembers->toArray()) }})"
     x-init="init()"
     class="w-full">

    {{-- Loading State --}}
    <div x-show="loading" class="flex flex-col items-center justify-center py-24 bg-white dark:bg-gray-850 rounded-2xl">
        <svg class="animate-spin w-8 h-8 text-blue-600 mb-3" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
        <p class="text-xs text-gray-400 font-medium">Memuat percakapan proyek...</p>
    </div>

    {{-- Chat Window --}}
    <div x-show="!loading" x-cloak
         class="flex flex-col bg-white dark:bg-gray-850 rounded-2xl overflow-hidden shadow-2xs"
         style="height: 74vh; min-height: 520px;">

        {{-- ── 1. Header (Ala Odama Studio Style Image 3) ──────────────────── --}}
        <div class="px-6 py-4 flex items-center justify-between border-b border-gray-100 dark:border-gray-800 bg-white/70 dark:bg-gray-850/70 backdrop-blur-sm shrink-0">
            <div class="flex items-center gap-3.5">
                <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center font-bold text-sm shadow-xs shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-gray-900 dark:text-white flex items-center gap-2">
                        <span>Diskusi Proyek</span>
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" title="Live channel"></span>
                    </h3>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5"
                       x-text="messages.filter(m => !m.deleted).length + ' pesan terkirim'"></p>
                </div>
            </div>

            {{-- Right: Team Avatars Stack --}}
            <div class="flex items-center gap-2">
                <div class="hidden sm:flex items-center -space-x-2 overflow-hidden">
                    <template x-for="(m, i) in members.slice(0, 4)" :key="m.id">
                        <div class="w-7 h-7 rounded-full ring-2 ring-white dark:ring-gray-800 bg-gradient-to-tr from-blue-500 to-indigo-600 text-white flex items-center justify-center font-bold text-[10px]"
                             :title="m.name">
                            <span x-text="m.name.substring(0,2).toUpperCase()"></span>
                        </div>
                    </template>
                </div>
                <template x-if="members.length > 4">
                    <span class="text-[11px] font-semibold text-gray-400 dark:text-gray-500"
                          x-text="'+' + (members.length - 4)"></span>
                </template>
            </div>
        </div>

        {{-- ── 2. Messages Scroll Area (No scrollbar on empty, smooth layout) ─── --}}
        <div x-ref="msgArea"
             class="flex-1 overflow-y-auto px-6 py-5 flex flex-col space-y-4"
             style="scrollbar-width: thin; scrollbar-color: rgba(156, 163, 175, 0.25) transparent;">

            {{-- Empty State (Vertically centered with my-auto, zero scrollbar) --}}
            <div x-show="messages.length === 0"
                 class="my-auto flex flex-col items-center justify-center text-center py-6 text-gray-400">
                <div class="w-14 h-14 bg-blue-50 dark:bg-blue-950/40 rounded-2xl flex items-center justify-center mb-3 text-blue-500">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>
                <p class="text-sm font-bold text-gray-700 dark:text-gray-300">Belum ada percakapan</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1 max-w-xs">
                    Mulai obrolan pertama atau berikan update perkembangan untuk tim proyek Anda di sini!
                </p>
            </div>

            {{-- Message Feed --}}
            <template x-for="(msg, idx) in messages" :key="msg.id">
                <div class="w-full">
                    {{-- Date Separator Capsule (Ala Image 3 Today, Jan 30) --}}
                    <template x-if="idx === 0 || messages[idx-1].date_label !== msg.date_label">
                        <div class="flex items-center justify-center my-4">
                            <span class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-750 px-4 py-1 rounded-full shadow-2xs"
                                  x-text="msg.date_label"></span>
                        </div>
                    </template>

                    {{-- Deleted Message --}}
                    <template x-if="msg.deleted">
                        <div class="flex justify-center my-1">
                            <span class="inline-flex items-center gap-1.5 text-xs text-gray-400 dark:text-gray-600 italic bg-gray-50 dark:bg-gray-800/50 rounded-full px-3 py-1 select-none">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                <span>Pesan ini telah dihapus</span>
                            </span>
                        </div>
                    </template>

                    {{-- Normal Message Container --}}
                    <template x-if="!msg.deleted">
                        <div>
                            {{-- ── A. OUTGOING MESSAGE (YOU — KANAN — SOLID BLUE ALA IMAGE 3) ── --}}
                            <div x-show="msg.is_mine" class="flex items-start gap-3 justify-end group my-1">
                                <div class="flex flex-col items-end max-w-[82%] sm:max-w-[70%]">
                                    {{-- Time & 'You' Header --}}
                                    <div class="flex items-baseline gap-2 mb-1 pr-1.5">
                                        <span class="text-[10px] text-gray-400 dark:text-gray-500 font-medium" x-text="msg.time_label"></span>
                                        <span class="text-xs font-bold text-gray-700 dark:text-gray-300">You</span>
                                        <span x-show="msg.edited_at" class="text-[9px] text-gray-400 italic">(diedit)</span>
                                    </div>

                                    {{-- Bubble (Solid Blue Pill) --}}
                                    <div class="relative bg-blue-600 text-white rounded-2xl rounded-tr-xs px-4 py-3 text-[13px] font-medium leading-relaxed shadow-xs">
                                        {{-- Reply quote if present --}}
                                        <template x-if="msg.parent">
                                            <div class="mb-2 p-2 rounded-xl bg-blue-700/60 border-l-2 border-white/80 text-xs space-y-0.5 text-left">
                                                <span class="font-bold text-white text-[11px]" x-text="msg.parent.user"></span>
                                                <p class="text-white/80 truncate text-[11px]" x-text="msg.parent.body"></p>
                                            </div>
                                        </template>

                                        {{-- Body text --}}
                                        <template x-if="editingId !== msg.id">
                                            <div class="whitespace-pre-wrap break-words leading-relaxed" x-html="msg.formatted_body"></div>
                                        </template>

                                        {{-- Inline Edit Box --}}
                                        <template x-if="editingId === msg.id">
                                            <div class="min-w-[240px] space-y-2">
                                                <textarea x-model="editBody"
                                                          @keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); editSave(msg); }"
                                                          @keydown.escape="cancelEdit()"
                                                          class="w-full bg-white text-gray-900 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-white outline-none resize-none"
                                                          rows="2"></textarea>
                                                <div class="flex items-center justify-end gap-2">
                                                    <button @click="editSave(msg)" class="text-xs px-3 py-1 bg-white text-blue-600 font-bold rounded-lg transition">Simpan</button>
                                                    <button @click="cancelEdit()" class="text-xs px-3 py-1 text-white/80 hover:text-white transition">Batal</button>
                                                </div>
                                            </div>
                                        </template>

                                        {{-- Attachments --}}
                                        <template x-if="msg.attachments && msg.attachments.length > 0">
                                            <div class="mt-2.5 pt-2 border-t border-white/15 flex flex-wrap gap-2">
                                                <template x-for="att in msg.attachments" :key="att.id">
                                                    <div>
                                                        <a x-show="att.is_image" :href="att.url" target="_blank" class="block">
                                                            <img :src="att.url" class="max-w-[240px] max-h-[160px] rounded-xl object-cover hover:opacity-95 transition shadow-2xs">
                                                        </a>
                                                        <a x-show="!att.is_image" :href="att.url" target="_blank"
                                                           class="inline-flex items-center gap-2 text-xs bg-white/20 hover:bg-white/30 rounded-xl px-3 py-2 text-white transition max-w-[240px]">
                                                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                            </svg>
                                                            <span class="truncate" x-text="att.name"></span>
                                                            <span class="text-white/70 text-[10px] shrink-0" x-text="att.size"></span>
                                                        </a>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </div>

                                    {{-- Reactions & Actions Toolbar (You) --}}
                                    <div class="flex items-center gap-1.5 mt-1 pr-1">
                                        {{-- Reactions Badges --}}
                                        <template x-if="msg.reactions && msg.reactions.length > 0">
                                            <div class="flex flex-wrap items-center gap-1">
                                                <template x-for="r in msg.reactions" :key="r.emoji">
                                                    <button @click="react(msg, r.emoji)"
                                                            class="inline-flex items-center gap-1 text-[11px] px-2 py-0.5 rounded-full bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 ring-1 ring-blue-300 dark:ring-blue-800 transition cursor-pointer"
                                                            :title="r.users.join(', ')">
                                                        <span x-text="r.emoji"></span>
                                                        <span class="font-bold text-[10px]" x-text="r.count"></span>
                                                    </button>
                                                </template>
                                            </div>
                                        </template>

                                        {{-- Hover Action Buttons (Reply, Edit, Delete) --}}
                                        <div class="opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-0.5">
                                            <button @click="setReply(msg)" class="p-1 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-gray-100 dark:hover:bg-gray-800 transition" title="Balas">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                                            </button>
                                            <button x-show="editingId !== msg.id" @click="startEdit(msg)" class="p-1 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-gray-100 dark:hover:bg-gray-800 transition" title="Edit">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                            </button>
                                            <button @click="deleteMessage(msg)" class="p-1 rounded-lg text-gray-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition" title="Hapus">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                {{-- Avatar of You on the Far Right --}}
                                <div class="w-9 h-9 rounded-full shrink-0 flex items-center justify-center font-bold text-white text-xs overflow-hidden shadow-2xs mt-0.5"
                                     style="background: linear-gradient(135deg, #3b82f6, #6366f1)">
                                    <img x-show="msg.user.avatar" :src="msg.user.avatar" class="w-full h-full object-cover">
                                    <span x-show="!msg.user.avatar" x-text="msg.user.initials"></span>
                                </div>
                            </div>

                            {{-- ── B. INCOMING MESSAGE (PEER — KIRI — WHITE CARD ALA IMAGE 3) ── --}}
                            <div x-show="!msg.is_mine" class="flex items-start gap-3 justify-start group my-1">
                                {{-- Avatar on Left --}}
                                <div class="w-9 h-9 rounded-full shrink-0 flex items-center justify-center font-bold text-white text-xs overflow-hidden shadow-2xs mt-0.5"
                                     style="background: linear-gradient(135deg, #10b981, #059669)">
                                    <img x-show="msg.user.avatar" :src="msg.user.avatar" class="w-full h-full object-cover">
                                    <span x-show="!msg.user.avatar" x-text="msg.user.initials"></span>
                                </div>

                                <div class="flex flex-col items-start max-w-[82%] sm:max-w-[70%]">
                                    {{-- Sender Name & Time Header --}}
                                    <div class="flex items-baseline gap-2 mb-1 pl-1.5">
                                        <span class="text-xs font-bold text-gray-900 dark:text-white" x-text="msg.user.name"></span>
                                        <span class="text-[10px] text-gray-400 dark:text-gray-500 font-medium" x-text="msg.time_label"></span>
                                        <span x-show="msg.edited_at" class="text-[9px] text-gray-400 italic">(diedit)</span>
                                    </div>

                                    {{-- Bubble (White Card Ala Image 3) --}}
                                    <div class="relative bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100 rounded-2xl rounded-tl-xs px-4 py-3 text-[13px] leading-relaxed shadow-2xs border border-gray-100 dark:border-gray-750">
                                        {{-- Reply quote if present --}}
                                        <template x-if="msg.parent">
                                            <div class="mb-2 p-2 rounded-xl bg-gray-50 dark:bg-gray-750 border-l-2 border-blue-500 text-xs space-y-0.5 text-left">
                                                <span class="font-bold text-blue-600 dark:text-blue-400 text-[11px]" x-text="msg.parent.user"></span>
                                                <p class="text-gray-500 dark:text-gray-400 truncate text-[11px]" x-text="msg.parent.body"></p>
                                            </div>
                                        </template>

                                        {{-- Body text --}}
                                        <div class="whitespace-pre-wrap break-words leading-relaxed" x-html="msg.formatted_body"></div>

                                        {{-- Attachments --}}
                                        <template x-if="msg.attachments && msg.attachments.length > 0">
                                            <div class="mt-2.5 pt-2 border-t border-gray-100 dark:border-gray-700 flex flex-wrap gap-2">
                                                <template x-for="att in msg.attachments" :key="att.id">
                                                    <div>
                                                        <a x-show="att.is_image" :href="att.url" target="_blank" class="block">
                                                            <img :src="att.url" class="max-w-[240px] max-h-[160px] rounded-xl object-cover hover:opacity-95 transition shadow-2xs">
                                                        </a>
                                                        <a x-show="!att.is_image" :href="att.url" target="_blank"
                                                           class="inline-flex items-center gap-2 text-xs bg-gray-50 dark:bg-gray-750 hover:bg-gray-100 rounded-xl px-3 py-2 text-gray-700 dark:text-gray-200 transition max-w-[240px]">
                                                            <svg class="w-4 h-4 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                            </svg>
                                                            <span class="truncate" x-text="att.name"></span>
                                                            <span class="text-gray-400 text-[10px] shrink-0" x-text="att.size"></span>
                                                        </a>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </div>

                                    {{-- Reactions & Actions Toolbar (Peer) --}}
                                    <div class="flex items-center gap-1.5 mt-1 pl-1">
                                        {{-- Reactions Badges --}}
                                        <template x-if="msg.reactions && msg.reactions.length > 0">
                                            <div class="flex flex-wrap items-center gap-1">
                                                <template x-for="r in msg.reactions" :key="r.emoji">
                                                    <button @click="react(msg, r.emoji)"
                                                            class="inline-flex items-center gap-1 text-[11px] px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 transition cursor-pointer"
                                                            :title="r.users.join(', ')">
                                                        <span x-text="r.emoji"></span>
                                                        <span class="font-bold text-[10px]" x-text="r.count"></span>
                                                    </button>
                                                </template>
                                            </div>
                                        </template>

                                        {{-- Hover Action Buttons (Emoji React & Reply) --}}
                                        <div class="opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-0.5">
                                            {{-- Quick Emoji Picker --}}
                                            <div class="relative" x-data="{ open: false }">
                                                <button @click="open = !open" class="p-1 rounded-lg text-gray-400 hover:text-yellow-500 hover:bg-gray-100 dark:hover:bg-gray-800 transition" title="Reaksi">😊</button>
                                                <div x-show="open" @click.outside="open = false" x-cloak
                                                     class="absolute left-0 bottom-full mb-1 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 p-1 flex gap-0.5 z-30">
                                                    <template x-for="emoji in ['👍','❤️','🔥','🎉','🚀','😂']" :key="emoji">
                                                        <button @click="react(msg, emoji); open = false" class="text-base p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-750 transition" x-text="emoji"></button>
                                                    </template>
                                                </div>
                                            </div>

                                            <button @click="setReply(msg)" class="p-1 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-gray-100 dark:hover:bg-gray-800 transition" title="Balas">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        {{-- ── 3. Reply Preview Banner ─────────────────────────────────────── --}}
        <div x-show="replyTo" x-cloak
             class="px-6 py-2 bg-blue-50/70 dark:bg-blue-950/40 border-t border-blue-100 dark:border-blue-900/60 flex items-center gap-3 shrink-0">
            <svg class="w-4 h-4 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
            </svg>
            <div class="flex-1 min-w-0 text-xs">
                <span class="font-bold text-blue-600 dark:text-blue-400" x-text="'Membalas ' + (replyTo?.user?.name ?? '')"></span>
                <p class="text-gray-500 dark:text-gray-400 truncate text-[11px]" x-text="replyTo?.body || '[Lampiran]'"></p>
            </div>
            <button @click="replyTo = null" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition text-sm cursor-pointer">&times;</button>
        </div>

        {{-- ── 4. Attached Files Chips ─────────────────────────────────────── --}}
        <div x-show="files.length > 0" x-cloak
             class="px-6 py-2 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-800 flex gap-2 flex-wrap shrink-0">
            <template x-for="(f, i) in files" :key="i">
                <div class="flex items-center gap-2 bg-white dark:bg-gray-800 rounded-xl px-3 py-1.5 text-xs text-gray-700 dark:text-gray-300 shadow-2xs">
                    <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                    </svg>
                    <span class="max-w-[140px] truncate" x-text="f.name"></span>
                    <button @click="removeFile(i)" class="text-rose-400 hover:text-rose-600 font-bold cursor-pointer ml-1">&times;</button>
                </div>
            </template>
        </div>

        {{-- ── 5. Pill Input Capsule (Ala Image 3 Style) ───────────────────── --}}
        <div class="px-6 py-4 bg-white dark:bg-gray-850 shrink-0 relative">

            {{-- Mentions Dropdown Popover --}}
            <div x-show="showMentions" x-cloak
                 class="absolute bottom-full left-6 mb-2 bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 z-30 w-64 overflow-hidden py-1">
                <div class="px-3 py-1.5 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Pilih Anggota</div>
                <template x-for="(m, i) in mentionResults" :key="m.id">
                    <button type="button" @click="selectMention(m)"
                            :class="i === mentionIndex ? 'bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-750'"
                            class="w-full text-left px-3.5 py-2 text-xs flex items-center gap-2.5 transition">
                        <div class="w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 flex items-center justify-center font-bold text-[10px] shrink-0"
                             x-text="m.name.substring(0,2).toUpperCase()"></div>
                        <span class="truncate font-medium" x-text="m.name"></span>
                    </button>
                </template>
            </div>

            {{-- Modern Floating Pill Capsule Input --}}
            <div class="bg-gray-100/80 dark:bg-gray-800/80 rounded-full px-4 py-2 flex items-center gap-2 transition-all focus-within:ring-2 focus-within:ring-blue-500/20 focus-within:bg-white dark:focus-within:bg-gray-800 border border-transparent focus-within:border-blue-200 dark:focus-within:border-gray-700">
                {{-- Attach File Icon --}}
                <label class="w-8 h-8 rounded-full hover:bg-gray-200/70 dark:hover:bg-gray-750 text-gray-400 hover:text-blue-600 flex items-center justify-center transition cursor-pointer shrink-0"
                       title="Lampirkan File">
                    <input type="file" multiple class="hidden" @change="addFiles($event)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                    </svg>
                </label>

                {{-- Mention Member Icon --}}
                <button type="button"
                        @click="newBody += '@'; handleInput({ target: $refs.inputArea }); $refs.inputArea?.focus()"
                        class="w-8 h-8 rounded-full hover:bg-gray-200/70 dark:hover:bg-gray-750 text-gray-400 hover:text-blue-600 flex items-center justify-center font-bold text-xs transition cursor-pointer shrink-0"
                        title="Mention Anggota (@)">
                    @
                </button>

                {{-- Textarea with No Border --}}
                <textarea x-model="newBody"
                          x-ref="inputArea"
                          @input="handleInput($event)"
                          @keydown="handleKeydown($event)"
                          @keydown.escape="showMentions = false; replyTo = null"
                          rows="1"
                          placeholder="Type a message... (Enter kirim, Shift+Enter baris baru)"
                          class="flex-1 bg-transparent px-2 py-1 text-xs sm:text-[13px] text-gray-800 dark:text-gray-100 placeholder-gray-400 resize-none leading-relaxed"
                          style="border: none !important; outline: none !important; box-shadow: none !important; max-height: 100px; overflow-y: auto"></textarea>

                {{-- Quick Emoji Button --}}
                <div class="relative shrink-0" x-data="{ openEmoji: false }">
                    <button type="button" @click="openEmoji = !openEmoji"
                            class="w-8 h-8 rounded-full hover:bg-gray-200/70 dark:hover:bg-gray-750 text-gray-400 hover:text-yellow-500 flex items-center justify-center text-sm transition cursor-pointer"
                            title="Emoji">
                        😊
                    </button>
                    <div x-show="openEmoji" @click.outside="openEmoji = false" x-cloak
                         class="absolute right-0 bottom-full mb-3 bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 p-2 flex gap-1 z-40">
                        <template x-for="em in ['👍','❤️','🔥','🎉','🚀','😂','👏','🙌']" :key="em">
                            <button type="button" @click="newBody += em; openEmoji = false; $refs.inputArea?.focus()"
                                    class="text-lg p-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-750 transition" x-text="em"></button>
                        </template>
                    </div>
                </div>

                {{-- Send Circular Button (Blue Circle Ala Image 3) --}}
                <button type="button"
                        @click="send()"
                        :disabled="sending || (!newBody.trim() && files.length === 0)"
                        class="w-9 h-9 rounded-full bg-blue-600 hover:bg-blue-700 text-white flex items-center justify-center shadow-xs disabled:opacity-35 disabled:cursor-not-allowed transition-all cursor-pointer shrink-0"
                        title="Kirim">
                    <svg x-show="!sending" class="w-4 h-4 translate-x-[1px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                    <svg x-show="sending" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                </button>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('chatApp', (projectId, membersData) => ({
        projectId,
        messages: [],
        newBody: '',
        replyTo: null,
        editingId: null,
        editBody: '',
        files: [],
        members: membersData || [],
        mentionResults: [],
        showMentions: false,
        mentionIndex: 0,
        mentionStart: 0,
        lastId: 0,
        loading: true,
        sending: false,
        pollingTimer: null,
        csrf: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',

        async init() {
            await this.loadMessages();
            this.startPolling();
            this.markRead();
        },

        async loadMessages() {
            this.loading = true;
            try {
                const res  = await fetch(`/projects/${this.projectId}/chat/messages`);
                const data = await res.json();
                this.messages = data.messages.map(m => this.addLabels(m));
                this.lastId   = this.messages.at(-1)?.id ?? 0;
                this.$nextTick(() => this.scrollBottom());
            } finally {
                this.loading = false;
            }
        },

        startPolling() {
            this.pollingTimer = setInterval(async () => {
                if (!document.hidden && this.$el.offsetParent !== null && this.lastId > 0) {
                    await this.pollNew();
                }
            }, 5000);
        },

        async pollNew() {
            try {
                const res  = await fetch(`/projects/${this.projectId}/chat/messages?after=${this.lastId}`);
                const data = await res.json();
                if (data.messages.length > 0) {
                    const atBottom = this.isNearBottom();
                    data.messages.forEach(m => this.messages.push(this.addLabels(m)));
                    this.lastId = data.messages.at(-1).id;
                    if (atBottom) this.$nextTick(() => this.scrollBottom());
                    this.markRead();
                }
            } catch (_) {}
        },

        addLabels(msg) {
            const d = new Date(msg.created_at);
            msg.date_label = d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', timeZone: 'Asia/Jakarta' });
            msg.time_label = d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', timeZone: 'Asia/Jakarta' });
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
            if (this.sending) return;
            const body = this.newBody.trim();
            if (!body && this.files.length === 0) return;

            this.sending = true;
            const fd = new FormData();
            if (body) fd.append('body', body);
            if (this.replyTo) fd.append('parent_id', this.replyTo.id);
            this.files.forEach(f => fd.append('files[]', f));
            fd.append('_token', this.csrf);

            try {
                const res = await fetch(`/projects/${this.projectId}/chat`, { method: 'POST', body: fd });
                if (res.ok) {
                    const data = await res.json();
                    this.messages.push(this.addLabels(data.message));
                    this.lastId   = data.message.id;
                    this.newBody  = '';
                    this.replyTo  = null;
                    this.files    = [];
                    this.$nextTick(() => this.scrollBottom());
                }
            } finally {
                this.sending = false;
            }
        },

        async editSave(msg) {
            if (!this.editBody.trim()) return;
            const res = await fetch(`/projects/${this.projectId}/chat/${msg.id}`, {
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
            const res = await fetch(`/projects/${this.projectId}/chat/${msg.id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': this.csrf },
            });
            if (res.ok) {
                const idx = this.messages.findIndex(m => m.id === msg.id);
                if (idx !== -1) this.messages[idx] = { ...this.messages[idx], deleted: true, body: '', formatted_body: '' };
            }
        },

        async react(msg, emoji) {
            const res = await fetch(`/projects/${this.projectId}/chat/${msg.id}/react`, {
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
            try {
                await fetch(`/projects/${this.projectId}/chat/read`, {
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
                if (e.key === 'Enter' || e.key === 'Tab') {
                    e.preventDefault();
                    if (this.mentionResults[this.mentionIndex]) this.selectMention(this.mentionResults[this.mentionIndex]);
                    return;
                }
            }
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.send();
            }
        },

        handleInput(e) {
            // Auto-resize textarea
            e.target.style.height = 'auto';
            e.target.style.height = Math.min(e.target.scrollHeight, 100) + 'px';

            // @mention detection
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
            const before    = this.newBody.substring(0, this.mentionStart);
            const after     = this.newBody.substring(this.$refs.inputArea?.selectionStart ?? this.newBody.length);
            this.newBody    = before + '@' + member.name + ' ' + after;
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
