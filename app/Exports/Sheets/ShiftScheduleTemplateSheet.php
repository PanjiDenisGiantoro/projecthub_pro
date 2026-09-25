<?php

namespace App\Exports\Sheets;

use App\Exports\Sheets\ShiftScheduleGuideSheet;
use App\Imports\ShiftScheduleImport;
use App\Models\Shift;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Sheet 1 template upload jadwal shift: baris 1 = periode (dibaca ulang saat import),
 * baris 2 = header tanggal, baris 3+ = 1 karyawan per baris. Sel yang sudah punya
 * override jadwal diisi (nama shift / LIBUR), sisanya kosong = ikut shift default.
 */
class ShiftScheduleTemplateSheet implements FromArray, WithEvents, WithTitle
{
    public function __construct(private array $data) {}

    public function array(): array
    {
        ['employees' => $employees, 'grid' => $grid, 'start' => $start, 'daysInMonth' => $days, 'holidays' => $holidays] = $this->data;

        $header = ['Email', 'Nama'];
        for ($d = 1; $d <= $days; $d++) {
            $header[] = $d . ' ' . Shift::DAY_LABELS[$start->copy()->day($d)->dayOfWeek];
        }

        $rows = [
            ['Periode', $start->format('Y-m'), 'Jadwal Shift ' . $start->locale('id')->isoFormat('MMMM Y') . ' — jangan ubah baris 1 & 2. Sel kosong = tidak diubah. Lihat sheet Petunjuk.'],
            $header,
        ];

        foreach ($employees as $emp) {
            $row = [$emp->email, $emp->name];
            for ($d = 1; $d <= $days; $d++) {
                $cell  = $grid[$emp->id][$d];
                $row[] = match (true) {
                    !$cell['is_override']    => null,
                    $cell['mode'] === 'off'  => ShiftScheduleImport::CODE_OFF,
                    default                  => $this->data['shifts']->firstWhere('id', $cell['shift_id'])?->name,
                };
            }
            $rows[] = $row;
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $days    = $this->data['daysInMonth'];
                $lastCol = Coordinate::stringFromColumnIndex($days + 2);
                $lastRow = max(3, $this->data['employees']->count() + 2);

                // Periode disimpan sebagai teks biar Excel tidak mengubahnya jadi tanggal.
                $sheet->setCellValueExplicit('B1', $this->data['start']->format('Y-m'), DataType::TYPE_STRING);
                $sheet->getStyle('A1:B1')->getFont()->setBold(true);
                $sheet->getStyle('C1')->getFont()->setItalic(true)->getColor()->setRGB('6B7280');
                $sheet->getStyle("A2:{$lastCol}2")->getFont()->setBold(true);
                $sheet->getStyle("A2:{$lastCol}2")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F5F3FF');

                $sheet->getColumnDimension('A')->setWidth(30);
                $sheet->getColumnDimension('B')->setWidth(24);
                for ($i = 3; $i <= $days + 2; $i++) {
                    $col  = Coordinate::stringFromColumnIndex($i);
                    $date = $this->data['start']->copy()->day($i - 2);
                    $sheet->getColumnDimension($col)->setWidth(12);

                    $holiday = $this->data['holidays']->get($date->toDateString());
                    if ($holiday || in_array($date->dayOfWeek, [0, 6], true)) {
                        $sheet->getStyle("{$col}2")->getFont()->getColor()->setRGB('DC2626');
                    }
                    if ($holiday) {
                        $sheet->getStyle("{$col}2")->getFill()->getStartColor()->setRGB('FEE2E2');
                        $sheet->getComment("{$col}2")->getText()->createText('Hari libur: ' . $holiday->name);
                    }
                }
                $sheet->freezePane('C3');

                // Dropdown pilihan kode (nama shift / LIBUR / DEFAULT) dari sheet Petunjuk.
                $codeCount  = $this->data['shifts']->count() + 2;
                $validation = $sheet->getCell('C3')->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST)
                    ->setErrorStyle(DataValidation::STYLE_STOP)
                    ->setAllowBlank(true)
                    ->setShowDropDown(true)
                    ->setShowErrorMessage(true)
                    ->setErrorTitle('Kode tidak valid')
                    ->setError('Pilih nama shift, LIBUR, atau DEFAULT dari daftar.')
                    ->setFormula1("'" . ShiftScheduleGuideSheet::TITLE . "'!\$A\$" . ShiftScheduleGuideSheet::CODES_START_ROW
                        . ':$A$' . (ShiftScheduleGuideSheet::CODES_START_ROW + $codeCount - 1));
                $validation->setSqref("C3:{$lastCol}{$lastRow}");
            },
        ];
    }

    public function title(): string
    {
        return 'Jadwal';
    }
}
