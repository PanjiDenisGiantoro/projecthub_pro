@extends('layouts.app')
@section('title', 'Add User')
@section('page-title', 'Add New User')

@push('head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
@endpush

@section('content')
    <div class="py-4 w-full">
        <form method="POST" action="{{ route('users.store') }}" class="fl-form">
            @csrf

            {{-- Akun --}}
            <section class="fl-section">
                <div>
                    <h3 class="fl-section-title">Account</h3>
                    <p class="fl-section-desc">Name, login email and initial password. The user can change the password later.</p>
                </div>
                <div class="fl-fields">
                    <div>
                        <label class="fl-label" for="name">Full Name <span class="fl-req">*</span></label>
                        <div class="fl-input-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <input type="text" id="name" name="name" value="{{ old('name') }}" required placeholder="e.g. Budi Santoso"
                                class="fl-input @error('name') is-invalid @enderror">
                        </div>
                        @error('name') <p class="fl-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="fl-label" for="email">Email <span class="fl-req">*</span></label>
                        <div class="fl-input-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <input type="email" id="email" name="email" value="{{ old('email') }}" required placeholder="name@company.com"
                                class="fl-input @error('email') is-invalid @enderror">
                        </div>
                        @error('email') <p class="fl-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="fl-label" for="password">Password <span class="fl-req">*</span></label>
                        <div class="fl-input-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            <input type="password" id="password" name="password" required minlength="8"
                                class="fl-input @error('password') is-invalid @enderror">
                        </div>
                        <p class="fl-help">Minimum 8 characters.</p>
                        @error('password') <p class="fl-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="fl-label" for="password_confirmation">Confirm Password <span class="fl-req">*</span></label>
                        <div class="fl-input-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            <input type="password" id="password_confirmation" name="password_confirmation" required class="fl-input">
                        </div>
                    </div>
                </div>
            </section>

            {{-- Peran & akses --}}
            <section class="fl-section">
                <div>
                    <h3 class="fl-section-title">Role &amp; Access</h3>
                    <p class="fl-section-desc">Determines which menus this user can open{{ session('active_package') !== 'hris' ? ' and which projects they join' : '' }}.</p>
                </div>
                <div class="fl-fields">
                    <div>
                        <label class="fl-label">Role <span class="fl-req">*</span></label>
                        <select name="role" id="select-role" required class="w-full">
                            <option value="">— Select Role —</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}" {{ old('role') === $role->name ? 'selected' : '' }}>
                                    {{ \App\Support\RoleLabel::for($role->name) }}</option>
                            @endforeach
                        </select>
                        @error('role') <p class="fl-error">{{ $message }}</p> @enderror
                    </div>

                    @if(session('active_package') === 'hris')
                        <div>
                            <label class="fl-label">Structural Level</label>
                            <select name="structural_level_id" id="select-level" class="w-full">
                                <option value="">— Not Specified —</option>
                                @foreach($structuralLevels as $level)
                                    <option value="{{ $level->id }}" {{ old('structural_level_id') == $level->id ? 'selected' : '' }}>
                                        {{ $level->sort_order }}. {{ $level->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <div>
                            <label class="fl-label">Projects</label>
                            <select name="project_ids[]" id="select-projects" multiple style="width:100%">
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}" {{ collect(old('project_ids'))->contains($project->id) ? 'selected' : '' }}>
                                        {{ $project->name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="fl-help">Optional. User will be added as a member of selected projects.</p>
                        </div>
                    @endif

                    <div class="fl-span-2">
                        <label class="fl-switch">
                            <input type="checkbox" name="is_active" value="1" id="is_active" {{ old('_token') ? (old('is_active') ? 'checked' : '') : 'checked' }}>
                            <span class="fl-switch-track"></span>
                            <span>Active Account</span>
                        </label>
                        <p class="fl-help">Inactive users cannot log in.</p>
                    </div>
                </div>
            </section>

            @if(session('active_package') === 'hris')
                {{-- Kepegawaian --}}
                <section class="fl-section" x-data="{ employmentType: '{{ old('employment_type', 'tetap') }}' }">
                    <div>
                        <h3 class="fl-section-title">Employment</h3>
                        <p class="fl-section-desc">Employment status and contract period.</p>
                    </div>
                    <div class="fl-fields">
                        <div>
                            <label class="fl-label" for="select-employment-type">Employment Type <span class="fl-req">*</span></label>
                            <select name="employment_type" id="select-employment-type" x-model="employmentType" required class="fl-input">
                                @foreach(\App\Support\EmploymentType::options() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div x-show="employmentType === 'lainnya'" x-cloak>
                            <label class="fl-label">Employment Type Name <span class="fl-req">*</span></label>
                            <input type="text" name="employment_type_other" value="{{ old('employment_type_other') }}"
                                placeholder="e.g. Seasonal Freelance, Consultant..." :required="employmentType === 'lainnya'"
                                class="fl-input @error('employment_type_other') is-invalid @enderror">
                            @error('employment_type_other') <p class="fl-error">{{ $message }}</p> @enderror
                        </div>

                        <div x-show="!['tetap','kontrak'].includes(employmentType)" x-cloak>
                            <label class="fl-label">Source Company <span class="fl-req">*</span></label>
                            <input type="text" name="outsourcing_company_name" value="{{ old('outsourcing_company_name') }}"
                                placeholder="Company or vendor name"
                                class="fl-input @error('outsourcing_company_name') is-invalid @enderror">
                            @error('outsourcing_company_name') <p class="fl-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="fl-label">Hire Date</label>
                            <input type="date" name="hire_date" value="{{ old('hire_date') }}"
                                class="fl-input @error('hire_date') is-invalid @enderror">
                            @error('hire_date') <p class="fl-error">{{ $message }}</p> @enderror
                        </div>

                        <div x-show="employmentType !== 'tetap'" x-cloak>
                            <label class="fl-label">
                                Contract End Date <span class="fl-req" x-show="employmentType === 'kontrak'">*</span>
                            </label>
                            <input type="date" name="contract_end_date" value="{{ old('contract_end_date') }}"
                                :required="employmentType === 'kontrak'"
                                class="fl-input @error('contract_end_date') is-invalid @enderror">
                            @error('contract_end_date') <p class="fl-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </section>

                {{-- Penempatan --}}
                <section class="fl-section">
                    <div>
                        <h3 class="fl-section-title">Placement</h3>
                        <p class="fl-section-desc">Organization unit and default work shift.</p>
                    </div>
                    <div class="fl-fields">
                        <div>
                            <label class="fl-label">Organization Unit</label>
                            <select name="organization_unit_id" id="sel-org-unit" class="w-full">
                                <option value="">— Not Specified —</option>
                                @foreach($organizationUnits as $unit)
                                    <option value="{{ $unit->id }}" {{ old('organization_unit_id') == $unit->id ? 'selected' : '' }}>
                                        {{ str_repeat('— ', $unit->level - 1) }}{{ $unit->name }} (L{{ $unit->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="fl-label">Shift Kerja</label>
                            <select name="shift_id" id="sel-shift" class="w-full">
                                <option value="">— Tidak Ditentukan —</option>
                                @foreach($shifts as $shift)
                                    <option value="{{ $shift->id }}" {{ old('shift_id') == $shift->id ? 'selected' : '' }}>
                                        {{ $shift->name }} ({{ $shift->timeRangeLabel() }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="fl-help">Atur daftar shift di halaman Pengaturan Absensi.</p>
                        </div>
                    </div>
                </section>
            @endif

            @include('users._custom-fields', ['values' => []])

            <div class="fl-actions">
                <a href="{{ route('users.index') }}" class="fl-btn fl-btn-secondary">Cancel</a>
                <button type="submit" class="fl-btn fl-btn-primary">Create User</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(function () {
            $('#select-role, #select-level, #sel-org-unit, #sel-shift').select2({
                placeholder: '— Pilih —',
                allowClear: true,
                width: '100%',
            });
            $('#select-projects').select2({
                placeholder: '— Select Projects —',
                width: '100%',
            });
        });
    </script>
@endpush