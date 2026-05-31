<?php

namespace App\Http\Controllers\Web\Hris;

use App\Http\Controllers\Controller;
use App\Models\Overtime;
use App\Services\OvertimeService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OvertimeController extends Controller
{
    public function __construct(private OvertimeService $overtimeService) {}

    public function index(Request $request)
    {
        $user = auth()->user();

        $overtimes = Overtime::with('user')
            ->where('company_id', $user->company_id)
            ->when(!$user->can('manage overtime'), fn($q) => $q->where('user_id', $user->id))
            ->orderByDesc('date')
            ->paginate(20);

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

        Overtime::create([
            'user_id'     => $user->id,
            'company_id'  => $user->company_id,
            'date'        => $request->date,
            'day_type'    => OvertimeService::dayType($date),
            'start_time'  => $request->start_time,
            'end_time'    => $request->end_time,
            'total_hours' => $hours,
            'description' => $request->description,
        ]);

        return redirect()->route('hris.overtime.index')->with('success', 'Pengajuan lembur berhasil dikirim.');
    }

    public function destroy(Overtime $overtime)
    {
        abort_if($overtime->user_id !== auth()->id() || $overtime->status !== 'pending', 403);
        $overtime->delete();
        return back()->with('success', 'Pengajuan lembur dihapus.');
    }

    public function approve(Overtime $overtime)
    {
        $this->authorize('manage overtime');
        abort_if($overtime->status !== 'pending', 422, 'Status tidak valid.');

        try {
            $this->overtimeService->approve($overtime, auth()->user());
            return back()->with('success', 'Lembur disetujui.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
