<?php

namespace App\Exports;

use App\Support\EmploymentType;
use App\Support\RoleLabel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UsersExport implements FromCollection, WithEvents, WithHeadings, WithMapping, WithStyles, WithTitle
{
    /** Sheet tersembunyi tempat opsi dropdown yang kepanjangan/ada koma disimpan (dirujuk via named range). */
    private const LIST_SHEET_TITLE = 'DropdownLists';

    private int $nextListColumn = 1;

    /**
     * @param Collection $users
     * @param Collection $customFields Definisi field kustom aktif milik company, urut sesuai tampilan
     * @param Collection $roles Role yang bisa dipilih (buat dropdown kolom Role)
     * @param Collection $organizationUnits Unit organisasi milik company (buat dropdown kolom Departemen/Unit)
     * @param Collection $structuralLevels Level struktural milik company (buat dropdown kolom Level Struktural)
     */
    public function __construct(
        private Collection $users,
        private Collection $customFields,
        private Collection $roles,
        private Collection $organizationUnits = new Collection(),
        private Collection $structuralLevels = new Collection(),
    ) {}

    public function collection(): Collection
    {
        return $this->users;
    }

    public function headings(): array
    {
        return [
            'Nama', 'Email', 'Role', 'Departemen/Unit', 'Level Struktural',
            'Tipe Karyawan', 'Tipe Karyawan (Lainnya)', 'Nama Perusahaan Asal',
            'Tanggal Bergabung', 'Tanggal Akhir Kontrak', 'Status Aktif',
            ...$this->customFields->pluck('label')->all(),
        ];
    }

    public function map($user): array
    {
        $row = [
            $user->name,
            $user->email,
            $user->getRoleNames()->map(fn ($r) => RoleLabel::for($r))->implode(', '),
            $user->organizationUnit?->name,
            $user->structuralLevel?->name,
            EmploymentType::for($user->employment_type),
            $user->employment_type_other,
            $user->outsourcing_company_name,
            $user->hire_date?->format('Y-m-d'),
            $user->contract_end_date?->format('Y-m-d'),
            $user->is_active ? 'Aktif' : 'Nonaktif',
        ];

        foreach ($this->customFields as $field) {
            $value = $user->custom_fields[$field->key] ?? null;
            $row[] = $field->type === 'checkbox' ? ($value ? 'Ya' : 'Tidak') : $value;
        }

        return $row;
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }

    public function title(): string
    {
        return 'Data Karyawan';
    }

    /**
     * Tambah dropdown (data validation) di kolom yang nilainya dari daftar tetap,
     * supaya admin gampang ubah data langsung di Excel tanpa salah ketik.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $lastRow = $this->users->count() + 1;

                if ($lastRow < 2) {
                    return;
                }

                $roleOptions = $this->roles->map(fn ($r) => RoleLabel::for($r->name))->all();

                $this->applyDropdown($sheet, 'C', 2, $lastRow, $roleOptions, 'Role');
                $this->applyDropdown($sheet, 'D', 2, $lastRow, $this->organizationUnits->pluck('name')->all(), 'OrgUnit');
                $this->applyDropdown($sheet, 'E', 2, $lastRow, $this->structuralLevels->pluck('name')->all(), 'StructLevel');
                $this->applyDropdown($sheet, 'F', 2, $lastRow, array_values(EmploymentType::LABELS), 'EmploymentType');
                $this->applyDropdown($sheet, 'K', 2, $lastRow, ['Aktif', 'Nonaktif'], 'ActiveStatus');

                $column = 11; // kolom terakhir yang fixed (K = Status Aktif)
                foreach ($this->customFields as $field) {
                    $column++;
                    if ($field->type === 'checkbox') {
                        $this->applyDropdown($sheet, Coordinate::stringFromColumnIndex($column), 2, $lastRow, ['Ya', 'Tidak'], "CustomField{$field->id}");
                    } elseif ($field->type === 'select' && ! empty($field->options)) {
                        $this->applyDropdown($sheet, Coordinate::stringFromColumnIndex($column), 2, $lastRow, $field->options, "CustomField{$field->id}");
                    }
                }

                // Sheet bantu dropdown cuma perlu ada di file, tidak perlu terlihat user.
                $spreadsheet = $sheet->getParent();
                if ($spreadsheet->sheetNameExists(self::LIST_SHEET_TITLE)) {
                    $spreadsheet->getSheetByName(self::LIST_SHEET_TITLE)->setSheetState(Worksheet::SHEETSTATE_VERYHIDDEN);
                }
            },
        ];
    }

    /**
     * Pasang dropdown list di kolom tertentu. Opsi pendek & bebas koma dipasang inline
     * (formula1 = "A,B,C"); opsi panjang atau yang mengandung koma dipindah ke named range
     * di sheet tersembunyi supaya tidak salah parse / kena limit 255 karakter Excel.
     */
    private function applyDropdown(Worksheet $sheet, string $column, int $fromRow, int $toRow, array $options, string $listKey): void
    {
        $options = array_values(array_filter($options, fn ($o) => $o !== null && $o !== ''));

        if (empty($options)) {
            return;
        }

        $inline   = '"' . implode(',', $options) . '"';
        $hasComma = collect($options)->contains(fn ($o) => str_contains((string) $o, ','));

        $formula = (! $hasComma && strlen($inline) <= 255)
            ? $inline
            : $this->namedRangeFor($sheet->getParent(), $listKey, $options);

        for ($row = $fromRow; $row <= $toRow; $row++) {
            $validation = $sheet->getCell("{$column}{$row}")->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
            $validation->setAllowBlank(true);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setShowDropDown(true);
            $validation->setFormula1($formula);
        }
    }

    /** Tulis $options ke kolom baru di sheet tersembunyi lalu daftarkan sebagai named range. */
    private function namedRangeFor(Spreadsheet $spreadsheet, string $key, array $options): string
    {
        $listSheet = $spreadsheet->sheetNameExists(self::LIST_SHEET_TITLE)
            ? $spreadsheet->getSheetByName(self::LIST_SHEET_TITLE)
            : $spreadsheet->createSheet()->setTitle(self::LIST_SHEET_TITLE);

        $col = Coordinate::stringFromColumnIndex($this->nextListColumn++);
        foreach ($options as $i => $option) {
            $listSheet->setCellValue($col . ($i + 1), $option);
        }

        $rangeName = 'DD_' . preg_replace('/[^A-Za-z0-9_]/', '_', $key);
        $spreadsheet->addNamedRange(new NamedRange($rangeName, $listSheet, "\${$col}\$1:\${$col}\$" . count($options)));

        return $rangeName;
    }
}
