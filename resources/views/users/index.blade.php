@extends('layouts.app')
@section('title', 'User Management')
@section('page-title', 'User Management')

@section('content')
<div class="py-4">
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-4">
        <form method="GET" class="flex gap-2 flex-1 flex-wrap">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama / email..."
                   class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-56">
            <select name="role" onchange="this.form.submit()" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Semua Role</option>
                @foreach($roles as $role)
                    <option value="{{ $role->name }}" {{ request('role') === $role->name ? 'selected' : '' }}>{{ ucfirst($role->name) }}</option>
                @endforeach
            </select>
            <button type="submit" class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm px-4 py-2 rounded-lg transition-colors">Filter</button>
        </form>
        <a href="{{ route('users.create') }}" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
            Tambah User
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 text-left">Nama</th>
                    <th class="px-4 py-3 text-left">Email</th>
                    <th class="px-4 py-3 text-left">Role</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Bergabung</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($users as $u)
                @php
                    $rc = ['admin'=>'bg-red-100 text-red-700','manager'=>'bg-purple-100 text-purple-700','developer'=>'bg-blue-100 text-blue-700','marketing'=>'bg-orange-100 text-orange-700','customer'=>'bg-green-100 text-green-700'];
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-bold">
                                {{ strtoupper(substr($u->name,0,2)) }}
                            </div>
                            <span class="font-medium text-gray-800">{{ $u->name }}</span>
                            @if($u->id === auth()->id())
                                <span class="badge bg-gray-100 text-gray-500 text-xs">Anda</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $u->email }}</td>
                    <td class="px-4 py-3">
                        @foreach($u->getRoleNames() as $role)
                            <span class="badge {{ $rc[$role] ?? 'bg-gray-100 text-gray-700' }}">{{ ucfirst($role) }}</span>
                        @endforeach
                    </td>
                    <td class="px-4 py-3">
                        <span class="badge {{ $u->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $u->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-500">{{ $u->created_at->format('d M Y') }}</td>
                    <td class="px-4 py-3">
                        <div class="flex gap-3">
                            <a href="{{ route('users.edit', $u) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Edit</a>
                            @if($u->id !== auth()->id())
                            <form method="POST" action="{{ route('users.destroy', $u) }}" onsubmit="return confirm('Hapus user {{ $u->name }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700 text-sm">Hapus</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">Tidak ada user ditemukan.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($users->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $users->links() }}</div>
        @endif
    </div>
</div>
@endsection
