<?php

namespace App\Support;

class EmploymentType
{
    public const TETAP = 'tetap';
    public const KONTRAK = 'kontrak';
    public const OUTSOURCING = 'outsourcing';
    public const MAGANG = 'magang';
    public const HARIAN = 'harian';
    public const LAINNYA = 'lainnya';

    public const LABELS = [
        self::TETAP => 'Tetap',
        self::KONTRAK => 'Kontrak',
        self::OUTSOURCING => 'Outsourcing',
        self::MAGANG => 'Magang',
        self::HARIAN => 'Harian Lepas',
        self::LAINNYA => 'Lainnya',
    ];

    /** Tipe yang tidak butuh nama perusahaan asal — karyawan langsung perusahaan sendiri. */
    private const DIRECT_TYPES = [self::TETAP, self::KONTRAK];

    public static function options(): array
    {
        return self::LABELS;
    }

    public static function for(?string $value): string
    {
        return self::LABELS[$value] ?? '—';
    }

    /** True kalau tipe "Lainnya" — admin isi sendiri nama tipenya secara manual. */
    public static function requiresCustomLabel(?string $value): bool
    {
        return $value === self::LAINNYA;
    }

    /** Label yang ditampilkan ke user: nama kustom kalau tipe "Lainnya" dan diisi, else label bawaan. */
    public static function displayFor(?string $value, ?string $customLabel = null): string
    {
        if (self::requiresCustomLabel($value) && $customLabel) {
            return $customLabel;
        }

        return self::for($value);
    }

    /** True kalau tipe ini butuh input "dari perusahaan mana" (outsourcing, magang, dll). */
    public static function requiresSourceCompany(?string $value): bool
    {
        return $value !== null && ! in_array($value, self::DIRECT_TYPES, true);
    }

    /** PKWT/Kontrak wajib punya tanggal akhir menurut UU Ketenagakerjaan. Tipe lain opsional. */
    public static function requiresContractEndDate(?string $value): bool
    {
        return $value === self::KONTRAK;
    }

    /** Tetap tidak relevan punya tanggal akhir kontrak sama sekali. */
    public static function allowsContractEndDate(?string $value): bool
    {
        return $value !== self::TETAP;
    }

    /**
     * Default checkbox BPJS saat input gaji baru. Tetap/Kontrak = karyawan langsung
     * perusahaan sendiri, BPJS-nya perusahaan ini yang tanggung. Outsourcing/Magang/
     * Harian Lepas biasanya BPJS-nya sudah ditanggung perusahaan asal/vendor —
     * default tidak dicentang, tapi admin tetap bisa ubah manual per karyawan.
     */
    public static function defaultBpjsEnrollment(?string $value): bool
    {
        return $value === null || in_array($value, self::DIRECT_TYPES, true);
    }

    /**
     * Tipe yang dipajaki pakai skema PPh 21 "Bukan Pegawai" (PER-16/PJ/2016) — 50% dari
     * bruto langsung kena tarif progresif Pasal 17, tanpa PTKP/biaya jabatan — bukan skema
     * pegawai (PTKP/TER) yang dipakai Tetap & Kontrak. Harian Lepas SENGAJA tidak dimasukkan
     * di sini: itu punya skema tersendiri (ambang batas harian/bulanan, bukan Bukan Pegawai)
     * yang belum diimplementasikan — untuk sekarang Harian tetap pakai skema pegawai biasa
     * supaya tidak salah terapkan rumus yang justru lebih keliru.
     */
    private const BUKAN_PEGAWAI_TYPES = [self::OUTSOURCING, self::MAGANG];

    public static function usesBukanPegawaiTax(?string $value): bool
    {
        return $value !== null && in_array($value, self::BUKAN_PEGAWAI_TYPES, true);
    }
}
