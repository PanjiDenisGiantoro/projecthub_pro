<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendancesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public const STATUS_LABELS = [
        'hadir' => 'Hadir',
        'alpha' => 'Alpha',
        'izin'  => 'Izin',
        'sakit' => 'Sakit',
        'cuti'  => 'Cuti',
        'libur' => 'Libur',
    ];

    /** @param Collection $attendances Attendance records with 'user' relation eager-loaded */
    public function __construct(private Collection $attendances) {}

    public function collection(): Collection
    {
        return $this->attendances;
    }

    public function headings(): array
    {
        return ['Nama Karyawan', 'Email Karyawan', 'Tanggal', 'Jam Masuk', 'Jam Keluar', 'Status', 'Catatan'];
    }

    public function map($attendance): array
    {
        return [
            $attendance->user?->name,
            $attendance->user?->email,
            $attendance->date?->format('Y-m-d'),
            $attendance->check_in,
            $attendance->check_out,
            self::STATUS_LABELS[$attendance->status] ?? $attendance->status,
            $attendance->notes,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }

    public function title(): string
    {
        return 'Data Absensi';
    }
}
