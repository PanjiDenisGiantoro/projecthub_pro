<?php

namespace App\Services;

use App\Models\User;

class ThrCalculatorService
{
    /**
     * THR sesuai Permenaker No. 6/2016: masa kerja >= 12 bulan → 1 bulan gaji penuh.
     * Masa kerja 1-11 bulan → pro-rata (masa kerja bulan / 12) x 1 bulan gaji.
     * "1 bulan gaji" = komponen gaji tetap bulanan (gaji pokok + tunjangan tetap),
     * bukan termasuk lembur/reimburse/bonus lain yang sifatnya tidak tetap.
     */
    public function calculate(User $user): array
    {
        $salary = $user->salaries()->latest('effective_date')->first();

        if (! $salary) {
            return [
                'eligible' => false,
                'message'  => 'Karyawan ini belum punya data gaji — atur data gaji dulu sebelum hitung THR.',
            ];
        }

        $gajiBulanan = $salary->gaji_pokok + $salary->tunjangan_transport
            + $salary->tunjangan_makan + $salary->tunjangan_jabatan;

        $masaKerjaBulan = $user->tenureMonths();

        if ($masaKerjaBulan === null) {
            return [
                'eligible'     => false,
                'gaji_bulanan' => $gajiBulanan,
                'message'      => 'Tanggal mulai kerja karyawan ini belum diisi — tidak bisa hitung pro-rata otomatis. Isi dulu di data karyawan, atau masukkan nominal manual.',
            ];
        }

        if ($masaKerjaBulan < 1) {
            return [
                'eligible'     => false,
                'gaji_bulanan' => $gajiBulanan,
                'message'      => 'Masa kerja karyawan ini belum genap 1 bulan — belum berhak THR menurut Permenaker No. 6/2016.',
            ];
        }

        $masaKerjaBulan = min($masaKerjaBulan, 12);
        $isProrata      = $masaKerjaBulan < 12;
        $proporsi       = $masaKerjaBulan / 12;
        $thr            = round($gajiBulanan * $proporsi);

        return [
            'eligible'         => true,
            'gaji_bulanan'     => $gajiBulanan,
            'masa_kerja_bulan' => $masaKerjaBulan,
            'proporsi'         => $proporsi,
            'is_prorata'       => $isProrata,
            'thr'              => $thr,
            'message'          => $isProrata
                ? "Pro-rata: masa kerja {$masaKerjaBulan} bulan dari 12 bulan ({$masaKerjaBulan}/12 x Rp " . number_format($gajiBulanan, 0, ',', '.') . ')'
                : 'Masa kerja sudah 12 bulan atau lebih — THR 1 bulan gaji penuh.',
        ];
    }
}
