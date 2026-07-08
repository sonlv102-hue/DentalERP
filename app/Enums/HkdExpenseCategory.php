<?php

namespace App\Enums;

enum HkdExpenseCategory: string
{
    case Materials    = 'materials';
    case Labor        = 'labor';
    case Rent         = 'rent';
    case Utilities    = 'utilities';
    case Depreciation = 'depreciation';
    case Tax          = 'tax';
    case Other        = 'other';

    public function label(): string
    {
        return match($this) {
            self::Materials    => 'Nguyên vật liệu, hàng hóa',
            self::Labor        => 'Chi phí nhân công',
            self::Rent         => 'Thuê mặt bằng, địa điểm',
            self::Utilities    => 'Điện, nước, viễn thông',
            self::Depreciation => 'Khấu hao tài sản cố định',
            self::Tax          => 'Thuế, phí, lệ phí',
            self::Other        => 'Chi phí khác',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Materials    => 'blue',
            self::Labor        => 'green',
            self::Rent         => 'orange',
            self::Utilities    => 'yellow',
            self::Depreciation => 'red',
            self::Tax          => 'purple',
            self::Other        => 'gray',
        };
    }
}
