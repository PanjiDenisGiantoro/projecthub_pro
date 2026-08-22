@extends('layouts.app')
@section('title', 'Master Approval Policy')
@section('page-title', 'Master Approval Policy')

@section('content')
<div class="py-4" x-data="{ addModal: false, editModal: null, editData: {} }">

    {{-- Flash --}}
    @if(session('success'))
    <div class="mb-4 flex items-center gap-3 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="mb-4 flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        {{ session('error') }}
    </div>
    @endif
    @if($errors->any())
    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
        <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <div class="text-2xl font-bold text-gray-800">{{ $stats['total'] }}</div>
            <div class="text-xs text-gray-500 mt-1">Total Policy</div>
        </div>
        <div class="bg-white rounded-xl border border-green-200 p-4 text-center">
            <div class="text-2xl font-bold text-green-600">{{ $stats['active'] }}</div>
            <div class="text-xs text-gray-500 mt-1">Aktif</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <div class="text-2xl font-bold text-gray-400">{{ $stats['inactive'] }}</div>
            <div class="text-xs text-gray-500 mt-1">Nonaktif</div>
        </div>
        <div class="bg-white rounded-xl border border-yellow-200 p-4 text-center">
            <div class="text-2xl font-bold text-yellow-600">{{ $stats['pending'] }}</div>
            <div class="text-xs text-gray-500 mt-1">Total Pending Approval</div>
        </div>
    </div>

    {{-- Header --}}
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-sm font-semibold text-gray-700">Daftar Approval Policy</h2>
        <button @click="addModal = true"
                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Policy
        </button>
    </div>

    {{-- Table --}}
    @foreach($policies->groupBy('module') as $module => $group)
    <div class="mb-6">
        <div class="flex items-center gap-2 mb-2">
            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Modul:</span>
            <span class="bg-blue-100 text-blue-700 text-xs font-semibold px-2.5 py-1 rounded-full">{{ ucfirst($module) }}</span>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">Action</th>
                        <th class="px-4 py-3 text-left">Flow Type</th>
                        <th class="px-4 py-3 text-left">Approver Roles</th>
                        <th class="px-4 py-3 text-left">Timeout</th>
                        <th class="px-4 py-3 text-left">Pending</th>
                        <th class="px-4 py-3 text-left">Total</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Keterangan</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($group as $policy)
                    @php
                        $flowColors = ['sequential'=>'bg-purple-100 text-purple-700','parallel_all'=>'bg-blue-100 text-blue-700','any_of'=>'bg-teal-100 text-teal-700','single'=>'bg-gray-100 text-gray-600'];
                        $roleColors = ['admin'=>'bg-red-100 text-red-700','manager'=>'bg-orange-100 text-orange-700','developer'=>'bg-blue-100 text-blue-700','marketing'=>'bg-pink-100 text-pink-700','customer'=>'bg-green-100 text-green-700'];
                    @endphp
                    <tr class="hover:bg-gray-50 {{ !$policy->is_active ? 'opacity-50' : '' }}">
                        <td class="px-4 py-3 font-semibold text-gray-800">{{ str_replace('_', ' ', ucfirst($policy->action)) }}</td>
                        <td class="px-4 py-3">
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $flowColors[$policy->flow_type] ?? '' }}">
                                {{ str_replace('_', ' ', $policy->flow_type) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1">
                                @foreach($policy->approver_roles as $i => $role)
                                <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $roleColors[$role] ?? 'bg-gray-100 text-gray-600' }}">
                                    @if($policy->flow_type === 'sequential') {{ $i+1 }}. @endif{{ ucfirst($role) }}
                                </span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $policy->timeout_hours }}j</td>
                        <td class="px-4 py-3">
                            @if($policy->pending_count > 0)
                            <span class="bg-yellow-100 text-yellow-700 text-xs font-bold px-2 py-0.5 rounded-full">{{ $policy->pending_count }}</span>
                            @else
                            <span class="text-gray-400 text-xs">0</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ $policy->approvals_count }}</td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('approval-policies.toggle', $policy) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors {{ $policy->is_active ? 'bg-green-500' : 'bg-gray-300' }}">
                                    <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform {{ $policy->is_active ? 'translate-x-4' : 'translate-x-1' }}"></span>
                                </button>
                            </form>
                        </td>
                        <td class="px-4 py-3 text-gray-400 text-xs max-w-xs truncate">{{ $policy->description ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2 justify-end">
                                {{-- Edit --}}
                                <button @click="editModal = {{ $policy->id }}; editData = {{ json_encode(['id'=>$policy->id,'flow_type'=>$policy->flow_type,'approver_roles'=>$policy->approver_roles,'timeout_hours'=>$policy->timeout_hours,'description'=>$policy->description]) }}"
                                        class="text-xs text-blue-600 hover:text-blue-800 font-medium">Edit</button>
                                {{-- Delete --}}
                                <form method="POST" action="{{ route('approval-policies.destroy', $policy) }}"
                                      onsubmit="return confirm('Hapus policy {{ addslashes($policy->module.'.'. $policy->action) }}? Aksi ini tidak dapat dibatalkan.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs text-red-400 hover:text-red-600 font-medium">Hapus</button>
                                </form>
                            </div>

                            {{-- Edit Modal --}}
                            <div x-show="editModal === {{ $policy->id }}" x-cloak
                                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                                <div @click.outside="editModal = null" class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6">
                                    <h3 class="font-semibold text-gray-800 mb-1">Edit Policy</h3>
                                    <p class="text-xs text-gray-500 mb-4 bg-gray-50 px-3 py-1.5 rounded-lg font-mono">{{ $policy->module }}.{{ $policy->action }}</p>
                                    <form method="POST" action="{{ route('approval-policies.update', $policy) }}">
                                        @csrf @method('PUT')
                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 mb-1">Flow Type</label>
                                                <select name="flow_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                    @foreach(['sequential','parallel_all','any_of','single'] as $ft)
                                                    <option value="{{ $ft }}" {{ $policy->flow_type === $ft ? 'selected' : '' }}>{{ str_replace('_',' ',ucfirst($ft)) }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 mb-1">Approver Roles <span class="text-gray-400">(urutan = urutan approval untuk sequential)</span></label>
                                                <div class="space-y-2">
                                                    @foreach(['admin','manager','developer','marketing','customer'] as $role)
                                                    <label class="flex items-center gap-2 cursor-pointer">
                                                        <input type="checkbox" name="approver_roles[]" value="{{ $role }}"
                                                               {{ in_array($role, $policy->approver_roles) ? 'checked' : '' }}
                                                               class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                                        <span class="text-sm text-gray-700 {{ $roleColors[$role] ?? '' }} px-2 py-0.5 rounded-full text-xs font-medium">{{ ucfirst($role) }}</span>
                                                    </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 mb-1">Timeout (jam)</label>
                                                <input type="number" name="timeout_hours" value="{{ $policy->timeout_hours }}" min="1" max="720"
                                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 mb-1">Keterangan</label>
                                                <input type="text" name="description" value="{{ $policy->description }}"
                                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </div>
                                        </div>
                                        <div class="flex gap-2 justify-end mt-5">
                                            <button type="button" @click="editModal = null" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Batal</button>
                                            <button type="submit" class="px-4 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium">Simpan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endforeach

    {{-- Add Policy Modal --}}
    <div x-show="addModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <div @click.outside="addModal = false" class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6">
            <h3 class="font-semibold text-gray-800 mb-4">Tambah Approval Policy Baru</h3>
            <form method="POST" action="{{ route('approval-policies.store') }}">
                @csrf
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Modul <span class="text-red-500">*</span></label>
                            <input type="text" name="module" placeholder="ticket, invoice, request..." required
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" value="{{ old('module') }}">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Action <span class="text-red-500">*</span></label>
                            <input type="text" name="action" placeholder="resolve, close, approve..." required
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" value="{{ old('action') }}">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Flow Type <span class="text-red-500">*</span></label>
                        <select name="flow_type" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Pilih flow type...</option>
                            <option value="single">Single — satu approver</option>
                            <option value="any_of">Any Of — salah satu dari daftar</option>
                            <option value="sequential">Sequential — berurutan satu per satu</option>
                            <option value="parallel_all">Parallel All — semua harus approve</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-2">Approver Roles <span class="text-red-500">*</span></label>
                        @php $roleColors = ['admin'=>'bg-red-100 text-red-700','manager'=>'bg-orange-100 text-orange-700','developer'=>'bg-blue-100 text-blue-700','marketing'=>'bg-pink-100 text-pink-700','customer'=>'bg-green-100 text-green-700']; @endphp
                        <div class="flex flex-wrap gap-3">
                            @foreach(['admin','manager','developer','marketing','customer'] as $role)
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="approver_roles[]" value="{{ $role }}"
                                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $roleColors[$role] }}">{{ ucfirst($role) }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Timeout (jam) <span class="text-red-500">*</span></label>
                        <input type="number" name="timeout_hours" value="{{ old('timeout_hours', 24) }}" min="1" max="720" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-gray-400 mt-1">Berapa jam sebelum approval otomatis expired.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Keterangan</label>
                        <input type="text" name="description" placeholder="Penjelasan singkat tujuan policy ini..."
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" value="{{ old('description') }}">
                    </div>
                </div>
                <div class="flex gap-2 justify-end mt-5">
                    <button type="button" @click="addModal = false" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Batal</button>
                    <button type="submit" class="px-4 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium">Simpan Policy</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
