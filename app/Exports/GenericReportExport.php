<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GenericReportExport implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    /**
     * @param array<string,string> $columns key => label, defines column order
     * @param Collection $rows assoc arrays keyed like $columns
     */
    public function __construct(
        private array $columns,
        private Collection $rows,
        private string $title,
    ) {}

    public function collection(): Collection
    {
        $keys = array_keys($this->columns);

        return $this->rows->map(fn ($row) => array_map(fn ($key) => $row[$key] ?? '', $keys));
    }

    public function headings(): array
    {
        return array_values($this->columns);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return $this->title;
    }
}
