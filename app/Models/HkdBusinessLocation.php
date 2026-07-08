<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HkdBusinessLocation extends Model
{
    protected $fillable = [
        'hkd_profile_id', 'address', 'province', 'district', 'ward',
        'business_type', 'is_primary', 'notes',
    ];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(HkdProfile::class, 'hkd_profile_id');
    }
}
