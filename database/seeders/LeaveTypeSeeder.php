<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            // code       name                          quota  paid   attach  approval  balance  gender
            ['TAHUNAN', 'Cuti Tahunan',                  12,   true,  false,  true,     true,   'all'],
            ['SAKIT',   'Cuti Sakit',                     0,   true,  true,   false,    false,  'all'],
            ['IZIN',    'Izin Tidak Masuk',               0,   false, false,  true,     false,  'all'],
            ['HAMIL',   'Cuti Melahirkan/Hamil',          90,  true,  true,   true,     false,  'female'],
            ['AYAH',    'Cuti Ayah (Paternity)',          2,   true,  false,  true,     false,  'male'],
            ['DUKA',    'Cuti Duka Cita',                 2,   true,  false,  true,     false,  'all'],
            ['NIKAH',   'Cuti Pernikahan',                3,   true,  false,  true,     false,  'all'],
            ['IBADAH',  'Cuti Ibadah (Haji/Umroh)',       40,  true,  true,   true,     false,  'all'],
            ['LAINNYA', 'Izin Khusus Lainnya',            0,   false, false,  true,     false,  'all'],
        ];

        foreach ($types as $i => [$code, $name, $quota, $paid, $attach, $approval, $balance, $gender]) {
            LeaveType::firstOrCreate(
                ['code' => $code, 'company_id' => null],
                [
                    'name'               => $name,
                    'default_quota'      => $quota,
                    'is_paid'            => $paid,
                    'needs_attachment'   => $attach,
                    'needs_approval'     => $approval,
                    'has_balance'        => $balance,
                    'gender_restriction' => $gender,
                    'is_active'          => true,
                    'sort_order'         => $i,
                ]
            );
        }
    }
}
