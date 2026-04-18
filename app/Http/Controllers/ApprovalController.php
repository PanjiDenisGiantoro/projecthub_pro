<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\ApprovalPolicy;
use App\Services\ApprovalService;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    public function __construct(private ApprovalService $approvalService) {}

    // Approvals waiting for current user to decide
    public function pendingForMe(Request $request)
    {
        $roles = $request->user()->getRoleNames()->toArray();

        $approvals = Approval::with(['steps', 'policy', 'requester', 'approvable'])
            ->where('status', 'pending')
            ->whereHas('steps', fn($q) =>
                $q->where('status', 'pending')->whereIn('approver_role', $roles)
            )
            ->latest()
            ->paginate(20);

        return response()->json($approvals);
    }

    // My submitted approvals
    public function mine(Request $request)
    {
        $approvals = Approval::with(['steps.approver', 'policy', 'approvable'])
            ->where('requested_by', $request->user()->id)
            ->latest()
            ->paginate(20);

        return response()->json($approvals);
    }

    // All approvals (admin/manager)
    public function index(Request $request)
    {
        $approvals = Approval::with(['steps.approver', 'policy', 'requester', 'approvable'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->module, fn($q) => $q->whereHas('policy', fn($p) => $p->where('module', $request->module)))
            ->latest()
            ->paginate(20);

        return response()->json($approvals);
    }

    public function show(Approval $approval)
    {
        return response()->json($approval->load(['steps.approver', 'policy', 'requester', 'approvable']));
    }

    public function approve(Request $request, Approval $approval)
    {
        $request->validate(['notes' => 'nullable|string|max:500']);

        try {
            $this->approvalService->approve($approval, $request->user(), $request->notes ?? '');

            return response()->json([
                'message'  => 'Approved successfully.',
                'approval' => $approval->fresh()->load(['steps.approver', 'policy']),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }

    public function reject(Request $request, Approval $approval)
    {
        $request->validate(['notes' => 'required|string|max:500']);

        try {
            $this->approvalService->reject($approval, $request->user(), $request->notes);

            return response()->json([
                'message'  => 'Rejected.',
                'approval' => $approval->fresh()->load(['steps.approver', 'policy']),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }

    public function cancel(Request $request, Approval $approval)
    {
        try {
            $this->approvalService->cancel($approval, $request->user());
            return response()->json(['message' => 'Approval request cancelled.']);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }

    // ─── Policy Management (admin only) ──────────────────────────────────────

    public function policies(Request $request)
    {
        $policies = ApprovalPolicy::query()
            ->when($request->module, fn($q) => $q->where('module', $request->module))
            ->when(isset($request->is_active), fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->withCount(['approvals', 'approvals as pending_count' => fn($q) => $q->where('status', 'pending')])
            ->orderBy('module')
            ->orderBy('action')
            ->get();

        return response()->json($policies);
    }

    public function showPolicy(ApprovalPolicy $policy)
    {
        $policy->loadCount([
            'approvals',
            'approvals as pending_count'  => fn($q) => $q->where('status', 'pending'),
            'approvals as approved_count' => fn($q) => $q->where('status', 'approved'),
            'approvals as rejected_count' => fn($q) => $q->where('status', 'rejected'),
        ]);

        $recentApprovals = $policy->approvals()
            ->with(['requester:id,name', 'approvable'])
            ->latest()
            ->limit(10)
            ->get();

        return response()->json([...$policy->toArray(), 'recent_approvals' => $recentApprovals]);
    }

    public function storePolicy(Request $request)
    {
        $request->validate([
            'module'          => 'required|string|max:50',
            'action'          => 'required|string|max:50',
            'flow_type'       => 'required|in:sequential,parallel_all,any_of,single',
            'approver_roles'  => 'required|array|min:1',
            'approver_roles.*'=> 'string|in:admin,manager,developer,marketing,customer',
            'timeout_hours'   => 'required|integer|min:1|max:720',
            'is_active'       => 'boolean',
            'description'     => 'nullable|string|max:500',
        ]);

        $exists = ApprovalPolicy::where('module', $request->module)
            ->where('action', $request->action)
            ->exists();

        if ($exists) {
            return response()->json(['message' => "Policy for '{$request->module}.{$request->action}' already exists."], 422);
        }

        $policy = ApprovalPolicy::create($request->only(
            'module', 'action', 'flow_type', 'approver_roles',
            'timeout_hours', 'is_active', 'description'
        ));

        return response()->json($policy, 201);
    }

    public function updatePolicy(Request $request, ApprovalPolicy $policy)
    {
        $request->validate([
            'flow_type'       => 'sometimes|in:sequential,parallel_all,any_of,single',
            'approver_roles'  => 'sometimes|array|min:1',
            'approver_roles.*'=> 'string|in:admin,manager,developer,marketing,customer',
            'timeout_hours'   => 'sometimes|integer|min:1|max:720',
            'is_active'       => 'sometimes|boolean',
            'description'     => 'sometimes|nullable|string|max:500',
        ]);

        $policy->update($request->only('flow_type', 'approver_roles', 'timeout_hours', 'is_active', 'description'));

        return response()->json($policy);
    }

    public function togglePolicy(ApprovalPolicy $policy)
    {
        $policy->update(['is_active' => !$policy->is_active]);

        $state = $policy->is_active ? 'activated' : 'deactivated';
        return response()->json(['message' => "Policy '{$policy->module}.{$policy->action}' {$state}.", 'policy' => $policy]);
    }

    public function destroyPolicy(ApprovalPolicy $policy)
    {
        $hasPending = $policy->approvals()->where('status', 'pending')->exists();

        if ($hasPending) {
            return response()->json(['message' => 'Cannot delete a policy that has pending approvals. Deactivate it instead.'], 422);
        }

        $policy->delete();

        return response()->json(['message' => 'Policy deleted.']);
    }
}
