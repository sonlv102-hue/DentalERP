<?php

namespace App\Enums;

enum RelationshipType: string
{
    case Parent   = 'parent';
    case Child    = 'child';
    case Spouse   = 'spouse';
    case Sibling  = 'sibling';
    case Referrer = 'referrer';

    public function label(): string
    {
        return match($this) {
            self::Parent   => 'Phụ huynh',
            self::Child    => 'Con',
            self::Spouse   => 'Vợ/Chồng',
            self::Sibling  => 'Anh/Chị/Em',
            self::Referrer => 'Người giới thiệu',
        };
    }
}
