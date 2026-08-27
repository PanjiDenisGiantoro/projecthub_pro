<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        // Migrasi data lama: slug 'starter' diganti jadi 'free' (lihat gambar paket baru).
        if (Package::where('slug', 'starter')->exists() && ! Package::where('slug', 'free')->exists()) {
            Package::where('slug', 'starter')->update(['slug' => 'free']);
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

            // Kartu harga (pricing tier) — ditampilkan di landing page & halaman daftar
            [
                'slug'          => 'free',
                'type'          => 'tier',
                'name'          => 'Free',
                'description'   => 'Untuk individu atau tim kecil yang baru mulai menjajal Flovig.',
                'tagline'       => 'Untuk individu atau tim kecil yang baru mulai.',
                'price'         => 0,
                'price_display' => 'Gratis',
                'price_period'  => 'Selamanya',
                'duration_days' => null,
                'max_users'     => 4,
                'is_active'     => true,
                'is_popular'    => false,
                'cta_label'     => 'Mulai Gratis',
                'cta_type'      => 'register',
                'sort_order'    => 1,
                'icon'          => 'paper-plane',
                'color'         => 'blue',
                'fitur_text'    => 'Akses semua fitur Free',
                'hris_feature'  => 'HRIS Basic (employee information)',
            ],
            [
                'slug'          => 'basic',
                'type'          => 'tier',
                'name'          => 'Basic',
                'description'   => 'Untuk tim kecil yang mulai butuh kolaborasi lebih rapi.',
                'tagline'       => 'Untuk tim kecil yang mulai berkembang.',
                'price'         => 149999,
                'price_display' => 'Rp 149,999',
                'price_period'  => '/ package',
                'duration_days' => 30,
                'max_users'     => 8,
                'is_active'     => true,
                'is_popular'    => false,
                'cta_label'     => 'Pilih Basic',
                'cta_type'      => 'register',
                'sort_order'    => 2,
                'icon'          => 'rocket',
                'color'         => 'green',
                'fitur_text'    => 'Akses semua fitur Free',
                'hris_feature'  => 'HRIS Standard (Database employee, Organization Chart)',
            ],
            [
                'slug'          => 'standard',
                'type'          => 'tier',
                'name'          => 'Standard',
                'description'   => 'Untuk tim yang butuh visibilitas kinerja lebih lengkap.',
                'tagline'       => 'Untuk tim yang sedang berkembang.',
                'price'         => 549999,
                'price_display' => 'Rp 549,999',
                'price_period'  => '/ package',
                'duration_days' => 30,
                'max_users'     => 30,
                'is_active'     => true,
                'is_popular'    => false,
                'cta_label'     => 'Pilih Standard',
                'cta_type'      => 'register',
                'sort_order'    => 3,
                'icon'          => 'bar-chart',
                'color'         => 'sky',
                'fitur_text'    => 'Akses semua fitur Standard',
                'hris_feature'  => 'HRIS Premium (Database employee, Organization Chart, Attendance, Leave)',
            ],
            [
                'slug'          => 'premium',
                'type'          => 'tier',
                'name'          => 'Premium',
                'description'   => 'Untuk tim menengah yang butuh HRIS & training lebih lengkap.',
                'tagline'       => 'Paling populer untuk tim menengah.',
                'price'         => 1599999,
                'price_display' => 'Rp 1,599,999',
                'price_period'  => '/ package',
                'duration_days' => 30,
                'max_users'     => 70,
                'is_active'     => true,
                'is_popular'    => true,
                'cta_label'     => 'Pilih Premium',
                'cta_type'      => 'register',
                'sort_order'    => 4,
                'icon'          => 'trophy',
                'color'         => 'purple',
                'fitur_text'    => 'Akses semua fitur Premium',
                'hris_feature'  => 'HRIS Pro (Database employee, Organization Chart, Attendance, Leave, Training)',
            ],
            [
                'slug'          => 'pro',
                'type'          => 'tier',
                'name'          => 'Pro',
                'description'   => 'Untuk tim besar yang sedang berkembang. Mencakup semua modul (HRIS & Task Management), project unlimited, CRM & Invoice, Bug tracker + SLA, chat real-time, priority support.',
                'tagline'       => 'Untuk tim besar yang sedang berkembang.',
                'price'         => 2599999,
                'price_display' => 'Rp 2,599,999',
                'price_period'  => '/ package',
                'duration_days' => 30,
                'max_users'     => 100,
                'is_active'     => true,
                'is_popular'    => false,
                'cta_label'     => 'Pilih Pro',
                'cta_type'      => 'register',
                'sort_order'    => 5,
                'icon'          => 'crown',
                'color'         => 'orange',
                'fitur_text'    => 'Akses semua fitur Pro',
                'hris_feature'  => 'HRIS Pro Plus',
            ],
            [
                'slug'          => 'enterprise',
                'type'          => 'tier',
                'name'          => 'Enterprise',
                'description'   => 'Untuk organisasi besar dengan kebutuhan kustom.',
                'tagline'       => 'Untuk organisasi besar.',
                'price'         => null,
                'price_display' => 'Contact Sales',
                'price_period'  => 'Custom Price',
                'duration_days' => null,
                'max_users'     => null,
                'is_active'     => true,
                'is_popular'    => false,
                'cta_label'     => 'Hubungi Sales',
                'cta_type'      => 'contact',
                'sort_order'    => 6,
                'icon'          => 'building',
                'color'         => 'navy',
                'fitur_text'    => 'Akses semua fitur',
                'hris_feature'  => 'HRIS Pro Plus (Advanced & Custom Solution)',
            ],
        ];

        foreach ($packages as $data) {
            Package::updateOrCreate(['slug' => $data['slug']], $data);
        }

        $features = [
            'free' => [
                '4 pengguna',
                'HRIS Basic (employee information)',
                'Unlimited storage',
                'Support intensif',
                'iOS, Web & Android',
                'AI',
            ],
            'basic' => [
                '8 pengguna',
                'HRIS Standard (Database employee, Organization Chart)',
                'Unlimited storage',
                'Support intensif',
                'iOS, Web & Android',
                'AI',
            ],
            'standard' => [
                '30 pengguna',
                'HRIS Premium (Database employee, Organization Chart, Attendance, Leave)',
                'Unlimited storage',
                'Support intensif',
                'iOS, Web & Android',
                'AI',
            ],
            'premium' => [
                '70 pengguna',
                'HRIS Pro (Database employee, Organization Chart, Attendance, Leave, Training)',
                'Unlimited storage',
                'Support intensif',
                'iOS, Web & Android',
                'AI',
            ],
            'pro' => [
                '100 pengguna',
                'HRIS Pro Plus',
                'Unlimited storage',
                'Support intensif',
                'iOS, Web & Android',
                'AI',
            ],
            'enterprise' => [
                'Pengguna custom',
                'HRIS Pro Plus (Advanced & Custom Solution)',
                'Unlimited storage',
                'Support intensif',
                'iOS, Web & Android',
                'AI',
                'SLA & dedicated manager',
            ],
        ];

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
