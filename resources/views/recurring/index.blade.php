@extends('layouts.app')
@section('title', 'Recurring Tasks — ' . $project->name)
@section('page-title', 'Recurring Tasks')

@section('content')
<div class="py-4" x-data="{showForm:false, freq:'daily'}">
    <nav class="text-sm text-gray-500 mb-4">
        <a href="{{ route('projects.show', $project) }}" class="hover:text-blue-600">{{ $project->name }}</a>
        <span class="mx-2">/</span><span class="text-gray-700">Recurring Tasks</span>
    </nav>

    <div class="flex justify-between items-center mb-5">
        <p class="text-sm text-gray-500">{{ $definitions->count() }} definisi terdaftar</p>
        @if(!auth()->user()->hasRole('customer'))
        <button @click="showForm=!showForm"
                class="inline-flex items-center gap-2 bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
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
                <input type="text" name="title" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Deskripsi</label>
                <textarea name="description" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500"></textarea>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Frekuensi *</label>
                <select name="frequency" required x-model="freq"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                    <option value="daily">Harian</option>
                    <option value="weekly">Mingguan</option>
                    <option value="biweekly">2 Minggu sekali</option>
                    <option value="monthly">Bulanan</option>
                </select>
            </div>
            <div x-show="freq==='weekly' || freq==='biweekly'" x-cloak>
                <label class="block text-xs font-medium text-gray-600 mb-1">Hari (untuk Mingguan/2 Minggu sekali)</label>
                <select name="day_of_week" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                    <option value="1" selected>Senin</option>
                    <option value="2">Selasa</option>
                    <option value="3">Rabu</option>
                    <option value="4">Kamis</option>
                    <option value="5">Jumat</option>
                    <option value="6">Sabtu</option>
                    <option value="0">Minggu</option>
                </select>
            </div>
            <div x-show="freq==='monthly'" x-cloak>
                <label class="block text-xs font-medium text-gray-600 mb-1">Tanggal (untuk Bulanan)</label>
                <input type="number" name="day_of_month" value="1" min="1" max="28" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                <p class="text-[11px] text-gray-400 mt-1">Maks tanggal 28 supaya berlaku di semua bulan.</p>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Milestone (opsional)</label>
                <select name="milestone_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                    <option value="">— Tidak ada —</option>
                    @foreach($milestones as $m)
                    <option value="{{ $m->id }}">{{ $m->title }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Assignee</label>
                <select name="assigned_to" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                    <option value="">— Tidak ada —</option>
                    @foreach($users as $u)
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Prioritas *</label>
                <select name="priority" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                    <option value="low">Low</option>
                    <option value="medium" selected>Medium</option>
                    <option value="high">High</option>
                    <option value="critical">Critical</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Due Offset (hari)</label>
                <input type="number" name="due_offset_days" value="1" min="0" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Est. Jam</label>
                <input type="number" name="estimated_hours" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
            </div>
            <div class="sm:col-span-2">
                <button type="submit" class="bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium px-5 py-2 rounded-lg">Simpan</button>
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
                    <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Task Dibuat</th>
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
                    <td class="px-4 py-3 text-center text-gray-600">{{ $def->tasks_count }}</td>
                    @if(!auth()->user()->hasRole('customer'))
                    <td class="px-4 py-3 text-right">
                        <div class="flex gap-3 justify-end items-center">
                            <form method="POST" action="{{ route('recurring.generateNow', [$project, $def]) }}"
                                  data-confirm-submit="Buat task dari definisi &quot;{{ $def->title }}&quot; sekarang?" data-confirm-btn="Ya, Generate">
                                @csrf
                                <button type="submit" class="text-xs text-violet-600 hover:text-violet-800 font-medium">Generate Sekarang</button>
                            </form>
                            <form method="POST" action="{{ route('recurring.destroy', [$project, $def]) }}"
                                  data-confirm-delete="{{ $def->title }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs text-red-500 hover:text-red-700">Hapus</button>
                            </form>
                        </div>
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
