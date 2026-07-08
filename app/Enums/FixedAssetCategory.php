<?php

namespace App\Enums;

enum FixedAssetCategory: string
{
    case Equipment = 'equipment';
    case Furniture  = 'furniture';
    case Vehicle    = 'vehicle';
    case Other      = 'other';

    public function label(): string
    {
        return match($this) {
            self::Equipment => 'Thiết bị',
            self::Furniture  => 'Nội thất',
            self::Vehicle    => 'Phương tiện',
            self::Other      => 'Khác',
        };
    }
}
