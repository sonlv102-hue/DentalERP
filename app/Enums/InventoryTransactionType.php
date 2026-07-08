<?php

namespace App\Enums;

enum InventoryTransactionType: string
{
    case Purchase   = 'purchase';
    case Usage      = 'usage';
    case Adjustment = 'adjustment';
    case Return     = 'return';

    public function label(): string
    {
        return match($this) {
            self::Purchase   => 'Nhập kho',
            self::Usage      => 'Xuất dùng',
            self::Adjustment => 'Điều chỉnh',
            self::Return     => 'Trả lại',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Purchase   => 'green',
            self::Usage      => 'blue',
            self::Adjustment => 'yellow',
            self::Return     => 'gray',
        };
    }

    public function isStockIn(): bool
    {
        return in_array($this, [self::Purchase, self::Return]);
    }
}
