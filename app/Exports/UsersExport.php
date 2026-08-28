<?php

namespace App\Exports;

use App\Support\EmploymentType;
use App\Support\RoleLabel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UsersExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    /**
     * @param Collection $users
     * @param Collection $customFields Definisi field kustom aktif milik company, urut sesuai tampilan
     */
    public function __construct(
        private Collection $users,
        private Collection $customFields,
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
}
