<?php

namespace App\Models;

use App\Enums\SalarySlipStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalarySlip extends Model
{
    protected $fillable = [
        'employee_id', 'branch_id', 'period', 'work_days',
        'base_salary', 'ot_hours', 'ot_rate', 'ot_amount',
        'commission_total', 'deductions', 'net_salary',
        'status', 'notes', 'created_by', 'paid_at',
    ];

    protected function casts(): array
    {
        return ['status' => SalarySlipStatus::class];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalarySlipItem::class);
    }
}
