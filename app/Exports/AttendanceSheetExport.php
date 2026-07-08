<?php

namespace App\Exports;

use App\Models\AttendancePeriod;
use App\Services\AttendanceSummaryService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceSheetExport implements FromArray, WithHeadings, WithTitle, WithStyles
{
    private array $employees;
    private array $grid;
    private array $summaries;
    private int   $daysInMonth;

    public function __construct(
        private AttendancePeriod $period,
        array $employees,
        AttendanceSummaryService $summaryService
    ) {
        $this->daysInMonth = $period->daysInMonth();
        $this->employees   = $employees;
        $this->grid        = $summaryService->buildGrid($period);
        $this->summaries   = $summaryService->summarize($period);
    }

    public function title(): string
    {
        return "CC-{$this->period->year}" . str_pad($this->period->month, 2, '0', STR_PAD_LEFT);
    }

    public function headings(): array
    {
        $heads = ['STT', 'Mã NV', 'Họ và tên', 'Chức vụ'];
        for ($d = 1; $d <= $this->daysInMonth; $d++) {
            $heads[] = $d;
        }
        return array_merge($heads, ['Công', 'NghỉHL', 'NghỉKL', 'OT(h)', 'Tổng']);
    }

    public function array(): array
    {
        $rows   = [];
        $totals = ['STT' => 'TỔNG CỘNG', 'code' => '', 'name' => '', 'position' => ''];
        $colTotals = array_fill(1, $this->daysInMonth, '');
        $sumTotals = ['cong' => 0, 'nghi_hl' => 0, 'nghi_kl' => 0, 'ot_hours' => 0, 'total' => 0];

        foreach ($this->employees as $i => $emp) {
            $row = [$i + 1, $emp['code'], $emp['full_name'], $emp['position'] ?? ''];

            for ($d = 1; $d <= $this->daysInMonth; $d++) {
                $key    = sprintf('%d-%02d-%02d', $this->period->year, $this->period->month, $d);
                $cell   = $this->grid[$emp['id']][$key] ?? null;
                $symbol = $cell ? ($cell['symbol'] === 'O' ? 'Ô' : $cell['symbol']) : '';
                if ($cell && $cell['overtime_hours'] > 0 && $cell['symbol'] === 'X') {
                    $symbol = 'X+OT';
                }
                $row[] = $symbol;
            }

            $s     = $this->summaries[$emp['id']] ?? ['cong' => 0, 'nghi_hl' => 0, 'nghi_kl' => 0, 'ot_hours' => 0, 'total' => 0];
            $row   = array_merge($row, [$s['cong'], $s['nghi_hl'], $s['nghi_kl'], $s['ot_hours'], $s['total']]);
            $rows[] = $row;

            foreach (['cong', 'nghi_hl', 'nghi_kl', 'ot_hours', 'total'] as $k) {
                $sumTotals[$k] += $s[$k] ?? 0;
            }
        }

        // Total row
        $totalRow = ['TỔNG CỘNG', '', '', ''];
        for ($d = 1; $d <= $this->daysInMonth; $d++) {
            $totalRow[] = '';
        }
        $totalRow = array_merge($totalRow, [
            $sumTotals['cong'], $sumTotals['nghi_hl'],
            $sumTotals['nghi_kl'], $sumTotals['ot_hours'], $sumTotals['total'],
        ]);
        $rows[] = $totalRow;

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $lastCol = 4 + $this->daysInMonth + 5; // fixed cols + days + summary cols
        $lastRow = count($this->employees) + 3; // heading + weekday + data + total

        // Header row bold navy background
        return [
            1 => ['font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E3A5F']]],
            $lastRow => ['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFF3CD']]],
        ];
    }
}
