@extends('layouts.app')
@section('title', 'User Management')
@section('page-title', 'User Management')

@section('content')
<div class="py-4">
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-4">
        <form method="GET" class="flex gap-2 flex-1 flex-wrap">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama / email..."
                   class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-56">
            @if($isAdmin)
            <select name="role" onchange="this.form.submit()" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Semua Role</option>
                @foreach($roles as $role)
                    <option value="{{ $role->name }}" {{ request('role') === $role->name ? 'selected' : '' }}>{{ \App\Support\RoleLabel::for($role->name) }}</option>
                @endforeach
            </select>
            @endif
            <button type="submit" class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm px-4 py-2 rounded-lg transition-colors">Filter</button>
        </form>
        @if($canCreate)
        <a href="{{ route('users.create') }}" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
            Tambah User
        </a>
        @endif
    </div>

    {{-- Stats summary --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-4">
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3 flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs text-gray-500">Total User</p>
                <p class="text-xl font-bold text-gray-800">{{ $totalUsers }}</p>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3 flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-green-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs text-gray-500">Aktif</p>
                <p class="text-xl font-bold text-gray-800">{{ $activeUsers }}</p>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3 flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-gray-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                </svg>
            </div>
            <div>
                <p class="text-xs text-gray-500">Nonaktif</p>
                <p class="text-xl font-bold text-gray-800">{{ $inactiveUsers }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 text-left">Nama</th>
                    <th class="px-4 py-3 text-left">Email</th>
                    <th class="px-4 py-3 text-left">Role</th>
                    <th class="px-4 py-3 text-left">Proyek</th>
                    @if(session('active_package') === 'hris')
                    <th class="px-4 py-3 text-left">Level Struktural</th>
                    <th class="px-4 py-3 text-left">Departemen</th>
                    @endif
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Bergabung</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($users as $u)
                @php
                    $rc = ['admin'=>'bg-red-100 text-red-700','member'=>'bg-purple-100 text-purple-700','client'=>'bg-green-100 text-green-700'];
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
                            <span class="badge {{ $rc[$role] ?? 'bg-gray-100 text-gray-700' }}">{{ \App\Support\RoleLabel::for($role) }}</span>
                        @endforeach
                    </td>
                    <td class="px-4 py-3">
                        @forelse($u->projects as $proj)
                            <a href="{{ route('projects.show', $proj) }}"
                               class="inline-block px-2 py-0.5 mb-1 mr-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 hover:bg-blue-100 transition-colors">
                                {{ $proj->name }}
                            </a>
                        @empty
                            <span class="text-gray-300 text-xs">—</span>
                        @endforelse
                    </td>
                    @if(session('active_package') === 'hris')
                    <td class="px-4 py-3">
                        @if($u->structuralLevel)
                            <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 bg-amber-50 text-amber-700 rounded-full font-medium">
                                {{ $u->structuralLevel->name }}
                            </span>
                        @else
                            <span class="text-gray-300 text-xs">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($u->organizationUnit)
                            <span class="text-xs text-gray-700">{{ $u->organizationUnit->name }}</span>
                        @else
                            <span class="text-gray-300 text-xs">—</span>
                        @endif
                    </td>
                    @endif
                    <td class="px-4 py-3">
                        <span class="badge {{ $u->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $u->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-500">{{ $u->created_at->format('d M Y') }}</td>
                    <td class="px-4 py-3">
                        @if($canCreate || $canUpdate || $canDelete)
                        <div class="flex gap-3 flex-wrap">
                            @if($canUpdate)
                            <a href="{{ route('users.edit', $u) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Edit</a>
                            @endif
                            @can('view payroll')
                            @if(Route::has('hris.salary.index') && session('active_package') === 'hris')
                            <a href="{{ route('hris.salary.index', $u) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Gaji</a>
                            @endif
                            @endcan
                            @if($canDelete && $u->id !== auth()->id())
                            <form method="POST" action="{{ route('users.destroy', $u) }}"
                                  data-confirm-delete="{{ $u->name }}" data-confirm-label="Hapus User">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700 text-sm">Hapus</button>
                            </form>
                            @endif
                        </div>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="{{ session('active_package') === 'hris' ? 9 : 7 }}" class="px-4 py-8 text-center text-gray-400">Tidak ada user ditemukan.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-gray-100 flex items-center justify-between gap-3 flex-wrap">
            <x-per-page />
            @if($users->hasPages())
            {{ $users->links() }}
            @endif
        </div>
    </div>
</div>
@endsection
