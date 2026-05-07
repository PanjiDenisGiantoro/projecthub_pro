@extends('layouts.app')
@section('title', 'Manajemen Role')
@section('page-title', 'Manajemen Role')

@section('content')
<div class="py-4">

    {{-- Toolbar --}}
    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-gray-500">Total <span class="font-semibold text-gray-800">{{ $roles->count() }}</span> role terdaftar</p>
        <a href="{{ route('roles.create') }}" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Role
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        @php
            $protected = ['admin','manager','developer','marketing','customer'];
            $roleColors = [
                'admin'     => 'bg-red-100 text-red-700',
                'manager'   => 'bg-purple-100 text-purple-700',
                'developer' => 'bg-blue-100 text-blue-700',
                'marketing' => 'bg-orange-100 text-orange-700',
                'customer'  => 'bg-green-100 text-green-700',
            ];
        @endphp

        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Nama Role</th>
                    <th class="text-center px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider w-32">Jumlah User</th>
                    <th class="text-center px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider w-28">Tipe</th>
                    <th class="px-5 py-3 w-32"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($roles as $role)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold {{ $roleColors[$role->name] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ ucfirst($role->name) }}
                            </span>
                            @if(in_array($role->name, $protected))
                                <span class="text-xs text-gray-400 flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                    default
                                </span>
                            @endif
                        </div>
                    </td>
                    <td class="px-5 py-3.5 text-center">
                        <span class="font-medium text-gray-700">{{ $role->users_count }}</span>
                        <span class="text-gray-400 text-xs ml-1">user</span>
                    </td>
                    <td class="px-5 py-3.5 text-center">
                        @if(in_array($role->name, $protected))
                            <span class="text-xs px-2 py-0.5 bg-gray-100 text-gray-500 rounded-full">Sistem</span>
                        @else
                            <span class="text-xs px-2 py-0.5 bg-blue-50 text-blue-600 rounded-full">Custom</span>
                        @endif
                    </td>
                    <td class="px-5 py-3.5">
                        <div class="flex items-center justify-end gap-3">
                            @if(!in_array($role->name, $protected))
                                <a href="{{ route('roles.edit', $role) }}" class="text-xs text-gray-500 hover:text-blue-600 font-medium transition-colors">Edit</a>
                            @endif
                            <a href="{{ route('permissions.index') }}#{{ $role->name }}" class="text-xs text-gray-500 hover:text-indigo-600 font-medium transition-colors">Permission</a>
                            @if(!in_array($role->name, $protected))
                                <form method="POST" action="{{ route('roles.destroy', $role) }}"
                                      data-confirm-delete="{{ $role->name }}" data-confirm-label="Hapus Role">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs text-red-500 hover:text-red-700 transition-colors">Hapus</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-5 py-10 text-center text-gray-400">Belum ada role.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="text-xs text-gray-400 mt-3">
        <svg class="w-3.5 h-3.5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Role bertanda <strong>Sistem</strong> tidak bisa diedit atau dihapus. Untuk mengatur permission per role, gunakan menu
        <a href="{{ route('permissions.index') }}" class="text-blue-500 hover:underline">Permission Management</a>.
    </p>
</div>
@endsection
