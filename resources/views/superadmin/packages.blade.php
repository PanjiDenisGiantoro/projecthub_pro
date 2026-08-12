@extends('superadmin.layout')
@section('title', 'Paket & Harga')
@section('page-title', 'Paket & Harga')

@section('content')

<div x-data="packagesPage()">

    @php
        $tierPackages   = $packages->where('type', 'tier')->values();
        $modulePackages = $packages->where('type', 'module')->values();

        $theme = [
            'blue'   => ['bg' => 'bg-blue-500/15',    'text' => 'text-blue-400',    'ring' => 'ring-1 ring-inset ring-blue-500/30'],
            'green'  => ['bg' => 'bg-emerald-500/15', 'text' => 'text-emerald-400', 'ring' => 'ring-1 ring-inset ring-emerald-500/30'],
            'sky'    => ['bg' => 'bg-sky-500/15',     'text' => 'text-sky-400',     'ring' => 'ring-1 ring-inset ring-sky-500/30'],
            'purple' => ['bg' => 'bg-violet-500/15',  'text' => 'text-violet-400',  'ring' => 'ring-1 ring-inset ring-violet-500/30'],
            'orange' => ['bg' => 'bg-orange-500/15',  'text' => 'text-orange-400',  'ring' => 'ring-1 ring-inset ring-orange-500/30'],
            'navy'   => ['bg' => 'bg-indigo-500/15',  'text' => 'text-indigo-300',  'ring' => 'ring-1 ring-inset ring-indigo-500/30'],
        ];
        $fallbackTheme = ['bg' => 'bg-slate-500/15', 'text' => 'text-slate-300', 'ring' => 'ring-1 ring-inset ring-slate-500/30'];

        $icons = [
            'paper-plane' => '<line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>',
            'rocket'      => '<path d="M12 2c2 2 3 5 3 8 0 2-.5 4-1.5 5.5L12 22l-1.5-6.5C9.5 14 9 12 9 10c0-3 1-6 3-8z"/><circle cx="12" cy="9" r="1.5"/><path d="M8 14l-3 3 1 4 4-1"/><path d="M16 14l3 3-1 4-4-1"/>',
            'bar-chart'   => '<line x1="4" y1="20" x2="20" y2="20"/><rect x="6" y="10" width="3" height="8"/><rect x="11" y="6" width="3" height="12"/><rect x="16" y="13" width="3" height="5"/>',
            'trophy'      => '<path d="M8 4h8v6a4 4 0 0 1-8 0V4z"/><path d="M8 6H5a2 2 0 0 0 0 4h3"/><path d="M16 6h3a2 2 0 0 1 0 4h-3"/><path d="M12 14v3"/><path d="M9 20h6"/><path d="M10 17h4l1 3H9l1-3z"/>',
            'crown'       => '<path d="M3 18h18l-1.5-9-4 4-3.5-6-3.5 6-4-4L3 18z"/><path d="M3 21h18"/>',
            'building'    => '<rect x="5" y="3" width="14" height="18" rx="1"/><path d="M9 7h1M14 7h1M9 11h1M14 11h1M9 15h1M14 15h1"/><path d="M10 21v-3h4v3"/>',
        ];
        $fallbackIcon = '<circle cx="12" cy="12" r="9"/>';

        $check = '<svg class="w-5 h-5 text-emerald-400 mx-auto" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M9 12l2 2 4-4"/></svg>';

        $pkgJsonMap = $packages->mapWithKeys(fn ($p) => [$p->id => [
            'id'              => $p->id,
            'type'            => $p->type,
            'slug'            => $p->slug,
            'name'            => $p->name,
            'description'     => $p->description,
            'tagline'         => $p->tagline,
            'price'           => $p->price,
            'is_custom_price' => $p->price === null,
            'price_display'   => $p->price_display,
            'price_period'    => $p->price_period,
            'duration_days'   => $p->duration_days,
            'max_users'       => $p->max_users,
            'is_custom_users' => $p->max_users === null,
            'is_active'       => (bool) $p->is_active,
            'is_popular'      => (bool) $p->is_popular,
            'cta_label'       => $p->cta_label,
            'cta_type'        => $p->cta_type ?? 'register',
            'sort_order'      => $p->sort_order,
            'icon'            => $p->icon ?? 'paper-plane',
            'color'           => $p->color ?? 'blue',
            'fitur_text'      => $p->fitur_text,
            'hris_feature'    => $p->hris_feature,
            'features'        => $p->features->pluck('label')->values(),
        ]]);
    @endphp

    <div class="flex items-center justify-between mb-5">
        <p class="text-sm text-slate-400">Total: <span class="text-white font-semibold">{{ $packages->count() }}</span> paket</p>
        <button type="button" @click="openCreate()"
                class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl transition-all">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Tambah Paket
        </button>
    </div>

    {{-- Tabel perbandingan paket (kartu harga) --}}
    <div class="mb-3">
        <h3 class="text-sm font-semibold text-white">Pilihan Paket yang Fleksibel untuk Tim Anda</h3>
        <p class="text-xs text-slate-500 mt-0.5">Kartu harga yang tampil di landing page & halaman daftar.</p>
    </div>

    @if($tierPackages->isEmpty())
        <div class="bg-slate-800/60 border border-white/5 rounded-2xl px-6 py-12 text-center text-slate-500 mb-6">
            Belum ada paket kartu harga.
        </div>
    @else
        <div class="bg-slate-800/60 border border-white/5 rounded-2xl overflow-hidden mb-6">
            <div class="overflow-x-auto">
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="border-b border-white/5">
                            <th class="sticky left-0 z-10 bg-slate-800 text-left px-5 py-4 align-bottom w-52 min-w-[190px]">
                                <p class="text-xs font-semibold text-slate-300 uppercase tracking-wide">Paket</p>
                                <p class="text-[11px] text-slate-600 mt-1 font-normal normal-case">Pilih paket terbaik sesuai kebutuhan tim Anda</p>
                            </th>
                            @foreach($tierPackages as $p)
                                @php $t = $theme[$p->color] ?? $fallbackTheme; @endphp
                                <th class="px-5 py-4 text-center align-bottom min-w-[190px] {{ $p->is_popular ? 'bg-white/[0.03]' : '' }}">
                                    <div class="flex flex-col items-center gap-2">
                                        <span class="w-11 h-11 rounded-full flex items-center justify-center {{ $t['bg'] }} {{ $t['text'] }}">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                                {!! $icons[$p->icon] ?? $fallbackIcon !!}
                                            </svg>
                                        </span>
                                        <span class="font-semibold text-white text-sm flex items-center gap-1.5">
                                            {{ $p->name }}
                                            @unless($p->is_active)
                                                <span class="w-1.5 h-1.5 rounded-full bg-red-500 shrink-0" title="Nonaktif"></span>
                                            @endunless
                                        </span>
                                        <span class="text-lg font-bold text-white leading-tight">{{ $p->priceDisplay() }}</span>
                                        <span class="text-[11px] text-slate-500">{{ $p->price_period }}</span>
                                        @if($p->is_popular)
                                            <span class="{{ $t['bg'] }} {{ $t['text'] }} text-[10px] font-medium px-2 py-0.5 rounded-full">★ Paling Populer</span>
                                        @endif
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <tr>
                            <td class="sticky left-0 z-10 bg-slate-800 px-5 py-3 text-xs font-medium text-slate-400">Harga Package</td>
                            @foreach($tierPackages as $p)
                                <td class="px-5 py-3 text-center text-slate-200 {{ $p->is_popular ? 'bg-white/[0.03]' : '' }}">{{ $p->priceDisplay() }}</td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="sticky left-0 z-10 bg-slate-800 px-5 py-3 text-xs font-medium text-slate-400">Pengguna</td>
                            @foreach($tierPackages as $p)
                                <td class="px-5 py-3 text-center text-slate-200 {{ $p->is_popular ? 'bg-white/[0.03]' : '' }}">{{ $p->maxUsersDisplay() }}</td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="sticky left-0 z-10 bg-slate-800 px-5 py-3 text-xs font-medium text-slate-400">Fitur</td>
                            @foreach($tierPackages as $p)
                                <td class="px-5 py-3 text-center text-slate-300 {{ $p->is_popular ? 'bg-white/[0.03]' : '' }}">{{ $p->fitur_text ?? '—' }}</td>
                            @endforeach
                        </tr>
                        @foreach(['Unlimited Storage', 'Support Intensif', 'iOS, Web, Android', 'AI'] as $label)
                            <tr>
                                <td class="sticky left-0 z-10 bg-slate-800 px-5 py-3 text-xs font-medium text-slate-400">{{ $label }}</td>
                                @foreach($tierPackages as $p)
                                    <td class="px-5 py-3 {{ $p->is_popular ? 'bg-white/[0.03]' : '' }}">{!! $check !!}</td>
                                @endforeach
                            </tr>
                        @endforeach
                        <tr>
                            <td class="sticky left-0 z-10 bg-slate-800 px-5 py-3 text-xs font-medium text-slate-400 align-top">Fitur HRIS</td>
                            @foreach($tierPackages as $p)
                                <td class="px-5 py-3 text-center text-slate-300 align-top {{ $p->is_popular ? 'bg-white/[0.03]' : '' }}">{{ $p->hris_feature ?? '—' }}</td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="sticky left-0 z-10 bg-slate-800 px-5 py-3 text-xs font-medium text-slate-400">Status</td>
                            @foreach($tierPackages as $p)
                                <td class="px-5 py-3 text-center {{ $p->is_popular ? 'bg-white/[0.03]' : '' }}">
                                    @if($p->is_active)
                                        <span class="bg-green-500/15 text-green-400 text-xs font-medium px-2.5 py-1 rounded-full">Aktif</span>
                                    @else
                                        <span class="bg-red-500/15 text-red-400 text-xs font-medium px-2.5 py-1 rounded-full">Nonaktif</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="sticky left-0 z-10 bg-slate-800 px-5 py-4 text-xs font-medium text-slate-400">Aksi</td>
                            @foreach($tierPackages as $p)
                                <td class="px-5 py-4 {{ $p->is_popular ? 'bg-white/[0.03]' : '' }}">
                                    <div class="flex flex-col items-center gap-1.5">
                                        <button type="button" @click="openEdit(@js($pkgJsonMap[$p->id]))"
                                                class="text-xs text-slate-400 hover:text-white transition-colors px-3 py-1 rounded-lg hover:bg-white/5 w-full text-center">
                                            Edit
                                        </button>
                                        <form method="POST" action="{{ route('superadmin.packages.toggle', $p) }}" class="w-full">
                                            @csrf @method('PATCH')
                                            <button type="submit"
                                                    class="text-xs {{ $p->is_active ? 'text-red-400 hover:text-red-300' : 'text-green-400 hover:text-green-300' }} transition-colors px-3 py-1 rounded-lg hover:bg-white/5 w-full text-center">
                                                {{ $p->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                        <button type="button"
                                                @click="deleteTarget = { id: {{ $p->id }}, name: @js($p->name) }"
                                                class="text-xs text-red-500 hover:text-red-400 transition-colors px-3 py-1 rounded-lg hover:bg-white/5 w-full text-center">
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Modul Add-on --}}
    <div class="mb-3">
        <h3 class="text-sm font-semibold text-white">Modul Add-on</h3>
        <p class="text-xs text-slate-500 mt-0.5">Modul tambahan yang dibundel ke paket, bukan kartu harga tersendiri.</p>
    </div>

    <div class="bg-slate-800/60 border border-white/5 rounded-2xl overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-white/5 text-xs text-slate-500 uppercase tracking-wide">
                    <th class="text-left px-6 py-3 font-medium">Modul</th>
                    <th class="text-left px-6 py-3 font-medium">Harga</th>
                    <th class="text-center px-6 py-3 font-medium">Pengguna</th>
                    <th class="text-center px-6 py-3 font-medium">Status</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @forelse($modulePackages as $p)
                <tr class="hover:bg-white/2 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center text-xs font-bold text-white shrink-0"
                                 style="background: linear-gradient(135deg, #6366f1, #8b5cf6)">
                                {{ strtoupper(substr($p->name, 0, 2)) }}
                            </div>
                            <div>
                                <p class="font-semibold text-white">{{ $p->name }}</p>
                                <p class="text-xs text-slate-500 font-mono">{{ $p->slug }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <p class="text-slate-200 font-medium">{{ $p->priceDisplay() }}</p>
                        <p class="text-xs text-slate-500">{{ $p->price_period ?? '—' }}</p>
                    </td>
                    <td class="px-6 py-4 text-center text-slate-300">{{ $p->users_count }}</td>
                    <td class="px-6 py-4 text-center">
                        @if($p->is_active)
                            <span class="bg-green-500/15 text-green-400 text-xs font-medium px-2.5 py-1 rounded-full">Aktif</span>
                        @else
                            <span class="bg-red-500/15 text-red-400 text-xs font-medium px-2.5 py-1 rounded-full">Nonaktif</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-1">
                            <button type="button" @click="openEdit(@js($pkgJsonMap[$p->id]))"
                                    class="text-xs text-slate-400 hover:text-white transition-colors px-3 py-1.5 rounded-lg hover:bg-white/5">
                                Edit
                            </button>
                            <form method="POST" action="{{ route('superadmin.packages.toggle', $p) }}">
                                @csrf @method('PATCH')
                                <button type="submit"
                                        class="text-xs {{ $p->is_active ? 'text-red-400 hover:text-red-300' : 'text-green-400 hover:text-green-300' }} transition-colors px-3 py-1.5 rounded-lg hover:bg-white/5">
                                    {{ $p->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>
                            <button type="button"
                                    @click="deleteTarget = { id: {{ $p->id }}, name: @js($p->name) }"
                                    class="text-xs text-red-500 hover:text-red-400 transition-colors px-3 py-1.5 rounded-lg hover:bg-white/5">
                                Hapus
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-slate-500">Belum ada modul add-on.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Modal Form (Tambah / Edit Paket) --}}
    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4 py-8" style="display:none;">
        <div @click.outside="modalOpen = false"
             class="bg-slate-900 border border-white/10 rounded-2xl w-full max-w-2xl shadow-2xl max-h-[90vh] overflow-y-auto">

            <div class="flex items-center justify-between px-6 py-4 border-b border-white/5 sticky top-0 bg-slate-900 z-10">
                <div>
                    <h3 class="font-semibold text-white text-sm" x-text="mode === 'edit' ? 'Edit Paket' : 'Tambah Paket'"></h3>
                    <p class="text-xs text-slate-500 mt-0.5">Kartu harga & modul tampil otomatis di landing page dan halaman daftar</p>
                </div>
                <button type="button" @click="modalOpen = false" class="text-slate-500 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form method="POST" :action="mode === 'edit' ? ('/superadmin/packages/' + form.id) : '{{ route('superadmin.packages.store') }}'" class="px-6 py-5 space-y-4">
                @csrf
                <template x-if="mode === 'edit'">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                {{-- Tipe & Slug --}}
                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-slate-400 uppercase tracking-wide">Tipe</label>
                        <select name="type" x-model="form.type"
                                class="w-full bg-slate-800 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white focus:outline-none focus:border-indigo-500/60">
                            <option value="tier">Kartu Harga (tampil di landing/daftar)</option>
                            <option value="module">Modul add-on</option>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-slate-400 uppercase tracking-wide">Slug</label>
                        <input type="text" name="slug" x-model="form.slug" required
                               :readonly="mode === 'edit'"
                               :class="mode === 'edit' ? 'opacity-60 cursor-not-allowed' : ''"
                               placeholder="mis. basic, standard, premium"
                               class="w-full bg-slate-800 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500/60 font-mono">
                        <p class="text-[11px] text-slate-600" x-show="mode === 'edit'">Slug dipakai kode aplikasi (mis. alur daftar & billing), tidak bisa diubah setelah dibuat.</p>
                    </div>
                </div>

                {{-- Nama & Tagline --}}
                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-slate-400 uppercase tracking-wide">Nama Paket</label>
                        <input type="text" name="name" x-model="form.name" required placeholder="Pro"
                               class="w-full bg-slate-800 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500/60">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-slate-400 uppercase tracking-wide">Tagline</label>
                        <input type="text" name="tagline" x-model="form.tagline" placeholder="Untuk tim yang sedang berkembang."
                               class="w-full bg-slate-800 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500/60">
                    </div>
                </div>

                {{-- Deskripsi --}}
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-slate-400 uppercase tracking-wide">Deskripsi</label>
                    <textarea name="description" x-model="form.description" rows="2" placeholder="Deskripsi singkat paket ini"
                              class="w-full bg-slate-800 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500/60"></textarea>
                </div>

                {{-- Ikon & Warna --}}
                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-slate-400 uppercase tracking-wide">Ikon Kolom</label>
                        <select name="icon" x-model="form.icon"
                                class="w-full bg-slate-800 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white focus:outline-none focus:border-indigo-500/60">
                            <option value="paper-plane">Paper Plane</option>
                            <option value="rocket">Rocket</option>
                            <option value="bar-chart">Bar Chart</option>
                            <option value="trophy">Trophy</option>
                            <option value="crown">Crown</option>
                            <option value="building">Building</option>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-slate-400 uppercase tracking-wide">Warna Kolom</label>
                        <select name="color" x-model="form.color"
                                class="w-full bg-slate-800 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white focus:outline-none focus:border-indigo-500/60">
                            <option value="blue">Biru</option>
                            <option value="green">Hijau</option>
                            <option value="sky">Biru Muda</option>
                            <option value="purple">Ungu</option>
                            <option value="orange">Oranye</option>
                            <option value="navy">Navy</option>
                        </select>
                    </div>
                </div>

                {{-- Harga --}}
                <div class="space-y-2 p-3 bg-slate-800/60 rounded-xl border border-white/5">
                    <label class="flex items-center gap-2 text-xs font-medium text-slate-300">
                        <input type="checkbox" name="is_custom_price" value="1" x-model="form.is_custom_price" class="accent-indigo-500">
                        Harga custom (tanpa angka tetap, mis. Enterprise → "Contact Sales")
                    </label>
                    <div class="grid grid-cols-3 gap-3">
                        <div class="space-y-1.5">
                            <label class="text-xs font-medium text-slate-400 uppercase tracking-wide">Harga (Rp)</label>
                            <input type="number" name="price" x-model.number="form.price" min="0" :disabled="form.is_custom_price"
                                   :class="form.is_custom_price ? 'opacity-40 cursor-not-allowed' : ''"
                                   class="w-full bg-slate-800 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white focus:outline-none focus:border-indigo-500/60">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-xs font-medium text-slate-400 uppercase tracking-wide">Teks Harga (opsional)</label>
                            <input type="text" name="price_display" x-model="form.price_display" placeholder="mis. Rp 149,999 / Contact Sales"
                                   class="w-full bg-slate-800 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500/60">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-xs font-medium text-slate-400 uppercase tracking-wide">Periode</label>
                            <input type="text" name="price_period" x-model="form.price_period" placeholder="/ package"
                                   class="w-full bg-slate-800 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500/60">
                        </div>
                    </div>
                </div>

                {{-- Pengguna --}}
                <div class="space-y-2 p-3 bg-slate-800/60 rounded-xl border border-white/5">
                    <label class="flex items-center gap-2 text-xs font-medium text-slate-300">
                        <input type="checkbox" name="is_custom_users" value="1" x-model="form.is_custom_users" class="accent-indigo-500">
                        Pengguna custom (tanpa batas angka tetap, mis. Enterprise → "Custom")
                    </label>
                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-slate-400 uppercase tracking-wide">Maks. Pengguna</label>
                        <input type="number" name="max_users" x-model.number="form.max_users" min="0" :disabled="form.is_custom_users"
                               :class="form.is_custom_users ? 'opacity-40 cursor-not-allowed' : ''"
                               class="w-full bg-slate-800 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white focus:outline-none focus:border-indigo-500/60">
                    </div>
                </div>

                {{-- Fitur & Fitur HRIS (baris tabel perbandingan) --}}
                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-slate-400 uppercase tracking-wide">Teks Baris "Fitur"</label>
                        <input type="text" name="fitur_text" x-model="form.fitur_text" placeholder="mis. Akses semua fitur Pro"
                               class="w-full bg-slate-800 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500/60">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-slate-400 uppercase tracking-wide">Teks Baris "Fitur HRIS"</label>
                        <input type="text" name="hris_feature" x-model="form.hris_feature" placeholder="mis. HRIS Pro Plus"
                               class="w-full bg-slate-800 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500/60">
                    </div>
                </div>

                {{-- Durasi, Urutan --}}
                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-slate-400 uppercase tracking-wide">Durasi Langganan (hari)</label>
                        <input type="number" name="duration_days" x-model.number="form.duration_days" min="1" placeholder="30, kosongkan jika tidak berlaku"
                               class="w-full bg-slate-800 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500/60">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-slate-400 uppercase tracking-wide">Urutan Tampil</label>
                        <input type="number" name="sort_order" x-model.number="form.sort_order" min="0"
                               class="w-full bg-slate-800 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white focus:outline-none focus:border-indigo-500/60">
                    </div>
                </div>

                {{-- CTA --}}
                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-slate-400 uppercase tracking-wide">Teks Tombol</label>
                        <input type="text" name="cta_label" x-model="form.cta_label" placeholder="Mulai Gratis"
                               class="w-full bg-slate-800 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500/60">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-slate-400 uppercase tracking-wide">Aksi Tombol</label>
                        <select name="cta_type" x-model="form.cta_type"
                                class="w-full bg-slate-800 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white focus:outline-none focus:border-indigo-500/60">
                            <option value="register">Arahkan ke halaman daftar (bisa dipilih user)</option>
                            <option value="contact">Hubungi sales (mailto, tidak bisa dipilih langsung)</option>
                        </select>
                    </div>
                </div>

                {{-- Toggles --}}
                <div class="flex items-center gap-6">
                    <label class="flex items-center gap-2 text-xs font-medium text-slate-300">
                        <input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="accent-indigo-500">
                        Aktif (tampil di landing/daftar)
                    </label>
                    <label class="flex items-center gap-2 text-xs font-medium text-slate-300">
                        <input type="checkbox" name="is_popular" value="1" x-model="form.is_popular" class="accent-amber-500">
                        Badge "Paling Populer"
                    </label>
                </div>

                {{-- Fitur (checklist) --}}
                <div class="space-y-2">
                    <label class="text-xs font-medium text-slate-400 uppercase tracking-wide">Daftar Fitur (checklist di landing/daftar)</label>
                    <div class="space-y-2">
                        <template x-for="(feature, i) in form.features" :key="i">
                            <div class="flex items-center gap-2">
                                <input type="text" :name="`features[${i}]`" x-model="form.features[i]"
                                       placeholder="mis. Project unlimited"
                                       class="flex-1 bg-slate-800 border border-white/10 rounded-xl px-3 py-2 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500/60">
                                <button type="button" @click="form.features.splice(i, 1)"
                                        class="text-slate-500 hover:text-red-400 transition-colors p-1.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </template>
                    </div>
                    <button type="button" @click="form.features.push('')"
                            class="inline-flex items-center gap-1.5 text-xs text-indigo-400 hover:text-indigo-300 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Tambah fitur
                    </button>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2 border-t border-white/5">
                    <button type="button" @click="modalOpen = false"
                            class="px-4 py-2 text-sm text-slate-400 hover:text-white border border-white/10 hover:border-white/25 rounded-xl transition-all">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2 text-sm font-semibold bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl transition-all">
                        Simpan Paket
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal konfirmasi hapus --}}
    <div x-show="deleteTarget" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4" style="display:none;">
        <div @click.outside="deleteTarget = null" class="bg-slate-800 border border-white/10 rounded-2xl p-6 w-full max-w-md">
            <h3 class="text-white font-semibold text-base">Hapus Paket?</h3>
            <p class="text-slate-400 text-sm mt-2">
                Ini akan menghapus paket <span class="text-white font-medium" x-text="deleteTarget?.name"></span> secara permanen.
                Kalau paket masih dipakai pelanggan, sebaiknya nonaktifkan saja daripada dihapus.
            </p>
            <form method="POST" :action="'/superadmin/packages/' + deleteTarget?.id">
                @csrf @method('DELETE')
                <div class="flex items-center justify-end gap-2 mt-5">
                    <button type="button" @click="deleteTarget = null"
                            class="text-xs text-slate-400 hover:text-white px-4 py-2 rounded-lg hover:bg-white/5">
                        Batal
                    </button>
                    <button type="submit" class="text-xs font-medium px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white transition-colors">
                        Hapus Permanen
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function packagesPage() {
    const emptyForm = () => ({
        id: null, type: 'tier', slug: '', name: '', description: '', tagline: '',
        price: 0, is_custom_price: false, price_display: '', price_period: '',
        duration_days: 30, max_users: 0, is_custom_users: false,
        is_active: true, is_popular: false,
        cta_label: '', cta_type: 'register', sort_order: 0,
        icon: 'paper-plane', color: 'blue', fitur_text: '', hris_feature: '',
        features: [],
    });

    return {
        modalOpen: false,
        mode: 'create',
        deleteTarget: null,
        form: emptyForm(),

        openCreate() {
            this.mode = 'create';
            this.form = emptyForm();
            this.modalOpen = true;
        },
        openEdit(pkg) {
            this.mode = 'edit';
            this.form = { ...emptyForm(), ...pkg };
            this.modalOpen = true;
        },
    }
}
</script>

@endsection
