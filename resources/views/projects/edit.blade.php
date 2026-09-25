@extends('layouts.app')

@section('title', 'Edit Project: ' . $project->name)

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
        <h1 class="text-2xl font-bold text-gray-900">Edit Project</h1>
        <a href="{{ route('board-columns.index', $project) }}"
           class="ml-auto text-sm text-blue-600 hover:text-blue-800 border border-blue-300 px-3 py-1.5 rounded-lg transition-colors">
            Manage Board Columns
        </a>
    </div>

    {{-- Validation Errors --}}
    @if($errors->any())
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-sm font-medium text-red-700 mb-2">There were some errors with your submission:</p>
            <ul class="list-disc list-inside text-sm text-red-600 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Form --}}
    <form action="{{ route('projects.update', $project) }}" method="POST" enctype="multipart/form-data"
          class="bg-white rounded-xl shadow-sm border border-gray-200">
        @csrf
        @method('PUT')

        <div class="p-6 space-y-6">

            {{-- Name --}}
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                    Project Name <span class="text-red-500">*</span>
                </label>
                <input type="text" id="name" name="name"
                       value="{{ old('name', $project->name) }}"
                       required
                       placeholder="Enter project name"
                       class="w-full px-3 py-2 border @error('name') border-red-400 @else border-gray-300 @enderror rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('name')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Description --}}
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea id="description" name="description" rows="4"
                          placeholder="Describe this project..."
                          class="w-full px-3 py-2 border @error('description') border-red-400 @else border-gray-300 @enderror rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none">{{ old('description', $project->description) }}</textarea>
                @error('description')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Client & Lead Project --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label for="client_id" class="block text-sm font-medium text-gray-700 mb-1">
                        Client
                    </label>
                    <select id="client_id" name="client_id"
                            class="w-full px-3 py-2 border @error('client_id') border-red-400 @else border-gray-300 @enderror rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Select Client --</option>
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
                        Project Lead
                    </label>
                    <select id="manager_id" name="manager_id"
                            class="w-full px-3 py-2 border @error('manager_id') border-red-400 @else border-gray-300 @enderror rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Select Project Lead --</option>
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

            {{-- Photos / Logo --}}
            <div x-data="projectImageEditor(@js($project->images ?? []), @js($project->imageUrls()), 4)">
                <label class="block text-sm font-medium text-gray-700 mb-1">Foto / Logo Proyek</label>
                <p class="text-xs text-gray-400 mb-2">JPG, PNG, GIF, atau WEBP &middot; maks 2MB per foto &middot; sampai 4 foto.</p>
                <div class="flex flex-wrap gap-3">
                    <template x-for="img in existing" :key="img.path">
                        <div class="relative w-20 h-20 rounded-lg overflow-hidden border group"
                             :class="img.removed ? 'border-red-300 opacity-40' : 'border-gray-200'">
                            <img :src="img.url" class="w-full h-full object-cover">
                            <button type="button" @click="img.removed = !img.removed"
                                    class="absolute top-1 right-1 w-5 h-5 rounded-full bg-black/60 text-white text-xs flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                <span x-show="!img.removed">&times;</span>
                                <span x-show="img.removed" x-cloak>&#8635;</span>
                            </button>
                            <input type="hidden" name="remove_images[]" :value="img.path" :disabled="!img.removed">
                        </div>
                    </template>
                    <template x-for="(url, idx) in newPreviews" :key="idx">
                        <div class="relative w-20 h-20 rounded-lg overflow-hidden border border-gray-200 group">
                            <img :src="url" class="w-full h-full object-cover">
                            <button type="button" @click="removeNew(idx)"
                                    class="absolute top-1 right-1 w-5 h-5 rounded-full bg-black/60 text-white text-xs flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                &times;
                            </button>
                        </div>
                    </template>
                    <button type="button" x-show="remainingSlots > 0" @click="$refs.imagesInput.click()"
                            class="w-20 h-20 rounded-lg border-2 border-dashed border-gray-300 hover:border-blue-400 text-gray-400 hover:text-blue-500 flex flex-col items-center justify-center text-xs transition-colors">
                        <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Add
                    </button>
                </div>
                <input type="file" name="images[]" x-ref="imagesInput" @change="onChange" multiple accept="image/*" class="hidden">
                <p x-show="error" x-cloak x-text="error" class="mt-1.5 text-xs text-red-500"></p>
                @error('images')
                    <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                @enderror
                @error('images.*')
                    <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Start Date & End Date --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                    <input type="date" id="start_date" name="start_date"
                           value="{{ old('start_date', $project->start_date ? \Carbon\Carbon::parse($project->start_date)->format('Y-m-d') : '') }}"
                           class="w-full px-3 py-2 border @error('start_date') border-red-400 @else border-gray-300 @enderror rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('start_date')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
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
                    <label for="budget" class="block text-sm font-medium text-gray-700 mb-1">Budget (IDR)</label>
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
                        <option value="active"    {{ old('status', $project->status) === 'active'    ? 'selected' : '' }}>Active</option>
                        <option value="on_hold"   {{ old('status', $project->status) === 'on_hold'   ? 'selected' : '' }}>On Hold</option>
                        <option value="completed" {{ old('status', $project->status) === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ old('status', $project->status) === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
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
                <h2 class="text-sm font-semibold text-gray-900 mb-4">Meeting Settings (Google Meet)</h2>

                <div class="space-y-3">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="google_meet_enabled" value="1"
                               {{ old('google_meet_enabled', $project->google_meet_enabled) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">Enable Google Meet for this project</span>
                    </label>

                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="meeting_auto_create" value="1"
                               {{ old('meeting_auto_create', $project->meeting_auto_create) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">Automatically create meetings when new Sprints/Milestones/Tasks/Tickets are created</span>
                    </label>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mt-4">
                    <div>
                        <label for="meeting_default_time" class="block text-sm font-medium text-gray-700 mb-1">Default meeting time</label>
                        <input type="time" id="meeting_default_time" name="meeting_default_time"
                               value="{{ old('meeting_default_time', $project->meeting_default_time ? \Carbon\Carbon::parse($project->meeting_default_time)->format('H:i') : '09:00') }}"
                               class="w-full px-3 py-2 border @error('meeting_default_time') border-red-400 @else border-gray-300 @enderror rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('meeting_default_time')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="meeting_default_duration_minutes" class="block text-sm font-medium text-gray-700 mb-1">Default duration (minutes)</label>
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
                &larr; Back
            </a>
            <button type="submit"
                    class="px-6 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg shadow hover:bg-blue-700 transition">
                Save Changes
            </button>
        </div>

    </form>

</div>

@push('scripts')
<script>
    function projectImageEditor(paths, urls, max) {
        return {
            max,
            existing: paths.map((p, i) => ({ path: p, url: urls[i], removed: false })),
            newFiles: [],
            newPreviews: [],
            error: '',
            get remainingSlots() {
                const kept = this.existing.filter(i => !i.removed).length;
                return Math.max(0, this.max - kept - this.newFiles.length);
            },
            onChange(e) {
                const selected = Array.from(e.target.files);
                const room = this.remainingSlots;
                if (selected.length > room) {
                    this.error = `Maksimal ${this.max} foto total. Sisa slot: ${room}.`;
                } else {
                    this.error = '';
                }
                this.newFiles = [...this.newFiles, ...selected].slice(0, this.newFiles.length + room);
                this.syncInput();
                this.newPreviews = this.newFiles.map(f => URL.createObjectURL(f));
            },
            removeNew(idx) {
                this.newFiles.splice(idx, 1);
                this.error = '';
                this.syncInput();
                this.newPreviews = this.newFiles.map(f => URL.createObjectURL(f));
            },
            syncInput() {
                const dt = new DataTransfer();
                this.newFiles.forEach(f => dt.items.add(f));
                this.$refs.imagesInput.files = dt.files;
            },
        };
    }
</script>
@endpush
@endsection
