<?php

namespace App\Support\Reports;

use App\Models\User;
use Illuminate\Support\Collection;

interface ReportQuery
{
    /**
     * Filter field definitions, keyed by request input name.
     * type 'select' needs an 'options' => [value => label] array.
     * type 'date_range' renders {key}_from / {key}_to date inputs.
     */
    public function filters(): array;

    /** Column key => label, defines table/export column order. */
    public function columns(): array;

    /** Rows as associative arrays keyed like columns(), scoped to $user's company. */
    public function rows(array $filters, User $user): Collection;
}
