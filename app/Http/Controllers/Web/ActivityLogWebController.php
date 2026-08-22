<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Concerns\HasPerPage;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogWebController extends Controller
{
    use HasPerPage;

    private array $logNames = [
        'project'          => 'Proyek',
        'task'             => 'Task',
        'ticket'           => 'Tiket',
        'customer_request' => 'Customer Request',
    ];

    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $activities = Activity::with('causer')
            ->when($companyId, fn($q) => $q->whereHasMorph('causer', [User::class], fn($q2) => $q2->where('company_id', $companyId)))
            ->when($request->log_name, fn($q) => $q->where('log_name', $request->log_name))
            ->when($request->search, fn($q) => $q->where('description', 'like', "%{$request->search}%"))
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();

        return view('activity-log.index', ['activities' => $activities, 'logNames' => $this->logNames]);
    }
}
