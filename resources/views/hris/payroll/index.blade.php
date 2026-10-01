@extends('layouts.app')
@section('title', 'Penggajian')
@section('page-title', 'Penggajian')

@section('content')
<div class="space-y-6 pt-5">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Penggajian (Payroll)</h1>
        @can('update payroll')
        <a href="{{ route('hris.payroll.setting') }}"
           class="inline-flex items-center gap-2 text-sm text-blue-600 hover:text-blue-800 font-medium">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Pengaturan Penggajian
        </a>
        @endcan
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-xl px-4 py-3">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-xl px-4 py-3">{{ session('error') }}</div>
    @endif

    {{-- Filter --}}
    <form method="GET" class="flex gap-3">
        <select name="year" class="border border-gray-200 rounded-xl px-3 py-2 text-sm">
            @for($y = now()->year; $y >= now()->year - 2; $y--)
            <option value="{{ $y }}" @selected($y == $year)>{{ $y }}</option>
            @endfor
        </select>
        <select name="month" class="border border-gray-200 rounded-xl px-3 py-2 text-sm">
            @foreach(range(1, 12) as $m)
            <option value="{{ $m }}" @selected($m == $month)>{{ \Carbon\Carbon::create(null, $m)->locale('id')->isoFormat('MMMM') }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700">Filter</button>
    </form>

    {{-- Alur: Generate → Finalize → Selesai --}}
    @if(auth()->user()->can('generate payroll') || auth()->user()->can('update payroll'))
    @php
        $eligibleCount = $employees->where('has_salary', true)->count();
        $draftCount    = (int) ($statusCounts['draft'] ?? 0);
        $finalCount    = (int) ($statusCounts['finalized'] ?? 0) + (int) ($statusCounts['paid'] ?? 0);
        $generatedCount = $draftCount + $finalCount;
        $currentStep   = $draftCount > 0 ? 2 : (($generatedCount > 0 && $generatedCount >= $eligibleCount) ? 3 : 1);
        $periodLabel   = \Carbon\Carbon::create($year, $month)->locale('id')->isoFormat('MMMM Y');
    @endphp
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        @foreach([
            1 => ['Generate', "{$generatedCount} / {$eligibleCount} karyawan sudah digenerate"],
            2 => ['Review & Finalize', $draftCount ? "{$draftCount} payroll draft menunggu finalize" : 'Tidak ada draft'],
            3 => ['Selesai', "{$finalCount} slip gaji final & siap diunduh"],
        ] as $n => [$label, $desc])
        @php $done = $n < $currentStep || ($n === 3 && $currentStep === 3); $active = $n === $currentStep && !$done; @endphp
        <div class="bg-white rounded-2xl border shadow-sm p-4 flex flex-col gap-3 {{ $active ? 'border-blue-300 ring-1 ring-blue-200' : 'border-gray-100' }}">
            <div class="flex items-center gap-3">
                <span class="w-7 h-7 shrink-0 rounded-full flex items-center justify-center text-xs font-bold
                    {{ $done ? 'bg-green-100 text-green-700' : ($active ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-500') }}">
                    @if($done)
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    @else {{ $n }} @endif
                </span>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-900">{{ $label }}</p>
                    <p class="text-xs text-gray-500">{{ $desc }}</p>
                </div>
            </div>
            @if($n === 2 && $draftCount > 0)
            @can('update payroll')
            <form action="{{ route('hris.payroll.finalizeBulk') }}" method="POST"
                  onsubmit="return confirm('Finalize {{ $draftCount }} payroll draft periode {{ $periodLabel }}? Payroll final tidak bisa digenerate ulang dan karyawan akan menerima notifikasi slip gaji.')">
                @csrf
                <input type="hidden" name="all" value="1">
                <input type="hidden" name="year" value="{{ $year }}">
                <input type="hidden" name="month" value="{{ $month }}">
                <button class="w-full px-4 py-2 bg-green-600 text-white text-sm font-semibold rounded-xl hover:bg-green-700">
                    Finalize Semua Draft ({{ $draftCount }})
                </button>
            </form>
            @endcan
            @endif
        </div>
        @endforeach
    </div>
    @endif

    {{-- Generate Form (only for HR) --}}
    @can('generate payroll')
    @php
        $selectableIds = $employees->filter(fn($e) => $e->has_salary && !in_array($existingStatus[$e->id] ?? null, ['finalized', 'paid']))->pluck('id')->values();
        $notGeneratedIds = $employees->filter(fn($e) => $e->has_salary && !isset($existingStatus[$e->id]))->pluck('id')->values();
        $noSalaryCount = $employees->where('has_salary', false)->count();
    @endphp
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4"
         x-data="{
            open: false,
            search: '',
            selected: [],
            selectable: @js($selectableIds),
            notGenerated: @js($notGeneratedIds),
            get allSelected() { return this.selectable.length > 0 && this.selected.length === this.selectable.length },
            toggleAll() { this.selected = this.allSelected ? [] : [...this.selectable] },
            selectNotGenerated() { this.selected = [...this.notGenerated] },
         }">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <button type="button" @click="open = !open" class="text-sm font-medium text-blue-700 hover:text-blue-900">
                + Generate Payroll Karyawan
            </button>
            <form action="{{ route('hris.payroll.generate') }}" method="POST"
                  onsubmit="return confirm('Generate payroll untuk semua karyawan ({{ $selectableIds->count() }}) periode ini? Payroll draft yang sudah ada akan dihitung ulang.')">
                @csrf
                @foreach($selectableIds as $id)
                <input type="hidden" name="user_ids[]" value="{{ $id }}">
                @endforeach
                <input type="hidden" name="year" value="{{ $year }}">
                <input type="hidden" name="month" value="{{ $month }}">
                <button @disabled($selectableIds->isEmpty())
                        class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-xl hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    Generate Semua ({{ $selectableIds->count() }})
                </button>
            </form>
        </div>

        <div x-show="open" x-cloak class="mt-4">
            <form action="{{ route('hris.payroll.generate') }}" method="POST" class="space-y-3">
                @csrf
                <input type="hidden" name="year" value="{{ $year }}">
                <input type="hidden" name="month" value="{{ $month }}">

                <div class="flex items-center gap-3 flex-wrap">
                    <input type="text" x-model="search" placeholder="Cari karyawan..."
                           class="border border-gray-200 rounded-xl px-3 py-2 text-sm w-full sm:w-64">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                        <input type="checkbox" :checked="allSelected" @change="toggleAll()" class="rounded border-gray-300">
                        Pilih semua
                    </label>
                    <button type="button" @click="selectNotGenerated()" class="text-sm text-blue-600 hover:text-blue-800">
                        Pilih yang belum digenerate ({{ $notGeneratedIds->count() }})
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
                        <a href="{{ route('hris.salary.create', $emp) }}" class="text-xs px-2 py-0.5 rounded-full font-medium bg-amber-100 text-amber-700 hover:bg-amber-200">
                            Belum ada data gaji · Atur
                        </a>
                        @elseif($st)
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium
                            @if($st === 'paid') bg-green-100 text-green-700
                            @elseif($st === 'finalized') bg-blue-100 text-blue-700
                            @else bg-gray-100 text-gray-700 @endif">
                            {{ ucfirst($st) }}
                        </span>
                        @endif
                    </label>
                    @empty
                    <p class="px-3 py-4 text-sm text-gray-400 text-center">Tidak ada karyawan aktif.</p>
                    @endforelse
                </div>

                <div class="flex items-center gap-3">
                    <button :disabled="selected.length === 0"
                            class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-xl hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        Generate Terpilih (<span x-text="selected.length"></span>)
                    </button>
                    <span class="text-xs text-gray-500">
                        Payroll yang sudah final/dibayar tidak bisa digenerate ulang.
                        @if($noSalaryCount) {{ $noSalaryCount }} karyawan belum punya data gaji sehingga tidak bisa dipilih. @endif
                    </span>
                </div>
            </form>
        </div>
    </div>
    @endcan

    @php
        $canFinalize = auth()->user()->can('update payroll');
        $pageDraftIds = $payrolls->getCollection()->where('status', 'draft')->pluck('id')->values();
    @endphp
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-x-auto"
         x-data="{
            picked: [],
            drafts: @js($pageDraftIds),
            get allPicked() { return this.drafts.length > 0 && this.picked.length === this.drafts.length },
            toggleAll() { this.picked = this.allPicked ? [] : [...this.drafts] },
         }">
        @if($canFinalize)
        <div x-show="picked.length > 0" x-cloak
             class="flex items-center justify-between gap-3 flex-wrap px-4 py-3 bg-blue-50 border-b border-blue-100">
            <span class="text-sm text-blue-900"><span class="font-semibold" x-text="picked.length"></span> payroll draft dipilih</span>
            <div class="flex items-center gap-3">
                <button type="button" @click="picked = []" class="text-sm text-gray-600 hover:text-gray-800">Batal</button>
                <form action="{{ route('hris.payroll.finalizeBulk') }}" method="POST"
                      @submit="if (!confirm('Finalize ' + picked.length + ' payroll terpilih?')) $event.preventDefault()">
                    @csrf
                    <template x-for="id in picked" :key="id">
                        <input type="hidden" name="payroll_ids[]" :value="id">
                    </template>
                    <button class="px-4 py-2 bg-green-600 text-white text-sm font-semibold rounded-xl hover:bg-green-700">
                        Finalize Terpilih (<span x-text="picked.length"></span>)
                    </button>
                </form>
            </div>
        </div>
        @endif
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    @if($canFinalize)
                    <th class="px-4 py-3 w-10">
                        <input type="checkbox" :checked="allPicked" @change="toggleAll()" :disabled="drafts.length === 0"
                               title="Pilih semua draft di halaman ini" class="rounded border-gray-300 disabled:opacity-40">
                    </th>
                    @endif
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Karyawan</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Bruto</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Potongan</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Bersih</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($payrolls as $p)
                <tr :class="picked.includes({{ $p->id }}) && 'bg-blue-50/50'">
                    @if($canFinalize)
                    <td class="px-4 py-3">
                        @if($p->status === 'draft')
                        <input type="checkbox" value="{{ $p->id }}" x-model.number="picked" class="rounded border-gray-300">
                        @endif
                    </td>
                    @endif
                    <td class="px-4 py-3 font-medium text-gray-900">{{ $p->user->name }}</td>
                    <td class="px-4 py-3 text-right text-gray-700">Rp {{ number_format($p->penghasilan_bruto, 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-right text-red-600">Rp {{ number_format($p->total_potongan, 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-right font-bold text-green-700">Rp {{ number_format($p->gaji_bersih, 0, ',', '.') }}</td>
                    <td class="px-4 py-3">
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium
                            @if($p->status === 'paid') bg-green-100 text-green-700
                            @elseif($p->status === 'finalized') bg-blue-100 text-blue-700
                            @else bg-gray-100 text-gray-700 @endif">
                            {{ ucfirst($p->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center flex items-center justify-center gap-2">
                        <a href="{{ route('hris.payroll.show', $p) }}" class="text-xs text-blue-600 hover:text-blue-800">Detail</a>
                        <a href="{{ route('hris.payroll.slip', $p) }}" class="text-xs text-blue-600 hover:text-blue-800">Slip PDF</a>
                        @can('update payroll')
                        @if($p->status === 'draft')
                        <form action="{{ route('hris.payroll.finalize', $p) }}" method="POST" class="inline">
                            @csrf @method('PATCH')
                            <button class="text-xs text-green-600 hover:text-green-800">Finalize</button>
                        </form>
                        @endif
                        @endcan
                    </td>
                </tr>
                @empty
                <tr><td colspan="{{ $canFinalize ? 7 : 6 }}" class="px-4 py-8 text-center text-gray-400 text-sm">Belum ada data payroll bulan ini.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4 flex items-center justify-between gap-3 flex-wrap">
            <x-per-page />
            {{ $payrolls->links() }}
        </div>
    </div>
</div>
@endsection
