<?php

namespace App\Exports;

use App\Exports\Sheets\UsersImportGuideSheet;
use App\Exports\Sheets\UsersImportListsSheet;
use App\Exports\Sheets\UsersTemplateSheet;
use App\Support\EmploymentType;
use App\Support\RoleLabel;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
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
        [$lists, $dropdowns] = $this->dropdowns();

        return [
            new UsersTemplateSheet($this->customFields, $dropdowns, [
                'role'  => RoleLabel::for($this->roles->firstWhere('name', 'member')?->name ?? $this->roles->first()?->name ?? 'member'),
                'unit'  => $this->organizationUnits->first()?->name ?? '',
                'level' => $this->structuralLevels->first()?->name ?? '',
            ]),
            new UsersImportGuideSheet($this->customFields, $this->roles, $this->organizationUnits, $this->structuralLevels),
            new UsersImportListsSheet($lists),
        ];
    }

    /**
     * Pilihan dropdown per kolom template. Nilainya sama persis dengan yang dikenali
     * UsersImport (label role, nama unit/level, label tipe karyawan, Aktif/Nonaktif, Ya/Tidak).
     *
     * @return array{0: array<int,array<int,string>>, 1: array<string,string>} [isi sheet Daftar, kolom template => rumus sumber dropdown]
     */
    private function dropdowns(): array
    {
        // Kolom di UsersTemplateSheet::headings() => pilihan.
        $options = [
            'C' => $this->roles->map(fn ($r) => RoleLabel::for($r->name))->values()->all(),
            'D' => $this->organizationUnits->pluck('name')->values()->all(),
            'E' => $this->structuralLevels->pluck('name')->values()->all(),
            'F' => array_values(EmploymentType::LABELS),
            'K' => ['Aktif', 'Nonaktif'],
        ];

        foreach ($this->customFields->values() as $i => $field) {
            $column = Coordinate::stringFromColumnIndex(UsersTemplateSheet::FIXED_COLUMNS + $i + 1);
            $options[$column] = match ($field->type) {
                'checkbox' => ['Ya', 'Tidak'],
                'select'   => array_values($field->options ?? []),
                default    => [],
            };
        }

        $lists     = [];
        $dropdowns = [];
        foreach (array_filter($options) as $templateColumn => $values) {
            $listColumn = Coordinate::stringFromColumnIndex(count($lists) + 1);
            $lists[]    = array_map('strval', $values);
            $dropdowns[$templateColumn] = "'" . UsersImportListsSheet::TITLE . "'!\${$listColumn}\$1:\${$listColumn}\$" . count($values);
        }

        return [$lists, $dropdowns];
    }
}
