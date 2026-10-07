{{-- Tab Integrasi Notifikasi Tim (Slack & Discord Webhook) --}}
<div class="space-y-6">

    {{-- Header Banner Info --}}
    <div class="bg-white dark:bg-gray-850 rounded-2xl p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Integrasi Notifikasi Tim</h3>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                    Hubungkan channel Slack atau Discord untuk menerima alert otomatis saat task atau ticket dibuat &amp; diperbarui.
                </p>
            </div>
        </div>
        @php
            $activeCount = ($project->hasSlackIntegration() ? 1 : 0) + ($project->hasDiscordIntegration() ? 1 : 0);
        @endphp
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold {{ $activeCount > 0 ? 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400' : 'bg-gray-100 dark:bg-gray-800 text-gray-500' }}">
            <span class="w-1.5 h-1.5 rounded-full {{ $activeCount > 0 ? 'bg-emerald-500 animate-pulse' : 'bg-gray-400' }}"></span>
            {{ $activeCount }} Layanan Terhubung
        </span>
    </div>

    {{-- Grid 2 Kolom: Slack & Discord --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- ── 1. Slack Integration ────────────────────────────────────── --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl p-6 flex flex-col justify-between" x-data="{ showGuide: false }">
            <div>
                {{-- Card Header --}}
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 shadow-xs" style="background:#4A154B">
                            <svg class="w-5.5 h-5.5" viewBox="0 0 24 24" fill="none">
                                <path d="M9 15.5a2.5 2.5 0 01-2.5 2.5A2.5 2.5 0 014 15.5 2.5 2.5 0 016.5 13H9v2.5zM10.25 15.5a2.5 2.5 0 012.5-2.5 2.5 2.5 0 012.5 2.5V22a2.5 2.5 0 01-2.5 2.5 2.5 2.5 0 01-2.5-2.5v-6.5zM12.75 9A2.5 2.5 0 0110.25 6.5 2.5 2.5 0 0112.75 4a2.5 2.5 0 012.5 2.5V9h-2.5zM12.75 10.25a2.5 2.5 0 012.5 2.5 2.5 2.5 0 01-2.5 2.5H6.25a2.5 2.5 0 01-2.5-2.5 2.5 2.5 0 012.5-2.5h6.5zM15.5 12.75a2.5 2.5 0 012.5-2.5 2.5 2.5 0 012.5 2.5A2.5 2.5 0 0118 15.25h-2.5v-2.5zM19 9A2.5 2.5 0 0121.5 6.5 2.5 2.5 0 0119 4a2.5 2.5 0 01-2.5 2.5V9H19z" fill="#fff"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white">Slack Incoming Webhook</h4>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Kirim pesan realtime ke channel workspace Slack</p>
                        </div>
                    </div>
                    <div>
                        @if($project->hasSlackIntegration())
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Connected
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Not Connected
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Status Body --}}
                @if(!$project->hasSlackIntegration())
                    <form method="POST" action="{{ route('team-notifications.store', $project) }}" class="space-y-4">
                        @csrf
                        <input type="hidden" name="provider" value="slack">

                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">
                                    Slack Webhook URL <span class="text-rose-500">*</span>
                                </label>
                                <button type="button" @click="showGuide = !showGuide" class="text-[11px] text-blue-600 dark:text-blue-400 hover:underline">
                                    <span x-text="showGuide ? 'Tutup Panduan' : 'Cara buat webhook?'"></span>
                                </button>
                            </div>

                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                    </svg>
                                </div>
                                <input type="url" name="webhook_url" required
                                       placeholder="https://hooks.slack.com/services/T00/B00/XXXX"
                                       class="w-full pl-9 pr-3.5 py-2.5 bg-gray-50/80 dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700 rounded-xl text-xs text-gray-800 dark:text-gray-100 placeholder-gray-400 focus:bg-white dark:focus:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-mono">
                            </div>

                            {{-- Collapsible Quick Guide --}}
                            <div x-show="showGuide" x-cloak class="mt-2.5 p-3 rounded-xl bg-gray-50 dark:bg-gray-800/50 text-[11px] text-gray-500 dark:text-gray-400 space-y-1">
                                <p class="font-semibold text-gray-700 dark:text-gray-300">Langkah mendapatkan URL Slack:</p>
                                <p>1. Buka Slack &rarr; App Directory &rarr; cari <strong>Incoming Webhooks</strong>.</p>
                                <p>2. Klik <em>Add to Slack</em> &rarr; pilih channel tujuan notifikasi proyek.</p>
                                <p>3. Salin <strong>Webhook URL</strong> dan tempel di formulir di atas.</p>
                            </div>
                        </div>

                        <button type="submit"
                                class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 text-white text-xs font-semibold rounded-xl transition-all shadow-xs hover:opacity-95 cursor-pointer"
                                style="background:#4A154B">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>Hubungkan Slack</span>
                        </button>
                    </form>
                @else
                    <div class="space-y-4">
                        <div class="p-3.5 rounded-xl bg-emerald-50/70 dark:bg-emerald-950/30 text-xs text-emerald-800 dark:text-emerald-300 flex items-start gap-2.5">
                            <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <p class="font-semibold">Integrasi Slack Aktif</p>
                                <p class="text-[11px] text-emerald-700/80 dark:text-emerald-400/80 mt-0.5">
                                    Setiap task baru, status done, atau ticket baru akan otomatis dikirimkan ke channel Slack terhubung.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2.5 pt-2">
                            <form method="POST" action="{{ route('team-notifications.test', $project) }}" class="flex-1">
                                @csrf
                                <input type="hidden" name="provider" value="slack">
                                <button type="submit"
                                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-750 text-gray-700 dark:text-gray-200 text-xs font-semibold transition-colors cursor-pointer">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                    </svg>
                                    <span>Kirim Pesan Tes</span>
                                </button>
                            </form>

                            <form method="POST" action="{{ route('team-notifications.destroy', $project) }}"
                                  data-confirm-submit="Putuskan integrasi notifikasi Slack?"
                                  data-confirm-btn="Ya, Putuskan">
                                @csrf @method('DELETE')
                                <input type="hidden" name="provider" value="slack">
                                <button type="submit"
                                        class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 dark:hover:bg-rose-900/60 text-rose-600 dark:text-rose-400 text-xs font-semibold transition-colors cursor-pointer"
                                        title="Putuskan sambungan Slack">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    <span>Disconnect</span>
                                </button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- ── 2. Discord Integration ──────────────────────────────────── --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl p-6 flex flex-col justify-between" x-data="{ showGuide: false }">
            <div>
                {{-- Card Header --}}
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 shadow-xs" style="background:#5865F2">
                            <svg class="w-5.5 h-5.5" viewBox="0 0 24 24" fill="#fff">
                                <path d="M20.3 5.4A18 18 0 0015.6 4l-.3.5a12.6 12.6 0 016.6 3.4 15.4 15.4 0 00-13.7-1.3A15.5 15.5 0 006.8 4l-.3.5a12.6 12.6 0 016.6-3.4l-.3-.5a18 18 0 00-4.7 1.4C4.7 8 3.7 12.4 4 16.8a18 18 0 005.4 2.7l.7-1.2a11.7 11.7 0 01-1.9-.9l.5-.4c3.5 1.6 7.4 1.6 10.9 0l.5.4a11.7 11.7 0 01-1.9.9l.7 1.2a18 18 0 005.4-2.7c.4-5.1-.9-9.5-3.9-11.4zM9.7 14.3c-1 0-1.9-1-1.9-2.1s.8-2.1 1.9-2.1 1.9 1 1.9 2.1-.9 2.1-1.9 2.1zm6.6 0c-1 0-1.9-1-1.9-2.1s.8-2.1 1.9-2.1 1.9 1 1.9 2.1-.8 2.1-1.9 2.1z"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white">Discord Webhook</h4>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Kirim pesan realtime ke text channel server Discord</p>
                        </div>
                    </div>
                    <div>
                        @if($project->hasDiscordIntegration())
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Connected
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Not Connected
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Status Body --}}
                @if(!$project->hasDiscordIntegration())
                    <form method="POST" action="{{ route('team-notifications.store', $project) }}" class="space-y-4">
                        @csrf
                        <input type="hidden" name="provider" value="discord">

                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">
                                    Discord Webhook URL <span class="text-rose-500">*</span>
                                </label>
                                <button type="button" @click="showGuide = !showGuide" class="text-[11px] text-blue-600 dark:text-blue-400 hover:underline">
                                    <span x-text="showGuide ? 'Tutup Panduan' : 'Cara buat webhook?'"></span>
                                </button>
                            </div>

                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                    </svg>
                                </div>
                                <input type="url" name="webhook_url" required
                                       placeholder="https://discord.com/api/webhooks/00000/XXXX"
                                       class="w-full pl-9 pr-3.5 py-2.5 bg-gray-50/80 dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700 rounded-xl text-xs text-gray-800 dark:text-gray-100 placeholder-gray-400 focus:bg-white dark:focus:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-mono">
                            </div>

                            {{-- Collapsible Quick Guide --}}
                            <div x-show="showGuide" x-cloak class="mt-2.5 p-3 rounded-xl bg-gray-50 dark:bg-gray-800/50 text-[11px] text-gray-500 dark:text-gray-400 space-y-1">
                                <p class="font-semibold text-gray-700 dark:text-gray-300">Langkah mendapatkan URL Discord:</p>
                                <p>1. Buka Discord &rarr; Server Settings &rarr; <strong>Integrations</strong>.</p>
                                <p>2. Klik <em>Webhooks</em> &rarr; <em>New Webhook</em> &rarr; pilih Channel.</p>
                                <p>3. Klik <strong>Copy Webhook URL</strong> dan tempel di formulir di atas.</p>
                            </div>
                        </div>

                        <button type="submit"
                                class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 text-white text-xs font-semibold rounded-xl transition-all shadow-xs hover:opacity-95 cursor-pointer"
                                style="background:#5865F2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>Hubungkan Discord</span>
                        </button>
                    </form>
                @else
                    <div class="space-y-4">
                        <div class="p-3.5 rounded-xl bg-emerald-50/70 dark:bg-emerald-950/30 text-xs text-emerald-800 dark:text-emerald-300 flex items-start gap-2.5">
                            <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <p class="font-semibold">Integrasi Discord Aktif</p>
                                <p class="text-[11px] text-emerald-700/80 dark:text-emerald-400/80 mt-0.5">
                                    Setiap task baru, status done, atau ticket baru akan otomatis dikirimkan ke channel Discord terhubung.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2.5 pt-2">
                            <form method="POST" action="{{ route('team-notifications.test', $project) }}" class="flex-1">
                                @csrf
                                <input type="hidden" name="provider" value="discord">
                                <button type="submit"
                                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-750 text-gray-700 dark:text-gray-200 text-xs font-semibold transition-colors cursor-pointer">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                    </svg>
                                    <span>Kirim Pesan Tes</span>
                                </button>
                            </form>

                            <form method="POST" action="{{ route('team-notifications.destroy', $project) }}"
                                  data-confirm-submit="Putuskan integrasi notifikasi Discord?"
                                  data-confirm-btn="Ya, Putuskan">
                                @csrf @method('DELETE')
                                <input type="hidden" name="provider" value="discord">
                                <button type="submit"
                                        class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 dark:hover:bg-rose-900/60 text-rose-600 dark:text-rose-400 text-xs font-semibold transition-colors cursor-pointer"
                                        title="Putuskan sambungan Discord">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    <span>Disconnect</span>
                                </button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>

    </div>

    {{-- Trigger Events Card --}}
    <div class="bg-white dark:bg-gray-850 rounded-2xl p-5">
        <h4 class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Event Pemicu Notifikasi Otomatis</h4>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="flex items-center gap-2.5 p-3 rounded-xl bg-gray-50 dark:bg-gray-800/50">
                <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Task baru dibuat di proyek</span>
            </div>
            <div class="flex items-center gap-2.5 p-3 rounded-xl bg-gray-50 dark:bg-gray-800/50">
                <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Task diselesaikan (Marked Done)</span>
            </div>
            <div class="flex items-center gap-2.5 p-3 rounded-xl bg-gray-50 dark:bg-gray-800/50">
                <div class="w-2 h-2 rounded-full bg-amber-500"></div>
                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Tiket kendala / issue baru dibuka</span>
            </div>
        </div>
    </div>

</div>
