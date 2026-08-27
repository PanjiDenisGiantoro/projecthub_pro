<?php

namespace App\Http\Controllers\Web\Hris;

use App\Http\Controllers\Concerns\HasPerPage;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\LeaveService;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    use HasPerPage;

    public function __construct(private LeaveService $leaveService) {}

    public function index(Request $request)
    {
        $user = auth()->user();

        $requests = LeaveRequest::with(['user', 'leaveType'])
            ->where('company_id', $user->company_id)
            ->when(!$user->can('view leave'), fn($q) => $q->where('user_id', $user->id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderByRaw("status = 'pending' desc")
            ->orderByDesc('created_at')
            ->paginate($this->perPage($request))
            ->withQueryString();

        $leaveTypes = $user->can('update leave')
            ? LeaveType::where('company_id', $user->company_id)->where('is_active', true)->orderBy('sort_order')->get()
            : collect();

        return view('hris.leave.index', compact('requests', 'leaveTypes'));
    }

    public function create()
    {
        $user     = auth()->user();
        $allTypes = LeaveType::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $leaveTypes    = $allTypes->filter(fn($t) => $t->isEligible($user));
        $tenureBlocked = $allTypes->map(fn($t) => $t->tenureBlockedMessage($user))->filter()->values();

        return view('hris.leave.create', compact('leaveTypes', 'tenureBlocked'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after_or_equal:start_date',
            'reason'        => 'required|string|max:1000',
            'attachment'    => 'nullable|file|max:2048',
        ]);

        $user      = auth()->user();
        $leaveType = LeaveType::findOrFail($request->leave_type_id);
        $data      = $request->only(['start_date', 'end_date', 'reason']);

        if ($request->hasFile('attachment')) {
            $data['attachment'] = $request->file('attachment')->store('leave-attachments', 'public');
        }

        try {
            $this->leaveService->submit($user, $leaveType, $data);
            return redirect()->route('hris.leave.index')->with('success', 'Pengajuan cuti berhasil dikirim.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function update(Request $request, LeaveRequest $leave)
    {
        $this->authorize('update leave');
        abort_if($leave->company_id !== auth()->user()->company_id, 403);
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
            'total_days'    => LeaveService::hitungHariKerja(
                \Carbon\Carbon::parse($request->start_date),
                \Carbon\Carbon::parse($request->end_date)
            ),
            'reason' => $request->reason,
        ]);

        return back()->with('success', 'Pengajuan cuti berhasil diperbarui.');
    }

    public function destroy(LeaveRequest $leave)
    {
        $user           = auth()->user();
        $isOwnerPending = $leave->user_id === $user->id && $leave->status === 'pending';
        $canForceDelete = $user->can('delete leave') && $leave->company_id === $user->company_id;

        abort_unless($isOwnerPending || $canForceDelete, 403);

        // Batalkan punya sendiri (masih pending) = soft cancel, bukan hard delete.
        if ($isOwnerPending && !$canForceDelete) {
            $leave->update(['status' => 'cancelled']);
            return back()->with('success', 'Pengajuan dibatalkan.');
        }

        // Hapus oleh admin/HR: balikkan efek samping kalau datanya sudah approved.
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

        return back()->with('success', 'Data cuti dihapus.');
    }

    public function approve(LeaveRequest $leave)
    {
        $this->authorize('approve leave');
        abort_if($leave->company_id !== auth()->user()->company_id, 403);
        abort_if($leave->status !== 'pending', 422, 'Status tidak valid.');
        $this->leaveService->approve($leave, auth()->user());
        return back()->with('success', 'Cuti disetujui.');
    }

    public function reject(Request $request, LeaveRequest $leave)
    {
        $this->authorize('approve leave');
        abort_if($leave->company_id !== auth()->user()->company_id, 403);
        $request->validate(['rejection_reason' => 'required|string|max:500']);
        $this->leaveService->reject($leave, auth()->user(), $request->rejection_reason);
        return back()->with('success', 'Cuti ditolak.');
    }
}
