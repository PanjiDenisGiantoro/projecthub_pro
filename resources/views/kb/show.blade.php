@extends('layouts.app')
@section('title', $article->title)
@section('page-title', 'Knowledge Base')

@section('content')
<div class="py-4 max-w-4xl" x-data="{editing:false, addingFiles:false}">
    <nav class="text-sm text-gray-500 mb-4 flex items-center gap-1.5 flex-wrap">
        <a href="{{ route('projects.show', $project) }}" class="hover:text-blue-600">{{ $project->name }}</a>
        <span>/</span>
        <a href="{{ route('kb.index', $project) }}" class="hover:text-blue-600">Knowledge Base</a>
        @if($article->parent)
            <span>/</span>
            <a href="{{ route('kb.show', [$project, $article->parent]) }}" class="hover:text-blue-600">{{ $article->parent->title }}</a>
        @endif
        <span>/</span>
        <span class="text-gray-700">{{ $article->title }}</span>
    </nav>

    {{-- Article --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-5">
        {{-- Header --}}
        <div class="px-6 py-5 border-b border-gray-100 flex items-start justify-between gap-4">
            <div class="flex-1">
                <h2 class="text-xl font-semibold text-gray-800">{{ $article->title }}</h2>
                @if($article->description)
                    <p class="text-sm text-gray-500 mt-1">{{ $article->description }}</p>
                @endif
                <p class="text-xs text-gray-400 mt-1.5">
                    Ditulis oleh <strong class="text-gray-600">{{ $article->author->name }}</strong> ·
                    v{{ $article->version }} ·
                    Diperbarui {{ $article->updated_at->diffForHumans() }}
                </p>
                @if($article->tags)
                    <div class="flex gap-1 mt-2 flex-wrap">
                        @foreach($article->tags as $tag)
                            <span class="badge bg-blue-50 text-blue-600 text-xs">{{ $tag }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
            @if(!auth()->user()->hasRole('customer'))
            <div class="flex items-center gap-3 flex-shrink-0">
                <button @click="editing=!editing"
                        class="text-sm text-violet-600 hover:text-violet-800 font-medium"
                        x-text="editing ? 'Batal' : 'Edit'"></button>
                <form method="POST" action="{{ route('kb.destroy', [$project, $article]) }}"
                      data-confirm-delete="{{ $article->title }}" data-confirm-label="Hapus Artikel">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-sm text-red-500 hover:text-red-700">Hapus</button>
                </form>
            </div>
            @endif
        </div>

        {{-- View mode --}}
        <div x-show="!editing" class="px-6 py-5">
            <pre class="whitespace-pre-wrap text-sm text-gray-700 font-sans leading-relaxed">{{ $article->body }}</pre>
        </div>

        {{-- Edit mode --}}
        <div x-show="editing" x-cloak class="px-6 py-5">
            <form method="POST" action="{{ route('kb.update', [$project, $article]) }}"
                  enctype="multipart/form-data" class="space-y-4">
                @csrf @method('PUT')
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Judul</label>
                    <input type="text" name="title" value="{{ $article->title }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi Singkat</label>
                    <input type="text" name="description" value="{{ $article->description }}" maxlength="500"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Konten</label>
                    <textarea name="body" rows="14" required
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 font-mono">{{ $article->body }}</textarea>
                </div>

                {{-- Upload more files --}}
                <div x-data="fileUpload()" class="border border-dashed border-gray-300 rounded-lg p-4">
                    <p class="text-xs font-medium text-gray-600 mb-2">Tambah Lampiran</p>
                    <input type="file" name="files[]" multiple
                           accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,.md,.jpg,.jpeg,.png,.gif,.zip"
                           @change="addFiles($event)"
                           class="block w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:font-medium file:bg-blue-50 file:text-blue-700">
                    <div x-show="files.length > 0" class="mt-2 space-y-1.5">
                        <template x-for="(f, i) in files" :key="i">
                            <div class="flex items-center gap-2 text-xs bg-gray-50 rounded px-2 py-1.5">
                                <span class="flex-1 truncate text-gray-700" x-text="f.name"></span>
                                <input type="text" :name="'file_descriptions['+i+']'" placeholder="Deskripsi (opsional)"
                                       class="border border-gray-200 rounded px-2 py-0.5 text-xs w-44 focus:outline-none focus:ring-1 focus:ring-blue-400">
                            </div>
                        </template>
                    </div>
                </div>

                <button type="submit"
                        class="bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">Simpan</button>
            </form>
        </div>
    </div>

    {{-- Attachments --}}
    @if($article->attachments->count())
    <div class="bg-white rounded-xl border border-gray-200 p-5 mb-5">
        <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
            Lampiran ({{ $article->attachments->count() }})
        </h4>
        <div class="space-y-2">
            @foreach($article->attachments as $att)
            @php
                $isImage = str_starts_with($att->mime_type ?? '', 'image/');
                $ext = strtolower(pathinfo($att->original_name, PATHINFO_EXTENSION));
                $icon = match(true) {
                    in_array($ext, ['pdf'])                        => '📄',
                    in_array($ext, ['doc','docx'])                 => '📝',
                    in_array($ext, ['xls','xlsx'])                 => '📊',
                    in_array($ext, ['jpg','jpeg','png','gif'])      => '🖼️',
                    in_array($ext, ['zip','rar','7z'])              => '📦',
                    default                                        => '📎',
                };
            @endphp
            <div class="flex items-center gap-3 py-2 px-3 bg-gray-50 rounded-lg group">
                <span class="text-lg">{{ $icon }}</span>
                <div class="flex-1 min-w-0">
                    <a href="{{ $att->url() }}" target="_blank"
                       class="text-sm font-medium text-gray-800 hover:text-blue-600 truncate block">
                        {{ $att->original_name }}
                    </a>
                    <div class="flex items-center gap-2 text-xs text-gray-400">
                        <span>{{ $att->humanSize() }}</span>
                        @if($att->description)
                            <span>·</span>
                            <span>{{ $att->description }}</span>
                        @endif
                        <span>·</span>
                        <span>{{ $att->uploader->name }}</span>
                        <span>·</span>
                        <span>{{ $att->created_at->diffForHumans() }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <a href="{{ $att->url() }}" download
                       class="text-xs text-violet-600 hover:text-violet-800 font-medium">Unduh</a>
                    @if(!auth()->user()->hasRole('customer'))
                    <form method="POST" action="{{ route('kb.attachment.destroy', $att) }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs text-red-500 hover:text-red-700"
                                onclick="return confirm('Hapus lampiran ini?')">Hapus</button>
                    </form>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Sub-articles --}}
    @if($article->children->count())
    <div class="mb-5">
        <h4 class="text-sm font-semibold text-gray-700 mb-3">Sub-artikel ({{ $article->children->count() }})</h4>
        <div class="space-y-2">
            @foreach($article->children as $child)
            <a href="{{ route('kb.show', [$project, $child]) }}"
               class="flex items-center justify-between bg-white rounded-lg border border-gray-200 px-4 py-3 hover:border-blue-300 hover:bg-blue-50 transition-colors">
                <div>
                    <span class="text-sm font-medium text-gray-800">{{ $child->title }}</span>
                    @if($child->description)
                        <p class="text-xs text-gray-400 mt-0.5">{{ Str::limit($child->description, 80) }}</p>
                    @endif
                    <div class="text-xs text-gray-400 mt-0.5">v{{ $child->version }} · {{ $child->author->name }}</div>
                </div>
                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
            @endforeach
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
function fileUpload() {
    return { files: [], addFiles(e) { this.files = Array.from(e.target.files); } }
}
</script>
@endpush
@endsection
