<?php

namespace App\Enums;

enum MessageLogStatus: string
{
    case Pending = 'pending';
    case Sent    = 'sent';
    case Failed  = 'failed';

    public function label(): string
    {
        return match($this) {
            self::Pending => 'Chờ gửi',
            self::Sent    => 'Đã gửi',
            self::Failed  => 'Lỗi',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Pending => 'yellow',
            self::Sent    => 'green',
            self::Failed  => 'red',
        };
    }
}
