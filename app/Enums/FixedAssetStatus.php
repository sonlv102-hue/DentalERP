<?php

namespace App\Enums;

enum FixedAssetStatus: string
{
    case Active           = 'active';
    case FullyDepreciated = 'fully_depreciated';
    case Disposed         = 'disposed';

    public function label(): string
    {
        return match($this) {
            self::Active           => 'Đang sử dụng',
            self::FullyDepreciated => 'Đã khấu hao hết',
            self::Disposed         => 'Đã thanh lý',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Active           => 'green',
            self::FullyDepreciated => 'gray',
            self::Disposed         => 'red',
        };
    }
}
