<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** Sheet 1 template import absensi — heading + satu baris contoh. */
class AttendanceTemplateSheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    public function headings(): array
    {
        return ['Nama Karyawan', 'Email Karyawan', 'Tanggal', 'Jam Masuk', 'Jam Keluar', 'Status', 'Catatan'];
    }

    public function array(): array
    {
        return [
            ['Budi Santoso', 'budi.santoso@contoh.com', '2024-01-15', '08:00', '17:00', 'Hadir', ''],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }

    public function title(): string
    {
        return 'Template';
    }
}
