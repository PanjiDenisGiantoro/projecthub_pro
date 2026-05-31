@extends('layouts.app')
@section('title', 'Edit Role')
@section('page-title', 'Edit Role')

@section('content')
<div class="py-4 max-w-md">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-xs text-gray-400 mb-5">
        <a href="{{ route('roles.index') }}" class="hover:text-blue-600 transition-colors">Manajemen Role</a>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-600 font-medium">{{ ucfirst($role->name) }}</span>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <form method="POST" action="{{ route('roles.update', $role) }}" class="space-y-5">
            @csrf @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Role <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $role->name) }}" required
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 @error('name') border-red-400 @enderror">
                <p class="text-xs text-gray-400 mt-1">Hanya huruf, angka, tanda hubung, dan underscore. Akan disimpan dalam huruf kecil.</p>
                @error('name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="bg-blue-50 border border-blue-100 rounded-lg px-4 py-3 text-xs text-blue-700">
                Role ini digunakan oleh <strong>{{ $role->users_count ?? $role->users()->count() }}</strong> user.
                Mengubah nama role akan mempengaruhi semua user yang memiliki role ini.
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium px-6 py-2.5 rounded-lg transition-colors">
                    Perbarui
                </button>
                <a href="{{ route('roles.index') }}" class="text-gray-600 text-sm font-medium px-4 py-2.5 rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
