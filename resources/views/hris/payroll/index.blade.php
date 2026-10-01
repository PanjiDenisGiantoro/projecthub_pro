@extends('layouts.app')
@section('title', 'Penggajian')
@section('page-title', 'Penggajian')

@section('content')
@php
    $me          = auth()->user();
    $canGenerate = $me->can('generate payroll');
    $canFinalize = $me->can('update payroll');
    $canManage   = $canGenerate || $canFinalize;

    $period      = \Carbon\Carbon::create($year, $month, 1);
    $periodLabel = $period->copy()->locale('id')->isoFormat('MMMM Y');
    $prev        = $period->copy()->subMonth();
    $next        = $period->copy()->addMonth();

    $rupiah      = fn($v) => 'Rp ' . number_format($v, 0, ',', '.');
    $statusMeta  = [
        'draft'     => ['Draft', 'bg-gray-100 text-gray-700'],
        'finalized' => ['Final', 'bg-blue-100 text-blue-700'],
        'paid'      => ['Dibayar', 'bg-green-100 text-green-700'],
    ];

    $eligibleCount  = $employees->where('has_salary', true)->count();
    $draftCount     = (int) ($statusCounts['draft'] ?? 0);
    $finalCount     = (int) ($statusCounts['finalized'] ?? 0) + (int) ($statusCounts['paid'] ?? 0);
    $generatedCount = $draftCount + $finalCount;
    $currentStep    = $draftCount > 0 ? 2 : (($generatedCount > 0 && $generatedCount >= $eligibleCount) ? 3 : 1);

    $selectableIds   = $employees->filter(fn($e) => $e->has_salary && !in_array($existingStatus[$e->id] ?? null, ['finalized', 'paid']))->pluck('id')->values();
    $notGeneratedIds = $employees->filter(fn($e) => $e->has_salary && !isset($existingStatus[$e->id]))->pluck('id')->values();
    $noSalaryCount   = $employees->where('has_salary', false)->count();

    $pageDraftIds = $payrolls->getCollection()->where('status', 'draft')->pluck('id')->values();
    $showCheckbox = $canFinalize && $pageDraftIds->isNotEmpty();
    $colCount     = $showCheckbox ? 7 : 6;
@endphp

<div class="space-y-5 pt-5">

    {{-- Header + pemilih periode --}}
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Penggajian</h1>
            <p class="text-sm text-gray-500 mt-0.5">Payroll periode <span class="font-medium text-gray-700">{{ $periodLabel }}</span></p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <div class="inline-flex items-center rounded-lg border border-gray-200 bg-white">
                <a href="{{ route('hris.payroll.index', ['year' => $prev->year, 'month' => $prev->month]) }}"
                   class="px-2.5 py-2 text-gray-400 hover:text-gray-700" title="Bulan sebelumnya">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <form method="GET" class="flex items-center border-x border-gray-200">
                    <select name="month" onchange="this.form.submit()" class="border-0 bg-transparent text-sm font-medium text-gray-700 py-2 pl-3 pr-8 focus:ring-0 cursor-pointer" aria-label="Bulan">
                        @foreach(range(1, 12) as $m)
                        <option value="{{ $m }}" @selected($m == $month)>{{ \Carbon\Carbon::create(null, $m)->locale('id')->isoFormat('MMMM') }}</option>
                        @endforeach
                    </select>
                    <select name="year" onchange="this.form.submit()" class="border-0 bg-transparent text-sm font-medium text-gray-700 py-2 pl-1 pr-8 focus:ring-0 cursor-pointer" aria-label="Tahun">
                        @for($y = now()->year + 1; $y >= now()->year - 3; $y--)
                        <option value="{{ $y }}" @selected($y == $year)>{{ $y }}</option>
                        @endfor
                    </select>
                </form>
                <a href="{{ route('hris.payroll.index', ['year' => $next->year, 'month' => $next->month]) }}"
                   class="px-2.5 py-2 text-gray-400 hover:text-gray-700" title="Bulan berikutnya">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
            @if(!$period->isSameMonth(now()))
            <a href="{{ route('hris.payroll.index') }}" class="text-sm text-blue-600 hover:text-blue-800 px-1">Bulan ini</a>
            @endif
            @can('update payroll')
            <a href="{{ route('hris.payroll.setting') }}" class="fl-btn fl-btn-secondary">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Pengaturan
            </a>
            @endcan
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-xl px-4 py-3">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-xl px-4 py-3">{{ session('error') }}</div>
    @endif
    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-xl px-4 py-3">{{ $errors->first() }}</div>
    @endif

    @if($canManage)
    {{-- Ringkasan periode --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        @foreach([
            ['Karyawan digenerate', "{$generatedCount} / {$eligibleCount}", 'text-gray-900'],
            ['Total Bruto', $rupiah($totals->bruto), 'text-gray-900'],
            ['Total Potongan', $rupiah($totals->potongan), 'text-red-600'],
            ['Total Gaji Bersih', $rupiah($totals->bersih), 'text-green-700'],
        ] as [$label, $value, $color])
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
            <p class="text-xs font-medium text-gray-500">{{ $label }}</p>
            <p class="mt-1 text-lg font-bold {{ $color }}">{{ $value }}</p>
        </div>
        @endforeach
    </div>

    {{-- Alur: Generate → Review & Finalize → Selesai --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden"
         x-data="{
            genOpen: false,
            search: '',
            selected: [],
            selectable: @js($selectableIds),
            notGenerated: @js($notGeneratedIds),
            get allSelected() { return this.selectable.length > 0 && this.selected.length === this.selectable.length },
            toggleAll() { this.selected = this.allSelected ? [] : [...this.selectable] },
            selectNotGenerated() { this.selected = [...this.notGenerated] },
         }">
        <div class="grid grid-cols-1 md:grid-cols-3 bg-gray-100" style="gap:1px">
            @foreach([
                1 => ['Generate', "{$generatedCount} dari {$eligibleCount} karyawan sudah digenerate"],
                2 => ['Review & Finalize', $draftCount ? "{$draftCount} payroll draft perlu dicek lalu difinalize" : 'Tidak ada draft yang menunggu'],
                3 => ['Selesai', $finalCount ? "{$finalCount} slip gaji final & siap diunduh" : 'Belum ada slip gaji final'],
            ] as $n => [$label, $desc])
            @php $done = $n < $currentStep || ($n === 3 && $currentStep === 3); $active = $n === $currentStep && !$done; @endphp
            <div class="bg-white p-4 flex flex-col justify-between gap-3">
                <div class="flex items-start gap-3">
                    <span class="w-7 h-7 shrink-0 rounded-full flex items-center justify-center text-xs font-bold
                        {{ $done ? 'bg-green-100 text-green-700' : ($active ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-500') }}">
                        @if($done)
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        @else {{ $n }} @endif
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold {{ $active ? 'text-blue-700' : 'text-gray-900' }}">{{ $label }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $desc }}</p>
                    </div>
                </div>

                @if($n === 1 && $canGenerate)
                <div class="flex items-center gap-2 flex-wrap">
                    @if($selectableIds->isNotEmpty())
                    <form action="{{ route('hris.payroll.generate') }}" method="POST"
                          onsubmit="return confirm('Generate payroll {{ $periodLabel }} untuk {{ $selectableIds->count() }} karyawan? Payroll draft yang sudah ada akan dihitung ulang.')">
                        @csrf
                        @foreach($selectableIds as $id)
                        <input type="hidden" name="user_ids[]" value="{{ $id }}">
                        @endforeach
                        <input type="hidden" name="year" value="{{ $year }}">
                        <input type="hidden" name="month" value="{{ $month }}">
                        <button @disabled($selectableIds->isEmpty())
                                class="fl-btn {{ $active ? 'fl-btn-primary' : 'fl-btn-secondary' }} disabled:opacity-50 disabled:cursor-not-allowed">
                            Generate Semua ({{ $selectableIds->count() }})
                        </button>
                    </form>
                    @endif
                    <button type="button" @click="genOpen = !genOpen" class="text-sm font-medium text-blue-600 hover:text-blue-800">
                        <span x-text="genOpen ? 'Tutup pilihan' : 'Pilih karyawan…'"></span>
                    </button>
                </div>
                @endif

                @if($n === 2 && $draftCount > 0 && $canFinalize)
                <form action="{{ route('hris.payroll.finalizeBulk') }}" method="POST"
                      onsubmit="return confirm('Finalize {{ $draftCount }} payroll draft periode {{ $periodLabel }}? Payroll final tidak bisa digenerate ulang dan karyawan akan menerima notifikasi slip gaji.')">
                    @csrf
                    <input type="hidden" name="all" value="1">
                    <input type="hidden" name="year" value="{{ $year }}">
                    <input type="hidden" name="month" value="{{ $month }}">
                    <button class="fl-btn bg-green-600 text-white hover:bg-green-700">
                        Finalize Semua Draft ({{ $draftCount }})
                    </button>
                </form>
                @endif
            </div>
            @endforeach
        </div>

        @if($canGenerate)
        {{-- Pilih karyawan untuk digenerate --}}
        <div x-show="genOpen" x-cloak class="border-t border-gray-100 p-4">
            <form action="{{ route('hris.payroll.generate') }}" method="POST" class="space-y-3">
                @csrf
                <input type="hidden" name="year" value="{{ $year }}">
                <input type="hidden" name="month" value="{{ $month }}">

                <div class="flex items-center gap-3 flex-wrap">
                    <input type="text" x-model="search" placeholder="Cari karyawan..." class="fl-input w-full sm:w-64">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                        <input type="checkbox" :checked="allSelected" @change="toggleAll()" class="rounded border-gray-300">
                        Pilih semua
                    </label>
                    <button type="button" @click="selectNotGenerated()" class="text-sm text-blue-600 hover:text-blue-800">
                        Belum digenerate ({{ $notGeneratedIds->count() }})
                    </button>
                    <button type="button" @click="selected = []" class="text-sm text-gray-500 hover:text-gray-700">Kosongkan</button>
                </div>

                <div class="max-h-72 overflow-y-auto border border-gray-100 rounded-xl divide-y divide-gray-50">
                    @forelse($employees as $emp)
                    @php $st = $existingStatus[$emp->id] ?? null; $locked = !$emp->has_salary || in_array($st, ['finalized', 'paid']); @endphp
                    <label x-show="search === '' || @js(strtolower($emp->name)).includes(search.toLowerCase())"
                           class="flex items-center justify-between gap-3 px-3 py-2 text-sm {{ $locked ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer hover:bg-gray-50' }}">
                        <span class="flex items-center gap-2">
                            <input type="checkbox" name="user_ids[]" value="{{ $emp->id }}" x-model.number="selected"
                                   class="rounded border-gray-300" @disabled($locked)>
                            <span class="text-gray-900">{{ $emp->name }}</span>
                        </span>
                        @if(!$emp->has_salary)
                        <a href="{{ route('hris.salary.create', $emp) }}" class="text-xs px-2 py-0.5 rounded-full font-medium bg-amber-100 text-amber-700">
                            Belum ada data gaji · Atur
                        </a>
                        @elseif($st)
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $statusMeta[$st][1] }}">{{ $statusMeta[$st][0] }}</span>
                        @endif
                    </label>
                    @empty
                    <p class="px-3 py-4 text-sm text-gray-400 text-center">Tidak ada karyawan aktif.</p>
                    @endforelse
                </div>

                <div class="flex items-center gap-3 flex-wrap">
                    <button :disabled="selected.length === 0" class="fl-btn fl-btn-primary disabled:opacity-50 disabled:cursor-not-allowed">
                        Generate Terpilih (<span x-text="selected.length"></span>)
                    </button>
                    <span class="text-xs text-gray-500">
                        Payroll final/dibayar tidak bisa digenerate ulang.
                        @if($noSalaryCount) {{ $noSalaryCount }} karyawan belum punya data gaji. @endif
                    </span>
                </div>
            </form>
        </div>
        @endif
    </div>
    @endif

    {{-- Daftar payroll --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm"
         x-data="{
            picked: [],
            drafts: @js($pageDraftIds),
            get allPicked() { return this.drafts.length > 0 && this.picked.length === this.drafts.length },
            toggleAll() { this.picked = this.allPicked ? [] : [...this.drafts] },
         }">

        {{-- Toolbar: filter status + cari --}}
        <div class="flex items-center justify-between gap-3 flex-wrap px-4 pt-4">
            <div class="flex items-center gap-1 flex-wrap">
                @php $base = ['year' => $year, 'month' => $month, 'q' => $search ?: null]; @endphp
                @foreach([null => 'Semua', 'draft' => 'Draft', 'finalized' => 'Final', 'paid' => 'Dibayar'] as $key => $label)
                @php
                    $count = $key ? (int) ($statusCounts[$key] ?? 0) : (int) $statusCounts->sum();
                    $isOn  = ($status ?? null) === ($key ?: null);
                @endphp
                <a href="{{ route('hris.payroll.index', array_filter($base + ['status' => $key ?: null])) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium {{ $isOn ? 'bg-blue-50 text-blue-700' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">
                    {{ $label }}
                    <span class="text-xs {{ $isOn ? 'text-blue-500' : 'text-gray-400' }}">{{ $count }}</span>
                </a>
                @endforeach
            </div>
            @if($canManage)
            <form method="GET" class="w-full sm:w-64">
                <input type="hidden" name="year" value="{{ $year }}">
                <input type="hidden" name="month" value="{{ $month }}">
                @if($status)<input type="hidden" name="status" value="{{ $status }}">@endif
                <div class="fl-input-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/></svg>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Cari nama karyawan..." class="fl-input">
                </div>
            </form>
            @endif
        </div>

        {{-- Bar aksi massal --}}
        @if($showCheckbox)
        <div x-show="picked.length > 0" x-cloak
             class="mx-4 mt-3 flex items-center justify-between gap-3 flex-wrap px-4 py-2.5 rounded-xl bg-blue-50 border border-blue-100">
            <span class="text-sm text-blue-900"><span class="font-semibold" x-text="picked.length"></span> payroll draft dipilih</span>
            <div class="flex items-center gap-3">
                <button type="button" @click="picked = []" class="text-sm text-gray-600 hover:text-gray-800">Batal</button>
                <form action="{{ route('hris.payroll.finalizeBulk') }}" method="POST"
                      @submit="if (!confirm('Finalize ' + picked.length + ' payroll terpilih?')) $event.preventDefault()">
                    @csrf
                    <template x-for="id in picked" :key="id">
                        <input type="hidden" name="payroll_ids[]" :value="id">
                    </template>
                    <button class="fl-btn bg-green-600 text-white hover:bg-green-700">
                        Finalize Terpilih (<span x-text="picked.length"></span>)
                    </button>
                </form>
            </div>
        </div>
        @endif

        <div class="overflow-x-auto mt-3">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-y border-gray-100">
                    <tr>
                        @if($showCheckbox)
                        <th class="pl-4 py-3 w-10">
                            <input type="checkbox" :checked="allPicked" @change="toggleAll()"
                                   title="Pilih semua draft di halaman ini" class="rounded border-gray-300">
                        </th>
                        @endif
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Karyawan</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Bruto</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Potongan</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Gaji Bersih</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($payrolls as $p)
                    <tr class="hover:bg-gray-50" :class="picked.includes({{ $p->id }}) && 'bg-blue-50'">
                        @if($showCheckbox)
                        <td class="pl-4 py-3">
                            @if($p->status === 'draft')
                            <input type="checkbox" value="{{ $p->id }}" x-model.number="picked" class="rounded border-gray-300">
                            @endif
                        </td>
                        @endif
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <span class="w-8 h-8 shrink-0 rounded-full flex items-center justify-center text-xs font-semibold text-white"
                                      style="background: {{ $p->user->avatarColor() }}">{{ $p->user->initials() }}</span>
                                <a href="{{ route('hris.payroll.show', $p) }}" class="font-medium text-gray-900 hover:text-blue-700">{{ $p->user->name }}</a>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right text-gray-700 whitespace-nowrap">{{ $rupiah($p->penghasilan_bruto) }}</td>
                        <td class="px-4 py-3 text-right text-red-600 whitespace-nowrap">{{ $rupiah($p->total_potongan) }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-gray-900 whitespace-nowrap">{{ $rupiah($p->gaji_bersih) }}</td>
                        <td class="px-4 py-3">
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $statusMeta[$p->status][1] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ $statusMeta[$p->status][0] ?? ucfirst($p->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1">
                                @if($canFinalize && $p->status === 'draft')
                                <form action="{{ route('hris.payroll.finalize', $p) }}" method="POST"
                                      onsubmit="return confirm('Finalize payroll {{ addslashes($p->user->name) }}?')">
                                    @csrf @method('PATCH')
                                    <button class="px-2.5 py-1 rounded-lg text-xs font-medium text-green-700 hover:bg-green-50">Finalize</button>
                                </form>
                                @endif
                                <a href="{{ route('hris.payroll.show', $p) }}" class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50" title="Detail">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                                <a href="{{ route('hris.payroll.slip', $p) }}" class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50" title="Unduh slip PDF">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $colCount }}" class="px-4 py-12 text-center">
                            <svg class="w-10 h-10 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                            @if($search !== '' || $status)
                            <p class="mt-2 text-sm text-gray-500">Tidak ada payroll yang cocok dengan filter.</p>
                            <a href="{{ route('hris.payroll.index', ['year' => $year, 'month' => $month]) }}" class="mt-1 inline-block text-sm text-blue-600 hover:text-blue-800">Reset filter</a>
                            @else
                            <p class="mt-2 text-sm text-gray-500">Belum ada payroll untuk {{ $periodLabel }}.</p>
                            @if($canGenerate)
                            <p class="text-xs text-gray-400 mt-0.5">Mulai dari langkah 1 di atas: <b>Generate Semua</b>.</p>
                            @endif
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 flex items-center justify-between gap-3 flex-wrap border-t border-gray-100">
            <x-per-page />
            {{ $payrolls->links() }}
        </div>
    </div>
</div>
@endsection
