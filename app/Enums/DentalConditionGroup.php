<?php

namespace App\Enums;

enum DentalConditionGroup: string
{
    case Cavity        = 'sâu_răng';
    case Pulpitis      = 'viêm_tủy';
    case ToothLoss     = 'mất_răng';
    case Periodontitis = 'viêm_nha_chu';
    case Malocclusion  = 'sai_khớp_cắn';
    case WisdomTooth   = 'răng_khôn';
    case Cosmetic      = 'thẩm_mỹ';
    case Pediatric     = 'răng_trẻ_em';
    case Other         = 'khác';

    public function label(): string
    {
        return match ($this) {
            self::Cavity        => 'Sâu răng',
            self::Pulpitis      => 'Viêm tủy',
            self::ToothLoss     => 'Mất răng',
            self::Periodontitis => 'Viêm nha chu',
            self::Malocclusion  => 'Sai khớp cắn',
            self::WisdomTooth   => 'Răng khôn',
            self::Cosmetic      => 'Thẩm mỹ',
            self::Pediatric     => 'Răng trẻ em',
            self::Other         => 'Khác',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Cavity        => 'red',
            self::Pulpitis      => 'orange',
            self::ToothLoss     => 'gray',
            self::Periodontitis => 'yellow',
            self::Malocclusion  => 'blue',
            self::WisdomTooth   => 'indigo',
            self::Cosmetic      => 'pink',
            self::Pediatric     => 'teal',
            self::Other         => 'gray',
        };
    }
}
