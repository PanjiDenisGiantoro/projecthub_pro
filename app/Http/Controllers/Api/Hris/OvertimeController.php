<?php

namespace App\Http\Controllers\Api\Hris;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Models\Overtime;
use App\Models\Pph21Setting;
use App\Services\NotificationService;
use App\Services\OvertimeService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Lembur untuk aplikasi mobile. Aturan sama dengan Web\Hris\OvertimeController. */
class OvertimeController extends Controller
{
    public function __construct(private OvertimeService $overtimeService, private NotificationService $notifier) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $overtimes = Overtime::with(['user:id,name', 'approver:id,name'])
            ->where('company_id', $user->company_id)
            ->when(!$user->can('view overtime'), fn ($q) => $q->where('user_id', $user->id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderByRaw("status = 'pending' desc")
            ->orderByDesc('date')
            ->limit(200)
            ->get();

        return response()->json($overtimes->map(fn ($o) => $this->json($o))->values());
    }

    /**
     * Perkiraan upah lembur sebelum diajukan (jenis hari dihitung server dari
     * tanggal & hari libur perusahaan). Nominal final dihitung saat disetujui.
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'date'       => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time'   => 'required|date_format:H:i|after:start_time',
        ]);

        $user    = $request->user();
        $date    = Carbon::parse($request->date);
        $hours   = round(Carbon::parse($request->start_time)->diffInMinutes(Carbon::parse($request->end_time)) / 60, 2);
        $dayType = OvertimeService::dayType($date, Holiday::datesForCompany($user->company_id));
        $salary  = $user->salaries()->where('effective_date', '<=', $date)->latest('effective_date')->first();

        return response()->json([
            'day_type'    => $dayType,
            'total_hours' => $hours,
            'has_salary'  => (bool) $salary,
            ...($salary ? $this->overtimeService->hitung((float) $salary->gaji_pokok, $hours, $dayType, $user->company_id) : ['total' => null, 'upah_sejam' => null, 'breakdown' => []]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'date'        => 'required|date',
            'start_time'  => 'required',
            'end_time'    => 'required|after:start_time',
            'description' => 'nullable|string|max:500',
        ]);

        $user  = $request->user();
        $date  = Carbon::parse($request->date);
        $hours = round(Carbon::parse($request->start_time)->diffInMinutes(Carbon::parse($request->end_time)) / 60, 2);

        $duplicate = Overtime::where('user_id', $user->id)
            ->where('date', $request->date)
            ->where('start_time', $request->start_time)
            ->where('end_time', $request->end_time)
            ->where('created_at', '>=', now()->subSeconds(10))
            ->latest()
            ->first();

        if ($duplicate) {
            return response()->json($this->json($duplicate->load(['user', 'approver'])), 201);
        }

        $overtime = Overtime::create([
            'user_id'     => $user->id,
            'company_id'  => $user->company_id,
            'date'        => $request->date,
            'day_type'    => OvertimeService::dayType($date, Holiday::datesForCompany($user->company_id)),
            'start_time'  => $request->start_time,
            'end_time'    => $request->end_time,
            'total_hours' => $hours,
            'description' => $request->description,
        ]);

        if (!Pph21Setting::forCompany($user->company_id)->overtime_needs_approval) {
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
                // Gagal auto-approve (mis. belum ada data gaji) — tetap pending.
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

        return response()->json($this->json($overtime->fresh(['user', 'approver'])), 201);
    }

    public function update(Request $request, Overtime $overtime): JsonResponse
    {
        $this->authorize('update overtime');
        abort_if($overtime->company_id !== $request->user()->company_id, 403);
        abort_if($overtime->status !== 'pending', 422, 'Hanya pengajuan berstatus Pending yang bisa diedit.');

        $request->validate([
            'date'        => 'required|date',
            'start_time'  => 'required',
            'end_time'    => 'required|after:start_time',
            'description' => 'nullable|string|max:500',
        ]);

        $date = Carbon::parse($request->date);
        $overtime->update([
            'date'        => $request->date,
            'day_type'    => OvertimeService::dayType($date, Holiday::datesForCompany($overtime->company_id)),
            'start_time'  => $request->start_time,
            'end_time'    => $request->end_time,
            'total_hours' => round(Carbon::parse($request->start_time)->diffInMinutes(Carbon::parse($request->end_time)) / 60, 2),
            'description' => $request->description,
        ]);

        return response()->json($this->json($overtime->fresh(['user', 'approver'])));
    }

    public function destroy(Request $request, Overtime $overtime): JsonResponse
    {
        $user           = $request->user();
        $isOwnerPending = $overtime->user_id === $user->id && $overtime->status === 'pending';
        $canForceDelete = $user->can('delete overtime') && $overtime->company_id === $user->company_id;

        abort_unless($isOwnerPending || $canForceDelete, 403);

        if ($isOwnerPending && !$canForceDelete) {
            $overtime->update(['status' => 'cancelled']);
            return response()->json(['message' => 'Pengajuan lembur dibatalkan.', 'overtime' => $this->json($overtime->fresh(['user', 'approver']))]);
        }

        $overtime->delete();
        return response()->json(['message' => 'Data lembur dihapus.', 'overtime' => null]);
    }

    public function approve(Request $request, Overtime $overtime): JsonResponse
    {
        $this->authorize('approve overtime');
        abort_if($overtime->company_id !== $request->user()->company_id, 403);
        abort_if($overtime->status !== 'pending', 422, 'Status tidak valid.');

        try {
            $this->overtimeService->approve($overtime, $request->user());
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $this->notifier->send(
            $overtime->user_id,
            'overtime_approved',
            'Lembur Disetujui',
            "Pengajuan lembur Anda ({$overtime->total_hours} jam, {$overtime->date->format('d M Y')}) disetujui oleh {$request->user()->name}.",
            ['overtime_id' => $overtime->id]
        );

        return response()->json($this->json($overtime->fresh(['user', 'approver'])));
    }

    public function reject(Request $request, Overtime $overtime): JsonResponse
    {
        $this->authorize('approve overtime');
        abort_if($overtime->company_id !== $request->user()->company_id, 403);
        abort_if($overtime->status !== 'pending', 422, 'Status tidak valid.');
        $request->validate(['rejection_reason' => 'required|string|max:500']);

        $this->overtimeService->reject($overtime, $request->user(), $request->rejection_reason);

        $this->notifier->send(
            $overtime->user_id,
            'overtime_rejected',
            'Lembur Ditolak',
            "Pengajuan lembur Anda ({$overtime->total_hours} jam, {$overtime->date->format('d M Y')}) ditolak oleh {$request->user()->name}. Alasan: {$request->rejection_reason}",
            ['overtime_id' => $overtime->id]
        );

        return response()->json($this->json($overtime->fresh(['user', 'approver'])));
    }

    private function json(Overtime $o): array
    {
        return [
            'id'               => $o->id,
            'user_id'          => $o->user_id,
            'user_name'        => $o->user?->name,
            'date'             => $o->date?->toDateString(),
            'day_type'         => $o->day_type,
            'start_time'       => substr((string) $o->start_time, 0, 5),
            'end_time'         => substr((string) $o->end_time, 0, 5),
            'total_hours'      => (float) $o->total_hours,
            'upah_sejam'       => $o->upah_sejam !== null ? (float) $o->upah_sejam : null,
            'total_amount'     => $o->total_amount !== null ? (float) $o->total_amount : null,
            'breakdown'        => $o->breakdown ?? [],
            'description'      => $o->description,
            'status'           => $o->status,
            'approved_by'      => $o->approved_by,
            'approved_by_name' => $o->approver?->name,
            'approved_at'      => $o->approved_at?->toIso8601String(),
            'rejection_reason' => $o->rejection_reason,
        ];
    }
}
