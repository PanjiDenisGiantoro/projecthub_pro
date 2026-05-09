@extends('layouts.app')
@section('title', 'Tambah Departemen')
@section('page-title', 'Tambah Departemen')

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
.select2-search--dropdown .select2-search__field { border-radius: 0.375rem !important; border: 1px solid #d1d5db !important; padding: 0.375rem 0.625rem !important; font-size: 0.875rem !important; }
</style>
@endpush

@section('content')
<div class="py-4 max-w-xl">

    <div class="flex items-center gap-2 text-xs text-gray-400 mb-5">
        <a href="{{ route('master.index') }}" class="hover:text-blue-600 transition-colors">Master Data</a>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('departments.index') }}" class="hover:text-blue-600 transition-colors">Departemen</a>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-600 font-medium">Tambah</span>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <form method="POST" action="{{ route('departments.store') }}" class="space-y-5"
              data-confirm-submit="Simpan departemen baru?" data-confirm-btn="Ya, Simpan">
            @csrf

            {{-- Cascade filters --}}
            <div class="grid grid-cols-2 gap-4 p-4 bg-gray-50 rounded-lg border border-gray-100">
                <p class="col-span-2 text-xs font-medium text-gray-500 -mb-1">Filter Lokasi (opsional)</p>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Perusahaan</label>
                    <select id="sel-company-filter"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none bg-white">
                        <option value="">— Semua —</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Branch</label>
                    <select id="sel-branch-filter"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none bg-white">
                        <option value="">— Semua —</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Divisi <span class="text-red-500">*</span></label>
                <select name="division_id" id="sel-division" required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('division_id') border-red-400 @enderror">
                    <option value="">— Pilih Divisi —</option>
                </select>
                @error('division_id')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Departemen <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-400 @enderror"
                           placeholder="Departemen Engineering">
                    @error('name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kode</label>
                    <input type="text" name="code" value="{{ old('code') }}" maxlength="50"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono uppercase @error('code') border-red-400 @enderror"
                           placeholder="DEPT-ENG">
                    @error('code')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kepala Departemen</label>
                    <select name="head_id" id="sel-head" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">— Pilih User —</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('head_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                    <textarea name="description" rows="3"
                              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
                              placeholder="Deskripsi singkat departemen...">{{ old('description') }}</textarea>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_active" value="1" id="is_active"
                       {{ old('is_active', '1') ? 'checked' : '' }} class="w-4 h-4 text-blue-600 rounded">
                <label for="is_active" class="text-sm text-gray-700">Departemen Aktif</label>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-6 py-2.5 rounded-lg transition-colors">Simpan Departemen</button>
                <a href="{{ route('departments.index') }}" class="text-gray-600 text-sm font-medium px-4 py-2.5 rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors">Batal</a>
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
    const allBranches = {!! json_encode($branches->map(fn($b) => ['id' => $b->id, 'text' => $b->name, 'companyId' => $b->company_id])) !!};
    const allDivisions = {!! json_encode($divisions->map(fn($d) => ['id' => $d->id, 'text' => $d->branch->company->name . ' / ' . $d->branch->name . ' / ' . $d->name, 'branchId' => $d->branch_id, 'companyId' => $d->branch->company_id])) !!};

    function renderBranchFilter(companyId, selectedId) {
        const list = companyId ? allBranches.filter(b => b.companyId == companyId) : allBranches;
        const $s = $('#sel-branch-filter').empty().append('<option value="">— Semua —</option>');
        list.forEach(b => $s.append(new Option(b.text, b.id, false, String(b.id) === String(selectedId))));
        $s.trigger('change');
    }

    function renderDivisions(branchId, companyId, selectedId) {
        let list = allDivisions;
        if (branchId) list = list.filter(d => d.branchId == branchId);
        else if (companyId) list = list.filter(d => d.companyId == companyId);
        const $s = $('#sel-division').empty().append('<option value="">— Pilih Divisi —</option>');
        list.forEach(d => $s.append(new Option(d.text, d.id, false, String(d.id) === String(selectedId))));
        $s.trigger('change');
    }

    $('#sel-company-filter').select2({ placeholder: '— Semua Perusahaan —', allowClear: true, width: '100%' });
    $('#sel-branch-filter').select2({ placeholder: '— Semua Branch —', allowClear: true, width: '100%' });
    $('#sel-division').select2({ placeholder: '— Pilih Divisi —', allowClear: true, width: '100%' });
    $('#sel-head').select2({ placeholder: '— Pilih User —', allowClear: true, width: '100%' });

    $('#sel-company-filter').on('change', function () {
        renderBranchFilter($(this).val(), null);
        renderDivisions(null, $(this).val(), null);
    });

    $('#sel-branch-filter').on('change', function () {
        renderDivisions($(this).val(), $('#sel-company-filter').val(), null);
    });

    const oldDivisionId = '{{ old('division_id') }}' || null;
    renderBranchFilter(null, null);
    renderDivisions(null, null, oldDivisionId);
});
</script>
@endpush
