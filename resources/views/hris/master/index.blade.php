@extends('layouts.app')
@section('title', 'Master Data HRIS')
@section('page-title', 'Master Data HRIS')

@section('content')
<div class="space-y-6 pt-5" x-data="{ tab: '{{ $tab }}' }">

    <h1 class="text-2xl font-bold text-gray-900">Master Data HRIS</h1>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-xl px-4 py-3">{{ session('success') }}</div>
    @endif

    {{-- Tab Navigation --}}
    <div class="flex gap-1 border-b border-gray-200">
        @foreach([
            ['leave-types',    'Jenis Cuti'],
            ['overtime-rules', 'Aturan Lembur'],
            ['tax-ptkp',       'PTKP'],
            ['tax-brackets',   'Tarif PPh 21'],
        ] as [$key, $label])
        <button @click="tab = '{{ $key }}'"
                :class="tab === '{{ $key }}'
                    ? 'border-b-2 border-violet-600 text-violet-700 font-semibold'
                    : 'text-gray-500 hover:text-gray-700'"
                class="px-4 py-2.5 text-sm transition-colors whitespace-nowrap">
            {{ $label }}
        </button>
        @endforeach
    </div>

    {{-- Tab: Jenis Cuti --}}
    <div x-show="tab === 'leave-types'" x-cloak>
        @include('hris.master._leave-types', ['leaveTypes' => $leaveTypes])
    </div>

    {{-- Tab: Aturan Lembur --}}
    <div x-show="tab === 'overtime-rules'" x-cloak>
        @include('hris.master._overtime-rules', ['overtimeRules' => $overtimeRules])
    </div>

    {{-- Tab: PTKP --}}
    <div x-show="tab === 'tax-ptkp'" x-cloak>
        @include('hris.master._tax-ptkp', ['ptkpList' => $ptkpList])
    </div>

    {{-- Tab: Tarif PPh 21 --}}
    <div x-show="tab === 'tax-brackets'" x-cloak>
        @include('hris.master._tax-brackets', ['taxBrackets' => $taxBrackets])
    </div>

</div>
@endsection
