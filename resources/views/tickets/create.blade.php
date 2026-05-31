@extends('layouts.app')
@section('title', 'Buat Tiket — ' . $project->name)
@section('page-title', 'Buat Tiket Baru')

@section('content')
<div class="py-4 max-w-2xl">
    <nav class="text-sm text-gray-500 mb-6">
        <a href="{{ route('projects.show', $project) }}" class="hover:text-blue-600">{{ $project->name }}</a>
        <span class="mx-2">/</span>
        <a href="{{ route('tickets.index', $project) }}" class="hover:text-blue-600">Tickets</a>
        <span class="mx-2">/</span>
        <span class="text-gray-700">Buat Baru</span>
    </nav>

    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <form method="POST" action="{{ route('tickets.store', $project) }}" class="space-y-5">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Judul <span class="text-red-500">*</span></label>
                <input type="text" name="title" value="{{ old('title') }}" required
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi <span class="text-red-500">*</span></label>
                <textarea name="description" rows="5" required
                          class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">{{ old('description') }}</textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipe</label>
                    <select name="type" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                        @foreach(['bug','issue','enhancement','security','performance'] as $t)
                            <option value="{{ $t }}" {{ old('type') === $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Prioritas</label>
                    <select name="priority" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                        @foreach(['critical','high','medium','low'] as $p)
                            <option value="{{ $p }}" {{ old('priority','medium') === $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium px-6 py-2.5 rounded-lg transition-colors">
                    Buat Tiket
                </button>
                <a href="{{ route('tickets.index', $project) }}" class="text-gray-600 hover:text-gray-800 text-sm font-medium px-4 py-2.5 rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
