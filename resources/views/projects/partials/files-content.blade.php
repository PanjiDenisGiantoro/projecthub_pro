<div x-data="{showUpload:false}">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-base font-semibold text-gray-900">
            File Terbaru
            <span class="text-gray-400 font-normal text-sm">({{ min($recentFiles->count(), $recentFilesTotal) }} dari {{ $recentFilesTotal }})</span>
        </h2>
        <div class="flex items-center gap-2">
            <a href="{{ route('project.files.index', $project) }}"
               class="inline-flex items-center gap-1.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                Lihat Semua &amp; Kelola Folder &rarr;
            </a>
            @if(!auth()->user()->hasRole('client'))
            <button @click="showUpload=!showUpload"
                    class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                <span x-text="showUpload ? 'Batal' : 'Upload File'"></span>
            </button>
            @endif
        </div>
    </div>

    {{-- Upload form --}}
    @if(!auth()->user()->hasRole('client'))
    <div x-show="showUpload" x-cloak class="bg-white rounded-xl border border-blue-200 p-5 mb-4"
         x-data="fileUpload()">
        <form method="POST" action="{{ route('project.files.store', $project) }}" enctype="multipart/form-data" class="space-y-3">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Folder</label>
                    <input type="text" name="folder" placeholder="General atau Docs/Kontrak"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-[11px] text-gray-400 mt-1">Pakai "/" untuk folder di dalam folder, mis. Docs/Kontrak/2024</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Deskripsi</label>
                    <input type="text" name="description" placeholder="Opsional"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
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
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">Upload</button>
        </form>
    </div>
    @endif

    {{-- File grid --}}
    @if($recentFiles->isEmpty())
    <div class="bg-white rounded-xl border border-gray-200 py-16 text-center text-gray-400">
        <p class="text-4xl mb-3">📁</p>
        <p class="font-medium text-gray-500">Belum ada file.</p>
    </div>
    @else
    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3">
        @foreach($recentFiles as $file)
        <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-col gap-2 hover:shadow-sm transition-shadow group">
            <div class="text-3xl text-center">{{ $file->icon() }}</div>
            <p class="text-xs font-medium text-gray-800 truncate text-center" title="{{ $file->original_name }}">{{ $file->original_name }}</p>
            <p class="text-xs text-gray-400 text-center">{{ $file->humanSize() }}</p>
            <p class="text-xs text-gray-400 text-center">{{ $file->folder }}</p>
            <p class="text-xs text-gray-400 text-center">{{ $file->uploader?->name }}</p>
            <div class="flex gap-2 justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                <a href="{{ $file->url() }}" target="_blank"
                   class="text-xs text-blue-600 hover:text-blue-800 font-medium">Buka</a>
                <a href="{{ $file->url() }}" download
                   class="text-xs text-blue-600 hover:text-blue-800 font-medium">Unduh</a>
                @if(!auth()->user()->hasRole('client'))
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
    @if($recentFilesTotal > $recentFiles->count())
    <div class="text-center mt-4">
        <a href="{{ route('project.files.index', $project) }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
            Lihat semua {{ $recentFilesTotal }} file &rarr;
        </a>
    </div>
    @endif
    @endif
</div>

@push('scripts')
<script>
function fileUpload() {
    return { files: [], addFiles(e) { this.files = Array.from(e.target.files); } }
}
</script>
@endpush
