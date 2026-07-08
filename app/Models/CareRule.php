<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareRule extends Model
{
    protected $fillable = [
        'name', 'trigger_event', 'trigger_service_id',
        'delay_days', 'message_template_id', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'delay_days' => 'integer'];
    }

    public function messageTemplate(): BelongsTo
    {
        return $this->belongsTo(MessageTemplate::class, 'message_template_id');
    }

    public function triggerService(): BelongsTo
    {
        return $this->belongsTo(DentalService::class, 'trigger_service_id');
    }
}
