<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait HasPerPage
{
    /**
     * Resolve the "per_page" value from the request, restricted to a safe allow-list.
     * Falls back to $default (10 unless a page needs otherwise) for anything invalid.
     */
    protected function perPage(Request $request, int $default = 10, string $key = 'per_page'): int
    {
        $allowed = [10, 25, 50, 100];
        $value = (int) $request->input($key, $default);

        return in_array($value, $allowed, true) ? $value : $default;
    }
}
