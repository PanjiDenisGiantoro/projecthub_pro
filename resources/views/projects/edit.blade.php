@extends('layouts.app')

@section('title', 'Edit Proyek: ' . $project->name)

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('projects.show', $project) }}"
           class="p-2 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
            </svg>
        </a>
        <h1 class="text-2xl font-bold text-gray-900">Edit Proyek</h1>
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
    <form action="{{ route('projects.update', $project) }}" method="POST" class="bg-white rounded-xl shadow-sm border border-gray-200">
        @csrf
        @method('PUT')

        <div class="p-6 space-y-6">

            {{-- Name --}}
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                    Nama Proyek <span class="text-red-500">*</span>
                </label>
                <input type="text" id="name" name="name"
                       value="{{ old('name', $project->name) }}"
                       required
                       placeholder="Masukkan nama proyek"
                       class="w-full px-3 py-2 border @error('name') border-red-400 @else border-gray-300 @enderror rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('name')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Description --}}
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                <textarea id="description" name="description" rows="4"
                          placeholder="Deskripsikan proyek ini..."
                          class="w-full px-3 py-2 border @error('description') border-red-400 @else border-gray-300 @enderror rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none">{{ old('description', $project->description) }}</textarea>
                @error('description')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Client & Manager --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label for="client_id" class="block text-sm font-medium text-gray-700 mb-1">
                        Client
                    </label>
                    <select id="client_id" name="client_id"
                            class="w-full px-3 py-2 border @error('client_id') border-red-400 @else border-gray-300 @enderror rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Pilih Client --</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ old('client_id', $project->client_id) == $client->id ? 'selected' : '' }}>
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
                        Manager
                    </label>
                    <select id="manager_id" name="manager_id"
                            class="w-full px-3 py-2 border @error('manager_id') border-red-400 @else border-gray-300 @enderror rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Pilih Manager --</option>
                        @foreach($managers as $manager)
                            <option value="{{ $manager->id }}" {{ old('manager_id', $project->manager_id) == $manager->id ? 'selected' : '' }}>
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
                           value="{{ old('start_date', $project->start_date ? \Carbon\Carbon::parse($project->start_date)->format('Y-m-d') : '') }}"
                           class="w-full px-3 py-2 border @error('start_date') border-red-400 @else border-gray-300 @enderror rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('start_date')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Selesai</label>
                    <input type="date" id="end_date" name="end_date"
                           value="{{ old('end_date', $project->end_date ? \Carbon\Carbon::parse($project->end_date)->format('Y-m-d') : '') }}"
                           class="w-full px-3 py-2 border @error('end_date') border-red-400 @else border-gray-300 @enderror rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                           value="{{ old('budget', $project->budget) }}"
                           min="0" step="1000"
                           placeholder="0"
                           class="w-full px-3 py-2 border @error('budget') border-red-400 @else border-gray-300 @enderror rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('budget')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select id="status" name="status"
                            class="w-full px-3 py-2 border @error('status') border-red-400 @else border-gray-300 @enderror rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="draft"     {{ old('status', $project->status) === 'draft'     ? 'selected' : '' }}>Draft</option>
                        <option value="active"    {{ old('status', $project->status) === 'active'    ? 'selected' : '' }}>Aktif</option>
                        <option value="on_hold"   {{ old('status', $project->status) === 'on_hold'   ? 'selected' : '' }}>On Hold</option>
                        <option value="completed" {{ old('status', $project->status) === 'completed' ? 'selected' : '' }}>Selesai</option>
                        <option value="cancelled" {{ old('status', $project->status) === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Progress --}}
            <div x-data="{ progress: {{ old('progress', $project->progress ?? 0) }} }">
                <label for="progress" class="block text-sm font-medium text-gray-700 mb-1">
                    Progress: <span class="font-bold text-blue-600" x-text="progress + '%'"></span>
                </label>
                <input
                    type="range"
                    id="progress"
                    name="progress"
                    min="0" max="100" step="1"
                    x-model="progress"
                    class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-blue-600"
                />
                <div class="flex justify-between text-xs text-gray-400 mt-1">
                    <span>0%</span>
                    <span>50%</span>
                    <span>100%</span>
                </div>
                @error('progress')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Pengaturan Meeting --}}
            <div class="pt-6 border-t border-gray-200">
                <h2 class="text-sm font-semibold text-gray-900 mb-4">Pengaturan Meeting (Google Meet)</h2>

                <div class="space-y-3">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="google_meet_enabled" value="1"
                               {{ old('google_meet_enabled', $project->google_meet_enabled) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">Aktifkan Google Meet untuk proyek ini</span>
                    </label>

                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="meeting_auto_create" value="1"
                               {{ old('meeting_auto_create', $project->meeting_auto_create) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">Buat meeting otomatis saat Sprint/Milestone/Task/Tiket baru dibuat</span>
                    </label>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mt-4">
                    <div>
                        <label for="meeting_default_time" class="block text-sm font-medium text-gray-700 mb-1">Jam default meeting</label>
                        <input type="time" id="meeting_default_time" name="meeting_default_time"
                               value="{{ old('meeting_default_time', $project->meeting_default_time ? \Carbon\Carbon::parse($project->meeting_default_time)->format('H:i') : '09:00') }}"
                               class="w-full px-3 py-2 border @error('meeting_default_time') border-red-400 @else border-gray-300 @enderror rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('meeting_default_time')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="meeting_default_duration_minutes" class="block text-sm font-medium text-gray-700 mb-1">Durasi default (menit)</label>
                        <input type="number" id="meeting_default_duration_minutes" name="meeting_default_duration_minutes"
                               value="{{ old('meeting_default_duration_minutes', $project->meeting_default_duration_minutes ?? 60) }}"
                               min="15" max="480" step="15"
                               class="w-full px-3 py-2 border @error('meeting_default_duration_minutes') border-red-400 @else border-gray-300 @enderror rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('meeting_default_duration_minutes')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

        </div>

        {{-- Form Actions --}}
        <div class="px-6 py-4 bg-gray-50 rounded-b-xl border-t border-gray-200 flex items-center justify-between">
            <a href="{{ route('projects.show', $project) }}"
               class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                &larr; Kembali
            </a>
            <button type="submit"
                    class="px-6 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg shadow hover:bg-blue-700 transition">
                Simpan Perubahan
            </button>
        </div>

    </form>

</div>
@endsection
