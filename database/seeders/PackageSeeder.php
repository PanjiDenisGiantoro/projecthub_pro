<?php

namespace Database\Seeders;

use App\Models\Package;
use App\Support\PricingCatalog;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        // Migrasi data lama: slug 'starter' diganti jadi 'free' (lihat gambar paket baru).
        if (Package::where('slug', 'starter')->exists() && ! Package::where('slug', 'free')->exists()) {
            Package::where('slug', 'starter')->update(['slug' => 'free']);
        }
        foreach (['basic' => 'starter', 'standard' => 'team', 'premium' => 'business'] as $old => $new) {
            if (! Package::where('slug', $new)->exists()) {
                Package::where('slug', $old)->update(['slug' => $new]);
            }
        }

        $packages = [
            // Modul add-on (bukan kartu harga di landing/register)
            [
                'slug'          => 'hris',
                'type'          => 'module',
                'name'          => 'HRIS',
                'description'   => 'Human Resource Information System — manajemen karyawan, struktur organisasi, dan penggajian.',
                'price'         => 500000,
                'duration_days' => 30,
                'is_active'     => true,
            ],
            [
                'slug'          => 'task_management',
                'type'          => 'module',
                'name'          => 'Task Management',
                'description'   => 'Manajemen tugas, proyek, sprint, dan pelacakan waktu.',
                'price'         => 300000,
                'duration_days' => 30,
                'is_active'     => true,
            ],
        ];

        // Kartu harga (pricing tier) per user — ditampilkan di landing page & halaman daftar
        foreach (PricingCatalog::tiers() as $slug => $data) {
            $packages[] = ['slug' => $slug, 'type' => 'tier', 'is_active' => true] + $data;
        }

        foreach ($packages as $data) {
            Package::updateOrCreate(['slug' => $data['slug']], $data);
        }

        $features = PricingCatalog::features();

        foreach ($features as $slug => $labels) {
            $package = Package::where('slug', $slug)->first();
            $package->features()->delete();
            foreach ($labels as $i => $label) {
                $package->features()->create(['label' => $label, 'sort_order' => $i]);
            }
        }

        $this->command->info('✅ Seeded packages: ' . implode(', ', array_column($packages, 'slug')));
    }
}
