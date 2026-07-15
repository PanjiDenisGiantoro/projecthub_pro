<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Project extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'company_id', 'name', 'description', 'client_id', 'manager_id',
        'status', 'start_date', 'end_date', 'budget', 'budget_alert_threshold', 'progress',
    ];

    protected static function booted(): void
    {
        // Auto-filter per tenant; super admin bypass
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && ! auth()->user()->is_super_admin && $cid = auth()->user()->company_id) {
                $builder->where('projects.company_id', $cid);
            }
        });

        // Auto-fill company_id saat create
        static::creating(function (Project $project) {
            if (! $project->company_id && auth()->check() && auth()->user()->company_id) {
                $project->company_id = auth()->user()->company_id;
            }
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'budget' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->useLogName('project');
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function members()
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function milestones()
    {
        return $this->hasMany(Milestone::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function tickets()
    {
        return $this->hasMany(BugTicket::class);
    }

    public function customerRequests()
    {
        return $this->hasMany(CustomerRequest::class);
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function kbArticles()
    {
        return $this->hasMany(KbArticle::class);
    }

    public function slaPolicies()
    {
        return $this->hasMany(SlaPolicy::class);
    }

    public function sprints()
    {
        return $this->hasMany(Sprint::class);
    }

    public function files()
    {
        return $this->hasMany(ProjectFile::class);
    }

    public function risks()
    {
        return $this->hasMany(Risk::class);
    }

    public function budgetEntries()
    {
        return $this->hasMany(BudgetEntry::class);
    }

    public function portalTokens()
    {
        return $this->hasMany(ClientPortalToken::class);
    }

    public function recurringTasks()
    {
        return $this->hasMany(RecurringTaskDefinition::class);
    }

    public function messages()
    {
        return $this->hasMany(ProjectMessage::class);
    }

    public function totalExpenses(): float
    {
        return (float) $this->budgetEntries()->where('type', 'expense')->sum('amount');
    }

    public function totalIncome(): float
    {
        return (float) $this->budgetEntries()->where('type', 'income')->sum('amount');
    }

    public function budgetUsedPercent(): float
    {
        if (!$this->budget || $this->budget <= 0) return 0;
        return min(100, round($this->totalExpenses() / $this->budget * 100, 1));
    }
}
