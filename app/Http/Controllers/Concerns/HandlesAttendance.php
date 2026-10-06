<?php

namespace App\Http\Controllers\Concerns;

use App\Mail\AttendanceCheckMail;
use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\Holiday;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

/**
 * Aturan absensi (check-in/out, shift malam, sesi split, telat/pulang cepat)
 * yang dipakai bersama oleh halaman web (AbsensiController) dan API mobile
 * (Api\Hris\AttendanceController) supaya aturannya hanya ada di satu tempat.
 */
trait HandlesAttendance
{
    /**
     * Logika check-in bersama web & API. Return [tipe, pesan, attendance|null]
     * dengan tipe "error" | "warning" | "success".
     */
    protected function attemptCheckIn(User $user, Request $request): array
    {
        $setting = AttendanceSetting::forCompany($user->company_id);

        // Shift malam/lintas hari: attendance check-in kemarin bisa masih terbuka
        // (belum check-out) sampai lewat tengah malam — jangan biarkan check-in baru
        // menimpa sesi yang belum ditutup.
        if ($this->openSession($user)) {
            return ['error', 'Anda masih punya sesi check-in yang belum check-out. Lakukan check-out dulu.', null];
        }

        $user->loadMissing('shift.workingDays');
        $workDate = $this->workDate($user);
        $today    = $workDate->toDateString();

        if ($user->isDayOffOn($workDate)) {
            $holiday = Holiday::where('company_id', $user->company_id)->whereDate('date', $workDate)->first();
            if ($holiday) {
                return ['error', "Hari ini libur: {$holiday->name}. Hubungi admin apabila Anda perlu masuk lembur.", null];
            }

            $shiftName = $user->effectiveShiftOn($workDate)->name ?? $user->shift->name ?? null;
            $label     = $shiftName ? "jadwal shift Anda ({$shiftName})" : 'jadwal shift Anda';
            return ['error', "Hari ini adalah hari libur sesuai {$label}. Hubungi admin apabila Anda perlu masuk lembur.", null];
        }

        $todayAttendance = Attendance::where('user_id', $user->id)->where('date', $today)->first();
        $shift           = $user->effectiveShiftOn($workDate);
        $session         = 1;

        if ($todayAttendance) {
            // openSession() di atas sudah memastikan sesi 1 tidak lagi terbuka
            // (kalau masih terbuka, sudah ditolak duluan) — jadi sesi 1 di sini pasti sudah selesai.
            if (!$shift || !$shift->isSplit()) {
                return ['error', 'Anda sudah check-in dan check-out hari ini.', null];
            }
            if ($todayAttendance->check_in_2) {
                return ['error', 'Anda sudah menyelesaikan kedua sesi shift split hari ini.', null];
            }
            $session = 2;
        }

        // Location validation
        $distanceIn = null;
        if ($setting->is_location_enabled && $setting->office_latitude) {
            $lat = (float) $request->input('lat');
            $lng = (float) $request->input('lng');

            if (!$lat || !$lng) {
                return ['error', 'Data lokasi tidak ditemukan. Aktifkan GPS dan coba lagi.', null];
            }

            $distanceIn = $setting->distanceFrom($lat, $lng);
            if ($distanceIn > $setting->max_distance_meters) {
                return ['error', "Anda terlalu jauh dari kantor ({$distanceIn}m). Maksimum: {$setting->max_distance_meters}m.", null];
            }
        }

        // Face recognition validation (client-side result, server trusts flag)
        if ($setting->is_face_recognition_enabled) {
            if (!$request->boolean('face_verified')) {
                return ['error', 'Verifikasi wajah diperlukan untuk check-in.', null];
            }
        }

        if ($session === 2) {
            $lateNote = $this->lateCheckInNote($shift, 2, $workDate);

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

            return [$lateNote ? 'warning' : 'success', $message, $session === 2 ? $todayAttendance : $newAttendance];
        }

        $lateNote = $this->lateCheckInNote($shift, 1, $workDate);

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

        return [$lateNote ? 'warning' : 'success', $message, $session === 2 ? $todayAttendance : $newAttendance];
    }

    /** Kirim email absensi kalau user mengaktifkan "Notifikasi Email" di profil — dikirim lewat queue biar tidak memperlambat response check-in/out. */
    protected function notifyAttendanceEmail(User $user, Attendance $attendance, string $event, ?string $note): void
    {
        if (! $user->email_notifications_enabled || ! $user->email) {
            return;
        }

        Mail::to($user->email)->queue(new AttendanceCheckMail($user, $attendance, $event, $note));
    }

    /**
     * Bandingkan waktu check-in sekarang dengan jadwal masuk shift di tanggal
     * shift $workDate (+ toleransi shift). Null kalau user tidak punya shift,
     * jaraknya >12 jam (data janggal — jangan dihitung biar tidak salah
     * "telat 700 menit"), atau masih dalam toleransi.
     */
    protected function lateCheckInNote(?Shift $shift, int $session, Carbon $workDate): ?string
    {
        if (!$shift) {
            return null;
        }

        // Datetime lengkap (bukan cuma jam) biar check-in lewat tengah malam di shift
        // malam tetap dibandingkan dengan jadwal masuk kemarin malam.
        [$scheduled] = $shift->sessionBounds($workDate, $session);
        $scheduledStart = $scheduled->format('H:i');
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

    /** Logika check-out bersama web & API. Return [tipe, pesan, attendance|null]. */
    protected function attemptCheckOut(User $user, Request $request): array
    {
        $setting = AttendanceSetting::forCompany($user->company_id);

        $open = $this->openSession($user);

        if (!$open) {
            return ['error', 'Tidak ada data check-in hari ini.', null];
        }

        ['attendance' => $attendance, 'session' => $session] = $open;

        // Shift yang dipakai = shift di tanggal absen (tanggal masuk), bukan hari ini —
        // check-out shift malam jatuh di hari berikutnya.
        $user->loadMissing('shift.workingDays');
        $shift = $user->effectiveShiftOn($attendance->date);

        // Location validation for checkout
        $latOut = null; $lngOut = null;
        if ($setting->is_location_enabled && $setting->require_location_for_checkout && $setting->office_latitude) {
            $latOut = (float) $request->input('lat');
            $lngOut = (float) $request->input('lng');
            if (!$latOut || !$lngOut) {
                return ['error', 'Data lokasi tidak ditemukan untuk check-out.', null];
            }
            $dist = $setting->distanceFrom($latOut, $lngOut);
            if ($dist > $setting->max_distance_meters) {
                return ['error', "Anda terlalu jauh dari kantor ({$dist}m). Maksimum: {$setting->max_distance_meters}m.", null];
            }
        }

        // Face validation for checkout
        if ($setting->is_face_recognition_enabled && $setting->require_face_for_checkout) {
            if (!$request->boolean('face_verified')) {
                return ['error', 'Verifikasi wajah diperlukan untuk check-out.', null];
            }
        }

        if ($session === 2) {
            $earlyNote = $this->earlyCheckOutNote($shift, 2, $attendance->date);

            $attendance->update([
                'check_out_2'         => now()->toTimeString(),
                'lat_out_2'           => $latOut ?: $request->input('lat') ?: null,
                'lng_out_2'           => $lngOut ?: $request->input('lng') ?: null,
                'face_verified_out_2' => $request->boolean('face_verified'),
                'notes'               => implode('; ', array_filter([$attendance->notes, $earlyNote])) ?: null,
            ]);

            $this->notifyAttendanceEmail($user, $attendance, 'check_out_2', $earlyNote);

            $message = 'Check-out sesi 2 berhasil. ' . ($earlyNote ?? 'Tepat waktu.');

            return [$earlyNote ? 'warning' : 'success', $message, $attendance];
        }

        $earlyNote = $this->earlyCheckOutNote($shift, 1, $attendance->date);

        $attendance->update([
            'check_out'         => now()->toTimeString(),
            'lat_out'           => $latOut ?: $request->input('lat') ?: null,
            'lng_out'           => $lngOut ?: $request->input('lng') ?: null,
            'face_verified_out' => $request->boolean('face_verified'),
            'notes'             => implode('; ', array_filter([$attendance->notes, $earlyNote])) ?: null,
        ]);

        $this->notifyAttendanceEmail($user, $attendance, 'check_out', $earlyNote);

        $message = 'Check-out berhasil. ' . ($earlyNote ?? 'Tepat waktu.');

        return [$earlyNote ? 'warning' : 'success', $message, $attendance];
    }

    /**
     * Bandingkan waktu check-out sekarang dengan jadwal pulang shift di tanggal
     * shift $workDate (+ toleransi shift) — shift malam otomatis jadwal pulangnya
     * besok pagi. Session 1 dibandingkan ke break_start_time kalau shiftnya split
     * (jam pulang sesi 1), session 2 (atau shift biasa) ke jam pulang. Null kalau
     * tidak ada shift, jaraknya >12 jam, atau checkout-nya di jam yang sama atau
     * lebih lambat dari jadwal (bukan "pulang cepat").
     */
    protected function earlyCheckOutNote(?Shift $shift, int $session, Carbon $workDate): ?string
    {
        if (!$shift) {
            return null;
        }

        [, $scheduled] = $shift->sessionBounds($workDate, $session);
        $scheduledEnd = $scheduled->format('H:i');
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
    /**
     * Tanggal shift buat absen saat ini. Normalnya hari ini, tapi kalau kemarin karyawan
     * punya shift malam (mis. 22:00-05:00) yang belum selesai & jam pulangnya belum lewat,
     * absen masuk ke tanggal kemarin — mis. telat check-in jam 00:30 tetap tercatat di shift kemarin.
     */
    protected function workDate(User $user): Carbon
    {
        $yesterday = today()->subDay();
        $shift     = $user->effectiveShiftOn($yesterday);

        if (!$shift || !$shift->isOvernight($yesterday->dayOfWeek) || $user->isDayOffOn($yesterday)) {
            return today();
        }

        [, $shiftEnd] = $shift->sessionBounds($yesterday, $shift->isSplit() ? 2 : 1);
        if (now()->gte($shiftEnd)) {
            return today();
        }

        $attendance = Attendance::where('user_id', $user->id)->whereDate('date', $yesterday)->first();
        $finished   = $attendance && $attendance->check_out && (!$shift->isSplit() || $attendance->check_out_2);

        return $finished ? today() : $yesterday;
    }

    protected function openSession(User $user): ?array
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
    protected function attendanceState(?Attendance $attendance, bool $isShiftDayOff, bool $isSplitShift): string
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

    protected function shiftValidationRules(Request $request): array
    {
        return [
            'name'               => 'required|string|max:100',
            'start_time'         => 'required|date_format:H:i',
            // Jam pulang boleh lebih kecil dari jam masuk = shift malam, pulang besok paginya (mis. 22:00-05:00).
            'end_time'           => ['required', 'date_format:H:i', function ($attribute, $value, $fail) use ($request) {
                if ($value === $request->input('start_time')) {
                    $fail('Jam pulang tidak boleh sama dengan jam masuk.');
                }
            }],
            // Diisi bareng buat shift split (2 sesi kerja + jeda panjang di tengah). Urutannya
            // dicek relatif ke jam masuk supaya shift malam (jeda lewat tengah malam) tetap valid.
            'break_start_time'   => ['nullable', 'required_with:break_end_time', 'date_format:H:i', function ($attribute, $value, $fail) use ($request) {
                $start = $request->input('start_time');
                $end   = $request->input('end_time');
                $breakEnd = $request->input('break_end_time');
                if (!$start || !$end || !$breakEnd) {
                    return;
                }
                $offset = fn ($time, $isEnd = false) => Shift::offsetFromStart($start, $time, $isEnd);
                if (!(0 < $offset($value) && $offset($value) < $offset($breakEnd) && $offset($breakEnd) < $offset($end, true))) {
                    $fail('Jam jeda harus berada di antara jam masuk dan jam pulang, dan jeda mulai sebelum jeda selesai.');
                }
            }],
            'break_end_time'     => 'nullable|required_with:break_start_time|date_format:H:i',
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
                // Jam pulang < jam masuk = lewat tengah malam (shift malam), yang tidak boleh cuma jam yang sama.
                if ($start && $value && $value === $start) {
                    $fail('Jam pulang custom tidak boleh sama dengan jam masuk custom untuk hari tersebut.');
                }
            }],
        ];
    }

    protected function workingDayRows(array $workingDays, array $dayTimes): array
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

    protected function scheduleValidationRules(int $companyId): array
    {
        return [
            'user_id'  => ['required', Rule::exists('users', 'id')->where('company_id', $companyId)],
            'date'     => 'required|date',
            'mode'     => 'required|in:default,off,shift',
            'shift_id' => ['nullable', 'required_if:mode,shift', Rule::exists('shifts', 'id')->where('company_id', $companyId)],
        ];
    }
}
