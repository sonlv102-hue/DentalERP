<?php

namespace App\Enums;

enum TimesheetStatus: string
{
    case Pending  = 'pending';
    case Approved = 'approved';

    public function label(): string
    {
        return match($this) {
            self::Pending  => 'Chờ duyệt',
            self::Approved => 'Đã duyệt',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Pending  => 'yellow',
            self::Approved => 'green',
        };
    }
}
