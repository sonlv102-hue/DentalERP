<?php

namespace App\Enums;

enum HkdRevenueCategory: string
{
    case Goods          = 'goods';
    case Services       = 'services';
    case Production     = 'production';
    case Construction   = 'construction';
    case Transportation = 'transportation';
    case Other          = 'other';

    public function label(): string
    {
        return match($this) {
            self::Goods          => 'Phân phối, cung cấp hàng hóa',
            self::Services       => 'Dịch vụ, xây dựng không bao thầu vật tư',
            self::Production     => 'Sản xuất, vận tải, dịch vụ có gắn với hàng hóa',
            self::Construction   => 'Xây dựng có bao thầu vật tư',
            self::Transportation => 'Vận tải',
            self::Other          => 'Hoạt động kinh doanh khác',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Goods          => 'blue',
            self::Services       => 'green',
            self::Production     => 'orange',
            self::Construction   => 'yellow',
            self::Transportation => 'purple',
            self::Other          => 'gray',
        };
    }
}
