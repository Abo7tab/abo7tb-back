<?php

namespace App\Domain\Media\Models;

use App\Domain\Device\Models\Device;
use App\Domain\Shared\Traits\HasUuid;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class AudioRecording extends Model
{
    use HasUuid;

    protected $table = 'audio_recordings';

    protected $fillable = [
        'uuid', 'device_id', 'file_path', 'file_size', 'duration_sec',
        'quality', 'trigger_type', 'trigger_reason', 'status',
        'requested_by', 'parent_viewed', 'parent_viewed_at',
        'parent_deleted', 'parent_notes', 'started_at', 'ended_at', 'uploaded_at',
    ];

    protected $casts = [
        'file_size'        => 'integer',
        'duration_sec'     => 'integer',
        'parent_viewed'    => 'boolean',
        'parent_deleted'   => 'boolean',
        'started_at'       => 'datetime',
        'ended_at'         => 'datetime',
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

    public function getFileUrl(): string
    {
        return Storage::disk('media')->url($this->file_path);
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

    public function getDurationFormatted(): string
    {
        $sec = $this->duration_sec ?? 0;
        return sprintf('%02d:%02d', (int) floor($sec / 60), $sec % 60);
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

    public function scopeUnviewed($query)
    {
        return $query->where('parent_viewed', false);
    }

    public function scopeNotDeleted($query)
    {
        return $query->where('parent_deleted', false)
                     ->where('status', '!=', 'deleted');
    }
}
