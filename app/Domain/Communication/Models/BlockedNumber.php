<?php

namespace App\Domain\Communication\Models;

use App\Domain\Device\Models\Device;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockedNumber extends Model
{
    protected $table = 'blocked_numbers';

    public $timestamps = false;

    protected $fillable = [
        'device_id',
        'phone_number',
        'phone_hash',
        'contact_name',
        'block_calls',
        'block_sms',
        'reason',
        'is_active',
        'blocked_at',
    ];

    protected $casts = [
        'block_calls'  => 'boolean',
        'block_sms'    => 'boolean',
        'is_active'    => 'boolean',
        'blocked_at'   => 'datetime',
        'phone_number' => 'encrypted',
        'contact_name' => 'encrypted',
    ];

    // ==================== Relations ====================

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    // ==================== Helpers ====================

    public function blocksCallsAndSms(): bool
    {
        return $this->block_calls && $this->block_sms;
    }

    // ==================== Scopes ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBlocksCalls($query)
    {
        return $query->where('block_calls', true)->where('is_active', true);
    }

    public function scopeBlocksSms($query)
    {
        return $query->where('block_sms', true)->where('is_active', true);
    }

    public static function hashPhone(string $number): string
    {
        return hash('sha256', preg_replace('/\s+/', '', $number));
    }
}
