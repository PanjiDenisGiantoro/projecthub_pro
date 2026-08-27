<?php

/**
 * Report registry — single source of truth for the Reports module.
 * Key = URL segment (/reports/{key}), 'query' = class implementing
 * App\Support\Reports\ReportQuery. Adding a new report = add an entry
 * here + a small query class, no new route/controller/view/export.
 */
return [

    'projects' => [
        'label'       => 'Proyek',
        'description' => 'Daftar proyek, klien, manager, dan status.',
        'category'    => 'Proyek & Tugas',
        'query'       => \App\Support\Reports\ProjectsReport::class,
    ],

    'project_tasks' => [
        'label'       => 'Tugas Proyek',
        'description' => 'Daftar tugas per proyek, status, dan tenggat waktu.',
        'category'    => 'Proyek & Tugas',
        'query'       => \App\Support\Reports\ProjectTasksReport::class,
    ],

    'tickets' => [
        'label'       => 'Tiket & SLA',
        'description' => 'Daftar bug ticket beserta status, prioritas, dan SLA.',
        'category'    => 'Tiket & Dukungan',
        'query'       => \App\Support\Reports\TicketsReport::class,
    ],

    'customer_requests' => [
        'label'       => 'Customer Requests',
        'description' => 'Daftar permintaan customer, tipe, prioritas, dan status.',
        'category'    => 'Tiket & Dukungan',
        'query'       => \App\Support\Reports\CustomerRequestsReport::class,
    ],

    'campaigns' => [
        'label'       => 'Campaigns',
        'description' => 'Daftar campaign marketing, channel, anggaran, dan leads.',
        'category'    => 'Marketing',
        'query'       => \App\Support\Reports\CampaignsReport::class,
    ],

    'invoices' => [
        'label'       => 'Invoice',
        'description' => 'Daftar invoice, status pembayaran, dan nominal.',
        'category'    => 'Keuangan',
        'query'       => \App\Support\Reports\InvoicesReport::class,
    ],

    'budget' => [
        'label'       => 'Anggaran Proyek',
        'description' => 'Pemasukan & pengeluaran anggaran per proyek.',
        'category'    => 'Keuangan',
        'query'       => \App\Support\Reports\BudgetReport::class,
    ],

    'hris_attendance' => [
        'label'       => 'Absensi',
        'description' => 'Rekap kehadiran karyawan — jam masuk, jam keluar, dan status.',
        'category'    => 'HRIS',
        'permission'  => 'view absensi',
        'query'       => \App\Support\Reports\HrisAttendanceReport::class,
    ],

    'hris_leave' => [
        'label'       => 'Cuti & Izin',
        'description' => 'Riwayat pengajuan cuti/izin, jenis, dan status persetujuan.',
        'category'    => 'HRIS',
        'permission'  => 'view leave',
        'query'       => \App\Support\Reports\HrisLeaveReport::class,
    ],

    'hris_overtime' => [
        'label'       => 'Lembur',
        'description' => 'Riwayat pengajuan lembur, total jam, dan nominal upah.',
        'category'    => 'HRIS',
        'permission'  => 'view overtime',
        'query'       => \App\Support\Reports\HrisOvertimeReport::class,
    ],

    'hris_reimbursement' => [
        'label'       => 'Reimburse',
        'description' => 'Riwayat pengajuan reimburse, kategori, dan status.',
        'category'    => 'HRIS',
        'permission'  => 'view reimbursement',
        'query'       => \App\Support\Reports\HrisReimbursementReport::class,
    ],

    'hris_bonus' => [
        'label'       => 'Bonus / THR',
        'description' => 'Riwayat bonus, THR, dan komponen non-gaji pokok lainnya.',
        'category'    => 'HRIS',
        'permission'  => 'view payroll',
        'query'       => \App\Support\Reports\HrisBonusReport::class,
    ],

    'hris_kasbon' => [
        'label'       => 'Kasbon',
        'description' => 'Riwayat pinjaman karyawan, cicilan, dan sisa tanggungan.',
        'category'    => 'HRIS',
        'permission'  => 'view payroll',
        'query'       => \App\Support\Reports\HrisKasbonReport::class,
    ],

    'hris_payroll' => [
        'label'       => 'Payroll',
        'description' => 'Rekap gaji bulanan karyawan — bruto, potongan, dan gaji bersih.',
        'category'    => 'HRIS',
        'permission'  => 'view payroll',
        'query'       => \App\Support\Reports\HrisPayrollReport::class,
    ],

];
