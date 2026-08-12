<?php

namespace App\Services;

use App\Models\EmployeeSalary;
use App\Models\TaxBracket;
use App\Models\TaxPtkp;
use App\Models\TaxTerRate;

class PPh21Service
{
    public function hitungTahunan(
        float  $brutoSetahun,
        string $statusPajak,
        bool   $punyaNpwp = true
    ): array {
        $biayaJabatan = min($brutoSetahun * 0.05, 6_000_000);
        $ptkp         = TaxPtkp::getAmount($statusPajak);
        $pkp          = max(0, $brutoSetahun - $biayaJabatan - $ptkp);
        $pkp          = floor($pkp / 1000) * 1000;

        $brackets  = TaxBracket::getActive();
        $pajak     = 0.0;
        $breakdown = [];

        foreach ($brackets as $bracket) {
            if ($pkp <= $bracket->income_from) break;

            $batasAtas = $bracket->income_to ?? PHP_FLOAT_MAX;
            $kena      = min($pkp, $batasAtas) - $bracket->income_from;
            $amount    = $kena * $bracket->rate;
            $pajak    += $amount;

            $breakdown[] = [
                'label'  => $bracket->label,
                'pkp'    => $kena,
                'rate'   => $bracket->rate,
                'amount' => round($amount),
            ];
        }

        if (!$punyaNpwp) {
            $pajak *= 1.20;
        }

        return [
            'bruto_setahun'  => $brutoSetahun,
            'biaya_jabatan'  => $biayaJabatan,
            'ptkp'           => $ptkp,
            'pkp'            => $pkp,
            'pajak_setahun'  => round($pajak),
            'non_npwp_extra' => !$punyaNpwp,
            'breakdown'      => $breakdown,
        ];
    }

    public function hitungBulanan(EmployeeSalary $salary): array
    {
        $brutoSebulan = $salary->gaji_pokok + $salary->tunjangan_jabatan
                      + $salary->tunjangan_transport + $salary->tunjangan_makan;
        $brutoSetahun = $brutoSebulan * 12;
        $result       = $this->hitungTahunan(
            $brutoSetahun,
            $salary->status_pajak,
            (bool) $salary->npwp
        );

        return [
            ...$result,
            'pajak_bulanan' => round($result['pajak_setahun'] / 12),
        ];
    }

    /**
     * Potongan bulanan metode TER (PP 58/2023 & PMK 168/2023) — dipakai untuk
     * masa pajak Januari-November. Langsung kalikan bruto bulan berjalan dengan
     * tarif efektif dari tabel TER, tanpa proyeksi setahun.
     */
    public function hitungBulananTer(EmployeeSalary $salary): array
    {
        $brutoBulanan = $salary->gaji_pokok + $salary->tunjangan_jabatan
                      + $salary->tunjangan_transport + $salary->tunjangan_makan;

        $kategori = TaxPtkp::getTerCategory($salary->status_pajak) ?? 'A';
        $tarif    = TaxTerRate::rateFor($kategori, $brutoBulanan);
        $pajak    = round($brutoBulanan * $tarif);

        return [
            'metode'        => 'ter',
            'kategori_ter'  => $kategori,
            'bruto_bulanan' => $brutoBulanan,
            'tarif_ter'     => $tarif,
            'pajak_bulanan' => $pajak,
        ];
    }

    /**
     * Rekonsiliasi masa pajak Desember (atau karyawan berhenti di tengah tahun):
     * hitung ulang pajak setahun pakai metode lama (progresif) dari bruto riil
     * setahun, lalu kurangi dengan yang sudah dipotong Jan-Nov (metode TER).
     * Hasil bisa negatif = kelebihan potong, dikembalikan ke karyawan bulan itu.
     */
    public function hitungDesember(
        float  $brutoSetahunRiil,
        string $statusPajak,
        bool   $punyaNpwp,
        float  $sudahDipotongJanNov
    ): array {
        $tahunan = $this->hitungTahunan($brutoSetahunRiil, $statusPajak, $punyaNpwp);

        return [
            ...$tahunan,
            'metode'                 => 'ter_rekonsiliasi',
            'sudah_dipotong_jan_nov' => $sudahDipotongJanNov,
            'pajak_bulanan'          => round($tahunan['pajak_setahun'] - $sudahDipotongJanNov),
        ];
    }
}
