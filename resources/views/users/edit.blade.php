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
.select2-container--default .select2-selection--multiple {
    min-height: 42px !important;
    border: 1px solid #d1d5db !important;
    border-radius: 0.5rem !important;
    padding: 0.25rem 0.5rem !important;
}
.select2-container--default.select2-container--focus .select2-selection--multiple {
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 2px rgba(59,130,246,.25) !important;
}
.select2-container--default .select2-selection--multiple .select2-selection__choice {
    background-color: #eff6ff !important;
    border: 1px solid #bfdbfe !important;
    color: #1d4ed8 !important;
    border-radius: 0.375rem !important;
    padding: 1px 6px !important;
    font-size: 0.75rem !important;
}
.select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
    color: #3b82f6 !important;
    margin-right: 4px !important;
}
</style>
@endpush

@section('content')
<div class="py-4 max-w-lg">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-5">
            @csrf @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
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
                        <option value="{{ $role->name }}" {{ old('role', $user->getRoleNames()->first()) === $role->name ? 'selected' : '' }}>{{ \App\Support\RoleLabel::for($role->name) }}</option>
                    @endforeach
                </select>
            </div>

            @if(session('active_package') === 'hris')
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Structural Level</label>
                <select name="structural_level_id" id="select-level" class="w-full">
                    <option value="">— Not Specified —</option>
                    @foreach($structuralLevels as $level)
                        <option value="{{ $level->id }}" {{ old('structural_level_id', $user->structural_level_id) == $level->id ? 'selected' : '' }}>
                            {{ $level->sort_order }}. {{ $level->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div x-data="{ employmentType: '{{ old('employment_type', $user->employment_type ?? 'tetap') }}' }">
                <label class="block text-sm font-medium text-gray-700 mb-1">Employment Type <span class="text-red-500">*</span></label>
                <select name="employment_type" id="select-employment-type" x-model="employmentType" required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                    @foreach(\App\Support\EmploymentType::options() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>

                <div class="mt-3" x-show="employmentType === 'lainnya'" x-cloak>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Employment Type Name <span class="text-red-500">*</span></label>
                    <input type="text" name="employment_type_other" value="{{ old('employment_type_other', $user->employment_type_other) }}"
                           placeholder="e.g. Seasonal Freelance, Consultant..."
                           :required="employmentType === 'lainnya'"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('employment_type_other') border-red-400 @enderror">
                    @error('employment_type_other') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="mt-3" x-show="!['tetap','kontrak'].includes(employmentType)" x-cloak>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Source Company <span class="text-red-500">*</span></label>
                    <input type="text" name="outsourcing_company_name" value="{{ old('outsourcing_company_name', $user->outsourcing_company_name) }}"
                           placeholder="Company or vendor name"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('outsourcing_company_name') border-red-400 @enderror">
                    @error('outsourcing_company_name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-3 mt-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Hire Date</label>
                        <input type="date" name="hire_date" value="{{ old('hire_date', $user->hire_date?->format('Y-m-d')) }}"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('hire_date') border-red-400 @enderror">
                        @error('hire_date') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div x-show="employmentType !== 'tetap'" x-cloak>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Contract End Date <span class="text-red-500" x-show="employmentType === 'kontrak'">*</span>
                        </label>
                        <input type="date" name="contract_end_date" value="{{ old('contract_end_date', $user->contract_end_date?->format('Y-m-d')) }}"
                                :required="employmentType === 'kontrak'"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('contract_end_date') border-red-400 @enderror">
                        @error('contract_end_date') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
            @endif

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Projects</label>
                <select name="project_ids[]" id="select-projects" multiple style="width:100%">
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}" {{ collect(old('project_ids', $selectedProjectIds))->contains($project->id) ? 'selected' : '' }}>
                            {{ $project->name }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-400">Optional. Project team members will be updated accordingly.</p>
            </div>

            @if(session('active_package') === 'hris')
            <div class="border border-gray-200 rounded-xl p-4 bg-gray-50">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">Organization Placement</p>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Organization Unit</label>
                    <select name="organization_unit_id" id="sel-org-unit" class="w-full">
                        <option value="">— Not Specified —</option>
                        @foreach($organizationUnits as $unit)
                            <option value="{{ $unit->id }}" {{ old('organization_unit_id', $user->organization_unit_id) == $unit->id ? 'selected' : '' }}>
                                {{ str_repeat('— ', $unit->level - 1) }}{{ $unit->name }} (L{{ $unit->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            @endif

            <input type="hidden" name="timezone" value="{{ $user->timezone ?? 'Asia/Jakarta' }}">

            @include('users._custom-fields', ['values' => $user->custom_fields ?? []])

            <div class="flex items-center gap-2">
                @if($user->hasRole('admin') || $user->is_super_admin)
                    <input type="hidden" name="is_active" value="1">
                    <input type="checkbox" id="is_active" checked disabled
                           class="w-4 h-4 text-blue-600 rounded opacity-60 cursor-not-allowed">
                @else
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" id="is_active"
                           {{ old('is_active', $user->is_active) ? 'checked' : '' }} class="w-4 h-4 text-blue-600 rounded cursor-pointer">
                @endif
                <label for="is_active" class="text-sm text-gray-700 select-none">Active Account</label>
                @if($user->hasRole('admin') || $user->is_super_admin)
                    <span class="text-xs text-gray-400">(Admin account is always active)</span>
                @endif
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-6 py-2.5 rounded-lg transition-colors">Save Changes</button>
                <a href="{{ route('users.index') }}" class="text-gray-600 text-sm font-medium px-4 py-2.5 rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors">Cancel</a>
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
    $('#select-role, #select-level, #sel-org-unit').select2({
        placeholder: '— Select —',
        allowClear: true,
        width: '100%',
    });
    $('#select-projects').select2({
        placeholder: '— Select Projects —',
        width: '100%',
    });

    $('#select-role').on('change', function () {
        var role = $(this).val();
        if (role === 'admin') {
            $('#is_active').prop('checked', true).prop('disabled', true).addClass('opacity-60 cursor-not-allowed');
        } else {
            @if(!($user->hasRole('admin') || $user->is_super_admin))
            $('#is_active').prop('disabled', false).removeClass('opacity-60 cursor-not-allowed');
            @endif
        }
    });
});
</script>
@endpush