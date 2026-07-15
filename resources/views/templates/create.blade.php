@extends('layouts.app')
@section('title', 'Buat Template')
@section('page-title', 'Template Baru')

@section('content')
<div class="py-4 max-w-3xl" x-data="templateBuilder()">
    <nav class="text-sm text-gray-500 mb-4">
        <a href="{{ route('templates.index') }}" class="hover:text-blue-600">Templates</a>
        <span class="mx-2">/</span><span class="text-gray-700">Baru</span>
    </nav>

    <form method="POST" action="{{ route('templates.store') }}" class="space-y-5">
        @csrf
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Info Template</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Nama Template *</label>
                    <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Kategori</label>
                    <input type="text" name="category" placeholder="e.g. Website, Mobile App..." class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Deskripsi</label>
                    <textarea name="description" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500"></textarea>
                </div>
            </div>
        </div>

        {{-- Milestones --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-sm font-semibold text-gray-700">Milestones</h3>
                <button type="button" @click="addMilestone()" class="text-sm text-violet-600 hover:text-violet-800 font-medium">+ Milestone</button>
            </div>

            <template x-for="(ms, mi) in milestones" :key="mi">
                <div class="border border-gray-200 rounded-xl p-4 mb-4">
                    <div class="flex justify-between items-center mb-3">
                        <span class="text-xs font-semibold text-gray-500" x-text="'Milestone ' + (mi+1)"></span>
                        <button type="button" @click="milestones.splice(mi,1)" class="text-red-500 hover:text-red-700 text-xs">Hapus</button>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                        <div class="sm:col-span-3">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Judul *</label>
                            <input type="text" :name="'milestones['+mi+'][title]'" x-model="ms.title" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Mulai (hari ke)</label>
                            <input type="number" :name="'milestones['+mi+'][offset_days]'" x-model="ms.offset_days" min="0" value="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Durasi (hari)</label>
                            <input type="number" :name="'milestones['+mi+'][duration_days]'" x-model="ms.duration_days" min="1" value="7" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                        </div>
                        <input type="hidden" :name="'milestones['+mi+'][sort_order]'" :value="mi">
                    </div>

                    {{-- Tasks in milestone --}}
                    <div class="pl-4 border-l-2 border-gray-100">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-xs text-gray-500">Tasks</span>
                            <button type="button" @click="ms.tasks.push({title:'',priority:'medium',estimated_hours:'',story_points:''})" class="text-xs text-violet-600 hover:text-violet-800">+ Task</button>
                        </div>
                        <template x-for="(task, ti) in ms.tasks" :key="ti">
                            <div class="flex gap-2 mb-2 items-center">
                                <input type="text" :name="'milestones['+mi+'][tasks]['+ti+'][title]'" x-model="task.title" placeholder="Judul task" required class="flex-1 px-2 py-1.5 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-violet-500">
                                <select :name="'milestones['+mi+'][tasks]['+ti+'][priority]'" x-model="task.priority" class="px-2 py-1.5 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-violet-500">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="critical">Critical</option>
                                </select>
                                <input type="number" :name="'milestones['+mi+'][tasks]['+ti+'][estimated_hours]'" x-model="task.estimated_hours" placeholder="Jam" min="0" class="w-16 px-2 py-1.5 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-violet-500">
                                <input type="number" :name="'milestones['+mi+'][tasks]['+ti+'][story_points]'" x-model="task.story_points" placeholder="Pts" min="0" class="w-14 px-2 py-1.5 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-violet-500">
                                <input type="hidden" :name="'milestones['+mi+'][tasks]['+ti+'][sort_order]'" :value="ti">
                                <button type="button" @click="ms.tasks.splice(ti,1)" class="text-red-400 hover:text-red-600 text-xs">✕</button>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            <template x-if="milestones.length === 0">
                <p class="text-sm text-gray-400 text-center py-4">Klik "+ Milestone" untuk menambahkan.</p>
            </template>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium px-6 py-2.5 rounded-lg transition-colors">Simpan Template</button>
            <a href="{{ route('templates.index') }}" class="text-sm text-gray-600 hover:text-gray-800 px-6 py-2.5 rounded-lg border border-gray-300">Batal</a>
        </div>
    </form>
</div>

@push('scripts')
<script>
function templateBuilder() {
    return {
        milestones: [],
        addMilestone() {
            this.milestones.push({ title:'', offset_days:0, duration_days:7, tasks:[] });
        }
    }
}
</script>
@endpush
@endsection
