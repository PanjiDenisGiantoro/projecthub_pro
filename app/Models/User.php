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
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmailContract
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles, MustVerifyEmail;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'is_active',
        'is_super_admin',
        'is_registered',
        'active_until',
        'timezone',
        'company_id',
        'department_id',
        'structural_level_id',
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
