<?php

namespace App\Http\Controllers\Web;

use App\Exports\GenericReportExport;
use App\Http\Controllers\Controller;
use App\Support\Reports\ReportQuery;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ReportWebController extends Controller
{
    public function index()
    {
        $reports = collect(config('reports'))->groupBy('category', preserveKeys: true);

        return view('reports.index', compact('reports'));
    }

    public function show(string $key, Request $request)
    {
        [$meta, $query] = $this->resolve($key);
        $filterDefs = $query->filters();
        $filters    = $this->extractFilters($request, $filterDefs);
        $submitted  = $request->boolean('submitted');

        $rows = $submitted ? $query->rows($filters, $request->user()) : collect();

        return view('reports.show', [
            'key'        => $key,
            'meta'       => $meta,
            'filterDefs' => $filterDefs,
            'filters'    => $filters,
            'submitted'  => $submitted,
            'columns'    => $query->columns(),
            'rows'       => $rows,
        ]);
    }

    public function export(string $key, string $format, Request $request)
    {
        [$meta, $query] = $this->resolve($key);
        abort_unless(in_array($format, ['pdf', 'xlsx']), 404);

        $filters  = $this->extractFilters($request, $query->filters());
        $columns  = $query->columns();
        $rows     = $query->rows($filters, $request->user());
        $filename = Str::slug($meta['label']) . '-' . now()->format('Ymd_His');

        if ($format === 'xlsx') {
            $title = mb_substr($meta['label'], 0, 31);

            return Excel::download(new GenericReportExport($columns, $rows, $title), "{$filename}.xlsx");
        }

        $pdf = Pdf::loadView('exports.generic_report_pdf', [
            'title'   => $meta['label'],
            'columns' => $columns,
            'rows'    => $rows,
        ])->setPaper('a4', 'landscape');

        return $pdf->download("{$filename}.pdf");
    }

    /** @return array{0: array, 1: ReportQuery} */
    private function resolve(string $key): array
    {
        $registry = config('reports');
        abort_unless(isset($registry[$key]), 404);

        $meta  = $registry[$key];
        $query = app($meta['query']);
        abort_unless($query instanceof ReportQuery, 500);

        return [$meta, $query];
    }

    private function extractFilters(Request $request, array $filterDefs): array
    {
        $filters = [];
        foreach ($filterDefs as $field => $def) {
            if ($def['type'] === 'date_range') {
                $filters["{$field}_from"] = $request->input("{$field}_from") ?: null;
                $filters["{$field}_to"]   = $request->input("{$field}_to") ?: null;
            } else {
                $filters[$field] = $request->input($field) ?: null;
            }
        }

        return $filters;
    }
}
