<?php

namespace App\Http\Controllers\Web\Hris;

use App\Exports\AttendanceImportTemplateExport;
use App\Exports\AttendancesExport;
use App\Http\Controllers\Concerns\HasPerPage;
use App\Http\Controllers\Controller;
use App\Imports\AttendancesImport;
use App\Mail\AttendanceCheckMail;
use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\Overtime;
use App\Models\Shift;
use App\Models\ShiftSchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Activitylog\Models\Activity;

class AbsensiController extends Controller
{
    use HasPerPage;

    public function index(Request $request)
    {
        $user     = auth()->user();
        $today    = today();
        $setting  = AttendanceSetting::forCompany($user->company_id);

        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first()
            ?? ($this->openSession($user)['attendance'] ?? null);

        $bulan = Attendance::where('user_id', $user->id)
            ->whereYear('date', $today->year)
            ->whereMonth('date', $today->month)
            ->orderByDesc('date')
            ->get();

        $user->loadMissing('shift.workingDays');
        $todayShift    = $user->effectiveShiftOn($today);
        $isShiftDayOff = $user->isDayOffOn($today);
        $isSplitShift  = $todayShift && $todayShift->isSplit();
        $todayHoliday  = Holiday::where('company_id', $user->company_id)->whereDate('date', $today)->first();

        // "day_off" | "checkin_1" | "checkout_1" | "checkin_2" | "checkout_2" | "done"
        $attendanceState = $this->attendanceState($attendance, $isShiftDayOff, $isSplitShift);

        return view('hris.absensi.index', compact(
            'attendance', 'bulan', 'today', 'setting', 'isShiftDayOff', 'todayShift', 'isSplitShift', 'attendanceState', 'todayHoliday'
        ));
    }

    public function checkIn(Request $request)
    {
        $user    = auth()->user();
        $today   = today()->toDateString();
        $setting = AttendanceSetting::forCompany($user->company_id);

        // Shift malam/lintas hari: attendance check-in kemarin bisa masih terbuka
        // (belum check-out) sampai lewat tengah malam — jangan biarkan check-in baru
        // menimpa sesi yang belum ditutup.
        if ($this->openSession($user)) {
            return back()->with('error', 'Anda masih punya sesi check-in yang belum check-out. Lakukan check-out dulu.');
        }

        $user->loadMissing('shift.workingDays');
        if ($user->isDayOffOn(today())) {
            $holiday = Holiday::where('company_id', $user->company_id)->whereDate('date', today())->first();
            if ($holiday) {
                return back()->with('error', "Hari ini libur: {$holiday->name}. Hubungi admin apabila Anda perlu masuk lembur.");
            }

            $shiftName = $user->effectiveShiftOn(today())->name ?? $user->shift->name ?? null;
            $label     = $shiftName ? "jadwal shift Anda ({$shiftName})" : 'jadwal shift Anda';
            return back()->with('error', "Hari ini adalah hari libur sesuai {$label}. Hubungi admin apabila Anda perlu masuk lembur.");
        }

        $todayAttendance = Attendance::where('user_id', $user->id)->where('date', $today)->first();
        $shift           = $user->effectiveShiftOn(today());
        $session         = 1;

        if ($todayAttendance) {
            // openSession() di atas sudah memastikan sesi 1 tidak lagi terbuka
            // (kalau masih terbuka, sudah ditolak duluan) — jadi sesi 1 di sini pasti sudah selesai.
            if (!$shift || !$shift->isSplit()) {
                return back()->with('error', 'Anda sudah check-in dan check-out hari ini.');
            }
            if ($todayAttendance->check_in_2) {
                return back()->with('error', 'Anda sudah menyelesaikan kedua sesi shift split hari ini.');
            }
            $session = 2;
        }

        // Location validation
        $distanceIn = null;
        if ($setting->is_location_enabled && $setting->office_latitude) {
            $lat = (float) $request->input('lat');
            $lng = (float) $request->input('lng');

            if (!$lat || !$lng) {
                return back()->with('error', 'Data lokasi tidak ditemukan. Aktifkan GPS dan coba lagi.');
            }

            $distanceIn = $setting->distanceFrom($lat, $lng);
            if ($distanceIn > $setting->max_distance_meters) {
                return back()->with('error', "Anda terlalu jauh dari kantor ({$distanceIn}m). Maksimum: {$setting->max_distance_meters}m.");
            }
        }

        // Face recognition validation (client-side result, server trusts flag)
        if ($setting->is_face_recognition_enabled) {
            if (!$request->boolean('face_verified')) {
                return back()->with('error', 'Verifikasi wajah diperlukan untuk check-in.');
            }
        }

        if ($session === 2) {
            $lateNote = $this->lateCheckInNote($shift, 2);

            $todayAttendance->update([
                'check_in_2'         => now()->toTimeString(),
                'lat_in_2'           => $request->input('lat') ?: null,
                'lng_in_2'           => $request->input('lng') ?: null,
                'distance_in_2'      => $distanceIn,
                'location_in_2'      => $request->input('address') ?: null,
                'face_verified_in_2' => $request->boolean('face_verified'),
                'notes'              => implode('; ', array_filter([$todayAttendance->notes, $lateNote])) ?: null,
            ]);

            $this->notifyAttendanceEmail($user, $todayAttendance, 'check_in_2', $lateNote);

            $message = 'Check-in sesi 2 berhasil' . ($distanceIn !== null ? " ({$distanceIn}m dari kantor)" : '') . '. ' . ($lateNote ?? 'Tepat waktu.');

            return back()->with($lateNote ? 'warning' : 'success', $message);
        }

        $lateNote = $this->lateCheckInNote($shift, 1);

        $newAttendance = Attendance::create([
            'user_id'          => $user->id,
            'company_id'       => $user->company_id,
            'date'             => $today,
            'check_in'         => now()->toTimeString(),
            'status'           => 'hadir',
            'lat_in'           => $request->input('lat') ?: null,
            'lng_in'           => $request->input('lng') ?: null,
            'distance_in'      => $distanceIn,
            'location_in'      => $request->input('address') ?: null,
            'face_verified_in' => $request->boolean('face_verified'),
            'notes'            => $lateNote,
        ]);

        $this->notifyAttendanceEmail($user, $newAttendance, 'check_in', $lateNote);

        $message = 'Check-in berhasil' . ($distanceIn !== null ? " ({$distanceIn}m dari kantor)" : '') . '. ' . ($lateNote ?? 'Tepat waktu.');

        return back()->with($lateNote ? 'warning' : 'success', $message);
    }

    /** Kirim email absensi kalau user mengaktifkan "Notifikasi Email" di profil — dikirim lewat queue biar tidak memperlambat response check-in/out. */
    private function notifyAttendanceEmail(User $user, Attendance $attendance, string $event, ?string $note): void
    {
        if (! $user->email_notifications_enabled || ! $user->email) {
            return;
        }

        Mail::to($user->email)->queue(new AttendanceCheckMail($user, $attendance, $event, $note));
    }

    /**
     * Bandingkan jam check-in sekarang dengan jadwal shift efektif hari ini
     * (+ toleransi shift). Null kalau user tidak punya shift, jadwal jam
     * kosong, jaraknya >12 jam (kemungkinan shift lintas hari — jangan
     * dihitung biar tidak salah "telat 700 menit"), atau masih dalam toleransi.
     */
    private function lateCheckInNote(?Shift $shift, int $session): ?string
    {
        if (!$shift) {
            return null;
        }

        $scheduledStart = $session === 2 && $shift->isSplit()
            ? $shift->break_end_time
            : $shift->effectiveStartTime(today()->dayOfWeek);

        if (!$scheduledStart) {
            return null;
        }

        $scheduled = Carbon::parse($scheduledStart);
        $now       = now();
        $diffMinutes = (int) round(abs($now->diffInMinutes($scheduled)));

        if ($diffMinutes > 720 || $now->lessThanOrEqualTo($scheduled)) {
            return null;
        }

        if ($diffMinutes <= $shift->tolerance_minutes) {
            return null;
        }

        $sessionLabel = $session === 2 ? ' sesi 2' : '';
        return "Telat {$diffMinutes} menit{$sessionLabel} (jadwal masuk " . substr($scheduledStart, 0, 5) . ')';
    }

    public function checkOut(Request $request)
    {
        $user    = auth()->user();
        $setting = AttendanceSetting::forCompany($user->company_id);

        $open = $this->openSession($user);

        if (!$open) {
            return back()->with('error', 'Tidak ada data check-in hari ini.');
        }

        ['attendance' => $attendance, 'session' => $session] = $open;

        $user->loadMissing('shift.workingDays');
        $shift = $user->effectiveShiftOn(today());

        // Location validation for checkout
        $latOut = null; $lngOut = null;
        if ($setting->is_location_enabled && $setting->require_location_for_checkout && $setting->office_latitude) {
            $latOut = (float) $request->input('lat');
            $lngOut = (float) $request->input('lng');
            if (!$latOut || !$lngOut) {
                return back()->with('error', 'Data lokasi tidak ditemukan untuk check-out.');
            }
            $dist = $setting->distanceFrom($latOut, $lngOut);
            if ($dist > $setting->max_distance_meters) {
                return back()->with('error', "Anda terlalu jauh dari kantor ({$dist}m). Maksimum: {$setting->max_distance_meters}m.");
            }
        }

        // Face validation for checkout
        if ($setting->is_face_recognition_enabled && $setting->require_face_for_checkout) {
            if (!$request->boolean('face_verified')) {
                return back()->with('error', 'Verifikasi wajah diperlukan untuk check-out.');
            }
        }

        if ($session === 2) {
            $earlyNote = $this->earlyCheckOutNote($shift, 2);

            $attendance->update([
                'check_out_2'         => now()->toTimeString(),
                'lat_out_2'           => $latOut ?: $request->input('lat') ?: null,
                'lng_out_2'           => $lngOut ?: $request->input('lng') ?: null,
                'face_verified_out_2' => $request->boolean('face_verified'),
                'notes'               => implode('; ', array_filter([$attendance->notes, $earlyNote])) ?: null,
            ]);

            $this->notifyAttendanceEmail($user, $attendance, 'check_out_2', $earlyNote);

            $message = 'Check-out sesi 2 berhasil. ' . ($earlyNote ?? 'Tepat waktu.');

            return back()->with($earlyNote ? 'warning' : 'success', $message);
        }

        $earlyNote = $this->earlyCheckOutNote($shift, 1);

        $attendance->update([
            'check_out'         => now()->toTimeString(),
            'lat_out'           => $latOut ?: $request->input('lat') ?: null,
            'lng_out'           => $lngOut ?: $request->input('lng') ?: null,
            'face_verified_out' => $request->boolean('face_verified'),
            'notes'             => implode('; ', array_filter([$attendance->notes, $earlyNote])) ?: null,
        ]);

        $this->notifyAttendanceEmail($user, $attendance, 'check_out', $earlyNote);

        $message = 'Check-out berhasil. ' . ($earlyNote ?? 'Tepat waktu.');

        return back()->with($earlyNote ? 'warning' : 'success', $message);
    }

    /**
     * Bandingkan jam check-out sekarang dengan jadwal jam pulang efektif hari
     * ini (+ toleransi shift). Session 1 dibandingkan ke break_start_time
     * kalau shiftnya split (jam pulang sesi 1), session 2 (atau shift biasa)
     * ke effectiveEndTime(). Null kalau tidak ada shift/jadwal, jaraknya >12
     * jam (kemungkinan shift lintas hari), atau checkout-nya di jam yang sama
     * atau lebih lambat dari jadwal (bukan "pulang cepat").
     */
    private function earlyCheckOutNote(?Shift $shift, int $session): ?string
    {
        if (!$shift) {
            return null;
        }

        $scheduledEnd = $session === 1 && $shift->isSplit()
            ? $shift->break_start_time
            : $shift->effectiveEndTime(today()->dayOfWeek);

        if (!$scheduledEnd) {
            return null;
        }

        $scheduled = Carbon::parse($scheduledEnd);
        $now       = now();
        $diffMinutes = (int) round(abs($now->diffInMinutes($scheduled)));

        if ($diffMinutes > 720 || $now->greaterThanOrEqualTo($scheduled)) {
            return null;
        }

        if ($diffMinutes <= $shift->tolerance_minutes) {
            return null;
        }

        $sessionLabel = $session === 1 && $shift->isSplit() ? ' sesi 1' : '';
        return "Pulang cepat {$diffMinutes} menit{$sessionLabel} (jadwal pulang " . substr($scheduledEnd, 0, 5) . ')';
    }

    /**
     * Sesi absensi user yang masih terbuka (sudah check-in, belum check-out) — dicari
     * lintas tanggal (bukan cuma "hari ini") supaya shift malam yang check-out-nya
     * lewat tengah malam tetap ketemu sesi kemarin, bukan dianggap belum check-in.
     * Return null kalau tidak ada sesi terbuka, atau ['attendance' => Attendance, 'session' => 1|2].
     */
    private function openSession(User $user): ?array
    {
        $attendance = Attendance::where('user_id', $user->id)
            ->where(function ($q) {
                $q->where(fn ($q2) => $q2->whereNotNull('check_in')->whereNull('check_out'))
                    ->orWhere(fn ($q2) => $q2->whereNotNull('check_in_2')->whereNull('check_out_2'));
            })
            ->where('date', '>=', today()->subDay())
            ->orderByDesc('date')
            ->first();

        if (!$attendance) {
            return null;
        }

        $session = ($attendance->check_in && !$attendance->check_out) ? 1 : 2;

        return ['attendance' => $attendance, 'session' => $session];
    }

    /** State tampilan halaman absensi hari ini, dipakai buat nentuin flow mana yang ditampilkan. */
    private function attendanceState(?Attendance $attendance, bool $isShiftDayOff, bool $isSplitShift): string
    {
        if (!$attendance) {
            return $isShiftDayOff ? 'day_off' : 'checkin_1';
        }

        if ($attendance->check_in && !$attendance->check_out) {
            return 'checkout_1';
        }

        if ($isSplitShift) {
            if (!$attendance->check_in_2) {
                return 'checkin_2';
            }
            if (!$attendance->check_out_2) {
                return 'checkout_2';
            }
        }

        return 'done';
    }

    public function rekap(Request $request)
    {
        $user  = auth()->user();
        $year  = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);

        $rekap = Attendance::with('user')
            ->where('company_id', $user->company_id)
            ->when(!$user->can('view absensi'), fn($q) => $q->where('user_id', $user->id))
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderByDesc('date')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return view('hris.absensi.rekap', compact('rekap', 'year', 'month'));
    }

    // ── Kalender Saya (jadwal + absensi + libur + cuti + lembur, gabung 1 tampilan) ──

    public function calendar(Request $request)
    {
        $user  = auth()->user();
        $year  = (int) $request->get('year', now()->year);
        $month = (int) $request->get('month', now()->month);
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        $user->loadMissing('shift.workingDays');

        $attendances = Attendance::where('user_id', $user->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn ($a) => $a->date->toDateString());

        $holidays = Holiday::where('company_id', $user->company_id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn ($h) => $h->date->toDateString());

        $overrides = $user->shiftSchedules()
            ->with('shift.workingDays')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn ($o) => $o->date->toDateString());

        $overtimes = Overtime::where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn ($o) => $o->date->toDateString());

        // Cuti bisa lintas bulan (start_date/end_date beda bulan) -> ambil yang overlap, lalu
        // sebar per tanggal supaya gampang di-lookup per sel kalender.
        $leaves = LeaveRequest::with('leaveType')
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->where('start_date', '<=', $end)
            ->where('end_date', '>=', $start)
            ->get();

        $leavesByDate = [];
        foreach ($leaves as $leave) {
            for ($d = $leave->start_date->copy()->max($start); $d->lte($leave->end_date->min($end)); $d->addDay()) {
                $leavesByDate[$d->toDateString()] = $leave;
            }
        }

        $days = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $dateStr = $d->toDateString();
            $days[] = $this->calendarDay(
                $user,
                $d->copy(),
                $attendances->get($dateStr),
                $holidays->get($dateStr),
                $overrides->get($dateStr),
                $leavesByDate[$dateStr] ?? null,
                $overtimes->get($dateStr)
            );
        }

        $summary = [
            'hadir'    => collect($days)->where('status.key', 'hadir')->count(),
            'alfa'     => collect($days)->where('status.key', 'alfa')->count(),
            'cuti'     => collect($days)->where('status.key', 'cuti')->count(),
            'izin'     => collect($days)->whereIn('status.key', ['izin', 'sakit'])->count(),
            'libur'    => collect($days)->where('status.key', 'libur')->count(),
            'lembur_jam' => $overtimes->sum('total_hours'),
        ];

        return view('hris.absensi.calendar', compact('days', 'year', 'month', 'start', 'summary'));
    }

    /** Rangkum status 1 hari kalender personal: gabungan jadwal shift, libur, absensi, cuti & lembur. */
    private function calendarDay(
        User $user,
        Carbon $date,
        ?Attendance $attendance,
        ?Holiday $holiday,
        ?ShiftSchedule $override,
        ?LeaveRequest $leave,
        ?Overtime $overtime
    ): array {
        $shift    = $override ? $override->shift : $user->shift;
        $isDayOff = $override
            ? $override->shift_id === null
            : ((bool) $holiday || ($user->shift && !$user->shift->isWorkingDay($date->dayOfWeek)));

        $isPast = $date->lt(today());

        $status = match (true) {
            (bool) $leave => [
                'key' => 'cuti', 'label' => $leave->leaveType->name ?? 'Cuti',
                'color' => '#7c3aed', 'bg' => 'rgba(124,58,237,0.12)',
            ],
            (bool) $attendance => match ($attendance->status) {
                'hadir' => ['key' => 'hadir', 'label' => 'Hadir', 'color' => '#16a34a', 'bg' => 'rgba(22,163,74,0.12)'],
                'sakit' => ['key' => 'sakit', 'label' => 'Sakit', 'color' => '#2563eb', 'bg' => 'rgba(37,99,235,0.12)'],
                'izin'  => ['key' => 'izin', 'label' => 'Izin', 'color' => '#d97706', 'bg' => 'rgba(217,119,6,0.12)'],
                'cuti'  => ['key' => 'cuti', 'label' => 'Cuti', 'color' => '#7c3aed', 'bg' => 'rgba(124,58,237,0.12)'],
                'alpha' => ['key' => 'alfa', 'label' => 'Alfa', 'color' => '#dc2626', 'bg' => 'rgba(220,38,38,0.12)'],
                default => ['key' => 'other', 'label' => ucfirst($attendance->status), 'color' => '#6b7280', 'bg' => 'rgba(107,114,128,0.12)'],
            },
            (bool) $holiday => ['key' => 'libur', 'label' => $holiday->name, 'color' => '#9ca3af', 'bg' => 'rgba(156,163,175,0.12)'],
            $isDayOff => ['key' => 'libur', 'label' => 'Libur', 'color' => '#9ca3af', 'bg' => 'rgba(156,163,175,0.12)'],
            // Belum ada catatan absensi di hari kerja yang sudah lewat & sudah punya shift -> Alfa.
            $isPast && $shift && (!$user->hire_date || $date->gte($user->hire_date)) => [
                'key' => 'alfa', 'label' => 'Alfa', 'color' => '#dc2626', 'bg' => 'rgba(220,38,38,0.12)',
            ],
            (bool) $shift => ['key' => 'scheduled', 'label' => 'Terjadwal', 'color' => '#2563eb', 'bg' => 'rgba(37,99,235,0.06)'],
            default => null,
        };

        return [
            'date'       => $date,
            'isToday'    => $date->isToday(),
            'isWeekend'  => in_array($date->dayOfWeek, [0, 6], true),
            'shift'      => $shift,
            'holiday'    => $holiday,
            'attendance' => $attendance,
            'leave'      => $leave,
            'overtime'   => $overtime,
            'status'     => $status,
        ];
    }

    // ── Import / Export Excel ────────────────────────────────────────────
    // Otorisasi lewat permission ('export absensi' / 'import absensi'), bukan
    // hardcode role — default cuma admin yang punya (lihat PermissionSeeder),
    // tapi company bisa meng-grant ke role lain lewat halaman /permissions.

    /** Export data absensi ke Excel — filter year/month opsional (default bulan berjalan). */
    public function export(Request $request)
    {
        $this->authorize('export absensi');
        $user  = auth()->user();
        $year  = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);

        $attendances = Attendance::with('user')
            ->where('company_id', $user->company_id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date')
            ->get();

        return Excel::download(
            new AttendancesExport($attendances),
            'data-absensi-' . $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '.xlsx'
        );
    }

    /** Template Excel siap isi buat import absensi — sheet contoh + sheet petunjuk & daftar email karyawan. */
    public function importTemplate()
    {
        $this->authorize('import absensi');
        $user = auth()->user();

        $employees = User::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->whereDoesntHave('roles', fn ($r) => $r->where('name', 'client'))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return Excel::download(new AttendanceImportTemplateExport($employees), 'template-import-absensi.xlsx');
    }

    public function import(Request $request)
    {
        $this->authorize('import absensi');
        $user = auth()->user();
        abort_unless($user->company_id, 403);

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        $import = new AttendancesImport($user->company_id);
        Excel::import($import, $request->file('file'));

        $message = "Import selesai: {$import->created} data absensi baru, {$import->updated} data diperbarui.";

        if ($import->errors) {
            $shown = array_slice($import->errors, 0, 15);
            $extra = count($import->errors) - count($shown);
            $message .= ' ' . count($import->errors) . ' baris gagal diproses: ' . implode(' | ', $shown)
                . ($extra > 0 ? " (+{$extra} baris lainnya)" : '');

            return redirect()->route('hris.absensi.rekap')->with('warning', $message);
        }

        return redirect()->route('hris.absensi.rekap')->with('success', $message);
    }

    // ── Settings ──────────────────────────────────────────────────────────

    public function setting()
    {
        $this->authorize('update absensi');
        $user    = auth()->user();
        $setting = AttendanceSetting::forCompany($user->company_id);
        $employees = User::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->where('is_super_admin', false)
            ->orderBy('name')
            ->get(['id', 'name', 'avatar', 'face_descriptor']);
        $shifts = Shift::with('workingDays')
            ->where('company_id', $user->company_id)
            ->orderBy('start_time')
            ->get();
        // Cuma hitung jumlah di sini (buat badge) — daftar log lengkap (dengan relasi
        // causer) baru di-load lewat settingLogs() saat panel riwayat dibuka user
        // (lazy load), biar halaman setting tidak ikut berat setiap kali dibuka.
        $logsCount = $setting->activitiesAsSubject()->count();

        return view('hris.absensi.setting', compact('setting', 'employees', 'shifts', 'logsCount'));
    }

    public function settingLogs()
    {
        $this->authorize('update absensi');
        $setting = AttendanceSetting::forCompany(auth()->user()->company_id);
        $logs = $setting->activitiesAsSubject()->with('causer')->latest()->limit(50)->get();

        return view('hris.absensi._setting-logs', compact('logs'));
    }

    public function saveSetting(Request $request)
    {
        $this->authorize('update absensi');
        $user    = auth()->user();
        $setting = AttendanceSetting::forCompany($user->company_id);

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

        // Normalize checkboxes
        $booleans = [
            'is_location_enabled', 'require_location_for_checkout',
            'is_face_recognition_enabled', 'require_face_for_checkout',
        ];
        foreach ($booleans as $key) {
            $data[$key] = $request->boolean($key);
        }

        $setting->update($data);

        return back()->with('success', 'Pengaturan absensi berhasil disimpan.');
    }

    // ── Shift Kerja ───────────────────────────────────────────────────────

    private function shiftValidationRules(Request $request): array
    {
        return [
            'name'               => 'required|string|max:100',
            'start_time'         => 'required|date_format:H:i',
            'end_time'           => 'required|date_format:H:i',
            // Diisi bareng buat shift split (2 sesi kerja + jeda panjang di tengah).
            'break_start_time'   => 'nullable|required_with:break_end_time|date_format:H:i|after:start_time|before:end_time',
            'break_end_time'     => 'nullable|required_with:break_start_time|date_format:H:i|after:break_start_time|before:end_time',
            'tolerance_minutes'  => 'required|integer|min:0|max:180',
            'working_days'       => 'required|array|min:1',
            'working_days.*'     => 'integer|min:0|max:6',
            // Jam kerja custom per hari (opsional) — kalau diisi, menimpa start_time/end_time
            // default shift buat hari itu saja (mis. Sabtu setengah hari).
            'day_times'          => 'nullable|array',
            'day_times.*.start'  => 'nullable|date_format:H:i',
            'day_times.*.end'    => ['nullable', 'date_format:H:i', function ($attribute, $value, $fail) use ($request) {
                preg_match('/day_times\.(\d+)\.end/', $attribute, $m);
                $start = $request->input("day_times.{$m[1]}.start");
                if ($start && $value && $value <= $start) {
                    $fail('Jam pulang custom harus setelah jam masuk custom untuk hari tersebut.');
                }
            }],
        ];
    }

    /** @param array<int,array{start:?string,end:?string}> $dayTimes keyed by day_of_week */
    private function workingDayRows(array $workingDays, array $dayTimes): array
    {
        return collect($workingDays)->unique()->map(function ($day) use ($dayTimes) {
            $t = $dayTimes[$day] ?? null;

            return [
                'day_of_week' => $day,
                'start_time'  => $t['start'] ?? null,
                'end_time'    => $t['end'] ?? null,
            ];
        })->all();
    }

    public function storeShift(Request $request)
    {
        $this->authorize('update absensi');
        $data = $request->validate($this->shiftValidationRules($request));
        $workingDays = $data['working_days'];
        $dayTimes    = $data['day_times'] ?? [];
        unset($data['working_days'], $data['day_times']);

        $shift = Shift::create([
            ...$data,
            'company_id' => auth()->user()->company_id,
            'is_active'  => true,
        ]);
        $shift->workingDays()->createMany($this->workingDayRows($workingDays, $dayTimes));

        return back()->with('success', 'Shift kerja berhasil ditambahkan.');
    }

    public function updateShift(Request $request, Shift $shift)
    {
        $this->authorize('update absensi');
        abort_unless($shift->company_id === auth()->user()->company_id, 403);

        $data = $request->validate($this->shiftValidationRules($request));
        $workingDays = $data['working_days'];
        $dayTimes    = $data['day_times'] ?? [];
        unset($data['working_days'], $data['day_times']);

        $shift->update($data);
        $shift->workingDays()->delete();
        $shift->workingDays()->createMany($this->workingDayRows($workingDays, $dayTimes));

        return back()->with('success', 'Shift kerja berhasil diperbarui.');
    }

    public function toggleShift(Shift $shift)
    {
        $this->authorize('update absensi');
        abort_unless($shift->company_id === auth()->user()->company_id, 403);

        $shift->update(['is_active' => !$shift->is_active]);

        return back()->with('success', 'Status shift diperbarui.');
    }

    public function destroyShift(Shift $shift)
    {
        $this->authorize('update absensi');
        abort_unless($shift->company_id === auth()->user()->company_id, 403);

        // Karyawan yang masih pakai shift ini otomatis balik ke "Tidak Ditentukan"
        // (kolom shift_id di users nullOnDelete) — tidak perlu dilepas manual di sini.
        $shift->delete();

        return back()->with('success', 'Shift kerja dihapus.');
    }

    // ── Jadwal Shift Bulanan (rotasi per karyawan per tanggal) ──────────────

    public function schedule(Request $request)
    {
        $this->authorize('update absensi');
        $companyId = auth()->user()->company_id;

        $year  = (int) $request->get('year', now()->year);
        $month = (int) $request->get('month', now()->month);
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        $employees = User::where('company_id', $companyId)
            ->where('is_super_admin', false)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'avatar', 'shift_id']);

        $shifts = Shift::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('start_time')
            ->get();

        // Shift nonaktif tetap perlu dimuat kalau masih dipakai sebagai default/override
        // karyawan, biar labelnya tetap tampil benar di grid (bukan cuma dropdown pilihan).
        $allShifts = Shift::where('company_id', $companyId)->with('workingDays')->get()->keyBy('id');

        $overrides = ShiftSchedule::whereIn('user_id', $employees->pluck('id'))
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn ($o) => $o->user_id . '_' . $o->date->format('Y-m-d'));

        $daysInMonth = $start->daysInMonth;
        $grid = [];
        foreach ($employees as $emp) {
            $row = [];
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = $start->copy()->day($day);
                $row[$day] = $this->scheduleCell($emp, $date, $overrides->get($emp->id . '_' . $date->toDateString()), $allShifts);
            }
            $grid[$emp->id] = $row;
        }

        return view('hris.absensi.schedule', compact('employees', 'shifts', 'grid', 'year', 'month', 'start', 'daysInMonth'));
    }

    /** Rangkum status 1 sel grid (1 karyawan, 1 tanggal): mode efektif, shift, label & warna buat ditampilkan. */
    private function scheduleCell(User $emp, Carbon $date, ?ShiftSchedule $override, $allShifts): array
    {
        if ($override) {
            $mode    = $override->shift_id ? 'shift' : 'off';
            $shiftId = $override->shift_id;
        } else {
            $mode    = 'default';
            $shiftId = $emp->shift_id;
        }

        $isOverride = (bool) $override;

        if ($mode === 'off') {
            return ['mode' => $mode, 'shift_id' => null, 'label' => 'Libur', 'color' => '#9ca3af', 'is_override' => $isOverride];
        }

        $shift = $shiftId ? $allShifts->get($shiftId) : null;
        if (!$shift) {
            return ['mode' => 'default', 'shift_id' => null, 'label' => '—', 'color' => '#d1d5db', 'is_override' => $isOverride];
        }

        // Default (belum di-override) tapi hari ini bukan hari kerja shift-nya -> libur.
        if ($mode === 'default' && !$shift->isWorkingDay($date->dayOfWeek)) {
            return ['mode' => 'default', 'shift_id' => null, 'label' => 'Libur', 'color' => '#9ca3af', 'is_override' => false];
        }

        return [
            'mode'        => $mode,
            'shift_id'    => $shift->id,
            'label'       => substr($shift->effectiveStartTime($date->dayOfWeek), 0, 5),
            'color'       => $isOverride ? '#7c3aed' : '#2563eb',
            'is_override' => $isOverride,
        ];
    }

    private function scheduleValidationRules(int $companyId): array
    {
        return [
            'user_id'  => ['required', Rule::exists('users', 'id')->where('company_id', $companyId)],
            'date'     => 'required|date',
            'mode'     => 'required|in:default,off,shift',
            'shift_id' => ['nullable', 'required_if:mode,shift', Rule::exists('shifts', 'id')->where('company_id', $companyId)],
        ];
    }

    public function saveScheduleCell(Request $request)
    {
        $this->authorize('update absensi');
        $companyId = auth()->user()->company_id;
        $data = $request->validate($this->scheduleValidationRules($companyId));

        if ($data['mode'] === 'default') {
            ShiftSchedule::where('user_id', $data['user_id'])->whereDate('date', $data['date'])->delete();
        } else {
            ShiftSchedule::updateOrCreate(
                ['user_id' => $data['user_id'], 'date' => $data['date']],
                ['company_id' => $companyId, 'shift_id' => $data['mode'] === 'off' ? null : $data['shift_id']]
            );
        }

        return back()->with('success', 'Jadwal shift diperbarui.');
    }

    public function bulkSetSchedule(Request $request)
    {
        $this->authorize('update absensi');
        $companyId = auth()->user()->company_id;

        $data = $request->validate([
            'user_id'            => ['required', Rule::exists('users', 'id')->where('company_id', $companyId)],
            'year'               => 'required|integer|min:2000|max:2100',
            'month'              => 'required|integer|min:1|max:12',
            'mode'               => 'required|in:default,off,shift',
            'shift_id'           => ['nullable', 'required_if:mode,shift', Rule::exists('shifts', 'id')->where('company_id', $companyId)],
            'only_working_days'  => 'nullable|boolean',
        ]);

        $start = Carbon::create($data['year'], $data['month'], 1)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        ShiftSchedule::where('user_id', $data['user_id'])
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->delete();

        if ($data['mode'] === 'default') {
            return back()->with('success', 'Jadwal bulan ini direset ke shift default.');
        }

        $shift = $data['mode'] === 'shift' ? Shift::findOrFail($data['shift_id']) : null;
        $onlyWorkingDays = $request->boolean('only_working_days') && $shift;

        $rows = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $shiftId = null;
            if ($data['mode'] === 'shift' && !($onlyWorkingDays && !$shift->isWorkingDay($d->dayOfWeek))) {
                $shiftId = $shift->id;
            }
            $rows[] = [
                'company_id' => $companyId,
                'user_id'    => $data['user_id'],
                'shift_id'   => $shiftId,
                'date'       => $d->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        ShiftSchedule::insert($rows);

        return back()->with('success', 'Jadwal shift bulan ini berhasil diterapkan.');
    }

    // ── Kalender Hari Libur ──────────────────────────────────────────────

    public function holidays(Request $request)
    {
        $this->authorize('update absensi');
        $companyId = auth()->user()->company_id;
        $year      = (int) $request->get('year', now()->year);

        $holidays = Holiday::where('company_id', $companyId)
            ->whereYear('date', $year)
            ->orderBy('date')
            ->get();

        $apiPreview = null;
        $apiError   = null;

        if ($request->boolean('fetch_api')) {
            $existingDates = $holidays->pluck('date')->map(fn ($d) => $d->toDateString())->all();

            try {
                $response = \Illuminate\Support\Facades\Http::timeout(8)
                    ->get("https://date.nager.at/api/v3/PublicHolidays/{$year}/ID");

                if ($response->successful()) {
                    $apiPreview = collect($response->json())
                        ->reject(fn ($h) => in_array($h['date'], $existingDates))
                        ->map(fn ($h) => ['date' => $h['date'], 'name' => $h['localName']])
                        ->values();
                } else {
                    $apiError = 'Gagal mengambil data dari API (server API merespons error). Coba lagi nanti.';
                }
            } catch (\Throwable $e) {
                $apiError = 'Gagal terhubung ke API hari libur. Periksa koneksi internet server, atau tambahkan manual.';
            }
        }

        return view('hris.absensi.holidays', compact('holidays', 'year', 'apiPreview', 'apiError'));
    }

    public function importHolidays(Request $request)
    {
        $this->authorize('update absensi');
        $companyId = auth()->user()->company_id;

        $data = $request->validate([
            'import'   => 'required|array|min:1',
            'import.*' => 'required|string',
        ]);

        $imported = 0;
        foreach ($data['import'] as $encoded) {
            $row = json_decode($encoded, true);
            if (!is_array($row) || empty($row['date']) || empty($row['name'])) {
                continue;
            }

            $holiday = Holiday::firstOrNew(['company_id' => $companyId, 'date' => $row['date']]);
            if (!$holiday->exists) {
                $holiday->name = $row['name'];
                $holiday->save();
                $imported++;
            }
        }

        return back()->with('success', "{$imported} hari libur berhasil diimpor dari API.");
    }

    public function storeHoliday(Request $request)
    {
        $this->authorize('update absensi');
        $companyId = auth()->user()->company_id;

        $data = $request->validate([
            'date' => ['required', 'date', Rule::unique('holidays', 'date')->where('company_id', $companyId)],
            'name' => 'required|string|max:150',
        ]);

        Holiday::create([...$data, 'company_id' => $companyId]);

        return back()->with('success', 'Hari libur berhasil ditambahkan.');
    }

    public function destroyHoliday(Holiday $holiday)
    {
        $this->authorize('update absensi');
        abort_unless($holiday->company_id === auth()->user()->company_id, 403);

        $holiday->delete();

        return back()->with('success', 'Hari libur dihapus.');
    }

    // ── Pendaftaran Wajah Karyawan (halaman terpisah) ───────────────────────

    public function faceEnrollment()
    {
        $this->authorizeFaceManagement();
        $user      = auth()->user();
        $setting   = AttendanceSetting::forCompany($user->company_id);
        $employees = User::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->where('is_super_admin', false)
            ->orderBy('name')
            ->get(['id', 'name', 'avatar', 'face_descriptor', 'face_photo']);
        // Cuma hitung jumlah di sini (buat badge) — daftar log lengkap baru di-load
        // lewat faceEnrollmentLogs() saat panel riwayat dibuka user (lazy load).
        $logsCount = $this->faceLogsQuery()->count();

        return view('hris.absensi.face-enrollment', compact('setting', 'employees', 'logsCount'));
    }

    public function faceEnrollmentLogs()
    {
        $this->authorizeFaceManagement();
        $logs = $this->faceLogsQuery()->with(['causer', 'subject'])->latest()->limit(50)->get();

        return view('hris.absensi._face-logs', compact('logs'));
    }

    /**
     * Log pendaftaran/hapus wajah dicatat manual (bukan LogsActivity otomatis di
     * model User) — descriptor-nya array 128 angka, tidak ada gunanya ditampilkan
     * sebagai diff. Aman di-scope lewat subject (karyawan) karena baris user-nya
     * tidak ikut terhapus, cuma face_descriptor/face_photo-nya di-null-kan.
     */
    private function faceLogsQuery()
    {
        $companyId = auth()->user()->company_id;

        return Activity::where('log_name', 'face_enrollment')
            ->when($companyId, fn ($q) => $q->whereHasMorph('subject', [User::class], fn ($q2) => $q2->where('company_id', $companyId)));
    }

    // ── Face enrollment (AJAX) ────────────────────────────────────────────

    public function enrollFace(Request $request, User $employee)
    {
        if ($employee->id !== auth()->id()) {
            $this->authorizeFaceManagement();
        }

        $request->validate([
            'descriptor' => 'required|string',
            'photo'      => 'nullable|string',
        ]);

        // Verify JSON is valid array of 128 floats
        $desc = json_decode($request->descriptor, true);
        if (!is_array($desc) || count($desc) !== 128) {
            return response()->json(['message' => 'Descriptor wajah tidak valid.'], 422);
        }

        $wasRegistered = (bool) $employee->face_descriptor;
        $data = ['face_descriptor' => $request->descriptor];

        // Foto (data URL base64 dari canvas kamera) cuma buat preview visual di UI —
        // matching absensi tetap pakai face_descriptor, jadi kalau fotonya gagal
        // diproses tetap lanjut simpan descriptor-nya.
        if ($request->filled('photo') && preg_match('/^data:image\/(jpeg|jpg|png);base64,(.+)$/', $request->photo, $m)) {
            $binary = base64_decode($m[2]);
            if ($binary !== false) {
                if ($employee->face_photo) {
                    Storage::disk('public')->delete($employee->face_photo);
                }
                $path = 'face-photos/' . $employee->id . '-' . Str::random(8) . '.jpg';
                if (Storage::disk('public')->put($path, $binary)) {
                    $data['face_photo'] = $path;
                }
            }
        }

        $employee->update($data);

        activity('face_enrollment')
            ->performedOn($employee)
            ->causedBy(auth()->user())
            ->event($wasRegistered ? 'updated' : 'created')
            ->log(($wasRegistered ? 'Memperbarui' : 'Mendaftarkan') . ' wajah ' . $employee->name);

        return response()->json(['message' => 'Wajah ' . $employee->name . ' berhasil didaftarkan.']);
    }

    public function deleteFace(User $employee)
    {
        $this->authorizeFaceManagement();
        if ($employee->face_photo) {
            Storage::disk('public')->delete($employee->face_photo);
        }
        $employee->update(['face_descriptor' => null, 'face_photo' => null]);

        activity('face_enrollment')
            ->performedOn($employee)
            ->causedBy(auth()->user())
            ->event('deleted')
            ->log('Menghapus data wajah ' . $employee->name);

        return back()->with('success', 'Data wajah ' . $employee->name . ' dihapus.');
    }

    private function authorizeFaceManagement(): void
    {
        if (!auth()->user()->can('update absensi') && !auth()->user()->can('manage face enrollment')) {
            abort(403);
        }
    }
}
