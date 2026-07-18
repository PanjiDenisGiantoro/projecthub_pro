@extends('superadmin.layout')
@section('title', 'Pelanggan')
@section('page-title', 'Pelanggan')

@section('content')

{{-- Stat Cards --}}
<div class="grid grid-cols-4 gap-4 mb-8">
    <div class="bg-slate-800/60 border border-white/5 rounded-2xl p-5">
        <div class="text-xs text-slate-400 font-medium mb-3">Total Pelanggan</div>
        <div class="text-3xl font-bold text-white">{{ number_format($counts['all']) }}</div>
    </div>
    <div class="bg-slate-800/60 border border-amber-500/10 rounded-2xl p-5">
        <div class="text-xs text-slate-400 font-medium mb-3">Lifetime</div>
        <div class="text-3xl font-bold text-amber-400">{{ number_format($counts['lifetime']) }}</div>
    </div>
    <div class="bg-slate-800/60 border border-violet-500/10 rounded-2xl p-5">
        <div class="text-xs text-slate-400 font-medium mb-3">Masa Aktif</div>
        <div class="text-3xl font-bold text-blue-400">{{ number_format($counts['expiring']) }}</div>
    </div>
    <div class="bg-slate-800/60 border border-red-500/10 rounded-2xl p-5">
        <div class="text-xs text-slate-400 font-medium mb-3">Expired</div>
        <div class="text-3xl font-bold text-red-400">{{ number_format($counts['expired']) }}</div>
    </div>
</div>

{{-- Filter Tabs --}}
<div class="flex items-center gap-2 mb-5">
    @php
        $tabs = [
            'all'      => ['label' => 'Semua Pelanggan', 'color' => 'slate'],
            'lifetime' => ['label' => 'Lifetime',        'color' => 'amber'],
            'expiring' => ['label' => 'Masa Aktif',      'color' => 'blue'],
            'expired'  => ['label' => 'Expired',         'color' => 'red'],
        ];
        $colorMap = [
            'slate' => ['on' => 'bg-slate-600/50 text-white border-slate-500/50',     'off' => 'bg-slate-800/60 text-slate-400 border-white/5 hover:text-white hover:border-white/15'],
            'amber' => ['on' => 'bg-amber-500/20 text-amber-400 border-amber-500/30', 'off' => 'bg-slate-800/60 text-slate-400 border-white/5 hover:text-white hover:border-white/15'],
            'blue'  => ['on' => 'bg-blue-500/20 text-blue-400 border-violet-500/30',    'off' => 'bg-slate-800/60 text-slate-400 border-white/5 hover:text-white hover:border-white/15'],
            'red'   => ['on' => 'bg-red-500/20 text-red-400 border-red-500/30',       'off' => 'bg-slate-800/60 text-slate-400 border-white/5 hover:text-white hover:border-white/15'],
        ];
    @endphp

    @foreach($tabs as $key => $tab)
    @php $cls = $colorMap[$tab['color']][$filter === $key ? 'on' : 'off']; @endphp
    <a href="{{ route('superadmin.registered-users', ['filter' => $key]) }}"
       class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium border transition-all {{ $cls }}">
        {{ $tab['label'] }}
        <span class="text-xs font-bold opacity-70">{{ $counts[$key] }}</span>
    </a>
    @endforeach
</div>

{{-- Tabel Pelanggan --}}
<div class="bg-slate-800/60 border border-white/5 rounded-2xl overflow-hidden">
    <div class="px-6 py-4 border-b border-white/5 flex items-center justify-between">
        <p class="text-sm font-semibold text-white">
            Daftar Pelanggan
            <span class="ml-2 text-xs font-normal text-slate-400">{{ $users->total() }} data</span>
        </p>
        <button onclick="openAddModal()"
                class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl transition-all">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Tambah Pelanggan
        </button>
    </div>

    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-white/5 text-xs text-slate-500 uppercase tracking-wide">
                <th class="text-left px-6 py-3 font-medium">Pelanggan</th>
                <th class="text-left px-6 py-3 font-medium">Perusahaan</th>
                <th class="text-left px-6 py-3 font-medium">Daftar Sejak</th>
                <th class="text-left px-6 py-3 font-medium">Package</th>
                <th class="text-center px-6 py-3 font-medium">Status Akun</th>
                <th class="text-center px-6 py-3 font-medium">Masa Aktif</th>
                <th class="text-center px-6 py-3 font-medium">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-white/5">
            @forelse($users as $user)
            @php $company = $user->organizationUnit?->company; @endphp
            <tr class="hover:bg-white/2 transition-colors">

                {{-- Pelanggan --}}
                <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center text-white font-bold text-xs shrink-0"
                             style="background: linear-gradient(135deg, #6366f1, #8b5cf6)">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </div>
                        <div>
                            <p class="font-medium text-white">{{ $user->name }}</p>
                            <p class="text-xs text-slate-500">{{ $user->email }}</p>
                        </div>
                    </div>
                </td>

                {{-- Perusahaan --}}
                <td class="px-6 py-4">
                    @if($company)
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-md flex items-center justify-center text-white font-bold text-xs shrink-0"
                             style="background: linear-gradient(135deg, #f59e0b, #ef4444)">
                            {{ strtoupper(substr($company->name, 0, 1)) }}
                        </div>
                        <span class="text-slate-300 text-xs font-medium">{{ $company->name }}</span>
                    </div>
                    @else
                        <span class="text-slate-600 text-xs">—</span>
                    @endif
                </td>

                {{-- Daftar Sejak --}}
                <td class="px-6 py-4">
                    <p class="text-slate-300 text-xs">{{ $user->created_at->format('d M Y') }}</p>
                    <p class="text-slate-600 text-xs">{{ $user->created_at->diffForHumans() }}</p>
                </td>

                {{-- Package --}}
                <td class="px-6 py-4">
                    @if($user->packages->isEmpty())
                        <span class="text-slate-600 text-xs">—</span>
                    @else
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($user->packages as $pkg)
                            @php
                                $pkgColors = [
                                    'hris'            => 'bg-violet-500/15 text-violet-400 border-violet-500/20',
                                    'task_management' => 'bg-blue-500/15 text-blue-400 border-violet-500/20',
                                ];
                                $cls = $pkgColors[$pkg->slug] ?? 'bg-slate-500/15 text-slate-400 border-slate-500/20';
                            @endphp
                            <span class="inline-flex items-center text-xs font-medium px-2 py-0.5 rounded-md border {{ $cls }}">
                                {{ $pkg->name }}
                            </span>
                            @endforeach
                        </div>
                    @endif
                </td>

                {{-- Status Akun --}}
                <td class="px-6 py-4 text-center">
                    @if($user->is_active)
                        <span class="bg-green-500/15 text-green-400 text-xs font-medium px-2.5 py-1 rounded-full">Aktif</span>
                    @else
                        <span class="bg-red-500/15 text-red-400 text-xs font-medium px-2.5 py-1 rounded-full">Nonaktif</span>
                    @endif
                </td>

                {{-- Masa Aktif --}}
                <td class="px-6 py-4 text-center">
                    @if($user->isLifetime())
                        <span class="inline-flex items-center gap-1.5 bg-amber-500/15 text-amber-400 text-xs font-semibold px-2.5 py-1 rounded-full">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Lifetime
                        </span>
                    @elseif($user->isExpired())
                        <div class="space-y-1">
                            <span class="bg-red-500/15 text-red-400 text-xs font-medium px-2.5 py-1 rounded-full">Expired</span>
                            <p class="text-xs text-slate-600">{{ $user->active_until->format('d M Y') }}</p>
                        </div>
                    @else
                        <div class="space-y-1">
                            <span class="bg-blue-500/15 text-blue-400 text-xs font-medium px-2.5 py-1 rounded-full">
                                {{ $user->active_until->diffForHumans() }}
                            </span>
                            <p class="text-xs text-slate-500">s/d {{ $user->active_until->format('d M Y') }}</p>
                        </div>
                    @endif
                </td>

                {{-- Aksi --}}
                <td class="px-6 py-4 text-center">
                    <button
                        onclick="openModal({{ $user->id }}, '{{ e($user->name) }}', '{{ $user->isLifetime() ? 'lifetime' : 'expiry' }}', '{{ $user->active_until?->format('Y-m-d') ?? '' }}')"
                        class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-400 hover:text-white border border-white/10 hover:border-white/25 px-3 py-1.5 rounded-lg transition-all">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Set Langganan
                    </button>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-6 py-16 text-center">
                    <div class="flex flex-col items-center gap-3 text-slate-500">
                        <svg class="w-12 h-12 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <p class="text-sm">Belum ada pelanggan di kategori ini.</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($users->hasPages())
    <div class="px-6 py-4 border-t border-white/5 text-slate-400">
        {{ $users->links() }}
    </div>
    @endif
</div>

{{-- Modal Set Langganan --}}
<div id="modal-lifetime" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="closeModal()"></div>
    <div class="relative bg-slate-900 border border-white/10 rounded-2xl w-full max-w-md shadow-2xl">

        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-white/5">
            <div>
                <h3 class="font-semibold text-white text-sm">Set Langganan</h3>
                <p class="text-xs text-slate-500 mt-0.5">Atur masa aktif pelanggan</p>
            </div>
            <button onclick="closeModal()" class="text-slate-500 hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form id="modal-form" method="POST" class="px-6 py-5 space-y-5">
            @csrf
            @method('PATCH')

            {{-- Info Pelanggan --}}
            <div class="flex items-center gap-3 p-3 bg-slate-800/60 rounded-xl border border-white/5">
                <div class="w-9 h-9 rounded-full flex items-center justify-center text-white font-bold text-xs shrink-0"
                     style="background: linear-gradient(135deg, #6366f1, #8b5cf6)">
                    <span id="modal-avatar"></span>
                </div>
                <div>
                    <p id="modal-username" class="text-sm font-semibold text-white"></p>
                    <p class="text-xs text-slate-500">Pelanggan terdaftar</p>
                </div>
            </div>

            {{-- Pilih Tipe --}}
            <div class="space-y-2">
                <label class="text-xs font-medium text-slate-400 uppercase tracking-wide">Tipe Langganan</label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="relative flex flex-col gap-1.5 p-4 rounded-xl border border-white/10 cursor-pointer hover:border-amber-500/40 transition-all has-[:checked]:border-amber-500/60 has-[:checked]:bg-amber-500/10">
                        <input type="radio" name="type" value="lifetime" class="sr-only" onchange="toggleDateField(this)">
                        <div class="w-8 h-8 rounded-lg bg-amber-500/20 flex items-center justify-center mb-1">
                            <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <p class="text-sm font-semibold text-white">Lifetime</p>
                        <p class="text-xs text-slate-500 leading-relaxed">Akses selamanya tanpa batas waktu</p>
                    </label>
                    <label class="relative flex flex-col gap-1.5 p-4 rounded-xl border border-white/10 cursor-pointer hover:border-violet-500/40 transition-all has-[:checked]:border-violet-500/60 has-[:checked]:bg-blue-500/10">
                        <input type="radio" name="type" value="expiry" class="sr-only" onchange="toggleDateField(this)">
                        <div class="w-8 h-8 rounded-lg bg-blue-500/20 flex items-center justify-center mb-1">
                            <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <p class="text-sm font-semibold text-white">Batas Waktu</p>
                        <p class="text-xs text-slate-500 leading-relaxed">Tentukan tanggal masa aktif</p>
                    </label>
                </div>
            </div>

            {{-- Tanggal (muncul jika pilih batas waktu) --}}
            <div id="date-field" class="hidden space-y-1.5">
                <label class="text-xs font-medium text-slate-400 uppercase tracking-wide">Aktif Hingga</label>
                <input type="date" name="active_until" id="input-date"
                       class="w-full bg-slate-800 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-violet-500/60 transition-all"
                       min="{{ now()->addDay()->format('Y-m-d') }}">
            </div>

            <div class="flex items-center justify-end gap-3 pt-1">
                <button type="button" onclick="closeModal()"
                        class="px-4 py-2 text-sm text-slate-400 hover:text-white border border-white/10 hover:border-white/25 rounded-xl transition-all">
                    Batal
                </button>
                <button type="submit"
                        class="px-5 py-2 text-sm font-semibold bg-amber-500 hover:bg-amber-400 text-black rounded-xl transition-all">
                    Simpan Langganan
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Tambah Pelanggan --}}
<div id="modal-add" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="closeAddModal()"></div>
    <div class="relative bg-slate-900 border border-white/10 rounded-2xl w-full max-w-lg shadow-2xl max-h-[90vh] overflow-y-auto">

        <div class="flex items-center justify-between px-6 py-4 border-b border-white/5 sticky top-0 bg-slate-900 z-10">
            <div>
                <h3 class="font-semibold text-white text-sm">Tambah Pelanggan</h3>
                <p class="text-xs text-slate-500 mt-0.5">Daftarkan pelanggan baru secara manual</p>
            </div>
            <button onclick="closeAddModal()" class="text-slate-500 hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        @if($errors->any())
        <div class="mx-6 mt-4 p-3 bg-red-500/10 border border-red-500/20 rounded-xl text-xs text-red-400 space-y-1">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
        @endif

        <form method="POST" action="{{ route('superadmin.registered-users.store') }}" class="px-6 py-5 space-y-4">
            @csrf

            {{-- Nama & Email --}}
            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-slate-400 uppercase tracking-wide">Nama</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           placeholder="John Doe"
                           class="w-full bg-slate-800 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500/60 transition-all">
                </div>
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-slate-400 uppercase tracking-wide">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           placeholder="john@company.com"
                           class="w-full bg-slate-800 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500/60 transition-all">
                </div>
            </div>

            {{-- Password --}}
            <div class="space-y-1.5">
                <label class="text-xs font-medium text-slate-400 uppercase tracking-wide">Password</label>
                <input type="password" name="password" required minlength="8"
                       placeholder="Minimal 8 karakter"
                       class="w-full bg-slate-800 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500/60 transition-all">
            </div>

            {{-- Nama Perusahaan --}}
            <div class="space-y-1.5">
                <label class="text-xs font-medium text-slate-400 uppercase tracking-wide">Nama Perusahaan</label>
                <input type="text" name="company_name" value="{{ old('company_name') }}" required
                       placeholder="PT. Contoh Sejahtera"
                       class="w-full bg-slate-800 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500/60 transition-all">
            </div>

            {{-- Package --}}
            <div class="space-y-2">
                <label class="text-xs font-medium text-slate-400 uppercase tracking-wide">Package</label>
                <div class="grid grid-cols-2 gap-2">
                    @foreach($packages as $pkg)
                    @php
                        $pkgColors = [
                            'hris'            => ['ring' => 'has-[:checked]:border-violet-500/60 has-[:checked]:bg-violet-500/10', 'icon' => 'text-violet-400 bg-violet-500/20'],
                            'task_management' => ['ring' => 'has-[:checked]:border-violet-500/60 has-[:checked]:bg-blue-500/10',   'icon' => 'text-blue-400 bg-blue-500/20'],
                        ];
                        $clr = $pkgColors[$pkg->slug] ?? ['ring' => 'has-[:checked]:border-indigo-500/60 has-[:checked]:bg-indigo-500/10', 'icon' => 'text-indigo-400 bg-indigo-500/20'];
                    @endphp
                    <label class="relative flex items-start gap-3 p-3 rounded-xl border border-white/10 cursor-pointer hover:border-white/20 transition-all {{ $clr['ring'] }}">
                        <input type="checkbox" name="packages[]" value="{{ $pkg->slug }}"
                               {{ in_array($pkg->slug, old('packages', [])) ? 'checked' : '' }}
                               class="mt-0.5 accent-indigo-500">
                        <div>
                            <p class="text-sm font-semibold text-white">{{ $pkg->name }}</p>
                            <p class="text-xs text-slate-500 leading-relaxed mt-0.5">{{ $pkg->description }}</p>
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- Masa Aktif --}}
            <div class="space-y-2">
                <label class="text-xs font-medium text-slate-400 uppercase tracking-wide">Masa Aktif</label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="relative flex flex-col gap-1 p-3 rounded-xl border border-white/10 cursor-pointer hover:border-amber-500/40 transition-all has-[:checked]:border-amber-500/60 has-[:checked]:bg-amber-500/10">
                        <input type="radio" name="type" value="lifetime" class="sr-only" checked onchange="toggleAddDateField(this)">
                        <p class="text-sm font-semibold text-white">Lifetime</p>
                        <p class="text-xs text-slate-500">Akses tanpa batas waktu</p>
                    </label>
                    <label class="relative flex flex-col gap-1 p-3 rounded-xl border border-white/10 cursor-pointer hover:border-violet-500/40 transition-all has-[:checked]:border-violet-500/60 has-[:checked]:bg-blue-500/10">
                        <input type="radio" name="type" value="expiry" class="sr-only" onchange="toggleAddDateField(this)">
                        <p class="text-sm font-semibold text-white">Batas Waktu</p>
                        <p class="text-xs text-slate-500">Tentukan tanggal berakhir</p>
                    </label>
                </div>
                <div id="add-date-field" class="hidden">
                    <input type="date" name="active_until" id="add-input-date"
                           class="w-full bg-slate-800 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white focus:outline-none focus:border-violet-500/60 transition-all"
                           min="{{ now()->addDay()->format('Y-m-d') }}">
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button" onclick="closeAddModal()"
                        class="px-4 py-2 text-sm text-slate-400 hover:text-white border border-white/10 hover:border-white/25 rounded-xl transition-all">
                    Batal
                </button>
                <button type="submit"
                        class="px-5 py-2 text-sm font-semibold bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl transition-all">
                    Tambah Pelanggan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modal-add').classList.remove('hidden');
}
function closeAddModal() {
    document.getElementById('modal-add').classList.add('hidden');
}
function toggleAddDateField(radio) {
    const field = document.getElementById('add-date-field');
    if (radio.value === 'expiry') {
        field.classList.remove('hidden');
    } else {
        field.classList.add('hidden');
        document.getElementById('add-input-date').value = '';
    }
}
@if($errors->any())
openAddModal();
@endif
</script>

<script>
function openModal(userId, userName, currentType, currentDate) {
    document.getElementById('modal-username').textContent = userName;
    document.getElementById('modal-avatar').textContent = userName.substring(0, 2).toUpperCase();
    document.getElementById('modal-form').action = `/superadmin/registered-users/${userId}/lifetime`;

    document.querySelectorAll('input[name="type"]').forEach(r => {
        r.checked = r.value === currentType;
    });

    const dateField = document.getElementById('date-field');
    const inputDate = document.getElementById('input-date');

    if (currentType === 'expiry' && currentDate) {
        dateField.classList.remove('hidden');
        inputDate.value = currentDate;
    } else {
        dateField.classList.add('hidden');
        inputDate.value = '';
    }

    document.getElementById('modal-lifetime').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('modal-lifetime').classList.add('hidden');
}

function toggleDateField(radio) {
    const dateField = document.getElementById('date-field');
    if (radio.value === 'expiry') {
        dateField.classList.remove('hidden');
    } else {
        dateField.classList.add('hidden');
        document.getElementById('input-date').value = '';
    }
}
</script>

@endsection
