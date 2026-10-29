<?php

namespace App\Exports;

use App\Models\Project;
use Carbon\CarbonInterface;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Gantt chart versi Excel: kolom info di kiri, satu kolom per hari/minggu di kanan,
 * bar digambar dengan sel yang di-merge + diwarnai (sama seperti tampilan web).
 */
class GanttChartExport implements FromArray, WithEvents, WithTitle
{
    private const INFO_COLS = ['Nama', 'Sprint / Milestone', 'Assignee / Lead', 'Status', 'Mulai', 'Selesai', 'Keterangan'];
    private const HEADER_ROW = 4;

    public function __construct(
        private Project $project,
        private array $chart,
        private CarbonInterface $ganttStart,
        private CarbonInterface $ganttEnd,
    ) {}

    public function array(): array
    {
        return [];
    }

    public function title(): string
    {
        return 'Gantt Chart';
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => fn (AfterSheet $event) => $this->draw($event->sheet->getDelegate())];
    }

    private function draw(Worksheet $sheet): void
    {
        $units = $this->chart['units'];
        $infoCount = count(self::INFO_COLS);
        $firstUnitCol = $infoCount + 1;
        $lastCol = $infoCount + max(1, count($units));
        $col = fn (int $i) => Coordinate::stringFromColumnIndex($i);
        $lastColL = $col($lastCol);
        $h1 = self::HEADER_ROW;
        $h2 = self::HEADER_ROW + 1;

        // Judul
        $sheet->setCellValue('A1', 'Gantt Chart — ' . $this->project->name);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->setCellValue('A2', 'Periode: ' . $this->ganttStart->format('d M Y') . ' s/d ' . $this->ganttEnd->format('d M Y')
            . ' (' . ($this->chart['byWeek'] ? 'per minggu' : 'per hari') . ')  |  Dibuat: ' . now()->format('d M Y H:i'));
        $sheet->getStyle('A2')->getFont()->setItalic(true)->getColor()->setRGB('6B7280');

        // Header kolom info (2 baris di-merge)
        foreach (self::INFO_COLS as $i => $label) {
            $c = $col($i + 1);
            $sheet->setCellValue("{$c}{$h1}", $label);
            $sheet->mergeCells("{$c}{$h1}:{$c}{$h2}");
        }

        // Header bulan + tanggal
        $monthStart = $firstUnitCol;
        foreach ($units as $i => $u) {
            $c = $col($firstUnitCol + $i);
            $sheet->setCellValue("{$c}{$h2}", $u['label'] . "\n" . $u['sub']);
            if ($u['weekend']) $this->fill($sheet, "{$c}{$h2}", 'E5E7EB');
            if ($u['today']) $this->fill($sheet, "{$c}{$h2}", 'DBEAFE');
            $next = $units[$i + 1] ?? null;
            if (!$next || $next['month'] !== $u['month']) {
                $sheet->setCellValue($col($monthStart) . $h1, $u['month']);
                if ($monthStart !== $firstUnitCol + $i) $sheet->mergeCells($col($monthStart) . "{$h1}:{$c}{$h1}");
                $monthStart = $firstUnitCol + $i + 1;
            }
            $sheet->getColumnDimension($c)->setWidth($this->chart['byWeek'] ? 6.5 : 4.6);
        }
        $header = "A{$h1}:{$lastColL}{$h2}";
        $sheet->getStyle($header)->getFont()->setBold(true)->setSize(9);
        $sheet->getStyle($header)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $this->fill($sheet, "A{$h1}:{$lastColL}{$h1}", 'F3F4F6');
        $this->fill($sheet, "A{$h2}:" . $col($infoCount) . $h2, 'F3F4F6');
        $sheet->getRowDimension($h2)->setRowHeight(26);

        // Baris data
        $r = $h2 + 1;
        foreach ($this->chart['rows'] as $row) {
            if ($row['kind'] === 'group') {
                $sheet->setCellValue("A{$r}", strtoupper($row['label']) . '  ·  ' . $row['count']);
                $sheet->mergeCells("A{$r}:{$lastColL}{$r}");
                $this->fill($sheet, "A{$r}:{$lastColL}{$r}", ltrim($row['bg'], '#'));
                $sheet->getStyle("A{$r}")->getFont()->setBold(true)->setSize(9)->getColor()->setRGB(ltrim($row['color'], '#'));
                $r++;
                continue;
            }

            foreach ([$row['name'], $row['sub'], $row['pic'], $row['status'], $row['start'], $row['end'], $row['note']] as $i => $v) {
                $sheet->setCellValue($col($i + 1) . $r, $v);
            }
            if ($row['kind'] === 'sprint') $sheet->getStyle("A{$r}")->getFont()->setBold(true);
            if ($row['note']) $sheet->getStyle($col($infoCount) . $r)->getFont()->setBold(true)->getColor()->setRGB('B45309');

            // Latar weekend/hari ini di area timeline
            foreach ($units as $i => $u) {
                if ($u['weekend']) $this->fill($sheet, $col($firstUnitCol + $i) . $r, 'F3F4F6');
                if ($u['today']) $this->fill($sheet, $col($firstUnitCol + $i) . $r, 'EFF6FF');
            }

            $b = $row['bar'];
            if ($b) {
                $from = $col($firstUnitCol + $b['from']) . $r;
                $to = $col($firstUnitCol + $b['to']) . $r;
                $range = "{$from}:{$to}";
                if ($from !== $to) $sheet->mergeCells($range);
                $sheet->setCellValue($from, ($b['clipStart'] ? '« ' : '') . $row['name'] . ($b['clipEnd'] ? ' »' : ''));
                // Tanggal ikut sprint: warna dipudarkan (Excel tidak punya opacity)
                $this->fill($sheet, $range, $row['estimated'] ? $this->lighten($row['color']) : ltrim($row['color'], '#'));
                $sheet->getStyle($range)->getFont()->setBold(true)->setSize(8)->getColor()->setRGB($row['estimated'] ? '312E81' : 'FFFFFF');
                $sheet->getStyle($range)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setShrinkToFit(true);
                if ($row['estimated']) {
                    $sheet->getStyle($range)->getBorders()->getOutline()->setBorderStyle(Border::BORDER_DASHED)->getColor()->setRGB(ltrim($row['color'], '#'));
                }
            } else {
                $cell = $col($firstUnitCol) . $r;
                $sheet->setCellValue($cell, 'Belum ada tanggal');
                $sheet->getStyle($cell)->getFont()->setItalic(true)->setSize(8)->getColor()->setRGB('B45309');
            }
            $sheet->getRowDimension($r)->setRowHeight(20);
            $r++;
        }

        $lastRow = max($h2, $r - 1);
        $sheet->getStyle("A{$h1}:{$lastColL}{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E5E7EB');
        $sheet->getStyle("A" . ($h2 + 1) . ":{$lastColL}{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("E" . ($h2 + 1) . ":F{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        foreach ([32, 18, 18, 12, 11, 11, 22] as $i => $w) {
            $sheet->getColumnDimension($col($i + 1))->setWidth($w);
        }
        $sheet->freezePane($col($firstUnitCol) . ($h2 + 1));

        // Legenda
        $r = $lastRow + 2;
        $sheet->setCellValue("A{$r}", 'Keterangan warna:');
        $sheet->getStyle("A{$r}")->getFont()->setBold(true);
        $legend = $this->chart['legend'] + ['Tanggal mengikuti sprint' => '#' . $this->lighten('#6366f1')];
        foreach ($legend as $label => $color) {
            $r++;
            $this->fill($sheet, "A{$r}", ltrim($color, '#'));
            $sheet->setCellValue("A{$r}", $label);
            $sheet->getStyle("A{$r}")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        }

        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)->setFitToWidth(1)->setFitToHeight(0);
    }

    private function fill(Worksheet $sheet, string $range, string $rgb): void
    {
        $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($rgb);
    }

    /** Campur warna dengan putih ~45% untuk efek "pudar". */
    private function lighten(string $hex): string
    {
        $hex = ltrim($hex, '#');
        return implode('', array_map(
            fn ($c) => str_pad(dechex((int) round(hexdec($c) + (255 - hexdec($c)) * 0.45)), 2, '0', STR_PAD_LEFT),
            str_split($hex, 2)
        ));
    }
}
