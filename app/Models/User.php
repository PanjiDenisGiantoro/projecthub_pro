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
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmailContract
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles, MustVerifyEmail, HasPushSubscriptions;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'google_id',
        'is_active',
        'is_super_admin',
        'is_registered',
        'active_until',
        'timezone',
        'company_id',
        'department_id',
        'structural_level_id',
        'face_descriptor',
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
            'active_until'      => 'datetime',
        ];
    }

    public function sendEmailVerificationNotification()
    {
        $this->notify(new QueuedVerifyEmail);
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

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function structuralLevel()
    {
        return $this->belongsTo(StructuralLevel::class);
    }

    public function division()
    {
        return $this->hasOneThrough(
            Division::class,
            Department::class,
            'id',
            'id',
            'department_id',
            'division_id'
        );
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
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
