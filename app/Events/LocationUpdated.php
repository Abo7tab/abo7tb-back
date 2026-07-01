<?php

namespace App\Events;

use App\Domain\Device\Models\Device;
use App\Domain\Device\Models\DeviceLocation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Device         $device,
        public readonly DeviceLocation $location
    ) {}

    /**
     * البث على Channel الخاص بالأب
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->device->user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'location.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'device_id'   => $this->device->id,
            'device_uuid' => $this->device->uuid,
            'child_name'  => $this->device->child_name,
            'latitude'    => $this->location->latitude,
            'longitude'   => $this->location->longitude,
            'accuracy'    => $this->location->accuracy,
            'speed'       => $this->location->speed,
            'address'     => $this->location->address,
            'city'        => $this->location->city,
            'recorded_at' => $this->location->recorded_at?->toIso8601String(),
        ];
    }
}
