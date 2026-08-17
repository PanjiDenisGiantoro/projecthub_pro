@extends('layouts.app')
@section('title', 'Laporan')
@section('page-title', 'Laporan')

@section('content')
<div class="py-4 space-y-6">
    <p class="text-xs text-gray-500">Pilih laporan untuk melihat data dengan filter, lalu export ke PDF atau Excel.</p>

    @foreach($reports as $category => $items)
    <div>
        <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-2">{{ $category }}</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($items as $key => $report)
            <a href="{{ route('reports.show', $key) }}"
               class="block bg-white rounded-xl border border-gray-200 p-5 hover:border-violet-300 hover:shadow-sm transition">
                <p class="font-semibold text-gray-800 mb-1">{{ $report['label'] }}</p>
                <p class="text-xs text-gray-500">{{ $report['description'] }}</p>
            </a>
            @endforeach
        </div>
    </div>
    @endforeach
</div>
@endsection
