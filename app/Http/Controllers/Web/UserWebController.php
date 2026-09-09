<?php

namespace App\Http\Controllers\Web;

use App\Exports\UsersExport;
use App\Exports\UsersImportTemplateExport;
use App\Http\Controllers\Concerns\HasPerPage;
use App\Http\Controllers\Controller;
use App\Imports\UsersImport;
use App\Models\CustomFieldDefinition;
use App\Models\OrganizationUnit;
use App\Models\Package;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\StructuralLevel;
use App\Models\User;
use App\Support\EmploymentType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

class UserWebController extends Controller
{
    use HasPerPage;

    public function index(Request $request)
    {
        $authUser  = auth()->user();
        $canCreate = $authUser->can('create user');
        $canUpdate = $authUser->can('update user');
        $canDelete = $authUser->can('delete user');
        $isAdmin   = $canCreate || $canUpdate || $canDelete;

        $baseScope = function ($q) use ($authUser, $isAdmin) {
            $q->whereDoesntHave('roles', fn($r) => $r->where('name', 'client'));

            if ($authUser->company_id) {
                $q->where('company_id', $authUser->company_id);
            } elseif (! $isAdmin) {
                $q->where('id', $authUser->id);
            }
        };

        $query = User::with(['roles', 'structuralLevel', 'organizationUnit', 'projects:id,name'])
            ->tap($baseScope)
            ->when($request->role, fn($q) => $q->role($request->role))
            // Dibungkus di dalam satu grup where() supaya OR-nya cuma berlaku di antara
            // name/email/custom_fields, bukan meng-OR seluruh scope company & role di atas
            // (kalau tidak dibungkus, "orWhere" bakal jadi top-level clause yang bisa
            // membocorkan user dari company lain begitu email-nya cocok).
            ->when($request->search, function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereRaw('JSON_SEARCH(custom_fields, "one", ?) IS NOT NULL', ["%{$search}%"]);
                });
            });

        $users = $query->paginate($this->perPage($request))->withQueryString();
        $roles = $isAdmin ? Role::whereNotIn('name', ['client', 'tester'])->get() : collect();

        $totalUsers    = User::tap($baseScope)->count();
        $activeUsers   = User::tap($baseScope)->where('is_active', true)->count();
        $inactiveUsers = User::tap($baseScope)->where('is_active', false)->count();
        $customFields  = $this->activeCustomFields();
        // Cuma hitung jumlah di sini (buat badge) — daftar log lengkap baru di-load
        // lewat logs() saat panel riwayat dibuka user (lazy load).
        $logsCount = $this->userLogsQuery()->count();

        return view('users.index', compact(
            'users', 'roles', 'isAdmin', 'canCreate', 'canUpdate', 'canDelete',
            'totalUsers', 'activeUsers', 'inactiveUsers', 'customFields', 'logsCount'
        ));
    }

    public function logs()
    {
        $logs = $this->userLogsQuery()->with('causer')->latest()->limit(50)->get();

        return view('users._logs', compact('logs'));
    }

    /**
     * Log create/update/delete user, dibatasi ke company admin yang login (via
     * causer, bukan subject — subject user yang sudah dihapus tidak lagi ada
     * baris company_id-nya buat di-join).
     */
    private function userLogsQuery()
    {
        $companyId = auth()->user()->company_id;

        return Activity::where('log_name', 'user')
            ->when($companyId, fn ($q) => $q->whereHasMorph('causer', [User::class], fn ($q2) => $q2->where('company_id', $companyId)));
    }

    /**
     * Admin Tim — daftar user ber-role admin di company sendiri, termasuk yang
     * juga is_super_admin=true (mis. admin yang punya akses /superadmin).
     * Dipisah dari index() supaya /users (Anggota Tim) tetap fokus ke tim non-admin.
     */
    public function adminTeam(Request $request)
    {
        $authUser = auth()->user();

        $users = User::with(['roles', 'structuralLevel', 'organizationUnit'])
            ->whereHas('roles', fn($q) => $q->where('name', 'admin'))
            ->where('company_id', $authUser->company_id)
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%"))
            ->paginate($this->perPage($request))
            ->withQueryString();

        return view('users.admin-team', compact('users'));
    }

    /**
     * Export data karyawan ke Excel. Otorisasi lewat middleware route
     * (can:export user) — permission ini default cuma dimiliki admin, tapi
     * company bisa meng-grant ke role lain lewat halaman /permissions.
     */
    public function export()
    {
        $authUser = auth()->user();

        $query = User::with(['roles', 'structuralLevel', 'organizationUnit'])
            ->whereDoesntHave('roles', fn($r) => $r->where('name', 'client'));

        if ($authUser->company_id) {
            $query->where('company_id', $authUser->company_id);
        }

        $users = $query->orderBy('name')->get();

        return Excel::download(
            new UsersExport($users, $this->activeCustomFields()),
            'data-karyawan-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    /** Template Excel siap isi buat import — sheet contoh + sheet petunjuk nilai valid. */
    public function importTemplate()
    {
        $companyId = auth()->user()->company_id;

        return Excel::download(
            new UsersImportTemplateExport(
                $this->activeCustomFields(),
                Role::whereNotIn('name', ['client', 'tester'])->get(),
                OrganizationUnit::where('company_id', $companyId)->orderBy('name')->get(),
                StructuralLevel::where('company_id', $companyId)->orderBy('name')->get(),
            ),
            'template-import-karyawan.xlsx'
        );
    }

    public function import(Request $request)
    {
        $authUser = auth()->user();
        abort_unless($authUser->company_id, 403);

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        $import = new UsersImport($authUser->company_id);
        Excel::import($import, $request->file('file'));

        $message = "Import selesai: {$import->created} user baru ditambahkan, {$import->updated} user diperbarui.";

        if ($import->errors) {
            $shown = array_slice($import->errors, 0, 15);
            $extra = count($import->errors) - count($shown);
            $message .= ' ' . count($import->errors) . ' baris gagal diproses: ' . implode(' | ', $shown)
                . ($extra > 0 ? " (+{$extra} baris lainnya)" : '');

            return redirect()->route('users.index')->with('warning', $message);
        }

        return redirect()->route('users.index')->with('success', $message);
    }

    public function create()
    {
        $roles             = Role::whereNotIn('name', ['tester', 'client'])->get();
        $structuralLevels  = StructuralLevel::active()->where('company_id', auth()->user()->company_id)->get();
        $organizationUnits = OrganizationUnit::orderedTree(auth()->user()->company_id);
        $projects          = Project::where('company_id', auth()->user()->company_id)->orderBy('name')->get();
        $customFields      = $this->activeCustomFields();
        return view('users.create', compact('roles', 'structuralLevels', 'organizationUnits', 'projects', 'customFields'));
    }

    public function store(Request $request)
    {
        $customFields = $this->activeCustomFields();

        $request->validate([
            'name'                      => 'required|string|max:255',
            'email'                     => 'required|email|unique:users',
            'password'                  => 'required|min:8|confirmed',
            'role'                      => 'required|exists:roles,name|not_in:client',
            'structural_level_id'       => 'nullable|exists:structural_levels,id',
            'organization_unit_id'      => 'nullable|exists:organization_units,id',
            'employment_type'           => ['nullable', Rule::in(array_keys(EmploymentType::LABELS))],
            'employment_type_other'     => 'nullable|string|max:100|required_if:employment_type,lainnya',
            'outsourcing_company_name'  => 'nullable|string|max:255|required_unless:employment_type,tetap,kontrak',
            'hire_date'                 => 'nullable|date',
            'contract_end_date'         => 'nullable|date|after_or_equal:hire_date|required_if:employment_type,kontrak',
            'project_ids'               => 'nullable|array',
            'project_ids.*'             => [Rule::exists('projects', 'id')->where('company_id', auth()->user()->company_id)],
            ...$this->customFieldValidationRules($customFields),
        ]);

        $user = User::create([
            'name'                      => $request->name,
            'email'                     => $request->email,
            'password'                  => $request->password,
            'company_id'                => auth()->user()->company_id,
            'is_active'                 => $request->boolean('is_active', true),
            'structural_level_id'       => $request->structural_level_id,
            'organization_unit_id'      => $request->organization_unit_id,
            'employment_type'           => $request->employment_type ?? EmploymentType::TETAP,
            'employment_type_other'     => EmploymentType::requiresCustomLabel($request->employment_type) ? $request->employment_type_other : null,
            'outsourcing_company_name'  => EmploymentType::requiresSourceCompany($request->employment_type) ? $request->outsourcing_company_name : null,
            'hire_date'                 => $request->hire_date,
            'contract_end_date'         => EmploymentType::allowsContractEndDate($request->employment_type) ? $request->contract_end_date : null,
            'custom_fields'             => $this->collectCustomFieldValues($request, $customFields),
        ]);
        $user->assignRole($request->role);

        foreach ($request->input('project_ids', []) as $projectId) {
            ProjectMember::firstOrCreate(['project_id' => $projectId, 'user_id' => $user->id]);
        }

        // User baru mengikuti package yang sudah dipakai company-nya,
        // supaya tidak perlu di-assign manual satu-satu oleh super admin.
        $companyPackageIds = Package::whereHas('users', function ($q) {
            $q->where('company_id', auth()->user()->company_id);
        })->pluck('id');
        $user->packages()->sync($companyPackageIds);

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $roles             = Role::all();
        $structuralLevels  = StructuralLevel::active()->where('company_id', auth()->user()->company_id)->get();
        $organizationUnits = OrganizationUnit::orderedTree(auth()->user()->company_id);
        $projects          = Project::where('company_id', auth()->user()->company_id)->orderBy('name')->get();
        $selectedProjectIds = ProjectMember::where('user_id', $user->id)->pluck('project_id')->toArray();
        $customFields      = $this->activeCustomFields();

        return view('users.edit', compact('user', 'roles', 'structuralLevels', 'organizationUnits', 'projects', 'selectedProjectIds', 'customFields'));
    }

    public function update(Request $request, User $user)
    {
        $customFields = $this->activeCustomFields();

        $request->validate([
            'name'                      => 'required|string|max:255',
            'email'                     => 'required|email|unique:users,email,' . $user->id,
            'role'                      => 'required|exists:roles,name',
            'structural_level_id'       => 'nullable|exists:structural_levels,id',
            'organization_unit_id'      => 'nullable|exists:organization_units,id',
            'employment_type'           => ['nullable', Rule::in(array_keys(EmploymentType::LABELS))],
            'employment_type_other'     => 'nullable|string|max:100|required_if:employment_type,lainnya',
            'outsourcing_company_name'  => 'nullable|string|max:255|required_unless:employment_type,tetap,kontrak',
            'hire_date'                 => 'nullable|date',
            'contract_end_date'         => 'nullable|date|after_or_equal:hire_date|required_if:employment_type,kontrak',
            'project_ids'               => 'nullable|array',
            'project_ids.*'             => [Rule::exists('projects', 'id')->where('company_id', auth()->user()->company_id)],
            ...$this->customFieldValidationRules($customFields),
        ]);

        $isAdminRole = $request->role === 'admin' || $user->hasRole('admin') || $user->is_super_admin;
        $data = [
            ...$request->only('name', 'email', 'timezone', 'structural_level_id', 'organization_unit_id'),
            'is_active' => $isAdminRole ? true : $request->boolean('is_active'),
        ];

        // Kalau tidak ada field kustom aktif, form tidak menampilkan blok ini sama
        // sekali — jangan timpa custom_fields yang sudah ada (mis. dari saat field-nya
        // masih aktif) dengan null.
        if ($customFields->isNotEmpty()) {
            $data['custom_fields'] = $this->collectCustomFieldValues($request, $customFields);
        }

        // Field HRIS ini tidak dikirim sama sekali kalau paket HRIS company tidak aktif
        // (form tidak menampilkannya) — jangan timpa data yang sudah ada dengan default.
        if ($request->has('employment_type')) {
            $data['employment_type'] = $request->employment_type ?? EmploymentType::TETAP;
            $data['employment_type_other'] = EmploymentType::requiresCustomLabel($request->employment_type)
                ? $request->employment_type_other
                : null;
            $data['outsourcing_company_name'] = EmploymentType::requiresSourceCompany($request->employment_type)
                ? $request->outsourcing_company_name
                : null;
            $data['hire_date'] = $request->hire_date;
            $data['contract_end_date'] = EmploymentType::allowsContractEndDate($request->employment_type)
                ? $request->contract_end_date
                : null;
        }

        $user->update($data);
        $user->syncRoles([$request->role]);

        $companyProjectIds = Project::where('company_id', auth()->user()->company_id)->pluck('id');
        $selectedProjectIds = $request->input('project_ids', []);

        ProjectMember::where('user_id', $user->id)
            ->whereIn('project_id', $companyProjectIds)
            ->whereNotIn('project_id', $selectedProjectIds)
            ->delete();

        foreach ($selectedProjectIds as $projectId) {
            ProjectMember::firstOrCreate(['project_id' => $projectId, 'user_id' => $user->id]);
        }

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['Cannot delete your own account.']);
        }
        $user->delete();
        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }

    /** Field kustom aktif milik company user yang login, urut sesuai sort_order. */
    private function activeCustomFields()
    {
        $companyId = auth()->user()->company_id;
        if (! $companyId) {
            return collect();
        }

        return CustomFieldDefinition::forCompany($companyId)->active()->get();
    }

    /** Rule validasi dinamis, satu per field kustom aktif (custom_fields.{key}). */
    private function customFieldValidationRules($customFields): array
    {
        $rules = [];
        foreach ($customFields as $field) {
            $fieldRules = [$field->is_required ? 'required' : 'nullable'];
            if ($field->type === 'number') $fieldRules[] = 'numeric';
            if ($field->type === 'date') $fieldRules[] = 'date';
            $rules["custom_fields.{$field->key}"] = implode('|', $fieldRules);
        }

        return $rules;
    }

    /** Kumpulkan nilai custom_fields[...] dari request sesuai definisi field aktif saja. */
    private function collectCustomFieldValues(Request $request, $customFields): ?array
    {
        if ($customFields->isEmpty()) {
            return null;
        }

        $values = [];
        foreach ($customFields as $field) {
            $values[$field->key] = $field->type === 'checkbox'
                ? $request->boolean("custom_fields.{$field->key}")
                : $request->input("custom_fields.{$field->key}");
        }

        return $values;
    }
}
