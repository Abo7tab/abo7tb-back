<?php

namespace App\Domain\Device\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceLocation extends Model
{
    protected $table = 'device_locations';

    public $timestamps = false;

    protected $fillable = [
        'device_id',
        'latitude',
        'longitude',
        'altitude',
        'accuracy',
        'speed',
        'bearing',
        'address',
        'city',
        'country',
        'provider',
        'recorded_at',
    ];

    protected $casts = [
        'latitude'    => 'decimal:8',
        'longitude'   => 'decimal:8',
        'altitude'    => 'decimal:2',
        'accuracy'    => 'float',
        'speed'       => 'float',
        'bearing'     => 'float',
        'recorded_at' => 'datetime',
        // Encrypt PII at-rest (automatically decrypted when read from DB)
        'address'     => 'encrypted',
        'city'        => 'encrypted',
        'country'     => 'encrypted',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
