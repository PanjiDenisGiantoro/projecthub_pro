@extends('layouts.app')
@section('title', 'Tambah Unit Organisasi')
@section('page-title', 'Tambah Unit Organisasi')

@push('head')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<style>
.select2-container--default .select2-selection--single {
    height: 42px !important; border: 1px solid #d1d5db !important; border-radius: 0.5rem !important;
    padding: 0.5rem 0.75rem !important; font-size: 0.875rem !important; display: flex; align-items: center;
}
.select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 1.5 !important; color: #111827 !important; padding-left: 0 !important; }
.select2-container--default .select2-selection--single .select2-selection__arrow { height: 40px !important; right: 8px !important; }
.select2-container--default.select2-container--focus .select2-selection--single { border-color: #3b82f6 !important; box-shadow: 0 0 0 2px rgba(59,130,246,.25) !important; }
.select2-dropdown { border: 1px solid #d1d5db !important; border-radius: 0.5rem !important; font-size: 0.875rem !important; }
.select2-results__option--highlighted { background-color: #2563eb !important; }
</style>
@endpush

@section('content')
<div class="py-4 max-w-xl">

    <div class="flex items-center gap-2 text-xs text-gray-400 mb-5">
        <a href="{{ route('master.index') }}" class="hover:text-blue-600 transition-colors">Master Data</a>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('organization-units.index') }}" class="hover:text-blue-600 transition-colors">Organisasi</a>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-600 font-medium">Tambah</span>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <form method="POST" action="{{ route('organization-units.store') }}" class="space-y-5"
              data-confirm-submit="Simpan unit organisasi baru?" data-confirm-btn="Ya, Simpan">
            @csrf

            @if($companies->count() > 1)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Perusahaan <span class="text-red-500">*</span></label>
                    <select name="company_id" onchange="this.form.submit()"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                        <option value="">— Pilih Perusahaan —</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" {{ (string) $selectedCompany === (string) $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <input type="hidden" name="company_id" value="{{ $selectedCompany }}">
            @endif

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Parent (opsional)</label>
                <select name="parent_id" id="sel-parent"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 @error('parent_id') border-red-400 @enderror">
                    <option value="">— Tanpa Parent (Level 1) —</option>
                    @foreach($tree as $node)
                        <option value="{{ $node->id }}" {{ (string) request('parent_id') === (string) $node->id ? 'selected' : '' }}>
                            {{ str_repeat('— ', $node->level - 1) }}{{ $node->name }} (L{{ $node->code }})
                        </option>
                    @endforeach
                </select>
                @error('parent_id')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                <p class="text-xs text-gray-400 mt-1">Kosongkan untuk membuat unit level teratas (root).</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Unit <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 @error('name') border-red-400 @enderror"
                       placeholder="Divisi IT">
                @error('name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Kepala Unit (opsional)</label>
                <select name="head_id" id="sel-head"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                    <option value="">— Tidak ada —</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ (string) old('head_id') === (string) $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_active" value="1" id="is_active"
                       {{ old('is_active', '1') ? 'checked' : '' }} class="w-4 h-4 text-violet-600 rounded">
                <label for="is_active" class="text-sm text-gray-700">Unit Aktif</label>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Warna Kotak (Bagan Organisasi)</label>
                <div class="flex items-center gap-3">
                    <label class="flex items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" id="color-auto" onchange="toggleColorAuto(this)" {{ old('color') ? '' : 'checked' }} class="w-4 h-4 text-violet-600 rounded">
                        Otomatis sesuai level
                    </label>
                    <input type="color" name="color" id="color-input" value="{{ old('color', '#1d4ed8') }}"
                           class="w-12 h-9 border border-gray-300 rounded-lg cursor-pointer" {{ old('color') ? '' : 'disabled' }}>
                </div>
                @error('color')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium px-6 py-2.5 rounded-lg transition-colors">Simpan Unit</button>
                <a href="{{ route('organization-units.index', ['company_id' => $selectedCompany]) }}" class="text-gray-600 text-sm font-medium px-4 py-2.5 rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(function () {
    $('#sel-parent').select2({ placeholder: '— Tanpa Parent (Level 1) —', allowClear: true, width: '100%' });
    $('#sel-head').select2({ placeholder: '— Tidak ada —', allowClear: true, width: '100%' });
});
function toggleColorAuto(checkbox) {
    document.getElementById('color-input').disabled = checkbox.checked;
}
</script>
@endpush
