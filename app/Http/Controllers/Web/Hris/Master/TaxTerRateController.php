<?php

namespace App\Http\Controllers\Web\Hris\Master;

use App\Http\Controllers\Controller;
use App\Models\TaxTerRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TaxTerRateController extends Controller
{
    public function store(Request $request)
    {
        $this->authorize('create hris master');
        $request->validate([
            'category'    => 'required|in:A,B,C',
            'income_from' => 'required|numeric|min:0',
            'income_to'   => 'nullable|numeric|min:0',
            'rate'        => 'required|numeric|min:0|max:1',
            'label'       => 'nullable|string|max:100',
        ]);
        TaxTerRate::create([...$request->only(['category', 'income_from', 'income_to', 'rate', 'label']), 'is_active' => true]);
        Cache::forget('tax_ter_rates_' . $request->category);
        return redirect()->route('hris.master.index', ['tab' => 'tax-ter'])->with('success', 'Lapisan tarif TER ditambahkan.');
    }

    public function edit(TaxTerRate $taxTerRate)
    {
        $this->authorize('view hris master');
        return view('hris.master.tax-ter.edit', compact('taxTerRate'));
    }

    public function update(Request $request, TaxTerRate $taxTerRate)
    {
        $this->authorize('update hris master');
        $request->validate([
            'income_from' => 'required|numeric|min:0',
            'income_to'   => 'nullable|numeric|min:0',
            'rate'        => 'required|numeric|min:0|max:1',
            'label'       => 'nullable|string|max:100',
        ]);
        $taxTerRate->update($request->only(['income_from', 'income_to', 'rate', 'label']));
        Cache::forget('tax_ter_rates_' . $taxTerRate->category);
        return redirect()->route('hris.master.index', ['tab' => 'tax-ter'])->with('success', 'Lapisan tarif TER diperbarui.');
    }

    public function destroy(TaxTerRate $taxTerRate)
    {
        $this->authorize('delete hris master');
        $category = $taxTerRate->category;
        $taxTerRate->delete();
        Cache::forget('tax_ter_rates_' . $category);
        return redirect()->route('hris.master.index', ['tab' => 'tax-ter'])->with('success', 'Lapisan tarif TER dihapus.');
    }

    public function toggle(TaxTerRate $taxTerRate)
    {
        $this->authorize('update hris master');
        $taxTerRate->update(['is_active' => !$taxTerRate->is_active]);
        Cache::forget('tax_ter_rates_' . $taxTerRate->category);
        return back()->with('success', 'Status diperbarui.');
    }

    /**
     * Reset ke tabel resmi PMK 168/2023 Lampiran (TER Bulanan Kategori A/B/C).
     * Baris terakhir tiap kategori (income_to = null) adalah lapisan tertinggi (34%).
     */
    public function resetDefault()
    {
        $this->authorize('create hris master');

        foreach (self::officialRates() as $category => $rows) {
            foreach ($rows as $i => [$from, $to, $ratePercent]) {
                TaxTerRate::updateOrCreate(
                    ['category' => $category, 'income_from' => $from],
                    [
                        'income_to'  => $to,
                        'rate'       => $ratePercent / 100,
                        'label'      => "TER {$category} — {$ratePercent}%",
                        'sort_order' => $i,
                        'is_active'  => true,
                    ]
                );
            }
            Cache::forget("tax_ter_rates_{$category}");
        }

        return redirect()->route('hris.master.index', ['tab' => 'tax-ter'])->with('success', 'Reset ke tarif TER PMK-168/2023 berhasil.');
    }

    /** [category => [[income_from, income_to|null, rate_persen], ...]] — PMK 168/2023 Lampiran. */
    private static function officialRates(): array
    {
        return [
            'A' => [
                [0, 5_400_000, 0], [5_400_001, 5_650_000, 0.25], [5_650_001, 5_950_000, 0.5],
                [5_950_001, 6_300_000, 0.75], [6_300_001, 6_750_000, 1], [6_750_001, 7_500_000, 1.25],
                [7_500_001, 8_550_000, 1.5], [8_550_001, 9_650_000, 1.75], [9_650_001, 10_050_000, 2],
                [10_050_001, 10_350_000, 2.25], [10_350_001, 10_700_000, 2.5], [10_700_001, 11_050_000, 3],
                [11_050_001, 11_600_000, 3.5], [11_600_001, 12_500_000, 4], [12_500_001, 13_750_000, 5],
                [13_750_001, 15_100_000, 6], [15_100_001, 16_950_000, 7], [16_950_001, 19_750_000, 8],
                [19_750_001, 24_150_000, 9], [24_150_001, 26_450_000, 10], [26_450_001, 28_000_000, 11],
                [28_000_001, 30_050_000, 12], [30_050_001, 32_400_000, 13], [32_400_001, 35_400_000, 14],
                [35_400_001, 39_100_000, 15], [39_100_001, 43_850_000, 16], [43_850_001, 47_800_000, 17],
                [47_800_001, 51_400_000, 18], [51_400_001, 56_300_000, 19], [56_300_001, 62_200_000, 20],
                [62_200_001, 68_600_000, 21], [68_600_001, 77_500_000, 22], [77_500_001, 89_000_000, 23],
                [89_000_001, 103_000_000, 24], [103_000_001, 125_000_000, 25], [125_000_001, 157_000_000, 26],
                [157_000_001, 206_000_000, 27], [206_000_001, 337_000_000, 28], [337_000_001, 454_000_000, 29],
                [454_000_001, 550_000_000, 30], [550_000_001, 695_000_000, 31], [695_000_001, 910_000_000, 32],
                [910_000_001, 1_400_000_000, 33], [1_400_000_001, null, 34],
            ],
            'B' => [
                [0, 6_200_000, 0], [6_200_001, 6_500_000, 0.25], [6_500_001, 6_850_000, 0.5],
                [6_850_001, 7_300_000, 0.75], [7_300_001, 9_200_000, 1], [9_200_001, 10_750_000, 1.5],
                [10_750_001, 11_250_000, 2], [11_250_001, 11_600_000, 2.5], [11_600_001, 12_600_000, 3],
                [12_600_001, 13_600_000, 4], [13_600_001, 14_950_000, 5], [14_950_001, 16_400_000, 6],
                [16_400_001, 18_450_000, 7], [18_450_001, 21_850_000, 8], [21_850_001, 26_000_000, 9],
                [26_000_001, 27_700_000, 10], [27_700_001, 29_350_000, 11], [29_350_001, 31_450_000, 12],
                [31_450_001, 33_950_000, 13], [33_950_001, 37_100_000, 14], [37_100_001, 41_100_000, 15],
                [41_100_001, 45_800_000, 16], [45_800_001, 49_500_000, 17], [49_500_001, 53_800_000, 18],
                [53_800_001, 58_500_000, 19], [58_500_001, 64_000_000, 20], [64_000_001, 71_000_000, 21],
                [71_000_001, 80_000_000, 22], [80_000_001, 93_000_000, 23], [93_000_001, 109_000_000, 24],
                [109_000_001, 129_000_000, 25], [129_000_001, 163_000_000, 26], [163_000_001, 211_000_000, 27],
                [211_000_001, 374_000_000, 28], [374_000_001, 459_000_000, 29], [459_000_001, 555_000_000, 30],
                [555_000_001, 704_000_000, 31], [704_000_001, 957_000_000, 32], [957_000_001, 1_405_000_000, 33],
                [1_405_000_001, null, 34],
            ],
            'C' => [
                [0, 6_600_000, 0], [6_600_001, 6_950_000, 0.25], [6_950_001, 7_350_000, 0.5],
                [7_350_001, 7_800_000, 0.75], [7_800_001, 8_850_000, 1], [8_850_001, 9_800_000, 1.25],
                [9_800_001, 10_950_000, 1.5], [10_950_001, 11_200_000, 1.75], [11_200_001, 12_050_000, 2],
                [12_050_001, 12_950_000, 3], [12_950_001, 14_150_000, 4], [14_150_001, 15_550_000, 5],
                [15_550_001, 17_050_000, 6], [17_050_001, 19_500_000, 7], [19_500_001, 22_700_000, 8],
                [22_700_001, 26_600_000, 9], [26_600_001, 28_100_000, 10], [28_100_001, 30_100_000, 11],
                [30_100_001, 32_600_000, 12], [32_600_001, 35_400_000, 13], [35_400_001, 38_900_000, 14],
                [38_900_001, 43_000_000, 15], [43_000_001, 47_400_000, 16], [47_400_001, 51_200_000, 17],
                [51_200_001, 55_800_000, 18], [55_800_001, 60_400_000, 19], [60_400_001, 66_700_000, 20],
                [66_700_001, 74_500_000, 21], [74_500_001, 83_200_000, 22], [83_200_001, 95_600_000, 23],
                [95_600_001, 110_000_000, 24], [110_000_001, 134_000_000, 25], [134_000_001, 169_000_000, 26],
                [169_000_001, 221_000_000, 27], [221_000_001, 390_000_000, 28], [390_000_001, 463_000_000, 29],
                [463_000_001, 561_000_000, 30], [561_000_001, 709_000_000, 31], [709_000_001, 965_000_000, 32],
                [965_000_001, 1_419_000_000, 33], [1_419_000_001, null, 34],
            ],
        ];
    }
}
