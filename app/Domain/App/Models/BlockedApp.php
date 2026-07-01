<?php

namespace App\Domain\App\Models;

use App\Domain\Device\Models\Device;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockedApp extends Model
{
    protected $table = 'blocked_apps';

    protected $fillable = [
        'device_id',
        'package_name',
        'app_name',
        'block_type',
        'reason',
        'blocked_until',
        'is_active',
        'blocked_at',
        'blocked_by',
    ];

    protected $casts = [
        'is_active'     => 'boolean',
        'blocked_until' => 'datetime',
        'blocked_at'    => 'datetime',
    ];

    // ==================== Relations ====================

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    // ==================== Helpers ====================

    public function isCurrentlyBlocked(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->block_type === 'time_limited' && $this->blocked_until) {
            return $this->blocked_until->isFuture();
        }

        return true;
    }

    // ==================== Scopes ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePermanent($query)
    {
        return $query->where('block_type', 'permanent');
    }
}
