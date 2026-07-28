<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            [
                'slug'          => 'hris',
                'name'          => 'HRIS',
                'description'   => 'Human Resource Information System — manajemen karyawan, struktur organisasi, dan penggajian.',
                'price'         => 500000,
                'duration_days' => 30,
                'is_active'     => true,
            ],
            [
                'slug'          => 'task_management',
                'name'          => 'Task Management',
                'description'   => 'Manajemen tugas, proyek, sprint, dan pelacakan waktu.',
                'price'         => 300000,
                'duration_days' => 30,
                'is_active'     => true,
            ],
            [
                'slug'          => 'pro',
                'name'          => 'Pro',
                'description'   => 'Untuk tim yang sedang berkembang. Mencakup semua modul (HRIS & Task Management), project unlimited, CRM & Invoice, Bug tracker + SLA, chat real-time, priority support.',
                'price'         => 299000,
                'duration_days' => 30,
                'is_active'     => true,
            ],
        ];

        foreach ($packages as $data) {
            Package::updateOrCreate(['slug' => $data['slug']], $data);
        }

        $this->command->info('✅ Seeded packages: ' . implode(', ', array_column($packages, 'slug')));
    }
}
