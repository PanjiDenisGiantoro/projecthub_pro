<?php

namespace App\Exports;

use App\Exports\Sheets\ShiftScheduleGuideSheet;
use App\Exports\Sheets\ShiftScheduleTemplateSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/** Template upload jadwal shift bulanan — sheet grid jadwal + sheet petunjuk & daftar kode shift. */
class ShiftScheduleTemplateExport implements WithMultipleSheets
{
    /** @param array $data Hasil AbsensiController::scheduleData() */
    public function __construct(private array $data) {}

    public function sheets(): array
    {
        return [
            new ShiftScheduleTemplateSheet($this->data),
            new ShiftScheduleGuideSheet($this->data['shifts']),
        ];
    }
}
