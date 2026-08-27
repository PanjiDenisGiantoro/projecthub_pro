@extends('layouts.app')
@section('title', 'Field Kustom Karyawan')
@section('page-title', 'Field Kustom Karyawan')

@section('content')
<div class="py-4 max-w-3xl">
    <div class="flex items-start justify-between gap-4 mb-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-800">Field Kustom Karyawan</h1>
            <p class="text-sm text-gray-500 mt-1">Tambahkan field khusus perusahaan Anda sendiri — akan otomatis muncul di form Tambah/Edit User, dan cuma berlaku untuk perusahaan Anda.</p>
        </div>
        <a href="{{ route('users.index') }}" class="text-sm text-gray-500 hover:text-gray-700 shrink-0">← Kembali</a>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-xl px-4 py-3 mb-4">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200">
        <div class="flex items-center justify-between p-4 border-b border-gray-100">
            <div>
                <h3 class="font-semibold text-gray-900">Daftar Field</h3>
                <p class="text-xs text-gray-500 mt-0.5">{{ $fields->count() }} field terdaftar</p>
            </div>
            <button onclick="document.getElementById('modal-add-field').showModal()"
                    class="text-xs bg-blue-600 text-white px-3 py-1.5 rounded-lg hover:bg-blue-700">+ Tambah Field</button>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Label</th>
                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Key</th>
                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Tipe</th>
                    <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-500 uppercase">Wajib</th>
                    <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($fields as $field)
                <tr>
                    <td class="px-4 py-3 text-gray-700 font-medium">{{ $field->label }}</td>
                    <td class="px-4 py-3 font-mono text-xs text-gray-400">{{ $field->key }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ \App\Models\CustomFieldDefinition::TYPES[$field->type] ?? $field->type }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="{{ $field->is_required ? 'text-green-600' : 'text-gray-400' }}">{{ $field->is_required ? '✓' : '—' }}</span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <form action="{{ route('custom-fields.toggle', $field) }}" method="POST" class="inline">
                            @csrf @method('PATCH')
                            <button class="text-xs {{ $field->is_active ? 'text-green-600' : 'text-gray-400' }}">
                                {{ $field->is_active ? 'Aktif' : 'Nonaktif' }}
                            </button>
                        </form>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <form action="{{ route('custom-fields.destroy', $field) }}" method="POST" class="inline"
                              data-confirm-delete="field {{ $field->label }}">
                            @csrf @method('DELETE')
                            <button class="text-xs text-red-500 hover:text-red-700">Hapus</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-6 text-center text-gray-400 text-sm">Belum ada field kustom.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Modal Tambah --}}
<dialog id="modal-add-field" class="rounded-2xl p-6 shadow-xl w-full max-w-lg" x-data="{ type: 'text' }">
    <form action="{{ route('custom-fields.store') }}" method="POST" class="space-y-4">
        @csrf
        <h3 class="font-bold text-gray-900 text-lg">Tambah Field Kustom</h3>

        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Label *</label>
            <input type="text" name="label" required placeholder="mis. Nomor BPJS Kesehatan"
                   class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Tipe Input *</label>
            <select name="type" x-model="type" required
                    class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none bg-white">
                @foreach(\App\Models\CustomFieldDefinition::TYPES as $val => $label)
                    <option value="{{ $val }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div x-show="type === 'select'" x-cloak>
            <label class="block text-xs font-medium text-gray-700 mb-1">Pilihan (pisahkan dengan koma)</label>
            <input type="text" name="options" placeholder="mis. Golongan A, Golongan B, Golongan C"
                   class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_required" value="1"> Wajib diisi
        </label>

        <div class="flex gap-2 justify-end pt-2">
            <button type="button" onclick="document.getElementById('modal-add-field').close()" class="px-4 py-2 text-sm text-gray-500 border border-gray-200 rounded-xl">Batal</button>
            <button class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-xl hover:bg-blue-700">Simpan</button>
        </div>
    </form>
</dialog>
@endsection
