<?php

namespace App\Domain\Media\Models;

use App\Domain\Device\Models\Device;
use App\Domain\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class GalleryItem extends Model
{
    use HasUuid;

    protected $table = 'device_gallery';

    protected $fillable = [
        'uuid', 'device_id', 'file_name', 'file_path', 'thumbnail_path',
        'file_size', 'mime_type', 'media_type', 'source_folder', 'source_app',
        'width', 'height', 'duration_sec', 'taken_at', 'latitude', 'longitude',
        'parent_viewed', 'parent_viewed_at', 'parent_flagged', 'flag_reason',
        'md5_hash', 'sync_status', 'first_seen_at',
    ];

    protected $casts = [
        'file_size'        => 'integer',
        'width'            => 'integer',
        'height'           => 'integer',
        'duration_sec'     => 'integer',
        'latitude'         => 'decimal:8',
        'longitude'        => 'decimal:8',
        'taken_at'         => 'datetime',
        'parent_viewed'    => 'boolean',
        'parent_viewed_at' => 'datetime',
        'parent_flagged'   => 'boolean',
        'first_seen_at'    => 'datetime',
    ];

    // ==================== Relations ====================

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    // ==================== Helpers ====================

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
        return sprintf('%02d:%02d', (int) floor($this->duration_sec / 60), $this->duration_sec % 60);
    }

    public function isPhoto(): bool
    {
        return $this->media_type === 'photo';
    }

    public function isVideo(): bool
    {
        return $this->media_type === 'video';
    }

    public function markAsViewed(): void
    {
        $this->update([
            'parent_viewed'    => true,
            'parent_viewed_at' => now(),
        ]);
    }

    public function flag(string $reason): void
    {
        $this->update([
            'parent_flagged' => true,
            'flag_reason'    => $reason,
        ]);
    }

    // ==================== Scopes ====================

    public function scopePhotos($query)
    {
        return $query->where('media_type', 'photo');
    }

    public function scopeVideos($query)
    {
        return $query->where('media_type', 'video');
    }

    public function scopeBySource($query, string $app)
    {
        return $query->where('source_app', $app);
    }

    public function scopeByFolder($query, string $folder)
    {
        return $query->where('source_folder', $folder);
    }

    public function scopeFlagged($query)
    {
        return $query->where('parent_flagged', true);
    }

    public function scopeSynced($query)
    {
        return $query->where('sync_status', 'synced');
    }
}
