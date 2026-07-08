<?php

namespace App\Enums;

enum HkdTaxStatus: string
{
    case NotSubject           = 'not_subject';
    case VatPitRevenue        = 'vat_pit_revenue';
    case VatRevenuePitIncome  = 'vat_revenue_pit_income';

    public function label(): string
    {
        return match($this) {
            self::NotSubject          => 'Không thuộc diện kê khai VAT & TNCN',
            self::VatPitRevenue       => 'VAT + TNCN theo % doanh thu',
            self::VatRevenuePitIncome => 'VAT theo % DT, TNCN trên thu nhập chịu thuế',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::NotSubject          => 'gray',
            self::VatPitRevenue       => 'blue',
            self::VatRevenuePitIncome => 'indigo',
        };
    }

    /** Returns which sổ kế toán this status generates. */
    public function books(): array
    {
        return match($this) {
            self::NotSubject          => ['S1a'],
            self::VatPitRevenue       => ['S2a'],
            self::VatRevenuePitIncome => ['S2b', 'S2c', 'S2d', 'S2e'],
        };
    }
}
