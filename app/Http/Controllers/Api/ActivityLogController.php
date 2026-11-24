<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\HasPerPage;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;

/**
 * API Activity Log (mobile ActivityLogsScreen) — padanan ActivityLogWebController.
 * Hanya aktivitas yang causer-nya user di company yang sama (super admin: semua).
 */
class ActivityLogController extends Controller
{
    use HasPerPage;

    public const LOG_NAMES = [
        'project'          => 'Proyek',
        'task'             => 'Task',
        'ticket'           => 'Tiket',
        'customer_request' => 'Customer Request',
        'payroll_setting'  => 'Pengaturan Payroll',
    ];

    /** GET /api/activity-logs?log_name=&search=&causer_id=&date_from=&date_to=&per_page= */
    public function index(Request $request)
    {
        $request->validate([
            'log_name'  => 'nullable|string',
            'search'    => 'nullable|string|max:255',
            'causer_id' => 'nullable|integer',
            'date_from' => 'nullable|date',
            'date_to'   => 'nullable|date',
        ]);

        $user      = $request->user();
        $companyId = $user->is_super_admin ? null : $user->company_id;

        $activities = Activity::with('causer:id,name,email,avatar')
            ->when($companyId, fn($q) => $q->whereHasMorph('causer', [User::class], fn($q2) => $q2->where('company_id', $companyId)))
            ->when($request->log_name, fn($q) => $q->where('log_name', $request->log_name))
            ->when($request->search, fn($q) => $q->where('description', 'like', "%{$request->search}%"))
            ->when($request->causer_id, fn($q) => $q->where('causer_type', User::class)->where('causer_id', $request->causer_id))
            ->when($request->date_from, fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->latest()
            ->paginate($this->perPage($request, 25))
            ->withQueryString()
            ->through(fn(Activity $a) => $this->transform($a));

        return response()->json(array_merge($activities->toArray(), [
            'log_names' => collect(self::LOG_NAMES)->map(fn($label, $key) => ['value' => $key, 'label' => $label])->values(),
        ]));
    }

    private function transform(Activity $a): array
    {
        $causer = $a->causer;

        return [
            'id'           => $a->id,
            'log_name'     => $a->log_name,
            'log_label'    => self::LOG_NAMES[$a->log_name] ?? ucfirst(str_replace('_', ' ', (string) $a->log_name)),
            'description'  => $a->description,
            'event'        => $a->event,
            'subject_type' => $a->subject_type ? class_basename($a->subject_type) : null,
            'subject_id'   => $a->subject_id,
            'causer'       => $causer ? [
                'id'         => $causer->id,
                'name'       => $causer->name,
                'email'      => $causer->email,
                'avatar_url' => $causer->avatar ? Storage::url($causer->avatar) : null,
            ] : null,
            'properties'   => $a->properties,
            'created_at'   => $a->created_at?->toIso8601String(),
        ];
    }
}
