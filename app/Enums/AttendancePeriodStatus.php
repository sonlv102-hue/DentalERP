<?php

namespace App\Enums;

enum AttendancePeriodStatus: string
{
    case Open   = 'open';
    case Locked = 'locked';

    public function label(): string
    {
        return match($this) {
            self::Open   => 'Đang mở',
            self::Locked => 'Đã khóa',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Open   => 'yellow',
            self::Locked => 'green',
        };
    }
}
