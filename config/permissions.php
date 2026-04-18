<?php

/**
 * Permission registry — single source of truth.
 * Groups are displayed as sections in the permission matrix UI.
 * Key = permission name (stored in DB), Value = human-readable label.
 */
return [

    'Menu Akses' => [
        'access dashboard'         => 'Dashboard',
        'access projects'          => 'Proyek',
        'access tickets'           => 'Bug Tickets',
        'access requests'          => 'Customer Requests',
        'access campaigns'         => 'Campaigns & Marketing',
        'access invoices'          => 'Invoice',
        'access calendar'          => 'Kalender',
        'access search'            => 'Global Search',
        'access templates'         => 'Templates',
        'access workload'          => 'Workload',
        'access analytics'         => 'Analytics',
        'access users'             => 'User Management',
        'access master data'       => 'Master Data',
        'access approvals'         => 'Approvals',
        'access approval policies' => 'Approval Policies',
        'access kb'                => 'Knowledge Base',
        'access sprints'           => 'Sprint',
        'access budget'            => 'Budget',
        'access risks'             => 'Risiko & Issues',
    ],

    'Proyek' => [
        'create project'         => 'Buat Proyek',
        'edit project'           => 'Edit Proyek',
        'delete project'         => 'Hapus Proyek',
        'manage project members' => 'Kelola Anggota Proyek',
    ],

    'Bug Tickets' => [
        'create ticket'    => 'Buat Tiket',
        'assign ticket'    => 'Assign Tiket ke Developer',
        'close ticket'     => 'Tutup/Resolve Tiket',
        'view all tickets' => 'Lihat Semua Tiket (bukan hanya milik sendiri)',
        'manage tickets'   => 'Kelola Tiket (edit, bulk, merge, linked)',
    ],

    'Customer Requests' => [
        'create request'  => 'Buat Request',
        'approve request' => 'Approve / Reject Request',
    ],

    'Campaigns & Marketing' => [
        'manage campaigns' => 'Kelola Campaign & Lead',
    ],

    'Invoice' => [
        'manage invoices' => 'Buat, Edit, Kirim, Mark Paid Invoice',
    ],

    'Approvals' => [
        'decide approvals'         => 'Setujui / Tolak Approval',
        'manage approval policies' => 'Kelola Master Approval Policy',
    ],

    'Admin & Master Data' => [
        'manage users'       => 'Kelola Users (tambah, edit, hapus)',
        'manage master data' => 'Kelola Master Data (perusahaan, divisi, dll)',
        'manage permissions' => 'Kelola Permission per Role (halaman ini)',
    ],
];
