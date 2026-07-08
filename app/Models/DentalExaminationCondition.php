<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DentalExaminationCondition extends Model
{
    protected $fillable = [
        'examination_id', 'condition_id', 'tooth_no', 'severity', 'note',
    ];

    public function examination()
    {
        return $this->belongsTo(DentalExamination::class, 'examination_id');
    }

    public function condition()
    {
        return $this->belongsTo(DentalCondition::class, 'condition_id');
    }
}
