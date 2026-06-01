<?php

namespace App\Http\Controllers\Web\Hris;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AbsensiController extends Controller
{
    public function index(Request $request)
    {
        $user  = auth()->user();
        $today = today();

        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        $bulan = Attendance::where('user_id', $user->id)
            ->whereYear('date', $today->year)
            ->whereMonth('date', $today->month)
            ->orderByDesc('date')
            ->get();

        return view('hris.absensi.index', compact('attendance', 'bulan', 'today'));
    }

    public function checkIn(Request $request)
    {
        $user = auth()->user();
        $today = today()->toDateString();

        $exists = Attendance::where('user_id', $user->id)->where('date', $today)->exists();
        if ($exists) {
            return back()->with('error', 'Anda sudah check-in hari ini.');
        }

        Attendance::create([
            'user_id'    => $user->id,
            'company_id' => $user->company_id,
            'date'       => $today,
            'check_in'   => now()->toTimeString(),
            'status'     => 'hadir',
        ]);

        return back()->with('success', 'Check-in berhasil.');
    }

    public function checkOut(Request $request)
    {
        $user = auth()->user();

        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', today())
            ->whereNotNull('check_in')
            ->whereNull('check_out')
            ->first();

        if (!$attendance) {
            return back()->with('error', 'Tidak ada data check-in hari ini.');
        }

        $attendance->update(['check_out' => now()->toTimeString()]);

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
}
