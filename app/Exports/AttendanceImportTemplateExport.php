<?php

namespace App\Exports;

use App\Exports\Sheets\AttendanceImportGuideSheet;
use App\Exports\Sheets\AttendanceTemplateSheet;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AttendanceImportTemplateExport implements WithMultipleSheets
{
    /** @param Collection $employees User records (id, name, email) milik company */
    public function __construct(private Collection $employees) {}

    public function sheets(): array
    {
        return [
            new AttendanceTemplateSheet(),
            new AttendanceImportGuideSheet($this->employees),
        ];
    }
}
