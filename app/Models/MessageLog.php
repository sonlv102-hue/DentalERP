<?php

namespace App\Models;

use App\Enums\MessageLogStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageLog extends Model
{
    protected $fillable = [
        'template_id', 'patient_id', 'channel', 'phone',
        'content_sent', 'status', 'sent_at', 'error_message',
    ];

    protected function casts(): array
    {
        return ['status' => MessageLogStatus::class, 'sent_at' => 'datetime'];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(MessageTemplate::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
