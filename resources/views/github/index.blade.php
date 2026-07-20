@extends('layouts.app')
@section('title', 'GitHub — ' . $project->name)
@section('page-title', 'Integrasi GitHub')

@section('content')
<div class="py-4">
    <nav class="text-sm text-gray-500 mb-4">
        <a href="{{ route('projects.show', $project) }}" class="hover:text-blue-600">{{ $project->name }}</a>
        <span class="mx-2">/</span><span class="text-gray-700">GitHub</span>
    </nav>

    @if(session('success'))
    <div class="mb-4 px-4 py-3 rounded-lg bg-emerald-50 text-emerald-700 text-sm font-medium">
        {{ session('success') }}
    </div>
    @endif

    @if(!$project->hasGithubIntegration())
        {{-- ── Connect Form ─────────────────────────────────────────────── --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 max-w-xl">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-gray-900 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 .3a12 12 0 00-3.8 23.4c.6.1.8-.3.8-.6v-2.2c-3.3.7-4-1.6-4-1.6-.6-1.4-1.4-1.8-1.4-1.8-1.1-.8.1-.8.1-.8 1.2.1 1.9 1.2 1.9 1.2 1.1 1.9 2.9 1.3 3.6 1 .1-.8.4-1.3.8-1.6-2.7-.3-5.5-1.3-5.5-5.9 0-1.3.5-2.4 1.2-3.2-.1-.3-.5-1.5.1-3.2 0 0 1-.3 3.3 1.2a11.5 11.5 0 016 0c2.3-1.5 3.3-1.2 3.3-1.2.6 1.7.2 2.9.1 3.2.8.8 1.2 1.9 1.2 3.2 0 4.6-2.8 5.6-5.5 5.9.4.4.8 1.1.8 2.2v3.3c0 .3.2.7.8.6A12 12 0 0012 .3z"/></svg>
                </div>
                <div>
                    <h2 class="text-base font-semibold text-gray-900">Hubungkan Repo GitHub</h2>
                    <p class="text-sm text-gray-500">Pantau commit &amp; pull request langsung dari proyek ini.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('github.store', $project) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">URL Repository</label>
                    <input type="url" name="github_repo_url" required placeholder="https://github.com/owner/repo"
                           value="{{ old('github_repo_url') }}"
                           class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('github_repo_url')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Personal Access Token</label>
                    <input type="password" name="github_token" required placeholder="ghp_xxxxxxxxxxxxxxxxxxxx"
                           class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <p class="text-xs text-gray-400 mt-1">
                        Buat token di GitHub → Settings → Developer settings → Personal access tokens, scope <code class="bg-gray-100 px-1 rounded">repo</code> (read-only cukup untuk repo publik).
                        Token disimpan terenkripsi.
                    </p>
                    @error('github_token')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <button type="submit"
                        class="inline-flex items-center gap-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium px-4 py-2.5 rounded-lg transition-colors">
                    Hubungkan Repo
                </button>
            </form>
        </div>
    @else
        {{-- ── Connected: Summary ───────────────────────────────────────── --}}
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 .3a12 12 0 00-3.8 23.4c.6.1.8-.3.8-.6v-2.2c-3.3.7-4-1.6-4-1.6-.6-1.4-1.4-1.8-1.4-1.8-1.1-.8.1-.8.1-.8 1.2.1 1.9 1.2 1.9 1.2 1.1 1.9 2.9 1.3 3.6 1 .1-.8.4-1.3.8-1.6-2.7-.3-5.5-1.3-5.5-5.9 0-1.3.5-2.4 1.2-3.2-.1-.3-.5-1.5.1-3.2 0 0 1-.3 3.3 1.2a11.5 11.5 0 016 0c2.3-1.5 3.3-1.2 3.3-1.2.6 1.7.2 2.9.1 3.2.8.8 1.2 1.9 1.2 3.2 0 4.6-2.8 5.6-5.5 5.9.4.4.8 1.1.8 2.2v3.3c0 .3.2.7.8.6A12 12 0 0012 .3z"/></svg>
                <span class="font-mono">{{ $project->githubOwnerRepo() }}</span>
            </div>
            <div class="flex items-center gap-2">
                <form method="POST" action="{{ route('github.refresh', $project) }}">
                    @csrf
                    <button type="submit" class="text-sm font-medium text-gray-600 hover:text-gray-900 px-3 py-2 rounded-lg border border-gray-200 hover:bg-gray-50 transition">
                        ↻ Refresh
                    </button>
                </form>
                <form method="POST" action="{{ route('github.destroy', $project) }}" onsubmit="return confirm('Putuskan integrasi repo GitHub ini?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-700 px-3 py-2 rounded-lg border border-red-200 hover:bg-red-50 transition">
                        Putuskan
                    </button>
                </form>
            </div>
        </div>

        @if(!($summary['ok'] ?? false))
            <div class="bg-amber-50 border border-amber-200 text-amber-700 text-sm rounded-xl p-4">
                {{ $summary['message'] ?? 'Gagal mengambil data dari GitHub.' }}
            </div>
        @else
            @php $repo = $summary['repo']; @endphp
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-5">
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Default Branch</p>
                    <p class="text-lg font-bold text-gray-800 font-mono">{{ $repo['default_branch'] }}</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Open Issues</p>
                    <p class="text-lg font-bold text-gray-800">{{ $repo['open_issues'] }}</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Stars</p>
                    <p class="text-lg font-bold text-gray-800">{{ $repo['stars'] }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                {{-- Commits --}}
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h3 class="text-sm font-semibold text-gray-800">Commit Terbaru</h3>
                    </div>
                    <div class="divide-y divide-gray-50">
                        @forelse($summary['commits'] as $c)
                        <a href="{{ $c['url'] }}" target="_blank" rel="noopener" class="flex items-start gap-3 px-5 py-3 hover:bg-gray-50 transition">
                            <span class="font-mono text-xs text-gray-400 mt-0.5 shrink-0">{{ $c['sha'] }}</span>
                            <div class="min-w-0">
                                <p class="text-sm text-gray-800 truncate">{{ $c['message'] }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $c['author'] }} · {{ \Carbon\Carbon::parse($c['date'])->diffForHumans() }}</p>
                            </div>
                        </a>
                        @empty
                        <p class="px-5 py-8 text-center text-sm text-gray-400">Belum ada commit.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Pull Requests --}}
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h3 class="text-sm font-semibold text-gray-800">Pull Request Terbuka</h3>
                    </div>
                    <div class="divide-y divide-gray-50">
                        @forelse($summary['pulls'] as $p)
                        <a href="{{ $p['url'] }}" target="_blank" rel="noopener" class="flex items-start gap-3 px-5 py-3 hover:bg-gray-50 transition">
                            <span class="font-mono text-xs text-emerald-600 mt-0.5 shrink-0">#{{ $p['number'] }}</span>
                            <div class="min-w-0">
                                <p class="text-sm text-gray-800 truncate">{{ $p['title'] }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $p['author'] }} · {{ \Carbon\Carbon::parse($p['date'])->diffForHumans() }}</p>
                            </div>
                        </a>
                        @empty
                        <p class="px-5 py-8 text-center text-sm text-gray-400">Tidak ada pull request terbuka.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
@endsection