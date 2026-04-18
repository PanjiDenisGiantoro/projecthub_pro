@extends('layouts.app')
@section('title', 'Recurring Tasks — ' . $project->name)
@section('page-title', 'Recurring Tasks')

@section('content')
<div class="py-4" x-data="{showForm:false}">
    <nav class="text-sm text-gray-500 mb-4">
        <a href="{{ route('projects.show', $project) }}" class="hover:text-blue-600">{{ $project->name }}</a>
        <span class="mx-2">/</span><span class="text-gray-700">Recurring Tasks</span>
    </nav>

    <div class="flex justify-between items-center mb-5">
        <p class="text-sm text-gray-500">{{ $definitions->count() }} definisi terdaftar</p>
        @if(!auth()->user()->hasRole('customer'))
        <button @click="showForm=!showForm"
                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span x-text="showForm ? 'Batal' : 'Tambah'"></span>
        </button>
        @endif
    </div>

    @if(!auth()->user()->hasRole('customer'))
    <div x-show="showForm" x-cloak class="bg-white rounded-xl border border-blue-200 p-5 mb-5">
        <h4 class="text-sm font-semibold text-gray-700 mb-4">Definisi Baru</h4>
        <form method="POST" action="{{ route('recurring.store', $project) }}" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @csrf
            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Judul Task *</label>
                <input type="text" name="title" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Frekuensi *</label>
                <select name="frequency" required x-model="freq"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        x-data="{freq:'daily'}">
                    <option value="daily">Harian</option>
                    <option value="weekly">Mingguan</option>
                    <option value="biweekly">2 Minggu sekali</option>
                    <option value="monthly">Bulanan</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Assignee</label>
                <select name="assigned_to" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">— Tidak ada —</option>
                    @foreach($users as $u)
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Prioritas *</label>
                <select name="priority" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="low">Low</option>
                    <option value="medium" selected>Medium</option>
                    <option value="high">High</option>
                    <option value="critical">Critical</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Due Offset (hari)</label>
                <input type="number" name="due_offset_days" value="1" min="0" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Est. Jam</label>
                <input type="number" name="estimated_hours" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="sm:col-span-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2 rounded-lg">Simpan</button>
            </div>
        </form>
    </div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        @if($definitions->isEmpty())
        <div class="text-center py-12 text-gray-400">
            <p class="text-3xl mb-2">🔄</p>
            <p class="font-medium text-gray-500">Belum ada recurring task.</p>
        </div>
        @else
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Judul</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Frekuensi</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Assignee</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Terakhir Dibuat</th>
                    @if(!auth()->user()->hasRole('customer'))
                    <th class="px-4 py-3"></th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($definitions as $def)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $def->title }}</td>
                    <td class="px-4 py-3 text-gray-600 capitalize">{{ $def->frequency }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $def->assignee?->name ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $def->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $def->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $def->last_generated_at?->format('d/m/Y') ?? '—' }}</td>
                    @if(!auth()->user()->hasRole('customer'))
                    <td class="px-4 py-3 text-right">
                        <form method="POST" action="{{ route('recurring.destroy', [$project, $def]) }}"
                              data-confirm-delete="{{ $def->title }}">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs text-red-500 hover:text-red-700">Hapus</button>
                        </form>
                    </td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>
@endsection
