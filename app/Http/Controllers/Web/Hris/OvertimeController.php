<?php

namespace App\Http\Controllers\Web\Hris;

use App\Http\Controllers\Concerns\HasPerPage;
use App\Http\Controllers\Controller;
use App\Models\Overtime;
use App\Models\Pph21Setting;
use App\Services\NotificationService;
use App\Services\OvertimeService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OvertimeController extends Controller
{
    use HasPerPage;

    public function __construct(private OvertimeService $overtimeService, private NotificationService $notifier) {}

    public function index(Request $request)
    {
        $user = auth()->user();

        $overtimes = Overtime::with('user')
            ->where('company_id', $user->company_id)
            ->when(!$user->can('view overtime'), fn($q) => $q->where('user_id', $user->id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderByRaw("status = 'pending' desc")
            ->orderByDesc('date')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return view('hris.overtime.index', compact('overtimes'));
    }

    public function create()
    {
        return view('hris.overtime.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'date'        => 'required|date',
            'start_time'  => 'required',
            'end_time'    => 'required|after:start_time',
            'description' => 'nullable|string|max:500',
        ]);

        $user  = auth()->user();
        $date  = Carbon::parse($request->date);
        $start = Carbon::parse($request->start_time);
        $end   = Carbon::parse($request->end_time);
        $hours = round($start->diffInMinutes($end) / 60, 2);

        // Cegah double-submit (klik ganda / submit ulang).
        $duplicate = Overtime::where('user_id', $user->id)
            ->where('date', $request->date)
            ->where('start_time', $request->start_time)
            ->where('end_time', $request->end_time)
            ->where('created_at', '>=', now()->subSeconds(10))
            ->latest()
            ->first();

        if ($duplicate) {
            return redirect()->route('hris.overtime.index')->with('success', 'Pengajuan lembur berhasil dikirim.');
        }

        $overtime = Overtime::create([
            'user_id'     => $user->id,
            'company_id'  => $user->company_id,
            'date'        => $request->date,
            'day_type'    => OvertimeService::dayType($date),
            'start_time'  => $request->start_time,
            'end_time'    => $request->end_time,
            'total_hours' => $hours,
            'description' => $request->description,
        ]);

        $needsApproval = Pph21Setting::forCompany($user->company_id)->overtime_needs_approval;

        if (!$needsApproval) {
            try {
                $this->overtimeService->approve($overtime);
                $this->notifier->send(
                    $user->id,
                    'overtime_approved',
                    'Lembur Disetujui',
                    "Pengajuan lembur Anda ({$hours} jam, {$date->format('d M Y')}) disetujui otomatis.",
                    ['overtime_id' => $overtime->id]
                );
            } catch (\Exception $e) {
                // Gagal auto-approve (mis. belum ada data gaji) — biarkan pending, approver manual yang tangani.
            }
        } else {
            $this->notifier->notifyByPermission(
                'approve overtime',
                'overtime_submitted',
                'Pengajuan Lembur Baru',
                "{$user->name} mengajukan lembur {$hours} jam pada {$date->format('d M Y')}.",
                ['overtime_id' => $overtime->id],
                companyId: $user->company_id,
                excludeUserId: $user->id
            );
        }

        return redirect()->route('hris.overtime.index')->with('success', 'Pengajuan lembur berhasil dikirim.');
    }

    public function update(Request $request, Overtime $overtime)
    {
        $this->authorize('update overtime');
        abort_if($overtime->company_id !== auth()->user()->company_id, 403);
        abort_if($overtime->status !== 'pending', 422, 'Hanya pengajuan berstatus Pending yang bisa diedit.');

        $request->validate([
            'date'        => 'required|date',
            'start_time'  => 'required',
            'end_time'    => 'required|after:start_time',
            'description' => 'nullable|string|max:500',
        ]);

        $date  = Carbon::parse($request->date);
        $start = Carbon::parse($request->start_time);
        $end   = Carbon::parse($request->end_time);

        $overtime->update([
            'date'        => $request->date,
            'day_type'    => OvertimeService::dayType($date),
            'start_time'  => $request->start_time,
            'end_time'    => $request->end_time,
            'total_hours' => round($start->diffInMinutes($end) / 60, 2),
            'description' => $request->description,
        ]);

        return back()->with('success', 'Pengajuan lembur berhasil diperbarui.');
    }

    public function destroy(Overtime $overtime)
    {
        $user           = auth()->user();
        $isOwnerPending = $overtime->user_id === $user->id && $overtime->status === 'pending';
        $canForceDelete = $user->can('delete overtime') && $overtime->company_id === $user->company_id;

        abort_unless($isOwnerPending || $canForceDelete, 403);

        // Batalkan punya sendiri (masih pending) = soft cancel, bukan hard delete.
        if ($isOwnerPending && !$canForceDelete) {
            $overtime->update(['status' => 'cancelled']);
            return back()->with('success', 'Pengajuan lembur dibatalkan.');
        }

        $overtime->delete();
        return back()->with('success', 'Data lembur dihapus.');
    }

    public function reject(Request $request, Overtime $overtime)
    {
        $this->authorize('approve overtime');
        abort_if($overtime->company_id !== auth()->user()->company_id, 403);
        abort_if($overtime->status !== 'pending', 422, 'Status tidak valid.');
        $request->validate(['rejection_reason' => 'required|string|max:500']);

        $this->overtimeService->reject($overtime, auth()->user(), $request->rejection_reason);

        $this->notifier->send(
            $overtime->user_id,
            'overtime_rejected',
            'Lembur Ditolak',
            "Pengajuan lembur Anda ({$overtime->total_hours} jam, {$overtime->date->format('d M Y')}) ditolak oleh " . auth()->user()->name . ". Alasan: {$request->rejection_reason}",
            ['overtime_id' => $overtime->id]
        );

        return back()->with('success', 'Lembur ditolak.');
    }

    public function approve(Overtime $overtime)
    {
        $this->authorize('approve overtime');
        abort_if($overtime->company_id !== auth()->user()->company_id, 403);
        abort_if($overtime->status !== 'pending', 422, 'Status tidak valid.');

        try {
            $this->overtimeService->approve($overtime, auth()->user());

            $this->notifier->send(
                $overtime->user_id,
                'overtime_approved',
                'Lembur Disetujui',
                "Pengajuan lembur Anda ({$overtime->total_hours} jam, {$overtime->date->format('d M Y')}) disetujui oleh " . auth()->user()->name . ".",
                ['overtime_id' => $overtime->id]
            );

            return back()->with('success', 'Lembur disetujui.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
