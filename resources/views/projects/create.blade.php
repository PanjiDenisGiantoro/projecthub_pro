@extends('layouts.app')

@section('title', 'Buat Proyek Baru')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('projects.index') }}"
           class="p-2 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
            </svg>
        </a>
        <h1 class="text-2xl font-bold text-gray-900">Buat Proyek Baru</h1>
    </div>

    {{-- Validation Errors --}}
    @if($errors->any())
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-sm font-medium text-red-700 mb-2">Terdapat kesalahan pada input:</p>
            <ul class="list-disc list-inside text-sm text-red-600 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Form --}}
    <form action="{{ route('projects.store') }}" method="POST" class="bg-white rounded-xl shadow-sm border border-gray-200">
        @csrf

        <div class="p-6 space-y-6">

            {{-- Name --}}
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                    Nama Proyek <span class="text-red-500">*</span>
                </label>
                <input type="text" id="name" name="name"
                       value="{{ old('name') }}"
                       required
                       placeholder="Masukkan nama proyek"
                       class="w-full px-3 py-2 border @error('name') border-red-400 @else border-gray-300 @enderror rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('name')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Description --}}
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                <textarea id="description" name="description" rows="4"
                          placeholder="Deskripsikan proyek ini..."
                          class="w-full px-3 py-2 border @error('description') border-red-400 @else border-gray-300 @enderror rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none">{{ old('description') }}</textarea>
                @error('description')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Client & Manager --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label for="client_id" class="block text-sm font-medium text-gray-700 mb-1">
                        Client <span class="text-red-500">*</span>
                    </label>
                    <select id="client_id" name="client_id" required
                            class="w-full px-3 py-2 border @error('client_id') border-red-400 @else border-gray-300 @enderror rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">-- Pilih Client --</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                                {{ $client->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('client_id')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="manager_id" class="block text-sm font-medium text-gray-700 mb-1">
                        Manager <span class="text-red-500">*</span>
                    </label>
                    <select id="manager_id" name="manager_id" required
                            class="w-full px-3 py-2 border @error('manager_id') border-red-400 @else border-gray-300 @enderror rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">-- Pilih Manager --</option>
                        @foreach($managers as $manager)
                            <option value="{{ $manager->id }}" {{ old('manager_id') == $manager->id ? 'selected' : '' }}>
                                {{ $manager->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('manager_id')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Start Date & End Date --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai</label>
                    <input type="date" id="start_date" name="start_date"
                           value="{{ old('start_date') }}"
                           class="w-full px-3 py-2 border @error('start_date') border-red-400 @else border-gray-300 @enderror rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('start_date')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Selesai</label>
                    <input type="date" id="end_date" name="end_date"
                           value="{{ old('end_date') }}"
                           class="w-full px-3 py-2 border @error('end_date') border-red-400 @else border-gray-300 @enderror rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('end_date')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Budget & Status --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label for="budget" class="block text-sm font-medium text-gray-700 mb-1">Budget (Rp)</label>
                    <input type="number" id="budget" name="budget"
                           value="{{ old('budget') }}"
                           min="0" step="1000"
                           placeholder="0"
                           class="w-full px-3 py-2 border @error('budget') border-red-400 @else border-gray-300 @enderror rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('budget')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select id="status" name="status"
                            class="w-full px-3 py-2 border @error('status') border-red-400 @else border-gray-300 @enderror rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="draft"     {{ old('status', 'draft') === 'draft'     ? 'selected' : '' }}>Draft</option>
                        <option value="active"    {{ old('status') === 'active'    ? 'selected' : '' }}>Aktif</option>
                        <option value="on_hold"   {{ old('status') === 'on_hold'   ? 'selected' : '' }}>On Hold</option>
                        <option value="completed" {{ old('status') === 'completed' ? 'selected' : '' }}>Selesai</option>
                        <option value="cancelled" {{ old('status') === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

        </div>

        {{-- Form Actions --}}
        <div class="px-6 py-4 bg-gray-50 rounded-b-xl border-t border-gray-200 flex items-center justify-between">
            <a href="{{ route('projects.index') }}"
               class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                &larr; Kembali
            </a>
            <button type="submit"
                    class="px-6 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg shadow hover:bg-indigo-700 transition">
                Simpan Proyek
            </button>
        </div>

    </form>

</div>
@endsection
