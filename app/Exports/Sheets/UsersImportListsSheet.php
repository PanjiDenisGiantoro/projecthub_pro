<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet tersembunyi berisi daftar nilai valid, 1 daftar per kolom — jadi sumber
 * dropdown di sheet Template (lihat UsersTemplateSheet). Disimpan terpisah karena
 * dropdown Excel dari teks langsung dibatasi 255 karakter & tidak boleh ada koma.
 */
class UsersImportListsSheet implements FromArray, WithEvents, WithTitle
{
    public const TITLE = 'Daftar';

    /** @param array<int,array<int,string>> $lists Daftar nilai, urutan index = urutan kolom di sheet ini */
    public function __construct(private array $lists) {}

    public function array(): array
    {
        $rows = [];
        $max  = max(array_map('count', $this->lists) ?: [0]);
        for ($i = 0; $i < $max; $i++) {
            $rows[] = array_map(fn ($list) => $list[$i] ?? null, $this->lists);
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => fn (AfterSheet $event) => $event->sheet->getDelegate()->setSheetState(Worksheet::SHEETSTATE_HIDDEN),
        ];
    }

    public function title(): string
    {
        return self::TITLE;
    }
}
