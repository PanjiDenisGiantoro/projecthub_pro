@extends('layouts.app')
@section('title', 'Edit User')
@section('page-title', 'Edit User')

@section('content')
<div class="py-4 max-w-lg">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-5">
            @csrf @method('PUT')
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></div>

            <div><label class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></div>

            <div><label class="block text-sm font-medium text-gray-700 mb-1">Role <span class="text-red-500">*</span></label>
            <select name="role" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                @foreach($roles as $role)
                    <option value="{{ $role->name }}" {{ old('role', $user->getRoleNames()->first()) === $role->name ? 'selected' : '' }}>{{ ucfirst($role->name) }}</option>
                @endforeach
            </select></div>

            <div><label class="block text-sm font-medium text-gray-700 mb-1">Level Struktural</label>
            <select name="structural_level_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">— Tidak Ditentukan —</option>
                @foreach($structuralLevels as $level)
                    <option value="{{ $level->id }}" {{ old('structural_level_id', $user->structural_level_id) == $level->id ? 'selected' : '' }}>
                        {{ $level->sort_order }}. {{ $level->name }}
                    </option>
                @endforeach
            </select></div>

            <div><label class="block text-sm font-medium text-gray-700 mb-1">Timezone</label>
            <select name="timezone" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                @foreach(['Asia/Jakarta','Asia/Makassar','Asia/Jayapura','UTC'] as $tz)
                    <option value="{{ $tz }}" {{ old('timezone', $user->timezone) === $tz ? 'selected' : '' }}>{{ $tz }}</option>
                @endforeach
            </select></div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_active" value="1" id="is_active" {{ $user->is_active ? 'checked' : '' }} class="w-4 h-4 text-blue-600 rounded">
                <label for="is_active" class="text-sm text-gray-700">Akun Aktif</label>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-6 py-2.5 rounded-lg transition-colors">Simpan</button>
                <a href="{{ route('users.index') }}" class="text-gray-600 text-sm font-medium px-4 py-2.5 rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
