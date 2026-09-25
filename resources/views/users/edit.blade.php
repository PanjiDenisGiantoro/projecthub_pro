@extends('layouts.app')
@section('title', 'Edit User')
@section('page-title', 'Edit User')

@push('head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/filepond@4/dist/filepond.min.css">
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/filepond-plugin-image-preview@4/dist/filepond-plugin-image-preview.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
@endpush

@section('content')
    @php $isAdminUser = $user->hasRole('admin') || $user->is_super_admin; @endphp
    <div class="py-4 w-full">
        <form method="POST" action="{{ route('users.update', $user) }}" enctype="multipart/form-data" class="fl-form">
            @csrf @method('PUT')
            <input type="hidden" name="timezone" value="{{ $user->timezone ?? 'Asia/Jakarta' }}">

            {{-- Profil --}}
            <section class="fl-section">
                <div>
                    <h3 class="fl-section-title">Profile</h3>
                    <p class="fl-section-desc">Photo, name and login email of this user.</p>
                </div>
                <div class="fl-fields">
                    <div class="fl-span-2">
                        <label class="fl-label">User Photo</label>
                        <div class="flex items-center gap-4">
                            @if($user->avatar)
                                <img src="{{ Storage::url($user->avatar) }}" alt="{{ $user->name }}"
                                    class="w-14 h-14 rounded-full object-cover ring-2 ring-white shadow shrink-0">
                            @else
                                <div class="w-14 h-14 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-semibold shrink-0">
                                    {{ strtoupper(substr($user->name, 0, 2)) }}
                                </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <input type="file" name="avatar" class="filepond-avatar-input">
                                @if($user->avatar)
                                    <label class="mt-1 inline-flex items-center gap-1.5 text-xs text-red-500 hover:text-red-700 cursor-pointer">
                                        <input type="checkbox" name="remove_avatar" value="1" class="w-3.5 h-3.5 rounded">
                                        Delete existing photo
                                    </label>
                                @endif
                            </div>
                        </div>
                        @error('avatar') <p class="fl-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="fl-label" for="name">Full Name <span class="fl-req">*</span></label>
                        <div class="fl-input-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                                class="fl-input @error('name') is-invalid @enderror">
                        </div>
                        @error('name') <p class="fl-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="fl-label" for="email">Email <span class="fl-req">*</span></label>
                        <div class="fl-input-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
                                class="fl-input @error('email') is-invalid @enderror">
                        </div>
                        @error('email') <p class="fl-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            {{-- Peran & akses --}}
            <section class="fl-section">
                <div>
                    <h3 class="fl-section-title">Role &amp; Access</h3>
                    <p class="fl-section-desc">Determines which menus this user can open{{ session('active_package') !== 'hris' ? ' and which projects they belong to' : '' }}.</p>
                </div>
                <div class="fl-fields">
                    <div>
                        <label class="fl-label">Role <span class="fl-req">*</span></label>
                        <select name="role" id="select-role" required class="w-full">
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}" {{ old('role', $user->getRoleNames()->first()) === $role->name ? 'selected' : '' }}>{{ \App\Support\RoleLabel::for($role->name) }}</option>
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
                                    <option value="{{ $level->id }}" {{ old('structural_level_id', $user->structural_level_id) == $level->id ? 'selected' : '' }}>
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
                                    <option value="{{ $project->id }}" {{ collect(old('project_ids', $selectedProjectIds))->contains($project->id) ? 'selected' : '' }}>
                                        {{ $project->name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="fl-help">Optional. Project team members will be updated accordingly.</p>
                        </div>
                    @endif

                    <div class="fl-span-2">
                        <label class="fl-switch">
                            @if($isAdminUser)
                                <input type="hidden" name="is_active" value="1">
                                <input type="checkbox" id="is_active" checked disabled>
                            @else
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
                            @endif
                            <span class="fl-switch-track"></span>
                            <span>Active Account</span>
                        </label>
                        <p class="fl-help">{{ $isAdminUser ? 'Admin accounts are always active.' : 'Inactive users cannot log in.' }}</p>
                    </div>
                </div>
            </section>

            @if(session('active_package') === 'hris')
                {{-- Kepegawaian --}}
                <section class="fl-section" x-data="{ employmentType: '{{ old('employment_type', $user->employment_type ?? 'tetap') }}' }">
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
                            <input type="text" name="employment_type_other"
                                value="{{ old('employment_type_other', $user->employment_type_other) }}"
                                placeholder="e.g. Seasonal Freelance, Consultant..." :required="employmentType === 'lainnya'"
                                class="fl-input @error('employment_type_other') is-invalid @enderror">
                            @error('employment_type_other') <p class="fl-error">{{ $message }}</p> @enderror
                        </div>

                        <div x-show="!['tetap','kontrak'].includes(employmentType)" x-cloak>
                            <label class="fl-label">Source Company <span class="fl-req">*</span></label>
                            <input type="text" name="outsourcing_company_name"
                                value="{{ old('outsourcing_company_name', $user->outsourcing_company_name) }}"
                                placeholder="Company or vendor name"
                                class="fl-input @error('outsourcing_company_name') is-invalid @enderror">
                            @error('outsourcing_company_name') <p class="fl-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="fl-label">Hire Date</label>
                            <input type="date" name="hire_date"
                                value="{{ old('hire_date', $user->hire_date?->format('Y-m-d')) }}"
                                class="fl-input @error('hire_date') is-invalid @enderror">
                            @error('hire_date') <p class="fl-error">{{ $message }}</p> @enderror
                        </div>

                        <div x-show="employmentType !== 'tetap'" x-cloak>
                            <label class="fl-label">
                                Contract End Date <span class="fl-req" x-show="employmentType === 'kontrak'">*</span>
                            </label>
                            <input type="date" name="contract_end_date"
                                value="{{ old('contract_end_date', $user->contract_end_date?->format('Y-m-d')) }}"
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
                                    <option value="{{ $unit->id }}" {{ old('organization_unit_id', $user->organization_unit_id) == $unit->id ? 'selected' : '' }}>
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
                                    <option value="{{ $shift->id }}" {{ old('shift_id', $user->shift_id) == $shift->id ? 'selected' : '' }}>
                                        {{ $shift->name }} ({{ $shift->timeRangeLabel() }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="fl-help">Atur daftar shift di halaman Pengaturan Absensi.</p>
                        </div>
                    </div>
                </section>
            @endif

            @include('users._custom-fields', ['values' => $user->custom_fields ?? []])

            <div class="fl-actions">
                <a href="{{ route('users.index') }}" class="fl-btn fl-btn-secondary">Cancel</a>
                <button type="submit" class="fl-btn fl-btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script
        src="https://cdn.jsdelivr.net/npm/filepond-plugin-file-validate-size@2/dist/filepond-plugin-file-validate-size.min.js"></script>
    <script
        src="https://cdn.jsdelivr.net/npm/filepond-plugin-image-preview@4/dist/filepond-plugin-image-preview.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/filepond@4/dist/filepond.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            FilePond.registerPlugin(FilePondPluginFileValidateSize, FilePondPluginImagePreview);

            var avatarInput = document.querySelector('.filepond-avatar-input');
            if (avatarInput) {
                FilePond.create(avatarInput, {
                    allowMultiple: false,
                    maxFiles: 1,
                    maxFileSize: '2MB',
                    instantUpload: false,
                    storeAsFile: true,
                    labelIdle: 'Seret foto ke sini atau <span class="filepond--label-action">Pilih Foto</span>',
                });
            }
        });

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