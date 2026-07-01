<?php

namespace App\Domain\Communication\Models;

use App\Domain\Device\Models\Device;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contact extends Model
{
    protected $table = 'contacts';

    public $timestamps = false;

    protected $fillable = [
        'device_id',
        'contact_name',
        'phone_number',
        'phone_hash',
        'phone_numbers',
        'email',
        'is_favorite',
        'synced_at',
    ];

    protected $casts = [
        'phone_numbers' => 'array',
        'is_favorite'   => 'boolean',
        'synced_at'     => 'datetime',
        'contact_name'  => 'encrypted',
        'phone_number'  => 'encrypted',
        'email'         => 'encrypted',
    ];

    // ==================== Relations ====================

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    // ==================== Helpers ====================

    public function getPrimaryNumberAttribute(): string
    {
        return $this->phone_number
            ?? ($this->phone_numbers[0] ?? '');
    }

    public function getNameAttribute(): ?string
    {
        return $this->contact_name;
    }

    // ==================== Scopes ====================

    public function scopeFavorites($query)
    {
        return $query->where('is_favorite', true);
    }

    /**
     * NOTE: name, phone_number, email are encrypted — SQL LIKE won't work.
     * Filter in PHP after decryption.
     */
    public function scopeSearch($query, string $keyword)
    {
        // Encrypted fields cannot be searched with LIKE.
        // Return all and let the caller filter in PHP.
        return $query;
    }

    public static function hashPhone(string $number): string
    {
        return hash('sha256', preg_replace('/\s+/', '', $number));
    }
}
