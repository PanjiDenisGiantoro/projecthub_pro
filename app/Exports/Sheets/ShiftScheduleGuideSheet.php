<?php

namespace App\Exports\Sheets;

use App\Imports\ShiftScheduleImport;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** Sheet 2 template upload jadwal shift — daftar kode valid (juga sumber dropdown di sheet Jadwal) + petunjuk. */
class ShiftScheduleGuideSheet implements FromArray, WithColumnWidths, WithStyles, WithTitle
{
    public const TITLE = 'Petunjuk';

    /** Baris pertama daftar kode — dirujuk dropdown sheet Jadwal, jadi urutan baris di atasnya jangan diubah. */
    public const CODES_START_ROW = 3;

    /** @param Collection $shifts Shift aktif milik company */
    public function __construct(private Collection $shifts) {}

    public function array(): array
    {
        $rows = [
            ['Kode', 'Keterangan'],
            ['(kosong)', 'Tidak diubah — sel yang dikosongkan tidak mengubah jadwal yang sudah ada.'],
        ];

        foreach ($this->shifts as $shift) {
            $rows[] = [$shift->name, 'Shift ' . $shift->sessionsLabel()];
        }
        $rows[] = [ShiftScheduleImport::CODE_OFF, 'Libur di tanggal tsb (juga bisa ditulis L / OFF).'];
        $rows[] = [ShiftScheduleImport::CODE_DEFAULT, 'Hapus jadwal khusus, kembali ke shift default karyawan (dari Data Karyawan).'];

        return [
            ...$rows,
            [''],
            ['Petunjuk'],
            ['1. Isi sel tanggal di sheet Jadwal dengan salah satu kode di atas (ada dropdown di tiap sel).'],
            ['2. Kolom Email dipakai untuk mencocokkan karyawan — jangan diubah. Kolom Nama cuma referensi.'],
            ['3. Jangan ubah baris 1 (Periode) & baris 2 (tanggal) di sheet Jadwal.'],
            ['4. Hari libur perusahaan otomatis libur; isi nama shift di tanggal itu kalau karyawan tetap harus masuk.'],
            ['5. Shift malam (mis. 22:00-05:00) cukup diisi di tanggal jam masuknya.'],
        ];
    }

    public function columnWidths(): array
    {
        return ['A' => 22, 'B' => 80];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }

    public function title(): string
    {
        return self::TITLE;
    }
}
