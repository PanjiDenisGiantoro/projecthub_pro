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
                'slug'        => 'hris',
                'name'        => 'HRIS',
                'description' => 'Human Resource Information System — manajemen karyawan, struktur organisasi, dan penggajian.',
                'is_active'   => true,
            ],
            [
                'slug'        => 'task_management',
                'name'        => 'Task Management',
                'description' => 'Manajemen tugas, proyek, sprint, dan pelacakan waktu.',
                'is_active'   => true,
            ],
        ];

        foreach ($packages as $data) {
            Package::updateOrCreate(['slug' => $data['slug']], $data);
        }

        $this->command->info('✅ Seeded packages: ' . implode(', ', array_column($packages, 'slug')));
    }
}
