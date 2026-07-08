<?php

namespace App\Enums;

enum KpiBaseType: string
{
    case Revenue     = 'revenue';
    case GrossMargin = 'gross_margin';
    case Fixed       = 'fixed';

    public function label(): string
    {
        return match ($this) {
            self::Revenue     => 'Theo doanh thu',
            self::GrossMargin => 'Theo lãi gộp',
            self::Fixed       => 'Cố định',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Revenue     => 'blue',
            self::GrossMargin => 'green',
            self::Fixed       => 'purple',
        };
    }
}
