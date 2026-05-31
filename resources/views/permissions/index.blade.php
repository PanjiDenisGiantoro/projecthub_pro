@extends('layouts.app')
@section('title', 'Permission Management')
@section('page-title', 'Permission Management per Role')

@section('content')
@php
    $roleColors = [
        'manager'   => ['bg'=>'bg-violet-600',  'light'=>'bg-blue-50',  'text'=>'text-blue-700',  'border'=>'border-blue-300'],
        'developer' => ['bg'=>'bg-violet-600','light'=>'bg-violet-50','text'=>'text-violet-700','border'=>'border-violet-300'],
        'marketing' => ['bg'=>'bg-pink-600',  'light'=>'bg-pink-50',  'text'=>'text-pink-700',  'border'=>'border-pink-300'],
        'customer'  => ['bg'=>'bg-teal-600',  'light'=>'bg-teal-50',  'text'=>'text-teal-700',  'border'=>'border-teal-300'],
    ];
@endphp

<div class="py-4" x-data="{ activeRole: '{{ $roles->first()?->name }}' }">

    {{-- Flash --}}
    @if(session('success'))
    <div class="mb-4 flex items-center gap-3 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        {!! session('success') !!}
    </div>
    @endif
    @if(session('error'))
    <div class="mb-4 flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        {{ session('error') }}
    </div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <div class="text-2xl font-bold text-gray-800">{{ $stats['total_permissions'] }}</div>
            <div class="text-xs text-gray-500 mt-1">Total Permission</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <div class="text-2xl font-bold text-gray-800">{{ $stats['total_roles'] }}</div>
            <div class="text-xs text-gray-500 mt-1">Total Role</div>
        </div>
        <div class="col-span-2 bg-blue-50 border border-blue-200 rounded-xl p-4 flex items-center gap-3">
            <svg class="w-8 h-8 text-blue-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            <div>
                <p class="text-sm font-semibold text-blue-800">Admin selalu memiliki akses penuh</p>
                <p class="text-xs text-blue-600">Permission admin tidak dapat diubah melalui halaman ini.</p>
            </div>
        </div>
    </div>

    {{-- Role Tabs --}}
    <div class="flex gap-2 mb-4 flex-wrap">
        {{-- Admin tab (disabled) --}}
        <div class="flex items-center gap-2 px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-lg opacity-70 cursor-not-allowed">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            Admin (Semua Akses)
        </div>
        @foreach($roles as $role)
        @php $c = $roleColors[$role->name] ?? ['bg'=>'bg-gray-500','light'=>'bg-gray-50','text'=>'text-gray-700','border'=>'border-gray-300']; @endphp
        <button @click="activeRole = '{{ $role->name }}'"
                :class="activeRole === '{{ $role->name }}' ? '{{ $c['bg'] }} text-white shadow' : 'bg-white {{ $c['text'] }} {{ $c['border'] }} border hover:opacity-80'"
                class="flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-all">
            {{ ucfirst($role->name) }}
            <span class="text-xs font-bold opacity-80">{{ count($rolePermissions[$role->name]) }} hak</span>
        </button>
        @endforeach
    </div>

    {{-- Per-role permission panel --}}
    @foreach($roles as $role)
    @php $c = $roleColors[$role->name] ?? ['bg'=>'bg-gray-500','light'=>'bg-gray-50','text'=>'text-gray-700','border'=>'border-gray-300']; @endphp
    <div x-show="activeRole === '{{ $role->name }}'" x-cloak>
        <form method="POST" action="{{ route('permissions.update', $role->name) }}"
              x-data="{ changed: false }" @change="changed = true">
            @csrf @method('PUT')

            <div class="bg-white rounded-xl border {{ $c['border'] }} overflow-hidden">
                {{-- Panel header --}}
                <div class="px-5 py-4 {{ $c['light'] }} border-b {{ $c['border'] }} flex items-center justify-between">
                    <div>
                        <h3 class="font-semibold {{ $c['text'] }}">Permission Role: {{ ucfirst($role->name) }}</h3>
                        <p class="text-xs text-gray-500 mt-0.5">{{ count($rolePermissions[$role->name]) }} dari {{ $stats['total_permissions'] }} permission aktif</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('permissions.reset', $role->name) }}"
                           onclick="return confirm('Reset permission {{ $role->name }} ke default?')"
                           class="text-xs text-gray-400 hover:text-gray-600 border border-gray-300 px-3 py-1.5 rounded-lg bg-white">
                            Reset Default
                        </a>
                        <button type="submit"
                                :class="changed ? '{{ $c['bg'] }} text-white' : 'bg-gray-100 text-gray-400 cursor-not-allowed'"
                                :disabled="!changed"
                                class="text-sm font-semibold px-5 py-1.5 rounded-lg transition-all">
                            Simpan Perubahan
                        </button>
                    </div>
                </div>

                {{-- Permission groups --}}
                <div class="divide-y divide-gray-100">
                    @foreach($groups as $groupName => $items)
                    <div x-data="{ open: true }" class="px-5 py-4">
                        {{-- Group header with check-all --}}
                        <div class="flex items-center justify-between mb-3 cursor-pointer" @click="open = !open">
                            <div class="flex items-center gap-2">
                                <svg :class="open ? 'rotate-90' : ''" class="w-4 h-4 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                                <span class="text-sm font-semibold text-gray-700">{{ $groupName }}</span>
                                <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">{{ count($items) }} permission</span>
                            </div>
                            @php
                                $groupPerms = array_keys($items);
                                $activeInGroup = count(array_intersect($groupPerms, $rolePermissions[$role->name]));
                            @endphp
                            <span class="text-xs {{ $activeInGroup === count($groupPerms) ? $c['text'] : 'text-gray-400' }}">
                                {{ $activeInGroup }}/{{ count($groupPerms) }} aktif
                            </span>
                        </div>

                        {{-- Permission checkboxes --}}
                        <div x-show="open" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 pl-6">
                            @foreach($items as $permName => $label)
                            @php $isChecked = in_array($permName, $rolePermissions[$role->name]); @endphp
                            <label class="flex items-start gap-2.5 p-2.5 rounded-lg border cursor-pointer transition-all
                                          {{ $isChecked ? $c['light'].' '.$c['border'] : 'border-gray-100 hover:border-gray-300 hover:bg-gray-50' }}">
                                <input type="checkbox"
                                       name="permissions[]"
                                       value="{{ $permName }}"
                                       {{ $isChecked ? 'checked' : '' }}
                                       class="mt-0.5 w-4 h-4 rounded border-gray-300 {{ str_replace('bg-', 'text-', $c['bg']) }} focus:ring-2 shrink-0">
                                <div>
                                    <p class="text-sm font-medium {{ $isChecked ? $c['text'] : 'text-gray-700' }}">{{ $label }}</p>
                                    <p class="text-xs text-gray-400 font-mono">{{ $permName }}</p>
                                </div>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Footer save --}}
                <div class="px-5 py-4 bg-gray-50 border-t border-gray-100 flex justify-end">
                    <button type="submit"
                            :class="changed ? '{{ $c['bg'] }} text-white' : 'bg-gray-200 text-gray-400 cursor-not-allowed'"
                            :disabled="!changed"
                            class="text-sm font-semibold px-6 py-2 rounded-lg transition-all">
                        Simpan Permission {{ ucfirst($role->name) }}
                    </button>
                </div>
            </div>
        </form>
    </div>
    @endforeach

</div>
@endsection
