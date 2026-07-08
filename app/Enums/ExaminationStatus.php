<?php

namespace App\Enums;

enum ExaminationStatus: string
{
    case Draft     = 'draft';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft     => 'Nháp',
            self::Completed => 'Hoàn thành',
            self::Cancelled => 'Đã hủy',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft     => 'gray',
            self::Completed => 'green',
            self::Cancelled => 'red',
        };
    }
}
