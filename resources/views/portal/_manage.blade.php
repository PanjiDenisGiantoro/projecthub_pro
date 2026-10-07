{{-- Kelola link portal klien; dirender di tab Portal (projects/show). --}}
<div x-data="{ showForm: false }" class="space-y-6">

    {{-- ── 1. Top Bar Header & Action ───────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-850 rounded-2xl p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Portal Tamu &amp; Link Klien</h3>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                    Bagikan akses pantau proyek khusus klien eksternal tanpa perlu login atau registrasi akun.
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3 self-end sm:self-auto">
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-500">
                {{ $tokens->count() }} Link Dibuat
            </span>
            <button @click="showForm = !showForm"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl shadow-xs transition-all cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span x-text="showForm ? 'Tutup Formulir' : 'Buat Link Baru'"></span>
            </button>
        </div>
    </div>

    {{-- ── 2. Success Alert Box (When New Token Created) ───────────────── --}}
    @if(session('new_token'))
    <div class="bg-emerald-50 dark:bg-emerald-950/40 rounded-2xl p-5 border border-emerald-200/60 dark:border-emerald-800/60 space-y-3"
         x-data="{ copied: false }">
        <div class="flex items-center gap-2.5 text-emerald-800 dark:text-emerald-300">
            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-xs font-bold">Link Portal Baru Berhasil Dibuat! Bagikan link berikut kepada klien Anda:</p>
        </div>

        <div class="flex items-center gap-2">
            <div class="relative flex-1">
                <input type="text" readonly value="{{ route('portal.view', session('new_token')) }}"
                       class="w-full bg-white dark:bg-gray-900 border border-emerald-300 dark:border-emerald-700 rounded-xl px-3.5 py-2.5 text-xs font-mono text-emerald-900 dark:text-emerald-200 select-all outline-none"
                       onclick="this.select()">
            </div>
            <button @click="navigator.clipboard.writeText('{{ route('portal.view', session('new_token')) }}'); copied = true; setTimeout(() => copied = false, 2500)"
                    class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-xl shadow-xs transition-all cursor-pointer flex items-center gap-1.5">
                <svg x-show="!copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
                <svg x-show="copied" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span x-text="copied ? 'Tersalin!' : 'Salin Link'"></span>
            </button>
        </div>
    </div>
    @endif

    {{-- ── 3. Create Portal Form ────────────────────────────────────────── --}}
    <div x-show="showForm" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="bg-white dark:bg-gray-850 rounded-2xl p-6">
        <div class="mb-5">
            <h4 class="text-sm font-bold text-gray-900 dark:text-white">Buat Link Portal Tamu Baru</h4>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Tentukan izin fitur apa saja yang dapat diakses oleh klien.</p>
        </div>

        <form method="POST" action="{{ route('portal.store', $project) }}" class="space-y-5">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Label --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Label / Nama Klien <span class="text-rose-500">*</span></label>
                    <input type="text" name="label" placeholder="e.g. PT Maju Jaya — Review Sprint Akhir" required
                           class="w-full bg-gray-50/80 dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700 rounded-xl px-3.5 py-2.5 text-xs text-gray-800 dark:text-gray-100 placeholder-gray-400 focus:bg-white dark:focus:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                </div>

                {{-- Kadaluarsa --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Masa Kadaluarsa Link</label>
                    <input type="datetime-local" name="expires_at"
                           class="w-full bg-gray-50/80 dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700 rounded-xl px-3.5 py-2.5 text-xs text-gray-800 dark:text-gray-100 focus:bg-white dark:focus:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">Kosongkan jika ingin link berlaku selamanya tanpa batas waktu.</p>
                </div>
            </div>

            {{-- Permission Checkbox Cards --}}
            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Hak Izin Akses Klien</label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <label class="flex items-start gap-3 p-3.5 rounded-xl bg-gray-50/80 dark:bg-gray-800/80 hover:bg-blue-50/40 dark:hover:bg-blue-950/20 border border-gray-200/70 dark:border-gray-700 transition cursor-pointer">
                        <input type="checkbox" name="can_comment" value="1" class="mt-0.5 rounded text-blue-600 focus:ring-blue-500">
                        <div>
                            <span class="block text-xs font-semibold text-gray-800 dark:text-gray-200">Kirim Komentar</span>
                            <span class="text-[11px] text-gray-400 dark:text-gray-500">Klien dapat memberikan catatan atau tanggapan.</span>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 p-3.5 rounded-xl bg-gray-50/80 dark:bg-gray-800/80 hover:bg-blue-50/40 dark:hover:bg-blue-950/20 border border-gray-200/70 dark:border-gray-700 transition cursor-pointer">
                        <input type="checkbox" name="can_approve" value="1" class="mt-0.5 rounded text-blue-600 focus:ring-blue-500">
                        <div>
                            <span class="block text-xs font-semibold text-gray-800 dark:text-gray-200">Sign-off / Terima Milestone</span>
                            <span class="text-[11px] text-gray-400 dark:text-gray-500">Klien dapat klik tombol 'Setujui' tanda terima saat milestone sudah selesai.</span>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 p-3.5 rounded-xl bg-gray-50/80 dark:bg-gray-800/80 hover:bg-blue-50/40 dark:hover:bg-blue-950/20 border border-gray-200/70 dark:border-gray-700 transition cursor-pointer">
                        <input type="checkbox" name="show_budget" value="1" class="mt-0.5 rounded text-blue-600 focus:ring-blue-500">
                        <div>
                            <span class="block text-xs font-semibold text-gray-800 dark:text-gray-200">Tampilkan Budget</span>
                            <span class="text-[11px] text-gray-400 dark:text-gray-500">Klien dapat melihat pagu &amp; ringkasan anggaran.</span>
                        </div>
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-2">
                <button type="button" @click="showForm = false"
                        class="px-4 py-2.5 text-xs font-medium text-gray-600 dark:text-gray-400 hover:text-gray-800 rounded-xl transition cursor-pointer">
                    Batal
                </button>
                <button type="submit"
                        class="inline-flex items-center gap-1.5 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl shadow-xs transition-all cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                    </svg>
                    <span>Generate Link Portal</span>
                </button>
            </div>
        </form>
    </div>

    {{-- ── 4. Table / List of Portal Tokens ─────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-850 rounded-2xl overflow-hidden">
        @if($tokens->isEmpty())
        <div class="text-center py-16 text-gray-400 dark:text-gray-500">
            <div class="w-12 h-12 rounded-2xl bg-gray-50 dark:bg-gray-800 flex items-center justify-center mx-auto mb-3 text-gray-300 dark:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
            </div>
            <p class="font-semibold text-sm text-gray-600 dark:text-gray-300">Belum ada link portal klien</p>
            <p class="text-xs text-gray-400 mt-1">Buat link baru untuk membagikan progres proyek ke klien luar secara instan.</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="text-gray-400 dark:text-gray-500 font-semibold uppercase text-[11px] tracking-wider border-b border-gray-100 dark:border-gray-800">
                    <tr>
                        <th class="px-6 py-3.5">Label &amp; Tautan</th>
                        <th class="px-6 py-3.5">Izin Akses</th>
                        <th class="px-6 py-3.5">Masa Berlaku</th>
                        <th class="px-6 py-3.5">Terakhir Diakses</th>
                        <th class="px-6 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800 text-gray-700 dark:text-gray-300 font-medium">
                    @foreach($tokens as $token)
                    <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition-colors {{ $token->isExpired() ? 'opacity-55' : '' }}">
                        <td class="px-6 py-3.5">
                            <div class="flex items-center gap-2">
                                <p class="font-bold text-gray-900 dark:text-white text-xs">{{ $token->label ?? '(Tanpa label)' }}</p>
                                @if($token->isExpired())
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-600 dark:bg-rose-950/50 dark:text-rose-400">Expired</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">Aktif</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-[11px] font-mono text-gray-400 dark:text-gray-500">{{ Str::limit($token->token, 24) }}</span>
                                <button onclick="navigator.clipboard.writeText('{{ route('portal.view', $token->token) }}'); this.textContent = 'Disalin!'; setTimeout(() => this.textContent = 'Salin', 2000)"
                                        class="text-[10px] text-blue-600 dark:text-blue-400 hover:underline cursor-pointer">
                                    Salin
                                </button>
                            </div>
                        </td>
                        <td class="px-6 py-3.5 whitespace-nowrap">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                @if($token->can_comment)
                                    <span class="px-2 py-0.5 rounded-lg text-[10px] font-semibold bg-blue-50 dark:bg-blue-950/50 text-blue-700 dark:text-blue-400">Komentar</span>
                                @endif
                                @if($token->can_approve)
                                    <span class="px-2 py-0.5 rounded-lg text-[10px] font-semibold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-400">Approve</span>
                                @endif
                                @if($token->show_budget)
                                    <span class="px-2 py-0.5 rounded-lg text-[10px] font-semibold bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-400">Budget</span>
                                @endif
                                @if(!$token->can_comment && !$token->can_approve && !$token->show_budget)
                                    <span class="text-[11px] text-gray-400">Hanya Lihat</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-3.5 whitespace-nowrap text-gray-500 dark:text-gray-400">
                            {{ $token->expires_at ? $token->expires_at->format('d M Y, H:i') : 'Tanpa batas (∞)' }}
                        </td>
                        <td class="px-6 py-3.5 whitespace-nowrap text-gray-500 dark:text-gray-400 text-xs">
                            {{ $token->last_accessed_at ? $token->last_accessed_at->diffForHumans() : 'Belum pernah' }}
                        </td>
                        <td class="px-6 py-3.5 whitespace-nowrap text-right">
                            <div class="flex items-center justify-end gap-2">
                                @if(!$token->isExpired())
                                <a href="{{ route('portal.view', $token->token) }}" target="_blank"
                                   class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 text-xs font-semibold transition"
                                   title="Buka tampilan klien">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                    </svg>
                                    <span>Buka</span>
                                </a>
                                @endif

                                <form method="POST" action="{{ route('portal.destroy', [$project, $token]) }}"
                                      data-confirm-delete="{{ $token->label ?? 'link portal ini' }}"
                                      class="inline-block">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="p-1.5 text-gray-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg transition-colors cursor-pointer"
                                            title="Cabut &amp; nonaktifkan link">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

</div>
