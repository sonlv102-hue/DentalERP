<?php

namespace App\Enums;

enum LabOrderStatus: string
{
    case Draft     = 'draft';
    case Sent      = 'sent';
    case Received  = 'received';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::Draft     => 'Nháp',
            self::Sent      => 'Đã gửi labo',
            self::Received  => 'Đã nhận hàng',
            self::Completed => 'Hoàn thành',
            self::Cancelled => 'Đã hủy',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Draft     => 'gray',
            self::Sent      => 'blue',
            self::Received  => 'yellow',
            self::Completed => 'green',
            self::Cancelled => 'red',
        };
    }

    public function allowedTransitions(): array
    {
        return match($this) {
            self::Draft     => [self::Sent, self::Cancelled],
            self::Sent      => [self::Received, self::Cancelled],
            self::Received  => [self::Completed],
            self::Completed => [],
            self::Cancelled => [],
        };
    }
}
