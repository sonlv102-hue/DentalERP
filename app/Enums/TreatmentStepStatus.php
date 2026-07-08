<?php

namespace App\Enums;

enum TreatmentStepStatus: string
{
    case Pending   = 'pending';
    case Done      = 'done';
    case Cancelled = 'cancelled';
    case Redo      = 'redo';

    public function label(): string
    {
        return match ($this) {
            self::Pending   => 'Chờ thực hiện',
            self::Done      => 'Hoàn thành',
            self::Cancelled => 'Đã hủy',
            self::Redo      => 'Làm lại',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending   => 'gray',
            self::Done      => 'green',
            self::Cancelled => 'red',
            self::Redo      => 'orange',
        };
    }
}
