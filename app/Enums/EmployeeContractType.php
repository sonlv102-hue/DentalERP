<?php

namespace App\Enums;

enum EmployeeContractType: string
{
    case Probation = 'probation';
    case FullTime  = 'full_time';
    case PartTime  = 'part_time';
    case Contractor = 'contractor';

    public function label(): string
    {
        return match($this) {
            self::Probation  => 'Thử việc',
            self::FullTime   => 'Chính thức',
            self::PartTime   => 'Bán thời gian',
            self::Contractor => 'Hợp đồng dịch vụ',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Probation  => 'yellow',
            self::FullTime   => 'green',
            self::PartTime   => 'blue',
            self::Contractor => 'gray',
        };
    }
}
