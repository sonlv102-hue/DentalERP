<?php

namespace App\Enums;

enum HkdPeriodCloseStatus: string
{
    case Open   = 'open';
    case Closed = 'closed';

    public function label(): string
    {
        return match($this) {
            self::Open   => 'Đang mở',
            self::Closed => 'Đã chốt',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Open   => 'green',
            self::Closed => 'gray',
        };
    }
}
