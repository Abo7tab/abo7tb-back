<?php

namespace App\Domain\Device\Models;

use App\Domain\Shared\Traits\HasUuid;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RemoteCommand extends Model
{
    use HasUuid;

    public const UPDATED_AT = null;

    protected $table = 'remote_commands';

    protected $fillable = [
        'uuid',
        'device_id',
        'command_category',
        'command_type',
        'command_data',
        'status',
        'result',
        'error_message',
        'priority',
        'retry_count',
        'max_retries',
        'created_by',
        'sent_at',
        'delivery_method',
        'executed_at',
        'completed_at',
        'expires_at',
    ];

    protected $casts = [
        'command_data' => 'array',
        'result'       => 'array',
        'retry_count'  => 'integer',
        'max_retries'  => 'integer',
        'sent_at'      => 'datetime',
        'executed_at'  => 'datetime',
        'completed_at' => 'datetime',
        'expires_at'   => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function markAsSent(): void
    {
        $this->update([
            'status'  => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function markAsCompleted(array $result = []): void
    {
        $this->update([
            'status'       => 'completed',
            'result'       => $result,
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed(string $error): void
    {
        $this->update([
            'status'        => 'failed',
            'error_message' => $error,
        ]);
    }
}
