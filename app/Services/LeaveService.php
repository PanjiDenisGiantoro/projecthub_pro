<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Carbon\Carbon;

class LeaveService
{
    public static function hitungHariKerja(Carbon $start, Carbon $end): int
    {
        $days    = 0;
        $current = $start->copy()->startOfDay();
        while ($current->lte($end->startOfDay())) {
            if (!$current->isWeekend()) $days++;
            $current->addDay();
        }
        return $days;
    }

    public function getOrCreateBalance(User $user, LeaveType $type, int $year): LeaveBalance
    {
        return LeaveBalance::firstOrCreate(
            ['user_id' => $user->id, 'leave_type_id' => $type->id, 'year' => $year],
            [
                'company_id'   => $user->company_id,
                'quota'        => $type->default_quota,
                'used'         => 0,
                'carried_over' => 0,
            ]
        );
    }

    public function submit(User $user, LeaveType $type, array $data): LeaveRequest
    {
        $start     = Carbon::parse($data['start_date']);
        $end       = Carbon::parse($data['end_date']);
        $totalDays = self::hitungHariKerja($start, $end);

        throw_if(!$type->isEligible($user), \Exception::class,
            "Jenis cuti {$type->name} tidak tersedia untuk Anda.");

        if ($type->has_balance && $type->default_quota > 0) {
            $balance = $this->getOrCreateBalance($user, $type, $start->year);
            $sisa    = $balance->quota + $balance->carried_over - $balance->used;
            throw_if($totalDays > $sisa, \Exception::class,
                "Saldo cuti tidak cukup. Sisa: {$sisa} hari, diajukan: {$totalDays} hari.");
        }

        return LeaveRequest::create([
            'user_id'       => $user->id,
            'company_id'    => $user->company_id,
            'leave_type_id' => $type->id,
            'start_date'    => $start,
            'end_date'      => $end,
            'total_days'    => $totalDays,
            'reason'        => $data['reason'],
            'attachment'    => $data['attachment'] ?? null,
            'status'        => $type->needs_approval ? 'pending' : 'approved',
        ]);
    }

    public function approve(LeaveRequest $request, User $approver): void
    {
        $request->update([
            'status'      => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        if ($request->leaveType->has_balance) {
            LeaveBalance::where([
                'user_id'       => $request->user_id,
                'leave_type_id' => $request->leave_type_id,
                'year'          => $request->start_date->year,
            ])->increment('used', $request->total_days);
        }

        $current = $request->start_date->copy();
        while ($current->lte($request->end_date)) {
            if (!$current->isWeekend()) {
                Attendance::updateOrCreate(
                    ['user_id' => $request->user_id, 'date' => $current->toDateString()],
                    [
                        'company_id' => $request->company_id,
                        'status'     => $request->leaveType->code === 'SAKIT' ? 'sakit' : 'cuti',
                        'notes'      => "Auto: {$request->leaveType->name}",
                    ]
                );
            }
            $current->addDay();
        }
    }

    public function reject(LeaveRequest $request, User $approver, string $reason): void
    {
        $request->update([
            'status'           => 'rejected',
            'approved_by'      => $approver->id,
            'approved_at'      => now(),
            'rejection_reason' => $reason,
        ]);
    }
}
