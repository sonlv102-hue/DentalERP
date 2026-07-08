<?php

namespace App\Enums;

enum HkdCashAccountType: string
{
    case Cash    = 'cash';
    case Bank    = 'bank';
    case EWallet = 'e_wallet';

    public function label(): string
    {
        return match($this) {
            self::Cash    => 'Tiền mặt',
            self::Bank    => 'Ngân hàng',
            self::EWallet => 'Ví điện tử',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Cash    => 'green',
            self::Bank    => 'blue',
            self::EWallet => 'purple',
        };
    }
}
