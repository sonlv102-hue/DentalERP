<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lab extends Model
{
    protected $fillable = [
        'code', 'name', 'phone', 'email', 'address',
        'contact_person', 'bank_account', 'notes', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public static function generateCode(): string
    {
        $last = static::max('id') ?? 0;
        return 'LAB-'.str_pad($last + 1, 4, '0', STR_PAD_LEFT);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(LabOrder::class);
    }

    public function priceItems(): HasMany
    {
        return $this->hasMany(LabPriceItem::class);
    }
}
