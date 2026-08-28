<?php

namespace App\Exports\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** Sheet 1 template import — heading + satu baris contoh, siap diisi admin. */
class UsersTemplateSheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    public function __construct(private Collection $customFields) {}

    public function headings(): array
    {
        return [
            'Nama', 'Email', 'Role', 'Departemen/Unit', 'Level Struktural',
            'Tipe Karyawan', 'Tipe Karyawan (Lainnya)', 'Nama Perusahaan Asal',
            'Tanggal Bergabung', 'Tanggal Akhir Kontrak', 'Status Aktif',
            ...$this->customFields->pluck('label')->all(),
        ];
    }

    public function array(): array
    {
        $example = [
            'Budi Santoso', 'budi.santoso@contoh.com', 'Member', 'Engineering', 'Staff',
            'Tetap', '', '', '2024-01-15', '', 'Aktif',
        ];

        foreach ($this->customFields as $field) {
            $example[] = match ($field->type) {
                'checkbox' => 'Tidak',
                'select'   => $field->options[0] ?? '',
                default    => '',
            };
        }

        return [$example];
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
