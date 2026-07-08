<?php

namespace App\Enums;

enum ContractType: string
{
    case FullTime  = 'full_time';
    case PartTime  = 'part_time';
    case Probation = 'probation';
    case Freelance = 'freelance';
    case Service   = 'service';
    case Other     = 'other';

    public function label(): string
    {
        return match ($this) {
            self::FullTime  => 'Toàn thời gian',
            self::PartTime  => 'Bán thời gian',
            self::Probation => 'Thử việc',
            self::Freelance => 'Cộng tác viên',
            self::Service   => 'Hợp đồng dịch vụ',
            self::Other     => 'Khác',
        };
    }
}
