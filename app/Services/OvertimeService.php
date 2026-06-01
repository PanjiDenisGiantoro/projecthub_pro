<?php

namespace App\Services;

use App\Models\Overtime;
use App\Models\OvertimeRule;
use App\Models\User;
use Carbon\Carbon;

class OvertimeService
{
    public function hitung(float $gajiPokok, float $jamLembur, string $dayType, int $companyId): array
    {
        $upahSejam = $gajiPokok / 173;
        $rules     = OvertimeRule::forCompany($companyId, $dayType);

        if ($rules->isEmpty()) {
            $rules = OvertimeRule::whereNull('company_id')
                ->where('day_type', $dayType)
                ->where('is_active', true)
                ->orderBy('hour_from')
                ->get();
        }

        $total     = 0.0;
        $breakdown = [];
        $jamSisa   = $jamLembur;

        foreach ($rules as $rule) {
            if ($jamSisa <= 0) break;

            if ($rule->hour_to === 0) {
                $jamLayer = $jamSisa;
            } else {
                $kapasitasLayer = $rule->hour_to - $rule->hour_from + 1;
                $jamLayer       = min($jamSisa, $kapasitasLayer);
            }

            $amount     = $jamLayer * $rule->multiplier * $upahSejam;
            $total     += $amount;
            $breakdown[] = [
                'label'      => $rule->label,
                'hours'      => $jamLayer,
                'multiplier' => $rule->multiplier,
                'amount'     => round($amount),
            ];

            $jamSisa -= $jamLayer;
        }

        return [
            'total'      => round($total),
            'upah_sejam' => round($upahSejam, 2),
            'breakdown'  => $breakdown,
        ];
    }

    public static function dayType(Carbon $date, array $hariLiburNasional = []): string
    {
        if (in_array($date->toDateString(), $hariLiburNasional)) return 'holiday';
        if ($date->isWeekend()) return 'weekend';
        return 'weekday';
    }

    public function approve(Overtime $overtime, User $approver): void
    {
        $salary = $overtime->user->salaries()->latest('effective_date')->first();
        $result = $this->hitung(
            $salary->gaji_pokok,
            $overtime->total_hours,
            $overtime->day_type,
            $overtime->company_id
        );

        $overtime->update([
            'status'       => 'approved',
            'approved_by'  => $approver->id,
            'approved_at'  => now(),
            'upah_sejam'   => $result['upah_sejam'],
            'total_amount' => $result['total'],
            'breakdown'    => $result['breakdown'],
        ]);
    }
}
