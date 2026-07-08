<?php

namespace App\Exports;

use App\Models\Payroll;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PayrollSheetExport implements FromArray, WithHeadings, WithTitle, WithStyles
{
    private array $items;

    public function __construct(private Payroll $payroll)
    {
        $this->items = $payroll->items()
            ->with('department')
            ->orderBy('department_name')
            ->orderBy('employee_name')
            ->get()
            ->toArray();
    }

    public function title(): string
    {
        return $this->payroll->code;
    }

    public function headings(): array
    {
        return [
            'STT', 'Mã NV', 'Họ và tên', 'Chức vụ', 'Phòng ban',
            'Lương chính', 'Ngày công chuẩn', 'Ngày công TT', 'Tỷ lệ', 'Lương theo công',
            'PC Cố định', 'PC Trách nhiệm', 'PC Ăn trưa', 'PC ĐT', 'PC Xăng',
            'KPI/HQCV', 'Phụ cấp khác',
            'BHXH CP DN', 'BHYT CP DN', 'BHTN CP DN', 'Tổng CP DN',
            'BHXH NV', 'BHYT NV', 'BHTN NV', 'Tổng NV',
            'TN chịu thuế', 'Giảm trừ', 'Thuế TNCN', 'KPCĐ',
            'Tổng thu nhập', 'Tổng khấu trừ', 'Thực lĩnh',
        ];
    }

    public function array(): array
    {
        $rows   = [];
        $totals = array_fill(0, 27, 0);
        $dept   = '';
        $stt    = 0;

        foreach ($this->items as $i => $item) {
            if ($item['department_name'] !== $dept) {
                $dept = $item['department_name'];
                $rows[] = array_merge(["── {$dept} ──"], array_fill(0, 31, ''));
            }

            $stt++;
            $deduction = (int)$item['family_deduction'] + (int)$item['dependent_deduction'];
            $row = [
                $stt,
                $item['employee_code'],
                $item['employee_name'],
                $item['position_name'] ?? '',
                $item['department_name'] ?? '',
                $item['base_salary'],
                $item['standard_working_days'],
                $item['actual_working_days'],
                number_format((float)$item['workday_ratio'] * 100, 1) . '%',
                $item['salary_by_workday'],
                $item['fixed_allowance'],
                $item['responsibility_allowance'],
                $item['lunch_allowance'],
                $item['phone_allowance'],
                $item['travel_allowance'],
                $item['performance_kpi_amount'],
                $item['other_allowance'],
                $item['company_social_insurance'],
                $item['company_health_insurance'],
                $item['company_unemployment_insurance'],
                $item['total_company_insurance'],
                $item['employee_social_insurance'],
                $item['employee_health_insurance'],
                $item['employee_unemployment_insurance'],
                $item['total_employee_insurance'],
                $item['taxable_income'],
                $deduction,
                $item['personal_income_tax'],
                $item['union_fee_amount'],
                $item['gross_income'],
                $item['total_deductions'],
                $item['net_salary'],
            ];
            $rows[] = $row;

            foreach ([5, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 27, 28, 29, 30, 31] as $col) {
                if (isset($row[$col]) && is_numeric($row[$col])) {
                    $totals[$col] = ($totals[$col] ?? 0) + (int)$row[$col];
                }
            }
        }

        // Total row
        $totalRow = ['TỔNG CỘNG', '', '', '', ''];
        for ($c = 5; $c <= 31; $c++) {
            $totalRow[] = $totals[$c] ?? '';
        }
        $rows[] = $totalRow;

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = count($this->items) + 3;
        return [
            1         => ['font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E3A5F']]],
            $lastRow  => ['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFF3CD']]],
        ];
    }
}
