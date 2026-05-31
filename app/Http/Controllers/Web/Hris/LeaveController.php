<?php

namespace App\Http\Controllers\Web\Hris;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\LeaveService;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function __construct(private LeaveService $leaveService) {}

    public function index(Request $request)
    {
        $user = auth()->user();

        $requests = LeaveRequest::with(['user', 'leaveType'])
            ->where('company_id', $user->company_id)
            ->when(!$user->can('manage leave'), fn($q) => $q->where('user_id', $user->id))
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('hris.leave.index', compact('requests'));
    }

    public function create()
    {
        $user       = auth()->user();
        $leaveTypes = LeaveType::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->filter(fn($t) => $t->isEligible($user));

        return view('hris.leave.create', compact('leaveTypes'));
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

    public function destroy(LeaveRequest $leave)
    {
        abort_if($leave->user_id !== auth()->id() || $leave->status !== 'pending', 403);
        $leave->update(['status' => 'cancelled']);
        return back()->with('success', 'Pengajuan dibatalkan.');
    }

    public function approve(LeaveRequest $leave)
    {
        $this->authorize('manage leave');
        abort_if($leave->status !== 'pending', 422, 'Status tidak valid.');
        $this->leaveService->approve($leave, auth()->user());
        return back()->with('success', 'Cuti disetujui.');
    }

    public function reject(Request $request, LeaveRequest $leave)
    {
        $this->authorize('manage leave');
        $request->validate(['rejection_reason' => 'required|string|max:500']);
        $this->leaveService->reject($leave, auth()->user(), $request->rejection_reason);
        return back()->with('success', 'Cuti ditolak.');
    }
}
