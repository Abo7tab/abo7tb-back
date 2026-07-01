<?php

namespace App\Domain\TimeManagement\Models;

use App\Domain\Device\Models\Device;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeLimit extends Model
{
    protected $table = 'time_limits';

    protected $fillable = [
        'device_id', 'limit_name', 'limit_type',
        'max_minutes_per_day', 'package_name', 'max_app_minutes',
        'start_time', 'end_time', 'active_days',
        'block_completely', 'allow_emergency_calls', 'is_active',
    ];

    protected $casts = [
        'max_minutes_per_day'   => 'integer',
        'max_app_minutes'       => 'integer',
        'active_days'           => 'array',
        'block_completely'      => 'boolean',
        'allow_emergency_calls' => 'boolean',
        'is_active'             => 'boolean',
    ];

    // ==================== Relations ====================

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    // ==================== Helpers ====================

    public function isActiveNow(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now        = now();
        $currentDay = $now->dayOfWeek;
        $activeDays = $this->active_days ?? [];

        if (!empty($activeDays) && !in_array($currentDay, $activeDays)) {
            return false;
        }

        if ($this->start_time && $this->end_time) {
            $current = $now->format('H:i');
            $start   = $this->start_time;
            $end     = $this->end_time;

            if ($start < $end) {
                return $current >= $start && $current < $end;
            }

            // يتجاوز منتصف الليل
            return $current >= $start || $current < $end;
        }

        return true;
    }

    // ==================== Scopes ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('limit_type', $type);
    }

    public function scopeForApp($query, string $package)
    {
        return $query->where('package_name', $package);
    }
}
