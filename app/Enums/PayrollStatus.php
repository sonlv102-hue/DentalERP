<?php

namespace App\Enums;

enum PayrollStatus: string
{
    case Draft     = 'draft';
    case Confirmed = 'confirmed';
    case Locked    = 'locked';
    case Posted    = 'posted';
    case Paid      = 'paid';

    public function label(): string
    {
        return match($this) {
            self::Draft     => 'Nháp',
            self::Confirmed => 'Đã xác nhận',
            self::Locked    => 'Đã khóa',
            self::Posted    => 'Đã hạch toán',
            self::Paid      => 'Đã thanh toán',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Draft     => 'gray',
            self::Confirmed => 'blue',
            self::Locked    => 'green',
            self::Posted    => 'purple',
            self::Paid      => 'emerald',
        };
    }

    public function canEdit(): bool
    {
        return $this === self::Draft || $this === self::Confirmed;
    }

    public function canConfirm(): bool   { return $this === self::Draft; }
    public function canUnconfirm(): bool { return $this === self::Confirmed; }
    public function canLock(): bool      { return $this === self::Confirmed; }
    public function canUnlock(): bool    { return $this === self::Locked; }
}
