<?php

namespace App\Domain\App\Models;

use App\Domain\Device\Models\Device;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppUsage extends Model
{
    protected $table = 'app_usage';

    protected $fillable = [
        'device_id',
        'package_name',
        'app_name',
        'usage_date',
        'foreground_sec',
        'background_sec',
        'launch_count',
        'data_sent',
        'data_received',
        'last_used_at',
    ];

    protected $casts = [
        'usage_date'     => 'date',
        'last_used_at'   => 'datetime',
        'foreground_sec' => 'integer',
        'background_sec' => 'integer',
        'launch_count'   => 'integer',
        'data_sent'      => 'integer',
        'data_received'  => 'integer',
    ];

    // ==================== Relations ====================

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    // ==================== Helpers ====================

    public function getForegroundFormattedAttribute(): string
    {
        return $this->formatSeconds($this->foreground_sec ?? 0);
    }

    public function getTotalSecAttribute(): int
    {
        return ($this->foreground_sec ?? 0) + ($this->background_sec ?? 0);
    }

    private function formatSeconds(int $seconds): string
    {
        $hours   = (int) floor($seconds / 3600);
        $minutes = (int) floor(($seconds % 3600) / 60);
        $secs    = $seconds % 60;

        if ($hours > 0) {
            return "{$hours}س {$minutes}ق";
        }
        if ($minutes > 0) {
            return "{$minutes}ق {$secs}ث";
        }
        return "{$secs}ث";
    }

    // ==================== Scopes ====================

    public function scopeToday($query)
    {
        return $query->whereDate('usage_date', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('usage_date', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ]);
    }

    public function scopeByPackage($query, string $package)
    {
        return $query->where('package_name', $package);
    }
}
