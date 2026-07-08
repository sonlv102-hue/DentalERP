<?php

namespace App\Enums;

enum LabWarrantyStatus: string
{
    case Active  = 'active';
    case Expired = 'expired';
    case Claimed = 'claimed';

    public function label(): string
    {
        return match($this) {
            self::Active  => 'Còn hạn',
            self::Expired => 'Hết hạn',
            self::Claimed => 'Đã bảo hành',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Active  => 'green',
            self::Expired => 'gray',
            self::Claimed => 'yellow',
        };
    }
}
