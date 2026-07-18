@extends('layouts.app')
@section('title', 'Admin Tim')
@section('page-title', 'Admin Tim')

@section('content')
<div class="py-4">
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-4">
        <form method="GET" class="flex gap-2 flex-1 flex-wrap">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama / email..."
                   class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 w-56">
            <button type="submit" class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm px-4 py-2 rounded-lg transition-colors">Filter</button>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 text-left">Nama</th>
                    <th class="px-4 py-3 text-left">Email</th>
                    <th class="px-4 py-3 text-left">Role</th>
                    <th class="px-4 py-3 text-left">Departemen</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Bergabung</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($users as $u)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-red-100 text-red-700 flex items-center justify-center text-xs font-bold">
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
                            <span class="badge bg-red-100 text-red-700">{{ ucfirst($role) }}</span>
                        @endforeach
                    </td>
                    <td class="px-4 py-3">
                        @if($u->organizationUnit)
                            <span class="text-xs text-gray-700">{{ $u->organizationUnit->name }}</span>
                        @else
                            <span class="text-gray-300 text-xs">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <span class="badge {{ $u->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $u->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-500">{{ $u->created_at->format('d M Y') }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">Tidak ada admin ditemukan.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($users->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $users->links() }}</div>
        @endif
    </div>
</div>
@endsection
