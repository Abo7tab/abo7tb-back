<?php

namespace App\Domain\App\Models;

use App\Domain\Device\Models\Device;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstalledApp extends Model
{
    protected $table = 'installed_apps';

    protected $fillable = [
        'device_id',
        'app_name',
        'package_name',
        'version_name',
        'version_code',
        'app_size',
        'app_icon',
        'is_system_app',
        'is_enabled',
        'install_date',
        'update_date',
        'first_seen',
        'last_seen',
    ];

    protected $casts = [
        'is_system_app' => 'boolean',
        'is_enabled'    => 'boolean',
        'app_size'      => 'integer',
        'version_code'  => 'integer',
        'install_date'  => 'datetime',
        'update_date'   => 'datetime',
        'first_seen'    => 'datetime',
        'last_seen'     => 'datetime',
    ];

    // ==================== Relations ====================

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    // ==================== Scopes ====================

    public function scopeUserApps($query)
    {
        return $query->where('is_system_app', false);
    }

    public function scopeSystemApps($query)
    {
        return $query->where('is_system_app', true);
    }

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    // ==================== Helpers ====================

    public function getAppSizeFormattedAttribute(): string
    {
        $bytes = $this->app_size ?? 0;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
