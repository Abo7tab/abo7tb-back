<?php

namespace App\Domain\Notification\Models;

use App\Domain\Device\Models\Device;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $table = 'notifications';

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'device_id', 'title', 'message',
        'type', 'priority', 'icon', 'data',
        'is_read', 'read_at', 'created_at',
    ];

    protected $casts = [
        'data'       => 'array',
        'is_read'    => 'boolean',
        'read_at'    => 'datetime',
        'created_at' => 'datetime',
    ];

    // ==================== Relations ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    // ==================== Helpers ====================

    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function getPriorityColor(): string
    {
        return match ($this->priority) {
            'urgent' => '#FF0000',
            'high'   => '#FF6600',
            'medium' => '#0066FF',
            default  => '#999999',
        };
    }

    // ==================== Scopes ====================

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
