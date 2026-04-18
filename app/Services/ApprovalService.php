<?php

namespace App\Services;

use App\Models\Approval;
use App\Models\ApprovalPolicy;
use App\Models\ApprovalStep;
use App\Models\TicketHistory;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Model;

class ApprovalService
{
    private array $handlers = [];

    // ─── Handler Registration ─────────────────────────────────────────────────

    public function registerHandler(string $module, string $action, Closure $callback): void
    {
        $this->handlers["{$module}.{$action}"] = $callback;
    }

    // ─── Policy Checks ────────────────────────────────────────────────────────

    public function needsApproval(string $module, string $action): ?ApprovalPolicy
    {
        return ApprovalPolicy::where('module', $module)
            ->where('action', $action)
            ->where('is_active', true)
            ->first();
    }

    public function hasPendingApproval(Model $approvable, string $action): bool
    {
        return Approval::where('approvable_type', get_class($approvable))
            ->where('approvable_id', $approvable->id)
            ->where('action', $action)
            ->where('status', 'pending')
            ->exists();
    }

    public function getPendingApproval(Model $approvable, string $action): ?Approval
    {
        return Approval::where('approvable_type', get_class($approvable))
            ->where('approvable_id', $approvable->id)
            ->where('action', $action)
            ->where('status', 'pending')
            ->with(['steps', 'policy', 'requester'])
            ->first();
    }

    // ─── Create Approval ──────────────────────────────────────────────────────

    public function createApproval(Model $approvable, ApprovalPolicy $policy, User $requester, array $metadata = []): Approval
    {
        $approval = Approval::create([
            'approvable_type' => get_class($approvable),
            'approvable_id'   => $approvable->id,
            'policy_id'       => $policy->id,
            'action'          => $policy->action,
            'status'          => 'pending',
            'requested_by'    => $requester->id,
            'metadata'        => $metadata,
            'expires_at'      => now()->addHours($policy->timeout_hours),
        ]);

        $this->buildSteps($approval, $policy);
        $this->notifyApprovers($approval, $policy);

        return $approval->load(['steps', 'policy', 'requester']);
    }

    private function buildSteps(Approval $approval, ApprovalPolicy $policy): void
    {
        foreach ($policy->approver_roles as $index => $role) {
            ApprovalStep::create([
                'approval_id'   => $approval->id,
                'step_order'    => $index + 1,
                'approver_role' => $role,
                'status'        => 'pending',
            ]);
        }
    }

    private function notifyApprovers(Approval $approval, ApprovalPolicy $policy): void
    {
        // Sequential: only notify first step; others: notify all
        $stepsToNotify = $policy->flow_type === 'sequential'
            ? $approval->steps()->where('step_order', 1)->get()
            : $approval->steps;

        foreach ($stepsToNotify as $step) {
            User::role($step->approver_role)->where('is_active', true)
                ->each(fn(User $user) => app(NotificationService::class)->send(
                    $user->id,
                    'approval_requested',
                    'Approval Required',
                    "Action \"{$approval->action}\" requires your approval.",
                    ['approval_id' => $approval->id, 'module' => $approval->policy->module]
                ));
        }
    }

    // ─── Authorization ────────────────────────────────────────────────────────

    public function canApprove(Approval $approval, User $user): bool
    {
        if (!$approval->isPending()) return false;

        return match ($approval->policy->flow_type) {
            'sequential'   => $this->canApproveCurrentStep($approval, $user),
            'parallel_all' => $this->hasMatchingPendingStep($approval, $user),
            'any_of',
            'single'       => $this->hasMatchingPendingStep($approval, $user),
        };
    }

    private function canApproveCurrentStep(Approval $approval, User $user): bool
    {
        $step = $approval->steps()->where('status', 'pending')->orderBy('step_order')->first();
        return $step && $user->hasRole($step->approver_role);
    }

    private function hasMatchingPendingStep(Approval $approval, User $user): bool
    {
        $roles = $user->getRoleNames()->toArray();
        return $approval->steps()->where('status', 'pending')->whereIn('approver_role', $roles)->exists();
    }

    // ─── Approve ──────────────────────────────────────────────────────────────

    public function approve(Approval $approval, User $actor, string $notes = ''): void
    {
        if (!$this->canApprove($approval, $actor)) {
            throw new \RuntimeException('You are not authorized to approve this request.');
        }

        match ($approval->policy->flow_type) {
            'sequential'   => $this->processSequentialApprove($approval, $actor, $notes),
            'parallel_all' => $this->processParallelApprove($approval, $actor, $notes),
            'any_of',
            'single'       => $this->processAnyOfApprove($approval, $actor, $notes),
        };
    }

    private function processSequentialApprove(Approval $approval, User $actor, string $notes): void
    {
        $step = $approval->steps()->where('status', 'pending')->orderBy('step_order')->first();

        $this->markStep($step, $actor, 'approved', $notes);

        $nextStep = $approval->steps()
            ->where('step_order', '>', $step->step_order)
            ->where('status', 'pending')
            ->orderBy('step_order')
            ->first();

        if ($nextStep) {
            // Notify next step's approvers
            User::role($nextStep->approver_role)->where('is_active', true)
                ->each(fn(User $u) => app(NotificationService::class)->send(
                    $u->id,
                    'approval_requested',
                    'Approval Required — Next Step',
                    "Action \"{$approval->action}\" is now at step {$nextStep->step_order} and needs your approval.",
                    ['approval_id' => $approval->id]
                ));
        } else {
            $this->finalizeApproval($approval, 'approved');
        }
    }

    private function processParallelApprove(Approval $approval, User $actor, string $notes): void
    {
        $roles = $actor->getRoleNames()->toArray();
        $step = $approval->steps()->where('status', 'pending')->whereIn('approver_role', $roles)->first();

        if ($step) {
            $this->markStep($step, $actor, 'approved', $notes);
        }

        // All steps approved?
        if (!$approval->steps()->where('status', 'pending')->exists()) {
            $this->finalizeApproval($approval, 'approved');
        }
    }

    private function processAnyOfApprove(Approval $approval, User $actor, string $notes): void
    {
        $roles = $actor->getRoleNames()->toArray();
        $step = $approval->steps()->where('status', 'pending')->whereIn('approver_role', $roles)->first();

        if ($step) {
            $this->markStep($step, $actor, 'approved', $notes);
        }

        // Skip remaining steps
        $approval->steps()->where('status', 'pending')->update(['status' => 'skipped']);

        $this->finalizeApproval($approval, 'approved');
    }

    // ─── Reject ───────────────────────────────────────────────────────────────

    public function reject(Approval $approval, User $actor, string $notes): void
    {
        if (!$this->canApprove($approval, $actor)) {
            throw new \RuntimeException('You are not authorized to reject this request.');
        }

        $roles = $actor->getRoleNames()->toArray();
        $step = $approval->steps()->where('status', 'pending')->whereIn('approver_role', $roles)->first();

        if ($step) {
            $this->markStep($step, $actor, 'rejected', $notes);
        }

        $approval->steps()->where('status', 'pending')->update(['status' => 'skipped']);

        $this->finalizeApproval($approval, 'rejected');
    }

    // ─── Cancel ───────────────────────────────────────────────────────────────

    public function cancel(Approval $approval, User $actor): void
    {
        if ($approval->requested_by !== $actor->id && !$actor->hasRole('admin')) {
            throw new \RuntimeException('Only the requester or admin can cancel this approval.');
        }

        if (!$approval->isPending()) {
            throw new \RuntimeException('Only pending approvals can be cancelled.');
        }

        $approval->steps()->where('status', 'pending')->update(['status' => 'skipped']);
        $approval->update(['status' => 'cancelled', 'decided_at' => now()]);
    }

    // ─── Expire ───────────────────────────────────────────────────────────────

    public function processExpired(): int
    {
        $count = 0;

        Approval::where('status', 'pending')
            ->where('expires_at', '<', now())
            ->each(function (Approval $approval) use (&$count) {
                $approval->steps()->where('status', 'pending')->update(['status' => 'skipped']);
                $approval->update(['status' => 'expired', 'decided_at' => now()]);

                app(NotificationService::class)->send(
                    $approval->requested_by,
                    'approval_expired',
                    'Approval Request Expired',
                    "Your \"{$approval->action}\" request expired without a decision.",
                    ['approval_id' => $approval->id]
                );

                app(NotificationService::class)->notifyManagers(
                    'approval_expired',
                    'Approval Expired Without Decision',
                    "Approval \"{$approval->action}\" (ID #{$approval->id}) expired.",
                    ['approval_id' => $approval->id]
                );

                $count++;
            });

        return $count;
    }

    // ─── Finalize ─────────────────────────────────────────────────────────────

    private function finalizeApproval(Approval $approval, string $status): void
    {
        $approval->update(['status' => $status, 'decided_at' => now()]);

        if ($status === 'approved') {
            $this->executeApproval($approval);
        }

        $isApproved = $status === 'approved';

        app(NotificationService::class)->send(
            $approval->requested_by,
            "approval_{$status}",
            $isApproved ? 'Your Request Was Approved' : 'Your Request Was Rejected',
            $isApproved
                ? "Your \"{$approval->action}\" request has been approved and executed."
                : "Your \"{$approval->action}\" request has been rejected.",
            ['approval_id' => $approval->id]
        );
    }

    private function executeApproval(Approval $approval): void
    {
        $key = "{$approval->policy->module}.{$approval->action}";

        if (isset($this->handlers[$key])) {
            ($this->handlers[$key])($approval);
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function markStep(ApprovalStep $step, User $actor, string $status, string $notes): void
    {
        $step->update([
            'status'      => $status,
            'approver_id' => $actor->id,
            'decided_at'  => now(),
            'notes'       => $notes,
        ]);
    }
}
