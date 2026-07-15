<?php

namespace Database\Seeders;

use App\Models\OvertimeRule;
use Illuminate\Database\Seeder;

class OvertimeRuleSeeder extends Seeder
{
    public function run(): void
    {
        // Permenaker No. 5 Tahun 2023
        $rules = [
            // day_type    from  to   multiplier  label
            ['weekday',    1,    1,   1.50,  'Hari Kerja — Jam ke-1'],
            ['weekday',    2,    0,   2.00,  'Hari Kerja — Jam ke-2 dst'],

            ['weekend',    1,    7,   2.00,  'Libur/Minggu — Jam ke-1 s/d 7'],
            ['weekend',    8,    8,   3.00,  'Libur/Minggu — Jam ke-8'],
            ['weekend',    9,    0,   4.00,  'Libur/Minggu — Jam ke-9 dst'],

            ['holiday',    1,    7,   2.00,  'Libur Nasional — Jam ke-1 s/d 7'],
            ['holiday',    8,    8,   3.00,  'Libur Nasional — Jam ke-8'],
            ['holiday',    9,    0,   4.00,  'Libur Nasional — Jam ke-9 dst'],
        ];

        foreach ($rules as $i => [$dayType, $from, $to, $multiplier, $label]) {
            OvertimeRule::firstOrCreate(
                ['company_id' => null, 'day_type' => $dayType, 'hour_from' => $from, 'hour_to' => $to],
                ['multiplier' => $multiplier, 'label' => $label, 'sort_order' => $i, 'is_active' => true]
            );
        }
    }
}
