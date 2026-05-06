@extends('layouts.app')
@section('title', 'Edit User')
@section('page-title', 'Edit User')

@push('head')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<style>
.select2-container--default .select2-selection--single {
    height: 42px !important;
    border: 1px solid #d1d5db !important;
    border-radius: 0.5rem !important;
    padding: 0.5rem 0.75rem !important;
    font-size: 0.875rem !important;
    display: flex; align-items: center;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 1.5 !important;
    color: #111827 !important;
    padding-left: 0 !important;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 40px !important;
    right: 8px !important;
}
.select2-container--default.select2-container--focus .select2-selection--single {
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 2px rgba(59,130,246,.25) !important;
}
.select2-dropdown { border: 1px solid #d1d5db !important; border-radius: 0.5rem !important; font-size: 0.875rem !important; }
.select2-results__option--highlighted { background-color: #2563eb !important; }
.select2-search--dropdown .select2-search__field { border-radius: 0.375rem !important; border: 1px solid #d1d5db !important; padding: 0.375rem 0.625rem !important; font-size: 0.875rem !important; }
</style>
@endpush

@section('content')
<div class="py-4 max-w-lg">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-5">
            @csrf @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Role <span class="text-red-500">*</span></label>
                <select name="role" id="select-role" required class="w-full">
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}" {{ old('role', $user->getRoleNames()->first()) === $role->name ? 'selected' : '' }}>{{ ucfirst($role->name) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Level Struktural</label>
                <select name="structural_level_id" id="select-level" class="w-full">
                    <option value="">— Tidak Ditentukan —</option>
                    @foreach($structuralLevels as $level)
                        <option value="{{ $level->id }}" {{ old('structural_level_id', $user->structural_level_id) == $level->id ? 'selected' : '' }}>
                            {{ $level->sort_order }}. {{ $level->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Cascade: Company → Branch → Division → Department --}}
            <div class="border border-gray-200 rounded-xl p-4 bg-gray-50">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">Penempatan Organisasi</p>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Perusahaan</label>
                        <select id="sel-company" class="w-full">
                            <option value="">— Pilih Perusahaan —</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}"
                                    {{ (old('company_id', $preselect['company_id'])) == $company->id ? 'selected' : '' }}>
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Branch</label>
                        <select id="sel-branch" class="w-full" disabled>
                            <option value="">— Pilih Branch —</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Divisi</label>
                        <select id="sel-division" class="w-full" disabled>
                            <option value="">— Pilih Divisi —</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Departemen</label>
                        <select id="sel-department" name="department_id" class="w-full" disabled>
                            <option value="">— Pilih Departemen —</option>
                        </select>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Timezone</label>
                <select name="timezone" id="select-timezone" class="w-full">
                    @foreach(['Asia/Jakarta','Asia/Makassar','Asia/Jayapura','UTC'] as $tz)
                        <option value="{{ $tz }}" {{ old('timezone', $user->timezone) === $tz ? 'selected' : '' }}>{{ $tz }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_active" value="1" id="is_active"
                       {{ $user->is_active ? 'checked' : '' }} class="w-4 h-4 text-blue-600 rounded">
                <label for="is_active" class="text-sm text-gray-700">Akun Aktif</label>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-6 py-2.5 rounded-lg transition-colors">Simpan</button>
                <a href="{{ route('users.index') }}" class="text-gray-600 text-sm font-medium px-4 py-2.5 rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors">Batal</a>
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
    const URLS = {
        branches:    '{{ route('ajax.branches') }}',
        divisions:   '{{ route('ajax.divisions') }}',
        departments: '{{ route('ajax.departments') }}',
    };

    // Preselect values dari server
    const preselect = {
        company_id:  {{ $preselect['company_id']  ?? 'null' }},
        branch_id:   {{ $preselect['branch_id']   ?? 'null' }},
        division_id: {{ $preselect['division_id'] ?? 'null' }},
        department_id: {{ old('department_id', $user->department_id) ?? 'null' }},
    };

    $('#select-role, #select-level, #select-timezone, #sel-company, #sel-branch, #sel-division, #sel-department').select2({
        placeholder: '— Pilih —',
        allowClear: true,
        width: '100%',
    });

    function loadOptions(selectEl, url, params, placeholder, preselectVal) {
        $(selectEl).empty().append(`<option value="">${placeholder}</option>`).prop('disabled', true).trigger('change');
        $.getJSON(url, params, function (data) {
            data.forEach(item => $(selectEl).append(new Option(item.name, item.id)));
            $(selectEl).prop('disabled', data.length === 0);
            if (preselectVal) {
                $(selectEl).val(preselectVal).trigger('change');
            } else {
                $(selectEl).trigger('change');
            }
        });
    }

    $('#sel-company').on('change', function () {
        const id = $(this).val();
        const isCascade = $(this).data('cascade');
        $('#sel-branch, #sel-division, #sel-department').empty()
            .append('<option value="">— Pilih —</option>').prop('disabled', true).trigger('change');
        if (id) {
            loadOptions('#sel-branch', URLS.branches, { company_id: id }, '— Pilih Branch —',
                isCascade ? preselect.branch_id : null);
        }
        $(this).removeData('cascade');
    });

    $('#sel-branch').on('change', function () {
        const id = $(this).val();
        const isCascade = $(this).data('cascade');
        $('#sel-division, #sel-department').empty()
            .append('<option value="">— Pilih —</option>').prop('disabled', true).trigger('change');
        if (id) {
            loadOptions('#sel-division', URLS.divisions, { branch_id: id }, '— Pilih Divisi —',
                isCascade ? preselect.division_id : null);
        }
        $(this).removeData('cascade');
    });

    $('#sel-division').on('change', function () {
        const id = $(this).val();
        const isCascade = $(this).data('cascade');
        $('#sel-department').empty()
            .append('<option value="">— Pilih —</option>').prop('disabled', true).trigger('change');
        if (id) {
            loadOptions('#sel-department', URLS.departments, { division_id: id }, '— Pilih Departemen —',
                isCascade ? preselect.department_id : null);
        }
        $(this).removeData('cascade');
    });

    // Trigger cascade pre-population on page load
    if (preselect.company_id) {
        $('#sel-company').data('cascade', true).val(preselect.company_id).trigger('change');
    }
});
</script>
@endpush