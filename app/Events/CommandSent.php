<?php

namespace App\Events;

use App\Domain\Device\Models\RemoteCommand;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommandSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly RemoteCommand $command
    ) {}

    /**
     * البث على Channel الخاص بالجهاز
     */
    public function broadcastOn(): array
    {
        return [
            new \Illuminate\Broadcasting\Channel('device.' . $this->command->device_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'command.received';
    }

    public function broadcastWith(): array
    {
        return [
            'command_uuid'     => $this->command->uuid,
            'command_category' => $this->command->command_category,
            'command_type'     => $this->command->command_type,
            'command_data'     => $this->command->command_data,
            'priority'         => $this->command->priority,
        ];
    }
}
