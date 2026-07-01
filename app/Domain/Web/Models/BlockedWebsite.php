<?php

namespace App\Domain\Web\Models;

use App\Domain\Device\Models\Device;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockedWebsite extends Model
{
    protected $table = 'blocked_websites';

    public $timestamps = false;

    protected $fillable = [
        'device_id',
        'domain',
        'category',
        'block_type',
        'is_active',
        'blocked_at',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'blocked_at' => 'datetime',
    ];

    // ==================== Relations ====================

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    // ==================== Scopes ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('block_type', $type);
    }
}
