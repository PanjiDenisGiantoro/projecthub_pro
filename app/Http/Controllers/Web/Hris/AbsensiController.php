<?php

namespace App\Http\Controllers\Web\Hris;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AbsensiController extends Controller
{
    public function index(Request $request)
    {
        $user     = auth()->user();
        $today    = today();
        $setting  = AttendanceSetting::forCompany($user->company_id);

        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        $bulan = Attendance::where('user_id', $user->id)
            ->whereYear('date', $today->year)
            ->whereMonth('date', $today->month)
            ->orderByDesc('date')
            ->get();

        return view('hris.absensi.index', compact('attendance', 'bulan', 'today', 'setting'));
    }

    public function checkIn(Request $request)
    {
        $user    = auth()->user();
        $today   = today()->toDateString();
        $setting = AttendanceSetting::forCompany($user->company_id);

        if (Attendance::where('user_id', $user->id)->where('date', $today)->exists()) {
            return back()->with('error', 'Anda sudah check-in hari ini.');
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

        Attendance::create([
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
        ]);

        return back()->with('success', 'Check-in berhasil' . ($distanceIn !== null ? " ({$distanceIn}m dari kantor)" : '') . '.');
    }

    public function checkOut(Request $request)
    {
        $user    = auth()->user();
        $setting = AttendanceSetting::forCompany($user->company_id);

        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', today())
            ->whereNotNull('check_in')
            ->whereNull('check_out')
            ->first();

        if (!$attendance) {
            return back()->with('error', 'Tidak ada data check-in hari ini.');
        }

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

        $attendance->update([
            'check_out'         => now()->toTimeString(),
            'lat_out'           => $latOut ?: $request->input('lat') ?: null,
            'lng_out'           => $lngOut ?: $request->input('lng') ?: null,
            'face_verified_out' => $request->boolean('face_verified'),
        ]);

        return back()->with('success', 'Check-out berhasil.');
    }

    public function rekap(Request $request)
    {
        $user  = auth()->user();
        $year  = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);

        $rekap = Attendance::with('user')
            ->where('company_id', $user->company_id)
            ->when(!$user->can('manage absensi'), fn($q) => $q->where('user_id', $user->id))
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderByDesc('date')
            ->paginate(30);

        return view('hris.absensi.rekap', compact('rekap', 'year', 'month'));
    }

    // ── Settings ──────────────────────────────────────────────────────────

    public function setting()
    {
        $this->authorize('manage absensi');
        $user    = auth()->user();
        $setting = AttendanceSetting::forCompany($user->company_id);
        $employees = User::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->where('is_super_admin', false)
            ->orderBy('name')
            ->get(['id', 'name', 'avatar', 'face_descriptor']);

        return view('hris.absensi.setting', compact('setting', 'employees'));
    }

    public function saveSetting(Request $request)
    {
        $this->authorize('manage absensi');
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

    // ── Face enrollment (AJAX) ────────────────────────────────────────────

    public function enrollFace(Request $request, User $employee)
    {
        if ($employee->id !== auth()->id()) {
            $this->authorize('manage absensi');
        }

        $request->validate([
            'descriptor' => 'required|string',
        ]);

        // Verify JSON is valid array of 128 floats
        $desc = json_decode($request->descriptor, true);
        if (!is_array($desc) || count($desc) !== 128) {
            return response()->json(['message' => 'Descriptor wajah tidak valid.'], 422);
        }

        $employee->update(['face_descriptor' => $request->descriptor]);

        return response()->json(['message' => 'Wajah ' . $employee->name . ' berhasil didaftarkan.']);
    }

    public function deleteFace(User $employee)
    {
        $this->authorize('manage absensi');
        $employee->update(['face_descriptor' => null]);
        return back()->with('success', 'Data wajah ' . $employee->name . ' dihapus.');
    }
}
