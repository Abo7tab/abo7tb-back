<?php

namespace App\Domain\Device\Models;

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScreenLock extends Model
{
    protected $table = 'screen_locks';

    protected $fillable = [
        'device_id',
        'lock_type',
        'message_title',
        'message_body',
        'background_color',
        'show_message',
        'is_active',
        'start_time',
        'end_time',
        'allow_emergency_calls',
        'allow_alarm',
        'allow_music',
        'whitelisted_numbers',
        'locked_by',
        'unlocked_by',
        'locked_at',
        'unlocked_at',
    ];

    protected $casts = [
        'show_message'          => 'boolean',
        'is_active'             => 'boolean',
        'allow_emergency_calls' => 'boolean',
        'allow_alarm'           => 'boolean',
        'allow_music'           => 'boolean',
        'whitelisted_numbers'   => 'array',
        'start_time'            => 'datetime',
        'end_time'              => 'datetime',
        'locked_at'             => 'datetime',
        'unlocked_at'           => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function lockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function unlockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unlocked_by');
    }

    public function isCurrentlyActive(): bool
    {
        return $this->is_active && $this->unlocked_at === null;
    }

    public function hasExpired(): bool
    {
        return $this->end_time !== null && $this->end_time->isPast();
    }

    public function getLockConfig(): array
    {
        return [
            'lock_type'             => $this->lock_type,
            'message_title'         => $this->message_title,
            'message_body'          => $this->message_body,
            'background_color'      => $this->background_color,
            'show_message'          => $this->show_message,
            'allow_emergency_calls' => $this->allow_emergency_calls,
            'allow_alarm'           => $this->allow_alarm,
            'allow_music'           => $this->allow_music,
            'whitelisted_numbers'   => $this->whitelisted_numbers ?? [],
            'end_time'              => $this->end_time?->toIso8601String(),
        ];
    }
}
