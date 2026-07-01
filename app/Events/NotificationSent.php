<?php

namespace App\Events;

use App\Domain\Notification\Models\Notification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Notification $notification
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->notification->user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'notification.new';
    }

    public function broadcastWith(): array
    {
        return [
            'id'         => $this->notification->id,
            'title'      => $this->notification->title,
            'message'    => $this->notification->message,
            'type'       => $this->notification->type,
            'priority'   => $this->notification->priority,
            'icon'       => $this->notification->icon,
            'color'      => $this->notification->getPriorityColor(),
            'device_id'  => $this->notification->device_id,
            'data'       => $this->notification->data,
            'created_at' => $this->notification->created_at?->toIso8601String(),
        ];
    }
}
