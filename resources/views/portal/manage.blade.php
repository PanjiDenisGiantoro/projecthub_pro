@extends('layouts.app')
@section('title', 'Client Portal — ' . $project->name)
@section('page-title', 'Client Portal')

@section('content')
<div class="py-4" x-data="{showForm:false}">
    <nav class="text-sm text-gray-500 mb-4">
        <a href="{{ route('projects.show', $project) }}" class="hover:text-blue-600">{{ $project->name }}</a>
        <span class="mx-2">/</span><span class="text-gray-700">Client Portal</span>
    </nav>

    <div class="flex justify-between items-center mb-5">
        <p class="text-sm text-gray-500">{{ $tokens->count() }} link portal dibuat</p>
        <button @click="showForm=!showForm"
                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
            <span x-text="showForm ? 'Batal' : 'Buat Link Baru'"></span>
        </button>
    </div>

    @if(session('new_token'))
    <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-5">
        <p class="text-sm font-semibold text-green-800 mb-2">Link portal baru berhasil dibuat! Bagikan link ini ke klien:</p>
        <div class="flex items-center gap-2">
            <input type="text" readonly value="{{ route('portal.view', session('new_token')) }}"
                   class="flex-1 px-3 py-2 border border-green-300 rounded-lg text-sm bg-white font-mono text-green-800"
                   onclick="this.select()">
            <button onclick="navigator.clipboard.writeText('{{ route('portal.view', session('new_token')) }}'); this.textContent='Disalin!'"
                    class="text-sm text-green-700 hover:text-green-900 border border-green-300 px-3 py-2 rounded-lg">Salin</button>
        </div>
    </div>
    @endif

    <div x-show="showForm" x-cloak class="bg-white rounded-xl border border-blue-200 p-5 mb-5">
        <h4 class="text-sm font-semibold text-gray-700 mb-4">Buat Link Portal</h4>
        <form method="POST" action="{{ route('portal.store', $project) }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Label</label>
                <input type="text" name="label" placeholder="e.g. PT Maju Jaya — Review Sprint 3"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="grid grid-cols-3 gap-3">
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="can_comment" value="1" class="rounded border-gray-300">
                    Bisa Komentar
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="can_approve" value="1" class="rounded border-gray-300">
                    Bisa Approve
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="show_budget" value="1" class="rounded border-gray-300">
                    Tampilkan Budget
                </label>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Kadaluarsa</label>
                <input type="datetime-local" name="expires_at"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p class="text-xs text-gray-400 mt-1">Kosongkan untuk tidak ada kadaluarsa.</p>
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2 rounded-lg">Buat Link</button>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        @if($tokens->isEmpty())
        <div class="text-center py-12 text-gray-400">
            <p class="text-3xl mb-2">🔗</p>
            <p class="font-medium text-gray-500">Belum ada portal link.</p>
        </div>
        @else
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Label</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Izin</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Kadaluarsa</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Terakhir Diakses</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($tokens as $token)
                <tr class="{{ $token->isExpired() ? 'opacity-50' : '' }}">
                    <td class="px-4 py-3">
                        <p class="font-medium text-gray-800">{{ $token->label ?? '(Tanpa label)' }}</p>
                        <p class="text-xs text-gray-400 font-mono">{{ Str::limit($token->token, 20) }}...</p>
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500 space-x-1">
                        @if($token->can_comment)<span class="bg-blue-50 text-blue-600 rounded-full px-2 py-0.5">Komentar</span>@endif
                        @if($token->can_approve)<span class="bg-green-50 text-green-600 rounded-full px-2 py-0.5">Approve</span>@endif
                        @if($token->show_budget)<span class="bg-yellow-50 text-yellow-600 rounded-full px-2 py-0.5">Budget</span>@endif
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500">
                        {{ $token->expires_at ? $token->expires_at->format('d/m/Y H:i') : '∞' }}
                        @if($token->isExpired())<span class="text-red-500 font-medium"> (Expired)</span>@endif
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500">{{ $token->last_accessed_at?->diffForHumans() ?? '—' }}</td>
                    <td class="px-4 py-3 text-right flex gap-2 justify-end">
                        @if(!$token->isExpired())
                        <a href="{{ route('portal.view', $token->token) }}" target="_blank"
                           class="text-xs text-blue-600 hover:text-blue-800">Buka</a>
                        @endif
                        <form method="POST" action="{{ route('portal.destroy', [$project, $token]) }}"
                              data-confirm-delete="{{ $token->label ?? 'token ini' }}">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs text-red-500 hover:text-red-700">Cabut</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>
@endsection
