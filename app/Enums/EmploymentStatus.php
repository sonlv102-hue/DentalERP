<?php

namespace App\Enums;

enum EmploymentStatus: string
{
    case Active   = 'active';
    case OnLeave  = 'on_leave';
    case Resigned = 'resigned';

    public function label(): string
    {
        return match ($this) {
            self::Active   => 'Đang làm',
            self::OnLeave  => 'Tạm nghỉ',
            self::Resigned => 'Nghỉ việc',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active   => 'green',
            self::OnLeave  => 'yellow',
            self::Resigned => 'red',
        };
    }
}
