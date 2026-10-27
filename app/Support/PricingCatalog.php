<?php

namespace App\Support;

/**
 * Isi paket harga FLOVIG PRICE (per user). Dipakai migration 2026_10_14_100000 dan PackageSeeder.
 */
class PricingCatalog
{
    /** Data paket tier per slug (kolom tabel packages). */
    public static function tiers(): array
    {
        $common = ['price_period' => '/ user / bulan', 'duration_days' => 30, 'cta_type' => 'register'];

        return [
            'free' => [
                'name' => 'Free', 'tagline' => 'Mulai gratis hingga 8 user.',
                'description' => 'Untuk tim kecil yang baru mulai menjajal Flovig.',
                'price' => 0, 'price_yearly' => 0, 'price_display' => 'Gratis', 'price_period' => 'Selamanya',
                'duration_days' => null, 'min_users' => 1, 'max_users' => 8, 'max_workspaces' => 1, 'max_projects' => 3,
                'storage_label' => '1 GB', 'has_android' => false, 'has_ios' => false, 'support_label' => 'Help Center',
                'cta_label' => 'Mulai Gratis', 'cta_type' => 'register', 'is_popular' => false, 'sort_order' => 1,
            ],
            'starter' => $common + [
                'name' => 'Starter', 'tagline' => 'Untuk tim 9–19 orang.',
                'description' => 'Untuk tim kecil yang mulai butuh kolaborasi lebih rapi.',
                'price' => 29900, 'price_yearly' => 20900, 'price_display' => null,
                'min_users' => 9, 'max_users' => 19, 'max_workspaces' => 3, 'max_projects' => 10,
                'storage_label' => '25 GB', 'has_android' => true, 'has_ios' => false, 'support_label' => 'Business Hours',
                'cta_label' => 'Pilih Starter', 'is_popular' => false, 'sort_order' => 2,
            ],
            'team' => $common + [
                'name' => 'Team', 'tagline' => 'Untuk tim 20–49 orang.',
                'description' => 'Untuk tim yang sedang berkembang.',
                'price' => 27900, 'price_yearly' => 19500, 'price_display' => null,
                'min_users' => 20, 'max_users' => 49, 'max_workspaces' => 5, 'max_projects' => 25,
                'storage_label' => '75 GB', 'has_android' => true, 'has_ios' => true, 'support_label' => 'Priority Support',
                'cta_label' => 'Pilih Team', 'is_popular' => true, 'sort_order' => 3,
            ],
            'business' => $common + [
                'name' => 'Business', 'tagline' => 'Untuk tim 50–99 orang.',
                'description' => 'Untuk tim menengah dengan banyak project.',
                'price' => 24900, 'price_yearly' => 17400, 'price_display' => null,
                'min_users' => 50, 'max_users' => 99, 'max_workspaces' => 10, 'max_projects' => 50,
                'storage_label' => '250 GB', 'has_android' => true, 'has_ios' => true, 'support_label' => 'Priority + SLA',
                'cta_label' => 'Pilih Business', 'is_popular' => false, 'sort_order' => 4,
            ],
            'pro' => $common + [
                'name' => 'Pro', 'tagline' => 'Untuk tim 100–199 orang.',
                'description' => 'Untuk organisasi besar yang sedang berkembang.',
                'price' => 21900, 'price_yearly' => 15300, 'price_display' => null,
                'min_users' => 100, 'max_users' => 199, 'max_workspaces' => null, 'max_projects' => null,
                'storage_label' => '1 TB', 'has_android' => true, 'has_ios' => true, 'support_label' => 'Dedicated Support',
                'cta_label' => 'Pilih Pro', 'is_popular' => false, 'sort_order' => 5,
            ],
            'enterprise' => [
                'name' => 'Enterprise', 'tagline' => 'Untuk organisasi 200+ orang.',
                'description' => 'Untuk organisasi besar dengan kebutuhan kustom.',
                'price' => null, 'price_yearly' => null, 'price_display' => 'Custom', 'price_period' => 'Hubungi sales',
                'duration_days' => null, 'min_users' => 200, 'max_users' => null, 'max_workspaces' => null, 'max_projects' => null,
                'storage_label' => 'Custom', 'has_android' => true, 'has_ios' => true, 'support_label' => 'Dedicated Support + SLA',
                'cta_label' => 'Hubungi Sales', 'cta_type' => 'contact', 'is_popular' => false, 'sort_order' => 6,
            ],
        ];
    }

    /** Ringkasan fitur per kartu (landing page). Tabel lengkap di /daftar dibangun dari kolom kuota. */
    public static function features(): array
    {
        return [
            'free'       => ['Hingga 8 user', '1 workspace', '3 project', 'Storage 1 GB', 'Web', 'AI', 'Help Center'],
            'starter'    => ['9–19 user', '3 workspace', '10 project', 'Storage 25 GB', 'Web & Android', 'AI', 'Support jam kerja'],
            'team'       => ['20–49 user', '5 workspace', '25 project', 'Storage 75 GB', 'Web, iOS & Android', 'AI', 'Priority support'],
            'business'   => ['50–99 user', '10 workspace', '50 project', 'Storage 250 GB', 'Web, iOS & Android', 'AI', 'Priority support + SLA'],
            'pro'        => ['100–199 user', 'Workspace unlimited', 'Project unlimited', 'Storage 1 TB', 'Web, iOS & Android', 'AI', 'Dedicated support'],
            'enterprise' => ['200+ user', 'Workspace unlimited', 'Project unlimited', 'Storage custom', 'Web, iOS & Android', 'AI', 'Dedicated support + SLA'],
        ];
    }
}
