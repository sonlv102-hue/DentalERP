<?php

namespace App\Enums;

enum AttachmentType: string
{
    case Xray     = 'xray';
    case Document = 'document';
    case Photo    = 'photo';
    case Other    = 'other';

    public function label(): string
    {
        return match($this) {
            self::Xray     => 'X-quang',
            self::Document => 'Tài liệu',
            self::Photo    => 'Ảnh',
            self::Other    => 'Khác',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Xray     => 'purple',
            self::Document => 'blue',
            self::Photo    => 'green',
            self::Other    => 'gray',
        };
    }
}
