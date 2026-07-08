<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TreatmentStepParticipant extends Model
{
    protected $fillable = [
        'step_execution_id', 'employee_id', 'role', 'share_percent', 'note',
    ];

    protected function casts(): array
    {
        return ['share_percent' => 'float'];
    }

    public function stepExecution()
    {
        return $this->belongsTo(TreatmentStepExecution::class, 'step_execution_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
