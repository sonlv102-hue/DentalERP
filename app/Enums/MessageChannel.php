<?php

namespace App\Enums;

enum MessageChannel: string
{
    case Sms  = 'sms';
    case Zalo = 'zalo';

    public function label(): string
    {
        return match($this) {
            self::Sms  => 'SMS',
            self::Zalo => 'Zalo',
        };
    }
}
