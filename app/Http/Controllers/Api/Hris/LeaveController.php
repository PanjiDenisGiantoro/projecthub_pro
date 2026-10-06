<?php

namespace App\Http\Controllers\Api\Hris;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\LeaveService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Cuti & izin untuk aplikasi mobile. Aturan sama dengan Web\Hris\LeaveController. */
class LeaveController extends Controller
{
    public function __construct(private LeaveService $leaveService) {}

    /** Jenis cuti yang bisa diajukan user + saldo tahun berjalan. */
    public function types(Request $request): JsonResponse
    {
        $user = $request->user();
        $year = now()->year;

        $types = LeaveType::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $balances = LeaveBalance::where('user_id', $user->id)->where('year', $year)->get()->keyBy('leave_type_id');

        return response()->json($types->map(fn (LeaveType $t) => [
            'id'               => $t->id,
            'name'             => $t->name,
            'code'             => $t->code,
            'default_quota'    => (int) $t->default_quota,
            'is_paid'          => $t->is_paid,
            'needs_attachment' => $t->needs_attachment,
            'has_balance'      => $t->has_balance,
            'eligible'         => $t->isEligible($user),
            'blocked_message'  => $t->tenureBlockedMessage($user),
            'balance'          => isset($balances[$t->id]) ? [
                'year'         => $year,
                'quota'        => (int) $balances[$t->id]->quota,
                'used'         => (int) $balances[$t->id]->used,
                'carried_over' => (int) $balances[$t->id]->carried_over,
            ] : null,
        ])->values());
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $requests = LeaveRequest::with(['user:id,name', 'leaveType:id,name', 'approver:id,name'])
            ->where('company_id', $user->company_id)
            ->when(!$user->can('view leave'), fn ($q) => $q->where('user_id', $user->id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderByRaw("status = 'pending' desc")
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();

        return response()->json($requests->map(fn ($l) => $this->json($l))->values());
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after_or_equal:start_date',
            'reason'        => 'required|string|max:1000',
            'attachment'    => 'nullable|file|max:2048',
        ]);

        $user      = $request->user();
        $leaveType = LeaveType::findOrFail($request->leave_type_id);
        abort_if($leaveType->company_id !== $user->company_id, 403);
        $data = $request->only(['start_date', 'end_date', 'reason']);

        if ($request->hasFile('attachment')) {
            $data['attachment'] = $request->file('attachment')->store('leave-attachments', 'public');
        }

        try {
            $leave = $this->leaveService->submit($user, $leaveType, $data);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($this->json($leave->fresh(['user', 'leaveType', 'approver'])), 201);
    }

    public function update(Request $request, LeaveRequest $leave): JsonResponse
    {
        $this->authorize('update leave');
        abort_if($leave->company_id !== $request->user()->company_id, 403);
        abort_if($leave->status !== 'pending', 422, 'Hanya pengajuan berstatus Pending yang bisa diedit.');

        $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after_or_equal:start_date',
            'reason'        => 'required|string|max:1000',
        ]);

        $leaveType = LeaveType::findOrFail($request->leave_type_id);
        abort_if($leaveType->company_id !== $leave->company_id, 403);

        $leave->update([
            'leave_type_id' => $leaveType->id,
            'start_date'    => $request->start_date,
            'end_date'      => $request->end_date,
            'total_days'    => LeaveService::hitungHariKerja(Carbon::parse($request->start_date), Carbon::parse($request->end_date)),
            'reason'        => $request->reason,
        ]);

        return response()->json($this->json($leave->fresh(['user', 'leaveType', 'approver'])));
    }

    public function destroy(Request $request, LeaveRequest $leave): JsonResponse
    {
        $user           = $request->user();
        $isOwnerPending = $leave->user_id === $user->id && $leave->status === 'pending';
        $canForceDelete = $user->can('delete leave') && $leave->company_id === $user->company_id;

        abort_unless($isOwnerPending || $canForceDelete, 403);

        if ($isOwnerPending && !$canForceDelete) {
            $leave->update(['status' => 'cancelled']);
            return response()->json(['message' => 'Pengajuan dibatalkan.', 'leave' => $this->json($leave->fresh(['user', 'leaveType', 'approver']))]);
        }

        if ($leave->status === 'approved') {
            if ($leave->leaveType->has_balance) {
                LeaveBalance::where([
                    'user_id'       => $leave->user_id,
                    'leave_type_id' => $leave->leave_type_id,
                    'year'          => $leave->start_date->year,
                ])->decrement('used', $leave->total_days);
            }

            Attendance::where('user_id', $leave->user_id)
                ->whereBetween('date', [$leave->start_date->toDateString(), $leave->end_date->toDateString()])
                ->where('notes', "Auto: {$leave->leaveType->name}")
                ->delete();
        }

        $leave->delete();

        return response()->json(['message' => 'Data cuti dihapus.', 'leave' => null]);
    }

    public function approve(Request $request, LeaveRequest $leave): JsonResponse
    {
        $this->authorize('approve leave');
        abort_if($leave->company_id !== $request->user()->company_id, 403);
        abort_if($leave->status !== 'pending', 422, 'Status tidak valid.');
        $this->leaveService->approve($leave, $request->user());

        return response()->json($this->json($leave->fresh(['user', 'leaveType', 'approver'])));
    }

    public function reject(Request $request, LeaveRequest $leave): JsonResponse
    {
        $this->authorize('approve leave');
        abort_if($leave->company_id !== $request->user()->company_id, 403);
        $request->validate(['rejection_reason' => 'required|string|max:500']);
        $this->leaveService->reject($leave, $request->user(), $request->rejection_reason);

        return response()->json($this->json($leave->fresh(['user', 'leaveType', 'approver'])));
    }

    private function json(LeaveRequest $l): array
    {
        return [
            'id'               => $l->id,
            'user_id'          => $l->user_id,
            'user_name'        => $l->user?->name,
            'leave_type_id'    => $l->leave_type_id,
            'leave_type'       => $l->leaveType?->name,
            'start_date'       => $l->start_date?->toDateString(),
            'end_date'         => $l->end_date?->toDateString(),
            'total_days'       => (int) $l->total_days,
            'reason'           => $l->reason,
            'attachment_url'   => $l->attachment ? asset('storage/' . $l->attachment) : null,
            'status'           => $l->status,
            'approved_by'      => $l->approved_by,
            'approved_by_name' => $l->approver?->name,
            'approved_at'      => $l->approved_at?->toIso8601String(),
            'rejection_reason' => $l->rejection_reason,
            'created_at'       => $l->created_at?->toIso8601String(),
        ];
    }
}
