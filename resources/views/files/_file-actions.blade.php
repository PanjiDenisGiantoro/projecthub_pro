{{-- Tombol aksi satu file (Buka, Unduh, Pindah, Hapus). Dipakai di grid & list File Manager. --}}
<a href="{{ $file->url() }}" target="_blank" title="Buka"
   class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
</a>
<a href="{{ $file->url() }}" download="{{ $file->original_name }}" title="Unduh"
   class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
</a>
@if(!auth()->user()->hasRole('client'))
<button type="button" title="Pindahkan ke folder lain"
        @click="moveFile = { action: @js(route('project.files.move', [$project, $file])), name: @js($file->original_name) }; moveFolder = @js($file->folder)"
        class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3l3 3-3 3M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
</button>
<form method="POST" action="{{ route('project.files.destroy', [$project, $file]) }}" data-confirm-delete="{{ $file->original_name }}">
    @csrf @method('DELETE')
    <button type="submit" title="Hapus" class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
    </button>
</form>
@endif
