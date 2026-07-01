<?php

namespace App\Domain\Device\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceSetting extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'device_id',
        'screen_time_limit',
        'bedtime_start',
        'bedtime_end',
        'blocked_apps',
        'allowed_apps',
        'location_tracking_enabled',
        'sms_monitoring_enabled',
        'call_monitoring_enabled',
        'web_filtering_enabled',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'blocked_apps'                => 'array',
            'allowed_apps'                => 'array',
            'location_tracking_enabled'   => 'boolean',
            'sms_monitoring_enabled'      => 'boolean',
            'call_monitoring_enabled'     => 'boolean',
            'web_filtering_enabled'       => 'boolean',
        ];
    }

    /**
     * Get the device this setting belongs to
     */
    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
