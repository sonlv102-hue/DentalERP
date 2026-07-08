<?php

namespace App\Enums;

enum PurchaseInvoiceStatus: string
{
    case Draft     = 'draft';
    case Received  = 'received';
    case Paid      = 'paid';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::Draft     => 'Nháp',
            self::Received  => 'Đã nhận',
            self::Paid      => 'Đã thanh toán',
            self::Cancelled => 'Đã hủy',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Draft     => 'gray',
            self::Received  => 'blue',
            self::Paid      => 'green',
            self::Cancelled => 'red',
        };
    }

    public function allowedTransitions(): array
    {
        return match($this) {
            self::Draft    => [self::Received, self::Cancelled],
            self::Received => [self::Paid, self::Cancelled],
            default        => [],
        };
    }
}
