<?php

namespace App\Domain\Device\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SafeZone extends Model
{
    protected $table = 'safe_zones';

    protected $fillable = [
        'device_id',
        'zone_name',
        'latitude',
        'longitude',
        'radius',
        'notify_on_enter',
        'notify_on_exit',
        'is_active',
    ];

    protected $casts = [
        'latitude'        => 'decimal:8',
        'longitude'       => 'decimal:8',
        'radius'          => 'integer',
        'notify_on_enter' => 'boolean',
        'notify_on_exit'  => 'boolean',
        'is_active'       => 'boolean',
    ];

    // ==================== Relations ====================

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    // ==================== Helpers ====================

    /**
     * هل نقطة معينة داخل المنطقة الآمنة؟
     */
    public function containsPoint(float $lat, float $lng): bool
    {
        $distance = $this->distanceTo($lat, $lng);
        return $distance <= $this->radius;
    }

    /**
     * المسافة بالمتر بين نقطة والمنطقة الآمنة
     */
    public function distanceTo(float $lat, float $lng): float
    {
        $earthRadius = 6371000; // متر

        $dLat = deg2rad($lat - (float) $this->latitude);
        $dLng = deg2rad($lng - (float) $this->longitude);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad((float) $this->latitude))
            * cos(deg2rad($lat))
            * sin($dLng / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }

    // ==================== Scopes ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
