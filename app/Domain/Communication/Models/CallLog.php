<?php

namespace App\Domain\Communication\Models;

use App\Domain\Device\Models\Device;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallLog extends Model
{
    protected $table = 'call_logs';

    public $timestamps = false;

    protected $fillable = [
        'device_id',
        'phone_number',
        'phone_hash',
        'contact_name',
        'call_type',
        'duration_sec',
        'called_at',
        'is_unknown',
        'parent_read',
        'is_blocked_number',
    ];

    protected $casts = [
        'duration_sec'      => 'integer',
        'called_at'         => 'datetime',
        'is_unknown'        => 'boolean',
        'parent_read'       => 'boolean',
        'is_blocked_number' => 'boolean',
        'phone_number'      => 'encrypted',
        'contact_name'      => 'encrypted',
    ];

    // ==================== Relations ====================

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    // ==================== Helpers ====================

    public function getDurationFormattedAttribute(): string
    {
        $sec     = $this->duration_sec ?? 0;
        $minutes = (int) floor($sec / 60);
        $secs    = $sec % 60;

        if ($minutes > 0) {
            return "{$minutes}ق {$secs}ث";
        }
        return "{$secs}ث";
    }

    public function isIncoming(): bool
    {
        return $this->call_type === 'incoming';
    }

    public function isOutgoing(): bool
    {
        return $this->call_type === 'outgoing';
    }

    public function isMissed(): bool
    {
        return $this->call_type === 'missed';
    }

    // ==================== Scopes ====================

    public function scopeIncoming($query)
    {
        return $query->where('call_type', 'incoming');
    }

    public function scopeOutgoing($query)
    {
        return $query->where('call_type', 'outgoing');
    }

    public function scopeMissed($query)
    {
        return $query->where('call_type', 'missed');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('called_at', today());
    }

    /**
     * NOTE: phone_number is encrypted — SQL LIKE won't work.
     * Use PHP-level filtering after decryption.
     */
    public function scopeByNumber($query, string $number)
    {
        return $query->where('phone_hash', self::hashPhone($number));
    }

    public static function hashPhone(string $number): string
    {
        return hash('sha256', preg_replace('/\s+/', '', $number));
    }
}
