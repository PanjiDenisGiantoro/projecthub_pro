@extends('layouts.app')
@section('title', 'Permission Management')
@section('page-title', 'Permission Management per Role')

@section('content')
@php
    $roleColors = [
        'member' => ['bg'=>'bg-blue-600',  'light'=>'bg-blue-50',  'text'=>'text-blue-700',  'border'=>'border-blue-300'],
        'client' => ['bg'=>'bg-teal-600',  'light'=>'bg-teal-50',  'text'=>'text-teal-700',  'border'=>'border-teal-300'],
    ];
@endphp

@php $roleNames = $roles->pluck('name'); @endphp
<div class="py-4"
     x-data="{ activeRole: '{{ $roles->first()?->name }}' }"
     x-init="if (@js($roleNames).includes(location.hash.slice(1))) activeRole = location.hash.slice(1)">

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

    {{-- Context banner --}}
    @if(($cid ?? null) === null)
    <div class="mb-6 bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-center gap-3">
        <svg class="w-6 h-6 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 3l9 4-9 4-9-4 9-4zm0 6l9 4-9 4-9-4 9-4z"/></svg>
        <div>
            <p class="text-sm font-semibold text-amber-800">Anda mengelola template default global</p>
            <p class="text-xs text-amber-600">Perubahan di sini jadi default awal untuk perusahaan yang belum kustomisasi permission role-nya sendiri.</p>
        </div>
    </div>
    @else
    <div class="mb-6 bg-blue-50 border border-blue-200 rounded-xl p-4 flex items-center gap-3">
        <svg class="w-6 h-6 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
        <div>
            <p class="text-sm font-semibold text-blue-800">Kustomisasi permission perusahaan Anda</p>
            <p class="text-xs text-blue-600">Role yang belum Anda ubah otomatis memakai default global. Simpan perubahan untuk membuat aturan khusus perusahaan Anda.</p>
        </div>
    </div>
    @endif

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
            {{ \App\Support\RoleLabel::for($role->name) }}
            <span class="text-xs font-bold opacity-80">{{ count($rolePermissions[$role->name]) }} hak</span>
            @if(($cid ?? null) && in_array($role->name, $customizedRoleNames ?? []))
                <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-white/25">Kustom</span>
            @endif
        </button>
        @endforeach
    </div>

    {{-- Per-role permission panel --}}
    @foreach($roles as $role)
    @php $c = $roleColors[$role->name] ?? ['bg'=>'bg-gray-500','light'=>'bg-gray-50','text'=>'text-gray-700','border'=>'border-gray-300']; @endphp
    <div x-show="activeRole === '{{ $role->name }}'" x-cloak>
        {{-- Standalone reset form — kept outside the update <form> below so it never nests inside it --}}
        <form id="reset-role-{{ $role->id }}" method="POST" action="{{ route('permissions.reset', $role->name) }}" class="hidden"
              data-confirm-submit="Reset permission {{ \App\Support\RoleLabel::for($role->name) }}?"
              data-confirm-text="Semua perubahan kustom untuk role ini akan kembali ke default."
              data-confirm-btn="Ya, Reset">
            @csrf
        </form>

        <form method="POST" action="{{ route('permissions.update', $role->name) }}"
              x-data="{ changed: false }" @change="changed = true">
            @csrf @method('PUT')

            <div class="bg-white rounded-xl border {{ $c['border'] }} overflow-hidden">
                {{-- Panel header --}}
                <div class="px-5 py-4 {{ $c['light'] }} border-b {{ $c['border'] }} flex items-center justify-between">
                    <div>
                        <h3 class="font-semibold {{ $c['text'] }}">Permission Role: {{ \App\Support\RoleLabel::for($role->name) }}</h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ count($rolePermissions[$role->name]) }} dari {{ $stats['total_permissions'] }} permission aktif
                            @if(($cid ?? null))
                                &middot;
                                @if(in_array($role->name, $customizedRoleNames ?? []))
                                    <span class="text-blue-600 font-medium">Kustom perusahaan Anda</span>
                                @else
                                    <span class="text-gray-400">Memakai default global</span>
                                @endif
                            @endif
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="submit" form="reset-role-{{ $role->id }}"
                                class="text-xs text-gray-400 hover:text-gray-600 border border-gray-300 px-3 py-1.5 rounded-lg bg-white">
                            Reset Default
                        </button>
                        <button type="submit"
                                :class="changed ? '{{ $c['bg'] }} text-white' : 'bg-gray-100 text-gray-400 cursor-not-allowed'"
                                :disabled="!changed"
                                class="text-sm font-semibold px-5 py-1.5 rounded-lg transition-all">
                            Simpan Perubahan
                        </button>
                    </div>
                </div>

                {{-- Permission groups — dipisah per paket (Task Management vs HRIS) supaya
                     jelas grup mana berlaku untuk paket mana, sesuai sidebar masing-masing
                     paket ($showTm / $showHris di layouts/sidebar-nav.blade.php). Grup HRIS
                     dikenali dari prefix "HRIS — " di nama grup (config/permissions.php). --}}
                @php
                    $tmGroups   = collect($groups)->reject(fn($items, $name) => str_starts_with($name, 'HRIS'));
                    $hrisGroups = collect($groups)->filter(fn($items, $name) => str_starts_with($name, 'HRIS'));
                    $packageSections = [
                        ['label' => 'Task Management', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4', 'tint' => 'text-blue-600 bg-blue-50 border-blue-100', 'items' => $tmGroups,   'show' => $showTm ?? true],
                        ['label' => 'HRIS',            'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z', 'tint' => 'text-emerald-600 bg-emerald-50 border-emerald-100', 'items' => $hrisGroups, 'show' => $showHris ?? true],
                    ];
                @endphp
                <div class="divide-y divide-gray-100">
                    @foreach($packageSections as $section)
                    @continue($section['items']->isEmpty() || !$section['show'])
                    <div>
                        {{-- Section header (per paket) --}}
                        <div class="px-5 py-2.5 flex items-center gap-2 border-b {{ $section['tint'] }}">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $section['icon'] }}"/>
                            </svg>
                            <span class="text-xs font-bold uppercase tracking-wide">Paket: {{ $section['label'] }}</span>
                        </div>

                        <div class="divide-y divide-gray-100">
                            @foreach($section['items'] as $groupName => $items)
                            <div x-data="{ open: true }" class="px-5 py-4">
                                {{-- Group header with check-all --}}
                                <div class="flex items-center justify-between mb-3 cursor-pointer" @click="open = !open">
                                    <div class="flex items-center gap-2">
                                        <svg :class="open ? 'rotate-90' : ''" class="w-4 h-4 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                        <span class="text-sm font-semibold text-gray-700">{{ str_replace('HRIS — ', '', $groupName) }}</span>
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
                    </div>
                    @endforeach
                </div>

                {{-- Footer save --}}
                <div class="px-5 py-4 bg-gray-50 border-t border-gray-100 flex justify-end">
                    <button type="submit"
                            :class="changed ? '{{ $c['bg'] }} text-white' : 'bg-gray-200 text-gray-400 cursor-not-allowed'"
                            :disabled="!changed"
                            class="text-sm font-semibold px-6 py-2 rounded-lg transition-all">
                        Simpan Permission {{ \App\Support\RoleLabel::for($role->name) }}
                    </button>
                </div>
            </div>
        </form>
    </div>
    @endforeach

</div>
@endsection
