<?php

/**
 * Permission registry — single source of truth.
 * Groups are displayed as sections in the permission matrix UI.
 * Key = permission name (stored in DB), Value = human-readable label.
 *
 * Pola granular per modul: view/access (lihat), create (buat), update (ubah),
 * delete (hapus). Karyawan selalu boleh ajukan/batalkan cuti/lembur/reimburse
 * miliknya sendiri tanpa izin khusus (self-service). "update X"/"delete X" di
 * modul Cuti & Izin, Lembur, dan Reimburse adalah kapasitas ADMIN/HR untuk
 * mengelola data milik siapapun: "update X" hanya berlaku selagi status masih
 * Pending (lihat LeaveController/OvertimeController/ReimbursementController),
 * "delete X" berlaku di semua status dan membalikkan efek samping bila
 * datanya sudah approved (saldo cuti, absensi otomatis).
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
        'access meetings'          => 'Meeting',
        'access search'            => 'Global Search',
        'access templates'         => 'Templates',
        'access workload'          => 'Workload',
        'access analytics'         => 'Analytics',
        'access reports'           => 'Laporan',
        'access users'             => 'User Management',
        'access master data'       => 'Master Data',
        'access approvals'         => 'Approvals',
        'access approval policies' => 'Approval Policies',
        'access kb'                => 'Knowledge Base',
        'access sprints'           => 'Sprint',
        'access budget'            => 'Budget',
        'access risks'             => 'Risiko & Issues',
        'access clients'           => 'Manajemen Klien',
        'access billing'           => 'Billing & Langganan',
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
        'create campaign' => 'Buat Campaign & Lead',
        'update campaign' => 'Edit Campaign, Lead & Metrik',
        'delete campaign' => 'Hapus Campaign & Lead',
    ],

    'Invoice' => [
        'create invoice' => 'Buat Invoice',
        'update invoice' => 'Kirim & Tandai Lunas Invoice',
    ],

    'Approvals' => [
        'decide approvals'        => 'Setujui / Tolak Approval',
        'create approval policy'  => 'Buat Approval Policy',
        'update approval policy'  => 'Edit / Aktifkan-Nonaktifkan Approval Policy',
        'delete approval policy'  => 'Hapus Approval Policy',
    ],

    'Admin & Master Data' => [
        'create user'         => 'Tambah User',
        'update user'         => 'Edit User',
        'delete user'         => 'Hapus User',
        'import user'         => 'Import Data Karyawan dari Excel',
        'export user'         => 'Export Data Karyawan ke Excel',
        'create master data'  => 'Tambah Perusahaan / Unit Organisasi / Level Struktural',
        'update master data'  => 'Edit Perusahaan / Unit Organisasi / Level Struktural',
        'delete master data'  => 'Hapus Perusahaan / Unit Organisasi / Level Struktural',
        'manage permissions'  => 'Kelola Permission per Role (halaman ini)',
    ],

    'HRIS — Akses' => [
        'access hris' => 'Akses Modul HRIS',
    ],

    'HRIS — Absensi' => [
        'view absensi'           => 'Lihat Rekap Absensi Semua Karyawan',
        'update absensi'         => 'Kelola Pengaturan Absensi (lokasi, wajah)',
        'manage face enrollment' => 'Kelola Pendaftaran Wajah Karyawan',
        'import absensi'         => 'Import Data Absensi dari Excel',
        'export absensi'         => 'Export Data Absensi ke Excel',
    ],

    'HRIS — Cuti & Izin' => [
        'view leave'    => 'Lihat Cuti & Izin Semua Karyawan',
        'update leave'  => 'Edit Cuti & Izin Semua Karyawan (selama masih Pending)',
        'delete leave'  => 'Hapus Data Cuti & Izin Semua Karyawan (semua status)',
        'approve leave' => 'Approve / Reject Cuti',
    ],

    'HRIS — Lembur' => [
        'view overtime'    => 'Lihat Lembur Semua Karyawan',
        'update overtime'  => 'Edit Lembur Semua Karyawan (selama masih Pending)',
        'delete overtime'  => 'Hapus Data Lembur Semua Karyawan (semua status)',
        'approve overtime' => 'Approve / Reject Lembur',
    ],

    'HRIS — Reimburse' => [
        'view reimbursement'    => 'Lihat Reimburse Semua Karyawan',
        'update reimbursement'  => 'Edit Reimburse Semua Karyawan (selama masih Pending)',
        'delete reimbursement'  => 'Hapus Data Reimburse Semua Karyawan (semua status)',
        'approve reimbursement' => 'Approve / Reject Reimburse',
    ],

    'HRIS — Payroll' => [
        'view payroll'   => 'Lihat Payroll & Data Gaji Semua Karyawan',
        'create payroll' => 'Generate Payroll & Tambah Data Gaji',
        'update payroll' => 'Edit Data Gaji & Finalize Payroll',
        'delete payroll' => 'Hapus Data Gaji',
    ],

    'HRIS — Master Data' => [
        'view hris master'   => 'Lihat Master Data HRIS (jenis cuti, aturan lembur, PTKP, tarif PPh21)',
        'create hris master' => 'Tambah / Reset Master Data HRIS',
        'update hris master' => 'Edit / Aktifkan-Nonaktifkan Master Data HRIS',
        'delete hris master' => 'Hapus Master Data HRIS',
    ],
];
