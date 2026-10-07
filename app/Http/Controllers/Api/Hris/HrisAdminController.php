<?php

namespace App\Http\Controllers\Api\Hris;

use App\Http\Controllers\Concerns\HandlesAttendance;
use App\Http\Controllers\Controller;
use App\Models\AttendanceSetting;
use App\Models\Holiday;
use App\Models\Shift;
use App\Models\ShiftSchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Data HRIS umum untuk aplikasi mobile: izin user, karyawan, konfigurasi
 * absensi, shift, jadwal shift, dan hari libur. Aturan validasi diambil
 * dari trait yang sama dengan halaman web (HandlesAttendance).
 */
class HrisAdminController extends Controller
{
    use HandlesAttendance;

    private const PERMISSIONS = [
        'view absensi', 'update absensi', 'manage face enrollment',
        'view leave', 'update leave', 'delete leave', 'approve leave',
        'view overtime', 'update overtime', 'delete overtime', 'approve overtime',
        'view reimbursement', 'update reimbursement', 'delete reimbursement', 'approve reimbursement',
        'view payroll', 'create payroll', 'update payroll', 'delete payroll', 'generate payroll',
    ];

    /** Izin HRIS user yang login — dipakai aplikasi untuk menampilkan/menyembunyikan aksi. */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'id'          => $user->id,
            'name'        => $user->name,
            'company_id'  => $user->company_id,
            'permissions' => collect(self::PERMISSIONS)->filter(fn ($p) => $user->can($p))->values(),
        ]);
    }

    // ── Karyawan ─────────────────────────────────────────────────────────────

    public function employees(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->can('view payroll') || $user->can('view absensi') || $user->can('update absensi'), 403, 'Anda tidak punya akses ke data karyawan.');

        $employees = User::with(['organizationUnit:id,name', 'structuralLevel:id,name', 'shift:id,name,start_time,end_time'])
            ->where('company_id', $user->company_id)
            ->where('is_super_admin', false)
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'client'))
            ->orderBy('name')
            ->get();

        return response()->json($employees->map(fn (User $e) => [
            'id'                => $e->id,
            'name'              => $e->name,
            'email'             => $e->email,
            'is_active'         => (bool) $e->is_active,
            'employment_type'   => $e->employment_type,
            'hire_date'         => $e->hire_date ? Carbon::parse($e->hire_date)->toDateString() : null,
            'contract_end_date' => $e->contract_end_date ? Carbon::parse($e->contract_end_date)->toDateString() : null,
            'organization_unit' => $e->organizationUnit?->name,
            'structural_level'  => $e->structuralLevel?->name,
            'shift_id'          => $e->shift_id,
            'shift_name'        => $e->shift?->name,
            'face_enrolled'     => !empty($e->face_descriptor),
        ])->values());
    }

    // ── Konfigurasi absensi ──────────────────────────────────────────────────

    public function setting(Request $request): JsonResponse
    {
        $this->authorize('update absensi');

        return response()->json($this->settingJson(AttendanceSetting::forCompany($request->user()->company_id)));
    }

    public function saveSetting(Request $request): JsonResponse
    {
        $this->authorize('update absensi');
        $setting = AttendanceSetting::forCompany($request->user()->company_id);

        $data = $request->validate([
            'is_location_enabled'           => 'boolean',
            'office_name'                   => 'nullable|string|max:100',
            'office_latitude'               => 'nullable|numeric|between:-90,90',
            'office_longitude'              => 'nullable|numeric|between:-180,180',
            'max_distance_meters'           => 'integer|min:10|max:10000',
            'require_location_for_checkout' => 'boolean',
            'is_face_recognition_enabled'   => 'boolean',
            'face_recognition_threshold'    => 'numeric|between:0.3,0.9',
            'require_face_for_checkout'     => 'boolean',
        ]);

        foreach (['is_location_enabled', 'require_location_for_checkout', 'is_face_recognition_enabled', 'require_face_for_checkout'] as $key) {
            $data[$key] = $request->boolean($key);
        }

        $setting->update($data);

        return response()->json($this->settingJson($setting->fresh()));
    }

    private function settingJson(AttendanceSetting $s): array
    {
        return [
            'is_location_enabled'           => (bool) $s->is_location_enabled,
            'office_name'                   => $s->office_name,
            'office_latitude'               => $s->office_latitude !== null ? (float) $s->office_latitude : null,
            'office_longitude'              => $s->office_longitude !== null ? (float) $s->office_longitude : null,
            'max_distance_meters'           => (int) $s->max_distance_meters,
            'require_location_for_checkout' => (bool) $s->require_location_for_checkout,
            'is_face_recognition_enabled'   => (bool) $s->is_face_recognition_enabled,
            'face_recognition_threshold'    => (float) $s->face_recognition_threshold,
            'require_face_for_checkout'     => (bool) $s->require_face_for_checkout,
        ];
    }

    // ── Hari libur ───────────────────────────────────────────────────────────

    public function storeHoliday(Request $request): JsonResponse
    {
        $this->authorize('update absensi');
        $companyId = $request->user()->company_id;

        $data = $request->validate([
            'date' => ['required', 'date', Rule::unique('holidays', 'date')->where('company_id', $companyId)],
            'name' => 'required|string|max:150',
        ]);

        $holiday = Holiday::create([...$data, 'company_id' => $companyId]);

        return response()->json(['id' => $holiday->id, 'date' => $holiday->date->toDateString(), 'name' => $holiday->name], 201);
    }

    public function destroyHoliday(Request $request, Holiday $holiday): JsonResponse
    {
        $this->authorize('update absensi');
        abort_unless($holiday->company_id === $request->user()->company_id, 403);
        $holiday->delete();

        return response()->json(['message' => 'Hari libur dihapus.']);
    }

    // ── Shift kerja ──────────────────────────────────────────────────────────

    public function shifts(Request $request): JsonResponse
    {
        $user = $request->user();

        $shifts = Shift::with('workingDays')
            ->withCount('users')
            ->where('company_id', $user->company_id)
            ->orderBy('start_time')
            ->get();

        return response()->json($shifts->map(fn (Shift $s) => $this->shiftJson($s))->values());
    }

    public function storeShift(Request $request): JsonResponse
    {
        $this->authorize('update absensi');
        $data = $request->validate($this->shiftValidationRules($request));
        $workingDays = $data['working_days'];
        $dayTimes    = $data['day_times'] ?? [];
        unset($data['working_days'], $data['day_times']);

        $shift = Shift::create([...$data, 'company_id' => $request->user()->company_id, 'is_active' => true]);
        $shift->workingDays()->createMany($this->workingDayRows($workingDays, $dayTimes));

        return response()->json($this->shiftJson($shift->load('workingDays')->loadCount('users')), 201);
    }

    public function updateShift(Request $request, Shift $shift): JsonResponse
    {
        $this->authorize('update absensi');
        abort_unless($shift->company_id === $request->user()->company_id, 403);

        $data = $request->validate($this->shiftValidationRules($request));
        $workingDays = $data['working_days'];
        $dayTimes    = $data['day_times'] ?? [];
        unset($data['working_days'], $data['day_times']);

        $shift->update($data);
        $shift->workingDays()->delete();
        $shift->workingDays()->createMany($this->workingDayRows($workingDays, $dayTimes));

        return response()->json($this->shiftJson($shift->fresh('workingDays')->loadCount('users')));
    }

    public function toggleShift(Request $request, Shift $shift): JsonResponse
    {
        $this->authorize('update absensi');
        abort_unless($shift->company_id === $request->user()->company_id, 403);
        $shift->update(['is_active' => !$shift->is_active]);

        return response()->json($this->shiftJson($shift->fresh('workingDays')->loadCount('users')));
    }

    public function destroyShift(Request $request, Shift $shift): JsonResponse
    {
        $this->authorize('update absensi');
        abort_unless($shift->company_id === $request->user()->company_id, 403);
        $shift->delete();

        return response()->json(['message' => 'Shift kerja dihapus.']);
    }

    /** Ganti shift default karyawan (kolom users.shift_id). */
    public function assignShift(Request $request, User $employee): JsonResponse
    {
        $this->authorize('update absensi');
        $companyId = $request->user()->company_id;
        abort_unless($employee->company_id === $companyId, 403);

        $data = $request->validate([
            'shift_id' => ['nullable', Rule::exists('shifts', 'id')->where('company_id', $companyId)],
        ]);
        $employee->update(['shift_id' => $data['shift_id'] ?? null]);

        return response()->json(['id' => $employee->id, 'shift_id' => $employee->shift_id]);
    }

    private function shiftJson(Shift $s): array
    {
        return [
            'id'                => $s->id,
            'name'              => $s->name,
            'start_time'        => substr((string) $s->start_time, 0, 5),
            'end_time'          => substr((string) $s->end_time, 0, 5),
            'break_start_time'  => $s->break_start_time ? substr((string) $s->break_start_time, 0, 5) : null,
            'break_end_time'    => $s->break_end_time ? substr((string) $s->break_end_time, 0, 5) : null,
            'tolerance_minutes' => (int) $s->tolerance_minutes,
            'is_active'         => (bool) $s->is_active,
            'working_days'      => $s->workingDays->pluck('day_of_week')->map(fn ($d) => (int) $d)->sort()->values(),
            'label'             => $s->timeRangeLabel(),
            'users_count'       => (int) ($s->users_count ?? 0),
        ];
    }

    // ── Jadwal shift (override per tanggal) ──────────────────────────────────

    public function schedules(Request $request): JsonResponse
    {
        $user = $request->user();
        $request->validate(['from' => 'nullable|date', 'to' => 'nullable|date']);
        $from = $request->date('from') ?? today()->startOfMonth();
        $to   = $request->date('to') ?? today()->endOfMonth();

        $rows = ShiftSchedule::with(['user:id,name', 'shift:id,name'])
            ->where('company_id', $user->company_id)
            ->when(!$user->can('update absensi') && !$user->can('view absensi'), fn ($q) => $q->where('user_id', $user->id))
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('date')
            ->get();

        return response()->json($rows->map(fn (ShiftSchedule $s) => [
            'id'         => $s->id,
            'user_id'    => $s->user_id,
            'user_name'  => $s->user?->name,
            'date'       => $s->date?->toDateString(),
            'shift_id'   => $s->shift_id,
            'shift_name' => $s->shift?->name,
            'is_off'     => $s->shift_id === null,
        ])->values());
    }

    /** mode: default (hapus override) | off (libur) | shift (pakai shift_id). */
    public function saveSchedule(Request $request): JsonResponse
    {
        $this->authorize('update absensi');
        $companyId = $request->user()->company_id;
        $data = $request->validate($this->scheduleValidationRules($companyId));

        if ($data['mode'] === 'default') {
            ShiftSchedule::where('user_id', $data['user_id'])->whereDate('date', $data['date'])->delete();
        } else {
            ShiftSchedule::updateOrCreate(
                ['user_id' => $data['user_id'], 'date' => $data['date']],
                ['company_id' => $companyId, 'shift_id' => $data['mode'] === 'off' ? null : $data['shift_id']]
            );
        }

        return response()->json(['message' => 'Jadwal shift diperbarui.']);
    }
}
