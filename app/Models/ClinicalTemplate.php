<?php

namespace App\Models;

use App\Enums\ClinicalTemplateType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicalTemplate extends Model
{
    protected $fillable = [
        'type', 'title', 'content', 'service_id', 'is_global', 'branch_id', 'created_by',
    ];

    protected function casts(): array
    {
        return ['type' => ClinicalTemplateType::class, 'is_global' => 'boolean'];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(DentalService::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
