<?php

namespace App\Http\Controllers\Api\Hris;

use App\Http\Controllers\Concerns\HandlesAttendance;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\Holiday;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Absensi untuk aplikasi mobile. Aturannya sama dengan halaman web
 * (AbsensiController) lewat trait HandlesAttendance.
 */
class AttendanceController extends Controller
{
    use HandlesAttendance;

    /** Status absensi hari ini: shift, sesi yang sedang berjalan, dan aturan lokasi/wajah. */
    public function today(Request $request): JsonResponse
    {
        return response()->json($this->todayPayload($request->user()));
    }

    public function checkIn(Request $request): JsonResponse
    {
        return $this->respond($request->user(), $this->attemptCheckIn($request->user(), $request));
    }

    public function checkOut(Request $request): JsonResponse
    {
        return $this->respond($request->user(), $this->attemptCheckOut($request->user(), $request));
    }

    /** Riwayat absensi milik user sendiri per bulan. */
    public function history(Request $request): JsonResponse
    {
        $request->validate([
            'year'  => 'nullable|integer|min:2000|max:2100',
            'month' => 'nullable|integer|min:1|max:12',
        ]);

        $rows = Attendance::where('user_id', $request->user()->id)
            ->whereYear('date', $request->integer('year', now()->year))
            ->whereMonth('date', $request->integer('month', now()->month))
            ->orderByDesc('date')
            ->get();

        return response()->json($rows->map(fn (Attendance $a) => $this->attendanceJson($a))->values());
    }

    /**
     * Kehadiran seluruh karyawan perusahaan di satu tanggal. Butuh izin
     * "view absensi", sama seperti halaman rekap web.
     */
    public function team(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->can('view absensi'), 403, 'Anda tidak punya akses melihat absensi tim.');

        $request->validate(['date' => 'nullable|date']);
        $date = $request->date('date') ?? today();

        $employees = User::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'client'))
            ->orderBy('name')
            ->get(['id', 'name']);

        $attendances = Attendance::where('company_id', $user->company_id)
            ->whereDate('date', $date)
            ->get()
            ->keyBy('user_id');

        return response()->json([
            'date'      => $date->toDateString(),
            'total'     => $employees->count(),
            'present'   => $attendances->whereNotNull('check_in')->count(),
            'employees' => $employees->map(fn (User $e) => [
                'id'         => $e->id,
                'name'       => $e->name,
                'attendance' => isset($attendances[$e->id]) ? $this->attendanceJson($attendances[$e->id]) : null,
            ])->values(),
        ]);
    }

    /** Hari libur perusahaan per tahun. */
    public function holidays(Request $request): JsonResponse
    {
        $holidays = Holiday::where('company_id', $request->user()->company_id)
            ->whereYear('date', $request->integer('year', now()->year))
            ->orderBy('date')
            ->get(['id', 'date', 'name']);

        return response()->json($holidays->map(fn ($h) => [
            'id'   => $h->id,
            'date' => $h->date instanceof \DateTimeInterface ? $h->date->format('Y-m-d') : substr((string) $h->date, 0, 10),
            'name' => $h->name,
        ])->values());
    }

    private function respond(User $user, array $result): JsonResponse
    {
        [$type, $message, $attendance] = $result + [null, null, null];

        return response()->json([
            'type'       => $type,
            'message'    => $message,
            'attendance' => $attendance ? $this->attendanceJson($attendance->fresh()) : null,
            'today'      => $this->todayPayload($user->fresh()),
        ], $type === 'error' ? 422 : 200);
    }

    private function todayPayload(User $user): array
    {
        $user->loadMissing('shift.workingDays');
        $workDate = $this->workDate($user);
        $setting  = AttendanceSetting::forCompany($user->company_id);

        $attendance = Attendance::where('user_id', $user->id)->where('date', $workDate)->first()
            ?? ($this->openSession($user)['attendance'] ?? null);

        $shift   = $user->effectiveShiftOn($workDate);
        $dayOff  = $user->isDayOffOn($workDate);
        $isSplit = $shift && $shift->isSplit();
        $holiday = Holiday::where('company_id', $user->company_id)->whereDate('date', $workDate)->first();

        return [
            'date'       => $workDate->toDateString(),
            // day_off | checkin_1 | checkout_1 | checkin_2 | checkout_2 | done
            'state'      => $this->attendanceState($attendance, $dayOff, $isSplit),
            'is_day_off' => $dayOff,
            'holiday'    => $holiday?->name,
            'shift'      => $shift ? [
                'id'                => $shift->id,
                'name'              => $shift->name,
                'start_time'        => substr((string) $shift->start_time, 0, 5),
                'end_time'          => substr((string) $shift->end_time, 0, 5),
                'break_start_time'  => $shift->break_start_time ? substr((string) $shift->break_start_time, 0, 5) : null,
                'break_end_time'    => $shift->break_end_time ? substr((string) $shift->break_end_time, 0, 5) : null,
                'tolerance_minutes' => (int) $shift->tolerance_minutes,
                'is_split'          => $isSplit,
                'label'             => $shift->timeRangeLabel(),
            ] : null,
            'attendance' => $attendance ? $this->attendanceJson($attendance) : null,
            'setting'    => [
                'is_location_enabled'           => (bool) $setting->is_location_enabled,
                'office_latitude'               => $setting->office_latitude !== null ? (float) $setting->office_latitude : null,
                'office_longitude'              => $setting->office_longitude !== null ? (float) $setting->office_longitude : null,
                'max_distance_meters'           => (int) $setting->max_distance_meters,
                'require_location_for_checkout' => (bool) $setting->require_location_for_checkout,
                'is_face_recognition_enabled'   => (bool) $setting->is_face_recognition_enabled,
                'require_face_for_checkout'     => (bool) $setting->require_face_for_checkout,
                'face_recognition_threshold'    => (float) $setting->face_recognition_threshold,
                'face_enrolled'                 => !empty($user->face_descriptor),
            ],
        ];
    }

    private function attendanceJson(Attendance $a): array
    {
        $time = fn ($v) => $v ? substr((string) $v, 0, 5) : null;

        return [
            'id'          => $a->id,
            'date'        => $a->date?->toDateString(),
            'status'      => $a->status,
            'check_in'    => $time($a->check_in),
            'check_out'   => $time($a->check_out),
            'check_in_2'  => $time($a->check_in_2),
            'check_out_2' => $time($a->check_out_2),
            'distance_in' => $a->distance_in,
            'location_in' => $a->location_in,
            'notes'       => $a->notes,
        ];
    }
}
