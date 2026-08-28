<?php

namespace App\Exports;

use App\Exports\Sheets\UsersImportGuideSheet;
use App\Exports\Sheets\UsersTemplateSheet;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class UsersImportTemplateExport implements WithMultipleSheets
{
    public function __construct(
        private Collection $customFields,
        private Collection $roles,
        private Collection $organizationUnits,
        private Collection $structuralLevels,
    ) {}

    public function sheets(): array
    {
        return [
            new UsersTemplateSheet($this->customFields),
            new UsersImportGuideSheet($this->customFields, $this->roles, $this->organizationUnits, $this->structuralLevels),
        ];
    }
}
