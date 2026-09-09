<?php

namespace App\Models;

use App\Notifications\QueuedVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use NotificationChannels\WebPush\HasPushSubscriptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable // implements MustVerifyEmailContract
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles, MustVerifyEmail, HasPushSubscriptions, LogsActivity;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'google_id',
        'is_active',
        'is_super_admin',
        'is_registered',
        'email_notifications_enabled',
        'active_until',
        'timezone',
        'company_id',
        'organization_unit_id',
        'structural_level_id',
        'employment_type',
        'employment_type_other',
        'outsourcing_company_name',
        'hire_date',
        'contract_end_date',
        'face_descriptor',
        'face_photo',
        'custom_fields',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
            'is_super_admin'    => 'boolean',
            'is_registered'     => 'boolean',
            'email_notifications_enabled' => 'boolean',
            'active_until'      => 'datetime',
            'hire_date'         => 'date',
            'contract_end_date' => 'date',
            'custom_fields'     => 'array',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        // Sengaja tidak logAll() — hindari kecatat password/token/face_descriptor
        // di activity log. Cukup field yang relevan buat "siapa mengubah apa" di
        // halaman /users.
        return LogOptions::defaults()
            ->logOnly([
                'name', 'email', 'is_active', 'employment_type', 'employment_type_other',
                'outsourcing_company_name', 'hire_date', 'contract_end_date', 'custom_fields',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('user');
    }

    public function sendEmailVerificationNotification()
    {
        $this->notify(new QueuedVerifyEmail);
    }

    /** Masa kerja dalam bulan sejak hire_date, dasar hitung THR pro-rata & eligibilitas cuti tahunan. */
    public function tenureMonths(): ?int
    {
        return $this->hire_date ? (int) $this->hire_date->diffInMonths(now()) : null;
    }

    public function contractDaysRemaining(): ?int
    {
        if (! $this->contract_end_date) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->contract_end_date->copy()->startOfDay(), false);
    }

    public function isContractExpired(): bool
    {
        return $this->contract_end_date !== null && $this->contract_end_date->isPast();
    }

    /** Perlu perhatian admin — kontrak habis dalam 30 hari atau sudah lewat. */
    public function isContractExpiringSoon(): bool
    {
        $days = $this->contractDaysRemaining();

        return $days !== null && $days <= 30;
    }

    public function packages()
    {
        return $this->belongsToMany(Package::class)->withTimestamps();
    }

    public function hasPackage(string $slug): bool
    {
        return $this->packages->contains('slug', $slug);
    }

    public function activePackages(): array
    {
        return $this->packages->where('is_active', true)->pluck('slug')->toArray();
    }

    public function isLifetime(): bool
    {
        return is_null($this->active_until);
    }

    public function isExpired(): bool
    {
        if ($this->isLifetime()) {
            return false;
        }

        return $this->active_until->isPast();
    }

    public function hasActiveAccess(): bool
    {
        return $this->is_active && ! $this->isExpired();
    }

    /**
     * Trial/masa aktif perusahaan mengikuti active_until milik user pendaftar
     * (is_registered), karena staff yang ditambahkan belakangan tidak punya
     * active_until sendiri.
     */
    public function companyRegistrant(): ?self
    {
        if (! $this->company_id) {
            return null;
        }

        return static::where('company_id', $this->company_id)
            ->where('is_registered', true)
            ->whereNotNull('active_until')
            ->first();
    }

    public function isCompanyExpired(): bool
    {
        return $this->companyRegistrant()?->isExpired() ?? false;
    }

    /** Sisa hari masa aktif company (dari registrant-nya), null kalau lifetime/belum expired-relevant. */
    public function companyDaysRemaining(): ?int
    {
        $registrant = $this->companyRegistrant();
        if (! $registrant || $registrant->isLifetime()) {
            return null;
        }

        return now()->diffInDays($registrant->active_until, false);
    }

    /** Masa aktif company akan berakhir dalam <= $days hari (tapi belum lewat). */
    public function isCompanyExpiringSoon(int $days = 7): bool
    {
        $remaining = $this->companyDaysRemaining();
        return $remaining !== null && $remaining >= 0 && $remaining <= $days;
    }

    public function scopeRegistered($query)
    {
        return $query->where('is_registered', true);
    }

    public function scopeRegisteredWithLifetime($query)
    {
        return $query->where('is_registered', true)->whereNull('active_until');
    }

    public function managedProjects()
    {
        return $this->hasMany(Project::class, 'manager_id');
    }

    public function clientProjects()
    {
        return $this->hasMany(Project::class, 'client_id');
    }

    public function projectMemberships()
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_members');
    }

    public function assignedTasks()
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function timeLogs()
    {
        return $this->hasMany(TimeLog::class);
    }

    public function phNotifications()
    {
        return $this->hasMany(PhNotification::class);
    }

    public function organizationUnit()
    {
        return $this->belongsTo(OrganizationUnit::class);
    }

    public function structuralLevel()
    {
        return $this->belongsTo(StructuralLevel::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /** Company tambahan (di luar company utama) yang boleh diakses user ini. */
    public function additionalCompanies()
    {
        return $this->belongsToMany(Company::class, 'user_companies');
    }

    public function googleToken()
    {
        return $this->hasOne(GoogleToken::class);
    }

    /** Semua company yang boleh diakses user ini: company utama + company tambahan. */
    public function accessibleCompanies()
    {
        return Company::query()
            ->where(fn($q) => $q->where('id', $this->company_id)
                ->orWhereIn('id', $this->additionalCompanies()->pluck('companies.id')))
            ->orderBy('name')
            ->get();
    }

    /** Cek apakah user ini boleh mengakses company tertentu (utama atau tambahan). */
    public function canAccessCompany(int $companyId): bool
    {
        return $this->company_id === $companyId
            || $this->additionalCompanies()->where('companies.id', $companyId)->exists();
    }

    /**
     * Override Spatie: jika company sudah kustomisasi permission salah satu role user
     * (lihat company_role_permissions / halaman /permissions), role itu dinilai penuh
     * dari kustomisasi tsb, bukan digabung dengan role_has_permissions global.
     * Role yang belum dikustomisasi company tetap memakai default global seperti biasa.
     */
    public function hasPermissionTo($permission, ?string $guardName = null): bool
    {
        if ($this->getWildcardClass()) {
            return $this->hasWildcardPermission($permission, $guardName);
        }

        $permission = $this->filterPermission($permission, $guardName);

        if ($this->company_id) {
            $roles = $this->roles;
            $customizedRoleIds = $roles->isEmpty() ? collect() : CompanyRolePermission::where('company_id', $this->company_id)
                ->whereIn('role_id', $roles->pluck('id'))
                ->pluck('role_id')
                ->unique();

            if ($customizedRoleIds->isNotEmpty()) {
                foreach ($roles as $role) {
                    if ($customizedRoleIds->contains($role->id)) {
                        $allowed = CompanyRolePermission::where('company_id', $this->company_id)
                            ->where('role_id', $role->id)
                            ->where('permission_id', $permission->id)
                            ->exists();
                    } else {
                        $allowed = $role->permissions->contains('id', $permission->id);
                    }

                    if ($allowed) {
                        return true;
                    }
                }

                return $this->hasDirectPermission($permission);
            }
        }

        return $this->hasDirectPermission($permission) || $this->hasPermissionViaRole($permission);
    }

    public function salaries()
    {
        return $this->hasMany(EmployeeSalary::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function overtimes()
    {
        return $this->hasMany(Overtime::class);
    }

    public function reimbursements()
    {
        return $this->hasMany(Reimbursement::class);
    }

    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }
}
