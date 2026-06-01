<?php

namespace Database\Seeders;

use App\Models\TaxBracket;
use App\Models\TaxPtkp;
use Illuminate\Database\Seeder;

class TaxConfigSeeder extends Seeder
{
    public function run(): void
    {
        // PTKP — PMK-168/2023
        $ptkpList = [
            ['TK/0',  'Tidak Kawin, 0 Tanggungan',            54_000_000],
            ['TK/1',  'Tidak Kawin, 1 Tanggungan',            58_500_000],
            ['TK/2',  'Tidak Kawin, 2 Tanggungan',            63_000_000],
            ['TK/3',  'Tidak Kawin, 3 Tanggungan',            67_500_000],
            ['K/0',   'Kawin, 0 Tanggungan',                  58_500_000],
            ['K/1',   'Kawin, 1 Tanggungan',                  63_000_000],
            ['K/2',   'Kawin, 2 Tanggungan',                  67_500_000],
            ['K/3',   'Kawin, 3 Tanggungan',                  72_000_000],
            ['K/I/0', 'Kawin, Istri Bekerja, 0 Tanggungan',  108_000_000],
            ['K/I/1', 'Kawin, Istri Bekerja, 1 Tanggungan',  112_500_000],
            ['K/I/2', 'Kawin, Istri Bekerja, 2 Tanggungan',  117_000_000],
            ['K/I/3', 'Kawin, Istri Bekerja, 3 Tanggungan',  121_500_000],
        ];

        foreach ($ptkpList as $i => [$code, $label, $amount]) {
            TaxPtkp::updateOrCreate(
                ['status_code' => $code],
                ['label' => $label, 'amount' => $amount, 'sort_order' => $i, 'is_active' => true]
            );
        }

        // Tarif Progresif PPh 21 — Pasal 17 UU HPP
        $brackets = [
            [0,              60_000_000,    0.05,   '5%  — s/d Rp 60 juta'],
            [60_000_000,     250_000_000,   0.15,   '15% — Rp 60 jt s/d Rp 250 jt'],
            [250_000_000,    500_000_000,   0.25,   '25% — Rp 250 jt s/d Rp 500 jt'],
            [500_000_000,    5_000_000_000, 0.30,   '30% — Rp 500 jt s/d Rp 5 M'],
            [5_000_000_000,  null,          0.35,   '35% — di atas Rp 5 M'],
        ];

        foreach ($brackets as $i => [$from, $to, $rate, $label]) {
            TaxBracket::updateOrCreate(
                ['income_from' => $from],
                ['income_to' => $to, 'rate' => $rate, 'label' => $label, 'sort_order' => $i, 'is_active' => true]
            );
        }
    }
}
