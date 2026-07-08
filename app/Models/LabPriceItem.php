<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabPriceItem extends Model
{
    protected $fillable = ['lab_id', 'service_name', 'unit_price', 'notes'];

    public function lab(): BelongsTo
    {
        return $this->belongsTo(Lab::class);
    }
}
