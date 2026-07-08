<?php

namespace App\Enums;

enum KpiAllocationStatus: string
{
    case Accrued         = 'accrued';
    case PendingApproval = 'pending_approval';
    case Approved        = 'approved';
    case Paid            = 'paid';
    case Reversed        = 'reversed';
    case Held            = 'held';

    public function label(): string
    {
        return match ($this) {
            self::Accrued         => 'Tạm tính',
            self::PendingApproval => 'Chờ duyệt',
            self::Approved        => 'Đã duyệt',
            self::Paid            => 'Đã trả',
            self::Reversed        => 'Đã đảo',
            self::Held            => 'Đang treo',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Accrued         => 'gray',
            self::PendingApproval => 'yellow',
            self::Approved        => 'teal',
            self::Paid            => 'green',
            self::Reversed        => 'red',
            self::Held            => 'orange',
        };
    }

    public function canApprove(): bool
    {
        return $this === self::PendingApproval;
    }

    public function canHold(): bool
    {
        return in_array($this, [self::Accrued, self::PendingApproval]);
    }

    public function canReverse(): bool
    {
        return in_array($this, [self::Approved, self::Paid]);
    }
}
