@extends('layouts.app')
@section('title', 'Knowledge Base — ' . $project->name)
@section('page-title', 'Knowledge Base')

@section('content')
<div class="py-4" x-data="{showForm:false}">
    <nav class="text-sm text-gray-500 mb-4">
        <a href="{{ route('projects.show', $project) }}" class="hover:text-blue-600">{{ $project->name }}</a>
        <span class="mx-2">/</span>
        <span class="text-gray-700">Knowledge Base</span>
    </nav>

    <div class="flex justify-between items-center mb-4">
        <form method="GET" class="flex gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari artikel..."
                   class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-64">
            <button type="submit" class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm px-4 py-2 rounded-lg transition-colors">Cari</button>
        </form>
        @if(!auth()->user()->hasRole('customer'))
        <button @click="showForm=!showForm"
                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span x-text="showForm ? 'Batal' : 'Artikel Baru'"></span>
        </button>
        @endif
    </div>

    {{-- New Article Form --}}
    @if(!auth()->user()->hasRole('customer'))
    <div x-show="showForm" x-cloak class="bg-white rounded-xl border border-blue-200 p-5 mb-5">
        <h4 class="text-sm font-semibold text-gray-700 mb-4">Artikel Baru</h4>
        <form method="POST" action="{{ route('kb.store', $project) }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Judul *</label>
                    <input type="text" name="title" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Deskripsi Singkat</label>
                    <input type="text" name="description" maxlength="500" placeholder="Ringkasan artikel (maks 500 karakter)..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Konten *</label>
                    <textarea name="body" rows="8" required
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Parent Artikel</label>
                    <select name="parent_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">— Root (tidak ada parent) —</option>
                        @foreach($articles as $a)
                            <option value="{{ $a->id }}">{{ $a->title }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Multiple file upload --}}
            <div x-data="fileUpload()" class="border border-dashed border-gray-300 rounded-lg p-4">
                <p class="text-xs font-medium text-gray-600 mb-2">Lampiran Dokumen</p>
                <input type="file" name="files[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,.md,.jpg,.jpeg,.png,.gif,.zip"
                       @change="addFiles($event)"
                       class="block w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                <p class="text-xs text-gray-400 mt-1">PDF, Word, Excel, gambar, zip — maks 20MB per file</p>

                <div x-show="files.length > 0" class="mt-3 space-y-2">
                    <template x-for="(f, i) in files" :key="i">
                        <div class="flex items-center gap-2 text-xs bg-gray-50 rounded px-2 py-1.5">
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span class="flex-1 truncate text-gray-700" x-text="f.name"></span>
                            <input type="text" :name="'file_descriptions['+i+']'" placeholder="Deskripsi file (opsional)"
                                   class="border border-gray-200 rounded px-2 py-0.5 text-xs w-48 focus:outline-none focus:ring-1 focus:ring-blue-400">
                        </div>
                    </template>
                </div>
            </div>

            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">Simpan Artikel</button>
        </form>
    </div>
    @endif

    {{-- Articles List --}}
    <div class="space-y-3">
        @forelse($articles as $article)
        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-sm transition-shadow">
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1 min-w-0">
                    <a href="{{ route('kb.show', [$project, $article]) }}"
                       class="text-base font-semibold text-gray-800 hover:text-blue-600">{{ $article->title }}</a>

                    @if($article->description)
                    <p class="text-sm text-gray-500 mt-1 leading-relaxed">{{ $article->description }}</p>
                    @endif

                    <div class="flex items-center gap-3 mt-1.5 text-xs text-gray-400 flex-wrap">
                        <span>v{{ $article->version }}</span>
                        <span>·</span>
                        <span>{{ $article->author->name }}</span>
                        <span>·</span>
                        <span>{{ $article->updated_at->diffForHumans() }}</span>
                        @if($article->children->count())
                            <span>·</span>
                            <span>{{ $article->children->count() }} sub-artikel</span>
                        @endif
                        @if($article->attachments->count())
                            <span>·</span>
                            <span class="flex items-center gap-1 text-blue-500">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                {{ $article->attachments->count() }} file
                            </span>
                        @endif
                    </div>

                    @if($article->tags)
                    <div class="flex gap-1 mt-2 flex-wrap">
                        @foreach($article->tags as $tag)
                            <span class="badge bg-blue-50 text-blue-600 text-xs">{{ $tag }}</span>
                        @endforeach
                    </div>
                    @endif
                </div>
                <a href="{{ route('kb.show', [$project, $article]) }}"
                   class="text-sm text-blue-600 hover:text-blue-800 font-medium flex-shrink-0">Baca →</a>
            </div>

            {{-- Sub-articles --}}
            @if($article->children->count())
            <div class="mt-3 pl-4 border-l-2 border-gray-100 space-y-1">
                @foreach($article->children as $child)
                <a href="{{ route('kb.show', [$project, $child]) }}"
                   class="block text-sm text-gray-600 hover:text-blue-600">
                    📄 {{ $child->title }}
                </a>
                @endforeach
            </div>
            @endif
        </div>
        @empty
        <div class="text-center py-12 text-gray-400">
            <p class="text-3xl mb-3">📚</p>
            <p class="font-medium text-gray-500">Belum ada artikel.</p>
            <p class="text-sm mt-1">Mulai dokumentasi proyek Anda.</p>
        </div>
        @endforelse
    </div>
</div>

@push('scripts')
<script>
function fileUpload() {
    return {
        files: [],
        addFiles(event) {
            this.files = Array.from(event.target.files);
        }
    }
}
</script>
@endpush
@endsection
