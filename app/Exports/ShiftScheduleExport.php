<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;

class ShiftScheduleExport implements FromView, WithColumnWidths, WithTitle
{
    /** @param array $data Hasil AbsensiController::scheduleData() + 'companyName' */
    public function __construct(private array $data) {}

    public function view(): View
    {
        return view('hris.absensi.schedule-export', $this->data + ['forPdf' => false]);
    }

    public function columnWidths(): array
    {
        $widths = ['A' => 28];
        for ($i = 2; $i <= $this->data['daysInMonth'] + 1; $i++) {
            $widths[\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i)] = 7;
        }

        return $widths;
    }

    public function title(): string
    {
        return 'Jadwal Shift';
    }
}
