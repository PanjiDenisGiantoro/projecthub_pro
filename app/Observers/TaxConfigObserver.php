<?php

namespace App\Observers;

use App\Models\TaxBracket;
use App\Models\TaxPtkp;
use Illuminate\Support\Facades\Cache;

class TaxConfigObserver
{
    public function savedPtkp(TaxPtkp $model): void
    {
        Cache::forget("tax_ptkp_{$model->status_code}");
    }

    public function savedBracket(TaxBracket $model): void
    {
        Cache::forget('tax_brackets_active');
    }
}
