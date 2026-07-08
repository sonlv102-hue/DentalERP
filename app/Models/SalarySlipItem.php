<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalarySlipItem extends Model
{
    protected $fillable = ['salary_slip_id', 'type', 'description', 'amount'];

    public function salarySlip(): BelongsTo
    {
        return $this->belongsTo(SalarySlip::class);
    }
}
