<?php

namespace App\Domain\App\Models;

use App\Domain\Device\Models\Device;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScreenTime extends Model
{
    protected $table = 'screen_time';

    public $timestamps = false;

    protected $fillable = [
        'device_id',
        'date',
        'total_sec',
        'screen_on_sec',
        'interactive_sec',
        'unlock_count',
    ];

    protected $casts = [
        'date'            => 'date',
        'total_sec'       => 'integer',
        'screen_on_sec'   => 'integer',
        'interactive_sec' => 'integer',
        'unlock_count'    => 'integer',
    ];

    // ==================== Relations ====================

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    // ==================== Helpers ====================

    public function getTotalFormattedAttribute(): string
    {
        $sec     = $this->total_sec ?? 0;
        $hours   = (int) floor($sec / 3600);
        $minutes = (int) floor(($sec % 3600) / 60);

        if ($hours > 0) {
            return "{$hours}س {$minutes}ق";
        }
        return "{$minutes} دقيقة";
    }

    // ==================== Scopes ====================

    public function scopeToday($query)
    {
        return $query->whereDate('date', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('date', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ]);
    }
}
