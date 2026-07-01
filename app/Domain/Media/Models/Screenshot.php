<?php

namespace App\Domain\Media\Models;

use App\Domain\Device\Models\Device;
use App\Domain\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Screenshot extends Model
{
    use HasUuid;

    protected $table = 'screenshots';

    public $timestamps = false;

    protected $fillable = [
        'uuid', 'device_id', 'file_path', 'thumbnail_path',
        'file_size', 'width', 'height', 'trigger_type', 'trigger_app',
        'parent_viewed', 'parent_viewed_at', 'captured_at',
    ];

    protected $casts = [
        'file_size'        => 'integer',
        'width'            => 'integer',
        'height'           => 'integer',
        'parent_viewed'    => 'boolean',
        'parent_viewed_at' => 'datetime',
        'captured_at'      => 'datetime',
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

    public function markAsViewed(): void
    {
        $this->update([
            'parent_viewed'    => true,
            'parent_viewed_at' => now(),
        ]);
    }

    // ==================== Scopes ====================

    public function scopeUnviewed($query)
    {
        return $query->where('parent_viewed', false);
    }

    public function scopeByApp($query, string $app)
    {
        return $query->where('trigger_app', $app);
    }
}
