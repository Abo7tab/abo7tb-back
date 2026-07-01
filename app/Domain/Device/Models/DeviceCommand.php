<?php

namespace App\Domain\Device\Models;

use App\Domain\Shared\Enums\CommandType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceCommand extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'device_id',
        'type',
        'payload',
        'status',
        'retries',
        'executed_at',
        'expires_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type'        => CommandType::class,
            'payload'     => 'array',
            'executed_at' => 'datetime',
            'expires_at'  => 'datetime',
        ];
    }

    /**
     * Get the device this command belongs to
     */
    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * Scope: pending commands only
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending')
                     ->where('expires_at', '>', now());
    }
}
