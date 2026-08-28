<?php

namespace App\Exports\Sheets;

use App\Exports\AttendancesExport;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** Sheet 2 template import absensi — petunjuk pengisian + daftar email karyawan valid. */
class AttendanceImportGuideSheet implements FromArray, WithStyles, WithTitle
{
    /** @param Collection $employees User records (id, name, email) milik company */
    public function __construct(private Collection $employees) {}

    public function array(): array
    {
        $rows = [
            ['Petunjuk Pengisian Import Data Absensi'],
            [''],
            ['Kolom', 'Keterangan'],
            ['Nama Karyawan', 'Opsional, cuma buat referensi. Yang dipakai untuk mencocokkan data adalah Email Karyawan.'],
            ['Email Karyawan', 'Wajib diisi, harus email karyawan yang sudah terdaftar di perusahaan ini.'],
            ['Tanggal', 'Wajib diisi. Format: YYYY-MM-DD, contoh 2024-01-15. Kalau kombinasi Email + Tanggal sudah ada datanya, baris ini akan memperbarui data tersebut.'],
            ['Jam Masuk', 'Opsional. Format jam: HH:MM, contoh 08:00.'],
            ['Jam Keluar', 'Opsional. Format jam: HH:MM, contoh 17:00.'],
            ['Status', 'Opsional, default "Hadir". Nilai valid: ' . implode(', ', AttendancesExport::STATUS_LABELS)],
            ['Catatan', 'Opsional, teks bebas.'],
            [''],
            ['Daftar Email Karyawan yang tersedia'],
        ];

        $rows = [...$rows, ...($this->employees->isEmpty()
            ? [['(belum ada data)']]
            : $this->employees->map(fn ($e) => [$e->name, $e->email])->all())];

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
