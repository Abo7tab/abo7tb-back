<?php

namespace App\Domain\Media\Models;

use App\Domain\Device\Models\Device;
use App\Domain\Shared\Traits\HasUuid;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CameraCapture extends Model
{
    use HasUuid;

    protected $table = 'camera_captures';

    protected $fillable = [
        'uuid', 'device_id', 'capture_type', 'camera_facing',
        'file_path', 'thumbnail_path', 'file_size', 'mime_type',
        'duration_sec', 'width', 'height', 'latitude', 'longitude',
        'trigger_type', 'trigger_reason', 'status', 'requested_by',
        'parent_viewed', 'parent_viewed_at', 'parent_deleted',
        'parent_notes', 'captured_at', 'uploaded_at',
    ];

    protected $casts = [
        'file_size'        => 'integer',
        'duration_sec'     => 'integer',
        'width'            => 'integer',
        'height'           => 'integer',
        'latitude'         => 'decimal:8',
        'longitude'        => 'decimal:8',
        'parent_viewed'    => 'boolean',
        'parent_deleted'   => 'boolean',
        'captured_at'      => 'datetime',
        'uploaded_at'      => 'datetime',
        'parent_viewed_at' => 'datetime',
    ];

    // ==================== Relations ====================

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    // ==================== Helpers ====================

    public function isPhoto(): bool
    {
        return $this->capture_type === 'photo';
    }

    public function isVideo(): bool
    {
        return $this->capture_type === 'video';
    }

    public function getFileUrl(): string
    {
        return Storage::disk('media')->url($this->file_path);
    }

    public function getThumbnailUrl(): ?string
    {
        if (!$this->thumbnail_path) {
            return null;
        }
        return Storage::disk('media')->url($this->thumbnail_path);
    }

    public function getFileSizeFormatted(): string
    {
        $bytes = $this->file_size ?? 0;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getDurationFormatted(): ?string
    {
        if (!$this->duration_sec) {
            return null;
        }
        return sprintf('%02d:%02d',
            (int) floor($this->duration_sec / 60),
            $this->duration_sec % 60
        );
    }

    public function markAsViewed(): void
    {
        $this->update([
            'parent_viewed'    => true,
            'parent_viewed_at' => now(),
            'status'           => 'viewed',
        ]);
    }

    public function markAsDeleted(): void
    {
        $this->update([
            'parent_deleted' => true,
            'status'         => 'deleted',
        ]);
    }

    // ==================== Scopes ====================

    public function scopePhotos($query)
    {
        return $query->where('capture_type', 'photo');
    }

    public function scopeVideos($query)
    {
        return $query->where('capture_type', 'video');
    }

    public function scopeUnviewed($query)
    {
        return $query->where('parent_viewed', false);
    }

    public function scopeNotDeleted($query)
    {
        return $query->where('parent_deleted', false)
                     ->where('status', '!=', 'deleted');
    }

    public function scopeByCamera($query, string $facing)
    {
        return $query->where('camera_facing', $facing);
    }
}
