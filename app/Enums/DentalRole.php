<?php

namespace App\Enums;

enum DentalRole: string
{
    case Doctor    = 'doctor';
    case Assistant = 'assistant';
    case Consultant = 'consultant';
    case Receptionist = 'receptionist';
    case XrayTech  = 'xray_tech';
    case Cskh      = 'cskh';
    case Accountant = 'accountant';
    case Warehouse  = 'warehouse';
    case Manager    = 'manager';
    case Other      = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Doctor       => 'Bác sĩ',
            self::Assistant    => 'Phụ tá',
            self::Consultant   => 'Tư vấn viên',
            self::Receptionist => 'Lễ tân',
            self::XrayTech     => 'KTV chụp phim / Scan',
            self::Cskh         => 'CSKH',
            self::Accountant   => 'Kế toán',
            self::Warehouse    => 'Kho vật tư',
            self::Manager      => 'Quản lý',
            self::Other        => 'Khác',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Doctor       => 'blue',
            self::Assistant    => 'teal',
            self::Consultant   => 'purple',
            self::Receptionist => 'gray',
            self::XrayTech     => 'cyan',
            self::Cskh         => 'pink',
            self::Accountant   => 'yellow',
            self::Warehouse    => 'orange',
            self::Manager      => 'indigo',
            self::Other        => 'slate',
        };
    }
}
