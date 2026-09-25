<?php

namespace App\Exports\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** Sheet 1 template import — heading + satu baris contoh, siap diisi admin, dengan dropdown di kolom pilihan. */
class UsersTemplateSheet implements FromArray, WithEvents, WithHeadings, WithStyles, WithTitle
{
    /** Jumlah kolom tetap (Nama .. Status Aktif) sebelum kolom field kustom. */
    public const FIXED_COLUMNS = 11;

    /** Dropdown dipasang sampai baris ini (baris 1 = heading). */
    private const LAST_ROW = 1000;

    /**
     * @param array<string,string> $dropdowns Kolom (mis. "C") => rumus range sumber pilihan di sheet Daftar
     * @param array<int,string>    $example   Nilai baris contoh buat kolom Role, Departemen/Unit, Level Struktural
     *                                        (diambil dari data company biar contohnya lolos import)
     */
    public function __construct(private Collection $customFields, private array $dropdowns = [], private array $example = []) {}

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
            'Budi Santoso', 'budi.santoso@contoh.com',
            $this->example['role'] ?? 'Member', $this->example['unit'] ?? '', $this->example['level'] ?? '',
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

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                foreach ($this->dropdowns as $column => $source) {
                    $validation = $sheet->getCell("{$column}2")->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST)
                        ->setErrorStyle(DataValidation::STYLE_STOP)
                        ->setAllowBlank(true)
                        ->setShowDropDown(true)
                        ->setShowErrorMessage(true)
                        ->setErrorTitle('Nilai tidak valid')
                        ->setError('Pilih salah satu nilai dari daftar.')
                        ->setFormula1($source);
                    $validation->setSqref("{$column}2:{$column}" . self::LAST_ROW);
                }

                foreach ($sheet->getColumnIterator() as $column) {
                    $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
                }
                $sheet->freezePane('A2');
            },
        ];
    }

    public function title(): string
    {
        return 'Template';
    }
}
