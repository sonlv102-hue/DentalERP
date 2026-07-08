<?php

namespace App\Enums;

enum ConsentStatus: string
{
    case Pending = 'pending';
    case Signed  = 'signed';

    public function label(): string
    {
        return match($this) {
            self::Pending => 'Chờ ký',
            self::Signed  => 'Đã ký',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Pending => 'yellow',
            self::Signed  => 'green',
        };
    }
}
