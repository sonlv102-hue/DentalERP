<?php

namespace App\Enums;

enum ClinicalTemplateType: string
{
    case Diagnosis    = 'diagnosis';
    case Prescription = 'prescription';
    case Note         = 'note';

    public function label(): string
    {
        return match($this) {
            self::Diagnosis    => 'Chẩn đoán',
            self::Prescription => 'Đơn thuốc / Hướng dẫn',
            self::Note         => 'Dặn dò tái khám',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Diagnosis    => 'indigo',
            self::Prescription => 'green',
            self::Note         => 'amber',
        };
    }

    /** Which ClinicalNote field this template type fills */
    public function targetField(): string
    {
        return match($this) {
            self::Diagnosis    => 'diagnosis',
            self::Prescription => 'prescription',
            self::Note         => 'next_visit_notes',
        };
    }
}
