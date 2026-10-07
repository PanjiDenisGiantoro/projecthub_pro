{{-- Halaman anggaran project; dirender di tab Budget (projects/show). --}}
<div x-data="{ showForm: false }" class="space-y-6">

    {{-- ============================================================
    1. SUMMARY KPI CARDS (BORDERLESS & MODERN)
    ============================================================ --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total Budget --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl p-5 transition-all">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Total Budget</span>
                <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-black text-gray-900 dark:text-white tracking-tight">
                Rp {{ number_format($summary['budget'], 0, ',', '.') }}
            </p>
            <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">Pagu batas anggaran proyek</p>
        </div>

        {{-- Total Pengeluaran --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl p-5 transition-all">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Pengeluaran</span>
                <div class="w-9 h-9 rounded-xl bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-black text-rose-600 dark:text-rose-400 tracking-tight">
                Rp {{ number_format($summary['expenses'], 0, ',', '.') }}
            </p>
            <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">Total biaya yang terpakai</p>
        </div>

        {{-- Total Pemasukan --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl p-5 transition-all">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Pemasukan</span>
                <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400 tracking-tight">
                Rp {{ number_format($summary['income'], 0, ',', '.') }}
            </p>
            <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">Termin / kas yang diterima</p>
        </div>

        {{-- Sisa Anggaran --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl p-5 transition-all">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Sisa Saldo</span>
                <div class="w-9 h-9 rounded-xl {{ $summary['balance'] < 0 ? 'bg-red-50 dark:bg-red-950/50 text-red-600 dark:text-red-400' : 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400' }} flex items-center justify-center shrink-0">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-black {{ $summary['balance'] < 0 ? 'text-red-600 dark:text-red-400' : 'text-indigo-600 dark:text-indigo-400' }} tracking-tight">
                Rp {{ number_format($summary['balance'], 0, ',', '.') }}
            </p>
            <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">
                {{ $summary['balance'] < 0 ? 'Melebihi total batas budget' : 'Tersedia untuk alokasi' }}
            </p>
        </div>
    </div>

    {{-- ============================================================
    2. BUDGET USAGE PROGRESS BAR
    ============================================================ --}}
    @if($summary['budget'] > 0)
    <div class="bg-white dark:bg-gray-850 rounded-2xl p-5">
        <div class="flex items-center justify-between text-xs mb-3">
            <div class="flex items-center gap-2">
                <span class="font-bold text-gray-800 dark:text-gray-200">Penggunaan Anggaran</span>
                <span class="text-[11px] px-2 py-0.5 rounded-full font-semibold {{ $summary['percent'] >= 90 ? 'bg-red-50 text-red-600 dark:bg-red-950/50 dark:text-red-400' : ($summary['percent'] >= 70 ? 'bg-amber-50 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400' : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400') }}">
                    {{ $summary['percent'] >= 100 ? 'Overbudget' : ($summary['percent'] >= 80 ? 'Hampir Habis' : 'Dalam Batas Aman') }}
                </span>
            </div>
            <span class="font-extrabold text-sm {{ $summary['percent'] >= 90 ? 'text-red-600 dark:text-red-400' : ($summary['percent'] >= 70 ? 'text-amber-600 dark:text-amber-400' : 'text-blue-600 dark:text-blue-400') }}">
                {{ $summary['percent'] }}%
            </span>
        </div>
        <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-3 overflow-hidden">
            <div class="h-3 rounded-full transition-all duration-500 {{ $summary['percent'] >= 90 ? 'bg-red-500' : ($summary['percent'] >= 70 ? 'bg-amber-500' : 'bg-blue-600') }}"
                 style="width: {{ min(100, $summary['percent']) }}%"></div>
        </div>
    </div>
    @endif

    {{-- ============================================================
    3. CATEGORY CHART & ADD TRANSACTION FORM
    ============================================================ --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- By Category chart --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl p-5 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">Pengeluaran per Kategori</h3>
                    <span class="text-[11px] text-gray-400 dark:text-gray-500 font-medium">{{ $byCategory->count() }} Kategori</span>
                </div>
                @if($byCategory->isEmpty())
                <div class="flex flex-col items-center justify-center py-12 text-center text-gray-400 dark:text-gray-500">
                    <svg class="w-10 h-10 mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
                    </svg>
                    <p class="text-xs">Belum ada data pengeluaran.</p>
                </div>
                @else
                <div class="relative py-2">
                    <canvas id="categoryChart" height="220" class="cursor-pointer mx-auto"></canvas>
                </div>
                <p class="text-[11px] text-gray-400 dark:text-gray-500 text-center mt-3">
                    Klik irisan chart untuk memfilter riwayat transaksi di bawah.
                </p>
                @endif
            </div>
        </div>

        {{-- Add entry form --}}
        <div class="lg:col-span-2">
            @if(!auth()->user()->hasRole('client'))
            <div class="bg-white dark:bg-gray-850 rounded-2xl p-6">
                <div class="flex items-center justify-between mb-5">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Catat Transaksi Anggaran</h3>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Tambah pengeluaran atau termin pemasukan untuk proyek ini.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('budget.store', $project) }}" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Custom Tipe Select (Mengikuti Style Task Modal Dropdown) --}}
                        <div x-data="{ open: false, type: 'expense' }" class="relative">
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                                Tipe Transaksi <span class="text-rose-500">*</span>
                            </label>
                            <input type="hidden" name="type" :value="type">

                            <button type="button" @click="open = !open"
                                    class="w-full flex items-center justify-between px-3.5 py-2.5 bg-gray-50/80 dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700 rounded-xl text-xs font-semibold hover:border-gray-300 dark:hover:border-gray-600 transition cursor-pointer">
                                <span class="flex items-center gap-2 truncate">
                                    <span class="w-2.5 h-2.5 rounded-full" :class="type === 'expense' ? 'bg-rose-500' : 'bg-emerald-500'"></span>
                                    <span class="text-gray-800 dark:text-gray-100" x-text="type === 'expense' ? 'Pengeluaran (Expense)' : 'Pemasukan (Income / Termin)'"></span>
                                </span>
                                <svg class="w-4 h-4 text-gray-400 transition-transform duration-150 shrink-0" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            <div x-show="open" @click.outside="open = false" x-cloak
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="opacity-0 translate-y-1"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 class="absolute left-0 top-full mt-1.5 w-full bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-100 dark:border-gray-750 py-1 z-30 space-y-0.5">
                                <button type="button" @click="type = 'expense'; open = false"
                                        class="w-full text-left px-3.5 py-2 text-xs flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/60 transition cursor-pointer"
                                        :class="type === 'expense' ? 'bg-rose-50/70 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 font-bold' : 'text-gray-700 dark:text-gray-300'">
                                    <span class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                        <span>Pengeluaran (Expense)</span>
                                    </span>
                                    <svg x-show="type === 'expense'" class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                </button>
                                <button type="button" @click="type = 'income'; open = false"
                                        class="w-full text-left px-3.5 py-2 text-xs flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/60 transition cursor-pointer"
                                        :class="type === 'income' ? 'bg-emerald-50/70 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 font-bold' : 'text-gray-700 dark:text-gray-300'">
                                    <span class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                        <span>Pemasukan (Income / Termin)</span>
                                    </span>
                                    <svg x-show="type === 'income'" class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </div>
                        </div>

                        {{-- Tanggal --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Tanggal <span class="text-rose-500">*</span></label>
                            <input type="date" name="entry_date" value="{{ date('Y-m-d') }}" required
                                   class="w-full bg-gray-50/80 dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700 rounded-xl px-3.5 py-2.5 text-xs text-gray-800 dark:text-gray-100 focus:bg-white dark:focus:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                        </div>

                        {{-- Kategori --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Kategori <span class="text-rose-500">*</span></label>
                            <input type="text" name="category" placeholder="e.g. Server, Software, Konsultan, Desain..." required
                                   class="w-full bg-gray-50/80 dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700 rounded-xl px-3.5 py-2.5 text-xs text-gray-800 dark:text-gray-100 placeholder-gray-400 focus:bg-white dark:focus:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                        </div>

                        {{-- Jumlah --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Jumlah (Rp) <span class="text-rose-500">*</span></label>
                            <input type="number" name="amount" step="0.01" min="0" placeholder="0" required
                                   class="w-full bg-gray-50/80 dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700 rounded-xl px-3.5 py-2.5 text-xs text-gray-800 dark:text-gray-100 placeholder-gray-400 focus:bg-white dark:focus:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-mono">
                        </div>
                    </div>

                    {{-- Deskripsi --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Deskripsi Transaksi <span class="text-rose-500">*</span></label>
                        <input type="text" name="description" placeholder="Penjelasan rincian transaksi..." required
                               class="w-full bg-gray-50/80 dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700 rounded-xl px-3.5 py-2.5 text-xs text-gray-800 dark:text-gray-100 placeholder-gray-400 focus:bg-white dark:focus:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-1 items-end">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Referensi / No. Invoice / PO</label>
                            <input type="text" name="reference" placeholder="e.g. INV-2026-001"
                                   class="w-full bg-gray-50/80 dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700 rounded-xl px-3.5 py-2.5 text-xs text-gray-800 dark:text-gray-100 placeholder-gray-400 focus:bg-white dark:focus:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                        </div>
                        <div>
                            <button type="submit"
                                    class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl shadow-xs hover:shadow transition-all cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                <span>Simpan Transaksi</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            @endif
        </div>
    </div>

    {{-- ============================================================
    4. ENTRIES TABLE & CUSTOM PAGINATION (ALA TASK / PROJECT)
    ============================================================ --}}
    <div class="bg-white dark:bg-gray-850 rounded-2xl overflow-hidden">
        <div class="px-6 py-4.5 flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-2.5">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Riwayat Transaksi Anggaran</h3>
                <span class="text-xs text-gray-400 dark:text-gray-500 font-medium">({{ $entries->total() }} entri)</span>
                @if(request('category'))
                <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full bg-blue-50 dark:bg-blue-950/50 text-blue-700 dark:text-blue-300">
                    Filter: {{ request('category') }}
                    <a href="{{ route('projects.tab', [$project, 'budget']) }}" class="hover:text-blue-900 dark:hover:text-blue-100 ml-1 text-xs" title="Hapus filter">✕</a>
                </span>
                @endif
            </div>
        </div>

        @if($entries->isEmpty())
        <div class="text-center py-16 text-gray-400 dark:text-gray-500">
            <div class="w-12 h-12 rounded-2xl bg-gray-50 dark:bg-gray-800 flex items-center justify-center mx-auto mb-3 text-gray-300 dark:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <p class="font-semibold text-sm text-gray-600 dark:text-gray-300">
                {{ request('category') ? 'Tidak ada transaksi di kategori "' . request('category') . '"' : 'Belum ada transaksi anggaran' }}
            </p>
            <p class="text-xs text-gray-400 mt-1">Catat pengeluaran atau pemasukan untuk memantau keuangan proyek.</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="text-gray-400 dark:text-gray-500 font-semibold uppercase text-[11px] tracking-wider border-b border-gray-100 dark:border-gray-800">
                    <tr>
                        <th class="px-6 py-3.5">Tanggal</th>
                        <th class="px-6 py-3.5">Deskripsi</th>
                        <th class="px-6 py-3.5">Kategori</th>
                        <th class="px-6 py-3.5 text-right">Jumlah</th>
                        <th class="px-6 py-3.5">Oleh</th>
                        @if(!auth()->user()->hasRole('client'))
                        <th class="px-6 py-3.5 text-right">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800 text-gray-700 dark:text-gray-300 font-medium">
                    @foreach($entries as $entry)
                    <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition-colors">
                        <td class="px-6 py-3.5 whitespace-nowrap text-gray-500 dark:text-gray-400">
                            {{ $entry->entry_date->format('d M Y') }}
                        </td>
                        <td class="px-6 py-3.5">
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $entry->description }}</p>
                            @if($entry->reference)
                                <p class="text-[11px] text-gray-400 dark:text-gray-500 font-mono mt-0.5">{{ $entry->reference }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-3.5 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-[11px] font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                                {{ $entry->category }}
                            </span>
                        </td>
                        <td class="px-6 py-3.5 whitespace-nowrap text-right font-bold text-[13px] {{ $entry->type === 'income' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                            {{ $entry->type === 'income' ? '+' : '-' }} Rp {{ number_format($entry->amount, 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-3.5 whitespace-nowrap text-gray-500 dark:text-gray-400">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 flex items-center justify-center font-bold text-[10px]">
                                    {{ strtoupper(substr($entry->creator?->name ?? 'U', 0, 1)) }}
                                </div>
                                <span class="truncate max-w-[120px]">{{ $entry->creator?->name ?? '—' }}</span>
                            </div>
                        </td>
                        @if(!auth()->user()->hasRole('client'))
                        <td class="px-6 py-3.5 whitespace-nowrap text-right">
                            <form method="POST" action="{{ route('budget.destroy', [$project, $entry]) }}"
                                  data-confirm-delete="{{ $entry->description }}" class="inline-block">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="p-1.5 text-gray-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg transition-colors cursor-pointer"
                                        title="Hapus transaksi">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        </td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- ── 5. Bottom Pagination Bar (Mengikuti Custom Style Task & Project) ─── --}}
        <div class="px-6 py-4 border-t border-gray-50 dark:border-gray-800 flex flex-col sm:flex-row items-center justify-between gap-4">
            {{-- Left: Showing entries & Custom Show per page dropdown --}}
            <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                <span>
                    Showing <strong class="font-bold text-gray-900 dark:text-white">{{ $entries->firstItem() ?? 0 }}</strong> to <strong class="font-bold text-gray-900 dark:text-white">{{ $entries->lastItem() ?? 0 }}</strong> of <strong class="font-bold text-gray-900 dark:text-white">{{ $entries->total() }}</strong> entries
                </span>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-400">Show:</span>
                    <div class="relative" x-data="{ open: false }">
                        <button type="button" @click="open = !open" @click.away="open = false"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-750 border border-gray-200/90 dark:border-gray-700 rounded-xl text-xs font-bold text-gray-700 dark:text-gray-200 transition cursor-pointer shadow-2xs">
                            <span class="text-blue-600 dark:text-blue-400">{{ request('per_page', 10) }}</span>
                            <span class="text-gray-400 font-normal">/ page</span>
                            <svg class="w-3 h-3 text-gray-400 transition-transform duration-150" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="open" x-cloak
                             class="absolute bottom-full mb-1.5 left-0 min-w-[110px] bg-white dark:bg-gray-800 border border-gray-200/90 dark:border-gray-700 rounded-xl shadow-xl p-1 z-40 space-y-0.5">
                            @foreach([10, 25, 50, 100] as $num)
                                <a href="{{ request()->fullUrlWithQuery(['per_page' => $num, 'page' => 1]) }}"
                                   class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700 transition cursor-pointer {{ (int) request('per_page', 10) === $num ? 'text-blue-600 dark:text-blue-400 font-bold bg-blue-50/60 dark:bg-blue-950/40' : 'text-gray-700 dark:text-gray-300' }}">
                                    <span>{{ $num }} / page</span>
                                    @if((int) request('per_page', 10) === $num)
                                        <svg class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m4.5 12.75 6 6 9-13.5"/>
                                        </svg>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right: Custom Pagination Buttons --}}
            @if ($entries->hasPages())
                <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center gap-1">
                    {{-- Previous Page Link --}}
                    @if ($entries->onFirstPage())
                        <span class="px-3 py-1.5 text-xs font-semibold text-gray-300 dark:text-gray-600 cursor-not-allowed flex items-center gap-1">
                            &lsaquo; Prev
                        </span>
                    @else
                        <a href="{{ $entries->previousPageUrl() }}" class="px-3 py-1.5 text-xs font-semibold text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition flex items-center gap-1">
                            &lsaquo; Prev
                        </a>
                    @endif

                    {{-- Pagination Numbers --}}
                    @foreach ($entries->getUrlRange(1, $entries->lastPage()) as $page => $url)
                        @if ($page == $entries->currentPage())
                            <span class="w-7 h-7 rounded-lg bg-blue-600 text-white font-bold flex items-center justify-center text-xs shadow-2xs">
                                {{ $page }}
                            </span>
                        @elseif ($page <= 3 || $page >= $entries->lastPage() - 1 || abs($page - $entries->currentPage()) <= 1)
                            <a href="{{ $url }}" class="w-7 h-7 rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 font-semibold flex items-center justify-center text-xs transition">
                                {{ $page }}
                            </a>
                        @elseif ($page == 4 && $entries->currentPage() > 4)
                            <span class="w-7 h-7 flex items-center justify-center text-xs text-gray-400 font-bold">&hellip;</span>
                        @elseif ($page == $entries->lastPage() - 2 && $entries->currentPage() < $entries->lastPage() - 3)
                            <span class="w-7 h-7 flex items-center justify-center text-xs text-gray-400 font-bold">&hellip;</span>
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($entries->hasMorePages())
                        <a href="{{ $entries->nextPageUrl() }}" class="px-3 py-1.5 text-xs font-semibold text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition flex items-center gap-1">
                            Next &rsaquo;
                        </a>
                    @else
                        <span class="px-3 py-1.5 text-xs font-semibold text-gray-300 dark:text-gray-600 cursor-not-allowed flex items-center gap-1">
                            Next &rsaquo;
                        </span>
                    @endif
                </nav>
            @endif
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
@if(!$byCategory->isEmpty())
var categoryLabels = @json($byCategory->keys());
var categoryChart = new Chart(document.getElementById('categoryChart'), {
    type: 'doughnut',
    data: {
        labels: categoryLabels,
        datasets: [{
            data: @json($byCategory->values()),
            backgroundColor: ['#3B82F6','#F59E0B','#EF4444','#10B981','#8B5CF6','#F97316','#06B6D4','#EC4899'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    font: { size: 11, family: "'Inter', sans-serif" },
                    padding: 12,
                    boxWidth: 10,
                    boxHeight: 10,
                    usePointStyle: true
                }
            }
        },
        onClick: function (evt) {
            var points = categoryChart.getElementsAtEventForMode(evt, 'nearest', { intersect: true }, true);
            if (!points.length) return;
            var category = categoryLabels[points[0].index];
            var url = new URL(window.location.href);
            url.searchParams.set('category', category);
            url.searchParams.delete('page');
            window.location.href = url.toString();
        }
    }
});
@endif
</script>
@endpush
