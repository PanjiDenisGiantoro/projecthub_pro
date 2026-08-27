<?php

namespace App\Services;

use App\Models\EmployeeSalary;
use App\Models\Pph21Setting;
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

    /**
     * PPh 21 "Bukan Pegawai" (PER-16/PJ/2016) — dipakai untuk Outsourcing/Magang, bukan
     * pegawai tetap: DPP = 50% dari bruto (tanpa PTKP, tanpa biaya jabatan 5%), langsung
     * kena tarif progresif Pasal 17 dari DPP itu.
     *
     * Simplifikasi yang perlu diketahui: versi ini menghitung tiap bulan berdiri sendiri
     * (non-kumulatif). Untuk Bukan Pegawai yang menerima penghasilan berkesinambungan dari
     * pemberi kerja yang sama sepanjang tahun, aturan resmi mewajibkan DPP dikumulatifkan
     * bulan berjalan terhadap lapisan tarif tahunan — kalau butuh kepatuhan penuh untuk kasus
     * itu, ini perlu tracking bruto kumulatif per tahun seperti rekonsiliasi TER Desember.
     */
    public function hitungBukanPegawai(float $brutoBulanan, bool $punyaNpwp = true): array
    {
        $dpp = max(0, $brutoBulanan * 0.5);
        $dpp = floor($dpp / 1000) * 1000;

        $brackets  = TaxBracket::getActive();
        $pajak     = 0.0;
        $breakdown = [];

        foreach ($brackets as $bracket) {
            if ($dpp <= $bracket->income_from) break;

            $batasAtas = $bracket->income_to ?? PHP_FLOAT_MAX;
            $kena      = min($dpp, $batasAtas) - $bracket->income_from;
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
            'metode'         => 'bukan_pegawai',
            'bruto_bulanan'  => $brutoBulanan,
            'dpp'            => $dpp,
            'pajak_bulanan'  => round($pajak),
            'non_npwp_extra' => !$punyaNpwp,
            'breakdown'      => $breakdown,
        ];
    }

    /**
     * Bonus/THR/gratifikasi (penghasilan tidak teratur) dihitung pakai metode
     * selisih: pajak atas bonus = pajak(setahun reguler + bonus) - pajak(setahun
     * reguler), lalu ditambahkan penuh ke potongan bulan bonus itu dibayarkan
     * (bukan ikut diproyeksikan x12 seperti gaji reguler).
     */
    public function hitungBulanan(EmployeeSalary $salary, Pph21Setting $setting, float $bonusBulanan = 0, float $tambahanTaxable = 0): array
    {
        $brutoSebulan = $setting->brutoPajak(
            $salary->gaji_pokok, $salary->tunjangan_jabatan,
            $salary->tunjangan_transport, $salary->tunjangan_makan
        ) + $tambahanTaxable;
        $brutoSetahun = $brutoSebulan * 12;
        $result       = $this->hitungTahunan(
            $brutoSetahun,
            $salary->status_pajak,
            (bool) $salary->npwp
        );

        $pajakBulanan = round($result['pajak_setahun'] / 12);

        if ($bonusBulanan > 0) {
            $resultDenganBonus = $this->hitungTahunan(
                $brutoSetahun + $bonusBulanan,
                $salary->status_pajak,
                (bool) $salary->npwp
            );
            $pajakBulanan += $resultDenganBonus['pajak_setahun'] - $result['pajak_setahun'];
        }

        return [
            ...$result,
            'pajak_bulanan' => $pajakBulanan,
        ];
    }

    /**
     * Potongan bulanan metode TER (PP 58/2023 & PMK 168/2023) — dipakai untuk
     * masa pajak Januari-November. Langsung kalikan bruto bulan berjalan dengan
     * tarif efektif dari tabel TER, tanpa proyeksi setahun.
     */
    public function hitungBulananTer(EmployeeSalary $salary, Pph21Setting $setting, float $bonusBulanan = 0, float $tambahanTaxable = 0): array
    {
        $brutoBulanan = $setting->brutoPajak(
            $salary->gaji_pokok, $salary->tunjangan_jabatan,
            $salary->tunjangan_transport, $salary->tunjangan_makan
        ) + $bonusBulanan + $tambahanTaxable;

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
     * Skema gross-up: perusahaan kasih tunjangan PPh21 yang besarnya harus persis sama
     * dengan pajak yang dihasilkan — tapi tunjangan itu sendiri menambah bruto (kena pajak
     * lagi), jadi diselesaikan dengan iterasi titik tetap: tunjangan_{n+1} = pajak(bruto +
     * tunjangan_n). Konvergen cepat karena tarif marjinal progresif < 100% (kontraksi), dan
     * TER konstan per lapisan bruto (biasanya 1-2 iterasi).
     */
    public function hitungGrossUp(EmployeeSalary $salary, Pph21Setting $setting, bool $useTer, float $bonusBulanan = 0): array
    {
        $tunjangan = 0.0;
        $result    = [];

        for ($i = 0; $i < 20; $i++) {
            $result = $useTer
                ? $this->hitungBulananTer($salary, $setting, $bonusBulanan, $tunjangan)
                : $this->hitungBulanan($salary, $setting, $bonusBulanan, $tunjangan);

            $pajakBaru = $result['pajak_bulanan'];
            $konvergen = abs($pajakBaru - $tunjangan) < 1;
            $tunjangan = $pajakBaru;
            if ($konvergen) break;
        }

        return [
            ...$result,
            'tunjangan_pph21' => $tunjangan,
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
