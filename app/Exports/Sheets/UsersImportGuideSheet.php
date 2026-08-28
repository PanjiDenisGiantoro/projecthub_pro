<?php

namespace App\Exports\Sheets;

use App\Support\EmploymentType;
use App\Support\RoleLabel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** Sheet 2 template import — daftar nilai valid supaya admin tidak salah ketik. */
class UsersImportGuideSheet implements FromArray, WithStyles, WithTitle
{
    public function __construct(
        private Collection $customFields,
        private Collection $roles,
        private Collection $organizationUnits,
        private Collection $structuralLevels,
    ) {}

    public function array(): array
    {
        $rows = [
            ['Petunjuk Pengisian Import Data Karyawan'],
            [''],
            ['Kolom', 'Keterangan'],
            ['Nama', 'Wajib diisi.'],
            ['Email', 'Wajib diisi. Kalau email sudah terdaftar, data user tersebut akan diperbarui (bukan dibuat baru).'],
            ['Role', 'Wajib diisi untuk user baru. Nilai valid: ' . $this->roles->map(fn ($r) => RoleLabel::for($r->name))->implode(', ')],
            ['Departemen/Unit', 'Opsional. Harus persis sama dengan nama unit yang sudah ada (lihat daftar di bawah).'],
            ['Level Struktural', 'Opsional. Harus persis sama dengan nama level yang sudah ada (lihat daftar di bawah).'],
            ['Tipe Karyawan', 'Opsional, default "Tetap". Nilai valid: ' . implode(', ', EmploymentType::LABELS)],
            ['Tipe Karyawan (Lainnya)', 'Isi hanya kalau Tipe Karyawan = Lainnya.'],
            ['Nama Perusahaan Asal', 'Isi hanya kalau Tipe Karyawan bukan Tetap/Kontrak (outsourcing, magang, dll).'],
            ['Tanggal Bergabung', 'Format tanggal: YYYY-MM-DD, contoh 2024-01-15.'],
            ['Tanggal Akhir Kontrak', 'Format tanggal: YYYY-MM-DD. Isi kalau Tipe Karyawan = Kontrak.'],
            ['Status Aktif', 'Nilai valid: Aktif / Nonaktif. Kosong = dianggap Aktif untuk user baru, atau tidak diubah untuk user lama.'],
            [''],
            ['Daftar Departemen/Unit yang tersedia'],
            ...($this->organizationUnits->isEmpty()
                ? [['(belum ada data)']]
                : $this->organizationUnits->map(fn ($u) => [$u->name])->all()),
            [''],
            ['Daftar Level Struktural yang tersedia'],
            ...($this->structuralLevels->isEmpty()
                ? [['(belum ada data)']]
                : $this->structuralLevels->map(fn ($l) => [$l->name])->all()),
        ];

        if ($this->customFields->isNotEmpty()) {
            $rows[] = [''];
            $rows[] = ['Field Kustom'];
            foreach ($this->customFields as $field) {
                $desc = match ($field->type) {
                    'checkbox' => 'Nilai valid: Ya / Tidak.',
                    'select'   => 'Nilai valid: ' . implode(', ', $field->options ?? []),
                    'number'   => 'Isi angka.',
                    'date'     => 'Format tanggal: YYYY-MM-DD.',
                    default    => 'Isi teks bebas.',
                };
                $rows[] = [$field->label . ($field->is_required ? ' (wajib)' : ''), $desc];
            }
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 13]],
            3 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Petunjuk';
    }
}
