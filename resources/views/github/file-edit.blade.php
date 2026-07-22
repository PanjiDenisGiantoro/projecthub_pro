@extends('layouts.app')
@section('title', 'Edit File — ' . $project->name)
@section('page-title', 'Edit File')

@section('content')
<div class="py-4">
    <nav class="text-sm text-gray-500 mb-4">
        <a href="{{ route('projects.show', $project) }}" class="hover:text-blue-600">{{ $project->name }}</a>
        <span class="mx-2">/</span>
        <a href="{{ route('github.index', $project) }}" class="hover:text-blue-600">GitHub</a>
        <span class="mx-2">/</span>
        <a href="{{ route('github.files', array_filter(['project' => $project->id, 'branch' => $activeBranch, 'path' => dirname($path) === '.' ? null : dirname($path)])) }}" class="hover:text-blue-600">File</a>
        <span class="mx-2">/</span><span class="text-gray-700 font-mono">{{ $path }}</span>
    </nav>

    @if(session('error'))
    <div class="mb-4 px-4 py-3 rounded-lg bg-red-50 text-red-700 text-sm font-medium">
        {{ session('error') }}
    </div>
    @endif

    @if(!($file['ok'] ?? false))
        <div class="bg-amber-50 border border-amber-200 text-amber-700 text-sm rounded-xl p-4">
            {{ $file['message'] ?? 'Gagal memuat file.' }}
        </div>
    @else
        <div class="bg-white rounded-xl border border-gray-200 p-5 mb-4">
            <div class="flex items-center justify-between flex-wrap gap-2">
                <div>
                    <p class="text-sm font-mono text-gray-800">{{ $file['path'] }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">Branch: <span class="font-mono">{{ $activeBranch }}</span></p>
                </div>
                @if($file['html_url'] ?? null)
                <a href="{{ $file['html_url'] }}" target="_blank" rel="noopener" class="text-xs text-gray-500 hover:text-blue-600">Lihat di GitHub ↗</a>
                @endif
            </div>
        </div>

        @if($file['binary'] ?? false)
            <div class="bg-amber-50 border border-amber-200 text-amber-700 text-sm rounded-xl p-4">
                File ini terdeteksi sebagai file biner dan tidak bisa diedit di sini. Gunakan tautan "Lihat di GitHub" di atas.
            </div>
        @else
            <form method="POST" action="{{ route('github.files.update', $project) }}" class="space-y-3">
                @csrf
                @method('PUT')
                <input type="hidden" name="path" value="{{ $file['path'] }}">
                <input type="hidden" name="branch" value="{{ $activeBranch }}">
                <input type="hidden" name="sha" value="{{ $file['sha'] }}">

                <textarea name="content" rows="24" spellcheck="false"
                          class="w-full rounded-lg border-gray-300 font-mono text-xs leading-relaxed focus:border-indigo-500 focus:ring-indigo-500">{{ old('content', $file['content']) }}</textarea>
                @error('content')<p class="text-xs text-red-600">{{ $message }}</p>@enderror

                <div class="flex items-center gap-3">
                    <input type="text" name="message" placeholder="Pesan commit (opsional)"
                           class="flex-1 rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <button type="submit"
                            class="inline-flex items-center gap-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium px-4 py-2.5 rounded-lg transition-colors shrink-0">
                        Commit Perubahan
                    </button>
                </div>
                <p class="text-xs text-gray-400">
                    Perubahan akan langsung di-commit ke branch <span class="font-mono">{{ $activeBranch }}</span> di GitHub.
                </p>
            </form>
        @endif
    @endif
</div>
@endsection