<?php

namespace App\Enums;

enum TreatmentItemStatus: string
{
    case Pending    = 'pending';
    case Scheduled  = 'scheduled';
    case InProgress = 'in_progress';
    case Completed  = 'completed';
    case Cancelled  = 'cancelled';
    case Warranty   = 'warranty';
    case Redo       = 'redo';

    public function label(): string
    {
        return match ($this) {
            self::Pending    => 'Chờ',
            self::Scheduled  => 'Đã lên lịch',
            self::InProgress => 'Đang thực hiện',
            self::Completed  => 'Hoàn thành',
            self::Cancelled  => 'Đã hủy',
            self::Warranty   => 'Bảo hành',
            self::Redo       => 'Làm lại',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending    => 'gray',
            self::Scheduled  => 'blue',
            self::InProgress => 'yellow',
            self::Completed  => 'green',
            self::Cancelled  => 'red',
            self::Warranty   => 'purple',
            self::Redo       => 'orange',
        };
    }

    /** Whether this status blocks KPI calculation */
    public function blocksKpi(): bool
    {
        return in_array($this, [self::Pending, self::Scheduled, self::InProgress, self::Cancelled]);
    }

    /** Whether this status requires KPI reversal */
    public function requiresKpiReversal(): bool
    {
        return in_array($this, [self::Cancelled, self::Warranty, self::Redo]);
    }
}
