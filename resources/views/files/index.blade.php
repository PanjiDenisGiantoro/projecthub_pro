@extends('layouts.app')
@section('title', 'File Manager — ' . $project->name)
@section('page-title', 'File Manager')

@section('content')
<div class="py-4" x-data="{showUpload:false,activeFolder:'All',newFolder:''}">
    <nav class="text-sm text-gray-500 mb-4">
        <a href="{{ route('projects.show', $project) }}" class="hover:text-blue-600">{{ $project->name }}</a>
        <span class="mx-2">/</span><span class="text-gray-700">File Manager</span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-5">
        {{-- Folder sidebar --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs font-semibold text-gray-500 uppercase mb-3">Folder</p>
                <button @click="activeFolder='All'"
                        :class="activeFolder==='All' ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50'"
                        class="w-full text-left px-3 py-2 rounded-lg text-sm flex items-center gap-2 transition-colors mb-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                    Semua ({{ $files->count() }})
                </button>
                <div class="space-y-0.5">
                    @foreach($folderTree as $name => $node)
                        <x-file-folder-node :name="$name" :node="$node" :files="$files" />
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Files area --}}
        <div class="lg:col-span-3">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-sm font-semibold text-gray-700" x-text="activeFolder === 'All' ? 'Semua File' : activeFolder"></h3>
                @if(!auth()->user()->hasRole('customer'))
                <button @click="showUpload=!showUpload; if(showUpload && activeFolder!=='All') newFolder=activeFolder"
                        class="inline-flex items-center gap-2 bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    <span x-text="showUpload ? 'Batal' : 'Upload File'"></span>
                </button>
                @endif
            </div>

            {{-- Upload form --}}
            @if(!auth()->user()->hasRole('customer'))
            <div x-show="showUpload" x-cloak class="bg-white rounded-xl border border-blue-200 p-5 mb-4"
                 x-data="fileUpload()">
                <form method="POST" action="{{ route('project.files.store', $project) }}" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Folder</label>
                            <input type="text" name="folder" x-model="newFolder" placeholder="General atau Docs/Kontrak" list="folder-list"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                            <datalist id="folder-list">
                                @foreach($folders as $f)<option value="{{ $f }}">@endforeach
                            </datalist>
                            <p class="text-[11px] text-gray-400 mt-1">Pakai "/" untuk folder di dalam folder, mis. Docs/Kontrak/2024</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Deskripsi</label>
                            <input type="text" name="description" placeholder="Opsional"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                        </div>
                    </div>
                    <div class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:border-blue-400 transition-colors">
                        <input type="file" name="files[]" multiple @change="addFiles($event)"
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        <p class="text-xs text-gray-400 mt-2">Semua tipe file didukung, maks 50MB per file</p>
                        <template x-if="files.length > 0">
                            <p class="text-xs text-blue-600 mt-1" x-text="files.length + ' file dipilih'"></p>
                        </template>
                    </div>
                    <button type="submit" class="bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">Upload</button>
                </form>
            </div>
            @endif

            {{-- File grid --}}
            @if($files->isEmpty())
            <div class="bg-white rounded-xl border border-gray-200 py-16 text-center text-gray-400">
                <p class="text-4xl mb-3">📁</p>
                <p class="font-medium text-gray-500">Belum ada file.</p>
            </div>
            @else
            <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3">
                @foreach($files as $file)
                <div x-show="activeFolder === 'All' || activeFolder === '{{ $file->folder }}'"
                     class="bg-white rounded-xl border border-gray-200 p-4 flex flex-col gap-2 hover:shadow-sm transition-shadow group">
                    <div class="text-3xl text-center">{{ $file->icon() }}</div>
                    <p class="text-xs font-medium text-gray-800 truncate text-center" title="{{ $file->original_name }}">{{ $file->original_name }}</p>
                    <p class="text-xs text-gray-400 text-center">{{ $file->humanSize() }}</p>
                    <p class="text-xs text-gray-400 text-center">{{ $file->folder }}</p>
                    <p class="text-xs text-gray-400 text-center">{{ $file->uploader?->name }}</p>
                    <div class="flex gap-2 justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                        <a href="{{ $file->url() }}" target="_blank"
                           class="text-xs text-violet-600 hover:text-violet-800 font-medium">Buka</a>
                        <a href="{{ $file->url() }}" download
                           class="text-xs text-violet-600 hover:text-violet-800 font-medium">Unduh</a>
                        @if(!auth()->user()->hasRole('customer'))
                        <form method="POST" action="{{ route('project.files.destroy', [$project, $file]) }}"
                              data-confirm-delete="{{ $file->original_name }}">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs text-red-500 hover:text-red-700">Hapus</button>
                        </form>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
function fileUpload() {
    return { files: [], addFiles(e) { this.files = Array.from(e.target.files); } }
}
</script>
@endpush
@endsection
