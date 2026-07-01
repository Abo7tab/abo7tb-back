<?php

namespace App\Domain\Device\Services;

use App\Domain\Device\DTOs\SendCommandDTO;
use App\Domain\Device\Models\Device;
use App\Domain\Device\Models\RemoteCommand;
use App\Domain\Device\Repositories\DeviceRepositoryInterface;
use App\Events\CommandSent;
use App\Jobs\SendCommandViaFcm;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CommandService
{
    public function __construct(
        protected DeviceRepositoryInterface $deviceRepo
    ) {}

    /**
     * إرسال أمر للجهاز
     */
    public function sendCommand(SendCommandDTO $dto): RemoteCommand
    {
        $device = Device::findOrFail($dto->deviceId);

        // التحقق من الموافقة للأوامر الحساسة
        $this->checkConsentForCommand($device, $dto->commandCategory);

        $expiryMinutes = config('parental-control.commands.expiration', 60);

        $command = RemoteCommand::create([
            'uuid'             => Str::uuid(),
            'device_id'        => $dto->deviceId,
            'command_category' => $dto->commandCategory,
            'command_type'     => $dto->commandType,
            'command_data'     => $dto->commandData,
            'status'           => 'pending',
            'priority'         => $dto->priority,
            'created_by'       => $dto->createdBy,
            'expires_at'       => now()->addMinutes($expiryMinutes),
        ]);

        // إرسال عبر Reverb إذا كان الجهاز متصلاً
        if ($device->isOnline()) {
            try {
                broadcast(new CommandSent($command))->toOthers();
                $command->markAsSent();
            } catch (\Exception $e) {
                // Ignore broadcast error, device will fetch via polling
                \Illuminate\Support\Facades\Log::warning("Failed to broadcast command: " . $e->getMessage());
            }
        }

        if ($device->fcm_token && $device->push_enabled) {
            SendCommandViaFcm::dispatch($device, $command);
        }

        return $command;
    }

    /**
     * تحديث حالة الأمر (من الجهاز بعد التنفيذ)
     */
    public function updateCommandStatus(
        string $uuid,
        string $status,
        array  $result = [],
        string $error  = ''
    ): RemoteCommand {

        $command = RemoteCommand::where('uuid', $uuid)->firstOrFail();

        match ($status) {
            'completed' => $command->markAsCompleted($result),
            'failed'    => $command->markAsFailed($error),
            default     => $command->update(['status' => $status]),
        };

        return $command->fresh();
    }

    /**
     * التحقق من الموافقة للأوامر الحساسة (كاميرا، مايك، معرض)
     */
    private function checkConsentForCommand(Device $device, string $category): void
    {
        $sensitiveCategories = [
            'camera'     => 'allow_camera',
            'microphone' => 'allow_microphone',
            'gallery'    => 'allow_gallery',
        ];

        if (!isset($sensitiveCategories[$category])) {
            return;
        }

        $consent = $device->consent;
        if (!$consent || !$consent->isAccepted()) {
            throw new \RuntimeException('لم يتم قبول موافقة الطفل بعد.');
        }

        $permission = $sensitiveCategories[$category];
        if (!$consent->{$permission}) {
            throw new \RuntimeException("الطفل لم يوافق على صلاحية: {$category}");
        }
    }

    /**
     * الحصول على الأوامر المعلقة (يستدعيها الجهاز)
     */
    public function getPendingCommands(int $deviceId): array
    {
        return DB::select('CALL sp_get_pending_commands(?)', [$deviceId]);
    }
}
