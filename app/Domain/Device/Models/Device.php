<?php

namespace App\Domain\Device\Models;

use App\Domain\Shared\Traits\HasUuid;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Device extends Model
{
    use HasUuid, SoftDeletes;

    protected $table = 'devices';

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($device) {
            if (!$device->isForceDeleting()) {
                return;
            }

            // مسح كل الملفات المرتبطة بالجهاز من السيرفر عشان ماتسيبش مساحة مهدرة
            $disk = Storage::disk('media');
            
            foreach ($device->cameraCaptures as $capture) {
                if ($capture->file_path) $disk->delete($capture->file_path);
                if ($capture->thumbnail_path) $disk->delete($capture->thumbnail_path);
            }
            
            foreach ($device->audioRecordings as $audio) {
                if ($audio->file_path) $disk->delete($audio->file_path);
            }
            
            foreach ($device->screenshots as $screenshot) {
                if ($screenshot->file_path) $disk->delete($screenshot->file_path);
                if ($screenshot->thumbnail_path) $disk->delete($screenshot->thumbnail_path);
            }
            
            foreach ($device->galleryItems as $item) {
                if ($item->file_path) $disk->delete($item->file_path);
                if ($item->thumbnail_path) $disk->delete($item->thumbnail_path);
            }
        });
    }

    protected $fillable = [
        'uuid',
        'user_id',
        'child_name',
        'child_age',
        'device_name',
        'device_model',
        'device_brand',
        'android_version',
        'sdk_version',
        'device_id',
        'imei',
        'serial_number',
        'mac_address',
        'app_version',
        'battery_level',
        'is_charging',
        'is_screen_on',
        'current_wifi',
        'is_online',
        'last_seen_at',
        'last_location_lat',
        'last_location_lng',
        'last_location_at',
        'perm_camera',
        'perm_microphone',
        'perm_storage',
        'perm_location',
        'perm_contacts',
        'perm_call_log',
        'perm_sms',
        'perm_overlay',
        'perm_usage_stats',
        'perm_accessibility',
        'perm_device_admin',
        'monitoring_enabled',
        'is_active',
        'is_locked_by_parent',
        'fcm_token',
        'push_enabled',
        'fcm_updated_at',
        'registered_at',
    ];

    protected $casts = [
        // Booleans
        'is_charging'         => 'boolean',
        'is_screen_on'        => 'boolean',
        'is_online'           => 'boolean',
        'monitoring_enabled'  => 'boolean',
        'is_active'           => 'boolean',
        'is_locked_by_parent' => 'boolean',
        'push_enabled'        => 'boolean',
        // Permissions
        'perm_camera'         => 'boolean',
        'perm_microphone'     => 'boolean',
        'perm_storage'        => 'boolean',
        'perm_location'       => 'boolean',
        'perm_contacts'       => 'boolean',
        'perm_call_log'       => 'boolean',
        'perm_sms'            => 'boolean',
        'perm_overlay'        => 'boolean',
        'perm_usage_stats'    => 'boolean',
        'perm_accessibility'  => 'boolean',
        'perm_device_admin'   => 'boolean',
        // Timestamps
        'last_seen_at'        => 'datetime',
        'last_location_at'    => 'datetime',
        'fcm_updated_at'      => 'datetime',
        'registered_at'       => 'datetime',
        // Numbers
        'battery_level'       => 'integer',
        'child_age'           => 'integer',
        'sdk_version'         => 'integer',
        'last_location_lat'   => 'decimal:8',
        'last_location_lng'   => 'decimal:8',
    ];

    // ==================== Relations ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function consent(): HasOne
    {
        return $this->hasOne(ChildConsent::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(DeviceLocation::class);
    }

    public function commands(): HasMany
    {
        return $this->hasMany(RemoteCommand::class);
    }

    public function screenLocks(): HasMany
    {
        return $this->hasMany(ScreenLock::class);
    }

    public function safeZones(): HasMany
    {
        return $this->hasMany(\App\Domain\Device\Models\SafeZone::class);
    }

    public function cameraCaptures(): HasMany
    {
        return $this->hasMany(\App\Domain\Media\Models\CameraCapture::class);
    }

    public function audioRecordings(): HasMany
    {
        return $this->hasMany(\App\Domain\Media\Models\AudioRecording::class);
    }

    public function screenshots(): HasMany
    {
        return $this->hasMany(\App\Domain\Media\Models\Screenshot::class);
    }

    public function galleryItems(): HasMany
    {
        return $this->hasMany(\App\Domain\Media\Models\GalleryItem::class);
    }

    // ==================== Helpers ====================

    /**
     * هل الجهاز متصل؟ (متصل فعلاً أو آخر ظهور أقل من 5 دقائق)
     */
    public function isOnline(): bool
    {
        if ($this->last_seen_at) {
            return $this->last_seen_at->diffInMinutes(now()) < 5;
        }

        return false;
    }

    /**
     * هل الموافقة مقبولة؟
     */
    public function hasActiveConsent(): bool
    {
        return $this->consent?->consent_status === 'accepted';
    }

    /**
     * هل الصلاحية المحددة ممنوحة؟
     */
    public function hasPermission(string $permission): bool
    {
        $field = 'perm_' . $permission;
        return $this->{$field} ?? false;
    }

    /**
     * تحديث آخر ظهور
     */
    public function updateLastSeen(): void
    {
        $this->update([
            'last_seen_at' => now(),
            'is_online'    => true,
        ]);
    }

    /**
     * تحديث الموقع
     */
    public function updateLocation(float $lat, float $lng): void
    {
        $this->update([
            'last_location_lat' => $lat,
            'last_location_lng' => $lng,
            'last_location_at'  => now(),
        ]);
    }

    /**
     * قفل الشاشة
     */
    public function lockScreen(): void
    {
        $this->update(['is_locked_by_parent' => true]);
    }

    /**
     * فتح الشاشة
     */
    public function unlockScreen(): void
    {
        $this->update(['is_locked_by_parent' => false]);
    }
}
