<?php

namespace App\Enums;

enum KpiSourceType: string
{
    case Manual       = 'manual';
    case KpiModule    = 'kpi_module';
    case PayrollInput = 'payroll_input';

    public function label(): string
    {
        return match($this) {
            self::Manual       => 'Nhập tay',
            self::KpiModule    => 'Từ module KPI',
            self::PayrollInput => 'Nhập khi tính lương',
        };
    }
}
