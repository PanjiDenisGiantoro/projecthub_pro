@extends('layouts.app')
@section('title', $meta['label'] . ' — Laporan')
@section('page-title', $meta['label'])

@section('content')
<div class="py-4 space-y-5">
    <nav class="text-sm text-gray-500">
        <a href="{{ route('reports.index') }}" class="hover:text-blue-600">Laporan</a>
        <span class="mx-2">/</span><span class="text-gray-700">{{ $meta['label'] }}</span>
    </nav>

    {{-- Filter form --}}
    <form method="GET" action="{{ route('reports.show', $key) }}" class="bg-white rounded-xl border border-gray-200 p-5">
        <input type="hidden" name="submitted" value="1">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($filterDefs as $field => $def)
                @if($def['type'] === 'select')
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">{{ $def['label'] }}</label>
                    <select name="{{ $field }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="">Semua</option>
                        @foreach($def['options'] as $value => $label)
                        <option value="{{ $value }}" {{ (string) ($filters[$field] ?? '') === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @elseif($def['type'] === 'date_range')
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">{{ $def['label'] }} (dari)</label>
                    <input type="date" name="{{ $field }}_from" value="{{ $filters[$field.'_from'] ?? '' }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">{{ $def['label'] }} (sampai)</label>
                    <input type="date" name="{{ $field }}_to" value="{{ $filters[$field.'_to'] ?? '' }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                @endif
            @endforeach
        </div>
        <div class="mt-4 flex items-center gap-2">
            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg">
                Tampilkan
            </button>
            @if($submitted)
            <a href="{{ route('reports.show', $key) }}" class="px-4 py-2 text-sm text-gray-500 hover:text-gray-700">Reset</a>
            @endif
        </div>
    </form>

    @if($submitted)
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <p class="text-sm text-gray-600">{{ $rows->count() }} baris</p>
            @if($rows->isNotEmpty())
            <div class="flex gap-2">
                <a href="{{ route('reports.export', array_merge(['key' => $key, 'format' => 'pdf'], request()->except(['submitted']))) }}"
                   class="px-3 py-1.5 text-xs font-medium border border-gray-300 rounded-lg hover:bg-gray-50">Export PDF</a>
                <a href="{{ route('reports.export', array_merge(['key' => $key, 'format' => 'xlsx'], request()->except(['submitted']))) }}"
                   class="px-3 py-1.5 text-xs font-medium border border-gray-300 rounded-lg hover:bg-gray-50">Export Excel</a>
            </div>
            @endif
        </div>

        @if($rows->isEmpty())
        <p class="text-sm text-gray-400 px-5 py-8 text-center">Tidak ada data untuk filter ini.</p>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        @foreach($columns as $label)
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase whitespace-nowrap">{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($rows as $row)
                    <tr class="hover:bg-gray-50">
                        @foreach(array_keys($columns) as $field)
                        <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $row[$field] ?? '-' }}</td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
    @endif
</div>
@endsection
