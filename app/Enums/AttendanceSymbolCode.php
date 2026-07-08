<?php

namespace App\Enums;

enum AttendanceSymbolCode: string
{
    case Work        = 'X';
    case Leave       = 'P';
    case UnpaidLeave = 'KP';
    case Holiday     = 'L';
    case Business    = 'CT';
    case Overtime    = 'OT';
    case CompOff     = 'NB';
    case Sick        = 'O';   // stored as O, displayed as Ô
    case Maternity   = 'TS';

    public function label(): string
    {
        return match($this) {
            self::Work        => 'Đi làm',
            self::Leave       => 'Nghỉ phép',
            self::UnpaidLeave => 'Nghỉ không phép',
            self::Holiday     => 'Nghỉ lễ',
            self::Business    => 'Công tác',
            self::Overtime    => 'Tăng ca',
            self::CompOff     => 'Nghỉ bù',
            self::Sick        => 'Ốm',
            self::Maternity   => 'Thai sản',
        };
    }

    public function displayCode(): string
    {
        return match($this) {
            self::Sick => 'Ô',
            default    => $this->value,
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Work        => 'green',
            self::Leave       => 'blue',
            self::UnpaidLeave => 'red',
            self::Holiday     => 'purple',
            self::Business    => 'teal',
            self::Overtime    => 'orange',
            self::CompOff     => 'violet',
            self::Sick        => 'yellow',
            self::Maternity   => 'pink',
        };
    }

    public function countsAsWorkday(): bool
    {
        return in_array($this, [self::Work, self::Business]);
    }

    public function countsAsPaidLeave(): bool
    {
        return in_array($this, [self::Leave, self::Holiday, self::CompOff, self::Sick, self::Maternity]);
    }

    public function countsAsUnpaidLeave(): bool
    {
        return $this === self::UnpaidLeave;
    }

    public function countsAsOvertime(): bool
    {
        return $this === self::Overtime;
    }

    public function defaultPaidWorkday(): float
    {
        return match($this) {
            self::Work, self::Business, self::Leave, self::Holiday, self::CompOff, self::Sick, self::Maternity => 1.0,
            self::UnpaidLeave, self::Overtime => 0.0,
        };
    }
}
