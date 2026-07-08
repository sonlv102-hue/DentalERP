<?php

namespace App\Enums;

enum PerformanceReviewStatus: string
{
    case Draft     = 'draft';
    case Finalized = 'finalized';

    public function label(): string
    {
        return match($this) {
            self::Draft     => 'Nháp',
            self::Finalized => 'Hoàn thành',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Draft     => 'gray',
            self::Finalized => 'green',
        };
    }
}
