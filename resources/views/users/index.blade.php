@extends('layouts.app')
@section('title', 'User Management')
@section('page-title', 'User Management')

@section('content')
<div class="py-4"
     x-data="{ logsOpen: false, logsLoading: false, logsLoaded: false }"
     x-init="$watch('logsOpen', value => {
         if (!value || logsLoaded) return;
         logsLoading = true;
         fetch('{{ route('users.logs') }}', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
             .then(r => r.text())
             .then(html => { $refs.userLogList.innerHTML = html; logsLoaded = true; })
             .catch(() => { $refs.userLogList.innerHTML = '<p class=&quot;px-4 py-6 text-center text-xs text-red-400&quot;>Gagal memuat log.</p>'; })
             .finally(() => { logsLoading = false; });
     })">
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-4">
        <form method="GET" class="flex gap-2 flex-1 flex-wrap">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama / email / field kustom..."
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
        @if(auth()->user()->hasRole('admin') || auth()->user()->is_super_admin)
        <a href="{{ route('custom-fields.index') }}" class="inline-flex items-center gap-2 bg-white hover:bg-gray-50 text-gray-700 text-sm font-medium px-4 py-2 rounded-lg border border-gray-300 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            Field Kustom
        </a>
        @endif
        @if($isAdmin)
        <button type="button" @click="logsOpen = !logsOpen"
                class="inline-flex items-center gap-2 bg-white hover:bg-gray-50 text-gray-700 text-sm font-medium px-4 py-2 rounded-lg border border-gray-300 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Log Aktivitas
            @if($logsCount)
            <span class="badge bg-gray-100 text-gray-600">{{ $logsCount }}</span>
            @endif
            <svg class="w-3.5 h-3.5 transition-transform" :class="logsOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        @endif
        @if($canCreate)
        <a href="{{ route('users.create') }}" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
            Tambah User
        </a>
        @endif
    </div>

    {{-- Log Aktivitas — collapsible, isinya lazy load pas dibuka, urut terbaru dulu --}}
    @if($isAdmin)
    <div x-show="logsOpen" x-cloak x-transition class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-4">
        <div class="max-h-96 overflow-y-auto">
            <p x-show="logsLoading" class="px-4 py-6 text-center text-xs text-gray-400">Memuat log...</p>
            <div x-show="!logsLoading" x-ref="userLogList" class="divide-y divide-gray-100"></div>
        </div>
    </div>
    @endif

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
                    <th class="px-4 py-3 text-left">Tipe Karyawan</th>
                    @endif
                    @foreach($customFields as $cf)
                    <th class="px-4 py-3 text-left">{{ $cf->label }}</th>
                    @endforeach
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
                    <td class="px-4 py-3">
                        <span class="text-xs text-gray-700">{{ \App\Support\EmploymentType::displayFor($u->employment_type, $u->employment_type_other) }}</span>
                        @if($u->outsourcing_company_name)
                            <p class="text-xs text-gray-400 mt-0.5">{{ $u->outsourcing_company_name }}</p>
                        @endif
                        @if($u->contract_end_date)
                            @if($u->isContractExpired())
                                <p class="text-xs text-red-600 font-medium mt-0.5">Kontrak berakhir {{ $u->contract_end_date->format('d M Y') }}</p>
                            @elseif($u->isContractExpiringSoon())
                                <p class="text-xs text-amber-600 font-medium mt-0.5">Berakhir {{ $u->contract_end_date->format('d M Y') }} ({{ $u->contractDaysRemaining() }}h lagi)</p>
                            @else
                                <p class="text-xs text-gray-400 mt-0.5">s/d {{ $u->contract_end_date->format('d M Y') }}</p>
                            @endif
                        @endif
                    </td>
                    @endif
                    @foreach($customFields as $cf)
                    @php $cfVal = $u->custom_fields[$cf->key] ?? null; @endphp
                    <td class="px-4 py-3 text-gray-600 text-xs">
                        @if($cf->type === 'checkbox')
                            <span class="{{ $cfVal ? 'text-green-600' : 'text-gray-400' }}">{{ $cfVal ? 'Ya' : 'Tidak' }}</span>
                        @elseif($cfVal !== null && $cfVal !== '')
                            {{ $cfVal }}
                        @else
                            <span class="text-gray-300">—</span>
                        @endif
                    </td>
                    @endforeach
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
                <tr><td colspan="{{ (session('active_package') === 'hris' ? 10 : 7) + $customFields->count() }}" class="px-4 py-8 text-center text-gray-400">Tidak ada user ditemukan.</td></tr>
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
