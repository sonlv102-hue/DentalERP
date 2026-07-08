<?php

namespace App\Enums;

enum SalarySlipStatus: string
{
    case Draft     = 'draft';
    case Confirmed = 'confirmed';
    case Paid      = 'paid';

    public function label(): string
    {
        return match($this) {
            self::Draft     => 'Nháp',
            self::Confirmed => 'Đã duyệt',
            self::Paid      => 'Đã thanh toán',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Draft     => 'gray',
            self::Confirmed => 'blue',
            self::Paid      => 'green',
        };
    }

    public function allowedTransitions(): array
    {
        return match($this) {
            self::Draft     => [self::Confirmed],
            self::Confirmed => [self::Paid],
            self::Paid      => [],
        };
    }
}
