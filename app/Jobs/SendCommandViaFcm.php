<?php

namespace App\Jobs;

use App\Domain\Device\Models\Device;
use App\Domain\Device\Models\RemoteCommand;
use App\Domain\Notification\Exceptions\InvalidFcmTokenException;
use App\Domain\Notification\Services\FirebaseMessagingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendCommandViaFcm implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(
        public Device $device,
        public RemoteCommand $command
    ) {}

    public function handle(FirebaseMessagingService $firebase): void
    {
        if (!$this->device->fcm_token || !$this->device->push_enabled) {
            Log::info('FCM skipped - no token or disabled', [
                'device_id' => $this->device->id,
                'command_uuid' => $this->command->uuid,
            ]);

            return;
        }

        try {
            $result = $firebase->sendToDevice($this->device->fcm_token, [
                'type' => 'new_command',
                'command_uuid' => $this->command->uuid,
                'category' => $this->command->command_category,
                'command_type' => $this->command->command_type,
                'priority' => $this->command->priority,
                'timestamp' => now()->toIso8601String(),
            ], $this->command->priority ?? 'high');

            $this->command->update([
                'sent_at' => now(),
                'delivery_method' => 'fcm',
            ]);

            Log::info('FCM sent successfully', [
                'device_id' => $this->device->id,
                'command_uuid' => $this->command->uuid,
                'message_id' => $result['name'] ?? null,
            ]);
        } catch (InvalidFcmTokenException $e) {
            Log::warning('FCM token invalid - clearing', [
                'device_id' => $this->device->id,
                'command_uuid' => $this->command->uuid,
                'error' => $e->getMessage(),
            ]);

            $this->device->update([
                'fcm_token' => null,
                'push_enabled' => false,
            ]);
        } catch (\Throwable $e) {
            Log::error('FCM Send Failed', [
                'device_id' => $this->device->id,
                'command_uuid' => $this->command->uuid,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('FCM Job Failed Permanently', [
            'device_id' => $this->device->id,
            'command_uuid' => $this->command->uuid,
            'error' => $exception->getMessage(),
        ]);
    }
}
