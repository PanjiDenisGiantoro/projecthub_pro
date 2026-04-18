<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Approval;
use App\Models\ApprovalPolicy;
use App\Services\ApprovalService;
use Illuminate\Http\Request;

class ApprovalWebController extends Controller
{
    public function __construct(private ApprovalService $approvalService) {}

    public function index(Request $request)
    {
        $user  = $request->user();
        $roles = $user->getRoleNames()->toArray();

        $pendingForMe = Approval::with(['steps', 'policy', 'requester', 'approvable'])
            ->where('status', 'pending')
            ->whereHas('steps', fn($q) => $q->where('status', 'pending')->whereIn('approver_role', $roles))
            ->latest()
            ->paginate(15, ['*'], 'pending_page');

        $myRequests = Approval::with(['steps.approver', 'policy', 'approvable'])
            ->where('requested_by', $user->id)
            ->latest()
            ->paginate(15, ['*'], 'my_page');

        $stats = [
            'pending_for_me' => Approval::where('status', 'pending')
                ->whereHas('steps', fn($q) => $q->where('status', 'pending')->whereIn('approver_role', $roles))
                ->count(),
            'my_pending'    => Approval::where('requested_by', $user->id)->where('status', 'pending')->count(),
            'my_approved'   => Approval::where('requested_by', $user->id)->where('status', 'approved')->count(),
            'my_rejected'   => Approval::where('requested_by', $user->id)->where('status', 'rejected')->count(),
        ];

        return view('approvals.index', compact('pendingForMe', 'myRequests', 'stats'));
    }

    public function approve(Request $request, Approval $approval)
    {
        $request->validate(['notes' => 'nullable|string|max:500']);

        try {
            $this->approvalService->approve($approval, $request->user(), $request->notes ?? '');
            return back()->with('success', 'Permintaan berhasil disetujui.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reject(Request $request, Approval $approval)
    {
        $request->validate(['notes' => 'required|string|max:500']);

        try {
            $this->approvalService->reject($approval, $request->user(), $request->notes);
            return back()->with('error_msg', 'Permintaan telah ditolak.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(Request $request, Approval $approval)
    {
        try {
            $this->approvalService->cancel($approval, $request->user());
            return back()->with('success', 'Permintaan approval dibatalkan.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    // ─── Policies ─────────────────────────────────────────────────────────────

    public function policies()
    {
        $policies = ApprovalPolicy::withCount([
            'approvals',
            'approvals as pending_count'  => fn($q) => $q->where('status', 'pending'),
            'approvals as approved_count' => fn($q) => $q->where('status', 'approved'),
        ])->orderBy('module')->orderBy('action')->get();

        $modules = $policies->pluck('module')->unique()->values();

        $stats = [
            'total'    => $policies->count(),
            'active'   => $policies->where('is_active', true)->count(),
            'inactive' => $policies->where('is_active', false)->count(),
            'pending'  => $policies->sum('pending_count'),
        ];

        return view('approvals.policies', compact('policies', 'modules', 'stats'));
    }

    public function storePolicy(Request $request)
    {
        $request->validate([
            'module'           => 'required|string|max:50',
            'action'           => 'required|string|max:50',
            'flow_type'        => 'required|in:sequential,parallel_all,any_of,single',
            'approver_roles'   => 'required|array|min:1',
            'approver_roles.*' => 'string|in:admin,manager,developer,marketing,customer',
            'timeout_hours'    => 'required|integer|min:1|max:720',
            'description'      => 'nullable|string|max:500',
        ]);

        $exists = ApprovalPolicy::where('module', $request->module)->where('action', $request->action)->exists();
        if ($exists) return back()->with('error', "Policy '{$request->module}.{$request->action}' sudah ada.");

        ApprovalPolicy::create([
            ...$request->only('module', 'action', 'flow_type', 'approver_roles', 'timeout_hours', 'description'),
            'is_active' => true,
        ]);

        return back()->with('success', 'Policy berhasil ditambahkan.');
    }

    public function updatePolicy(Request $request, ApprovalPolicy $policy)
    {
        $request->validate([
            'flow_type'        => 'required|in:sequential,parallel_all,any_of,single',
            'approver_roles'   => 'required|array|min:1',
            'approver_roles.*' => 'string|in:admin,manager,developer,marketing,customer',
            'timeout_hours'    => 'required|integer|min:1|max:720',
            'description'      => 'nullable|string|max:500',
        ]);

        $policy->update($request->only('flow_type', 'approver_roles', 'timeout_hours', 'description'));
        return back()->with('success', 'Policy berhasil diupdate.');
    }

    public function togglePolicy(ApprovalPolicy $policy)
    {
        $policy->update(['is_active' => !$policy->is_active]);
        $state = $policy->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Policy berhasil {$state}.");
    }

    public function destroyPolicy(ApprovalPolicy $policy)
    {
        if ($policy->approvals()->where('status', 'pending')->exists()) {
            return back()->with('error', 'Tidak bisa hapus policy yang masih punya approval pending. Nonaktifkan dulu.');
        }
        $policy->delete();
        return back()->with('success', 'Policy berhasil dihapus.');
    }
}
