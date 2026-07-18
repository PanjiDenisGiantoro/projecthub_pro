@extends('superadmin.layout')
@section('title', 'Semua User')
@section('page-title', 'Semua User')

@section('content')

<div class="bg-slate-800/60 border border-white/5 rounded-2xl overflow-hidden">
    <div class="px-6 py-4 border-b border-white/5">
        <p class="text-sm text-slate-400">Total: <span class="text-white font-semibold">{{ $users->total() }}</span> user</p>
    </div>

    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-white/5 text-xs text-slate-500 uppercase tracking-wide">
                <th class="text-left px-6 py-3 font-medium">User</th>
                <th class="text-left px-6 py-3 font-medium">Perusahaan</th>
                <th class="text-left px-6 py-3 font-medium">Role</th>
                <th class="text-left px-6 py-3 font-medium">Terdaftar</th>
                <th class="text-center px-6 py-3 font-medium">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-white/5">
            @forelse($users as $user)
            @php $company = $user->organizationUnit?->company; @endphp
            <tr class="hover:bg-white/2 transition-colors">
                <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-white font-bold text-xs shrink-0"
                             style="background: linear-gradient(135deg, #6366f1, #8b5cf6)">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </div>
                        <div>
                            <p class="font-medium text-white">{{ $user->name }}</p>
                            <p class="text-xs text-slate-500">{{ $user->email }}</p>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 text-slate-300 text-xs">{{ $company?->name ?? '—' }}</td>
                <td class="px-6 py-4">
                    @foreach($user->getRoleNames() as $role)
                    <span class="bg-indigo-500/15 text-indigo-400 text-xs font-medium px-2 py-0.5 rounded-full capitalize">{{ $role }}</span>
                    @endforeach
                </td>
                <td class="px-6 py-4 text-slate-400 text-xs">{{ $user->created_at->format('d M Y') }}</td>
                <td class="px-6 py-4 text-center">
                    @if($user->is_active)
                        <span class="bg-green-500/15 text-green-400 text-xs font-medium px-2 py-0.5 rounded-full">Aktif</span>
                    @else
                        <span class="bg-red-500/15 text-red-400 text-xs font-medium px-2 py-0.5 rounded-full">Nonaktif</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-6 py-12 text-center text-slate-500">Belum ada user.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($users->hasPages())
    <div class="px-6 py-4 border-t border-white/5 text-slate-400">
        {{ $users->links() }}
    </div>
    @endif
</div>

@endsection
