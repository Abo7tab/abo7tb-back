<?php

namespace App\Domain\App\Services;

use App\Domain\App\DTOs\BlockAppDTO;
use App\Domain\App\Models\BlockedApp;
use App\Domain\Device\Models\Device;
use App\Domain\Device\Models\RemoteCommand;
use App\Events\CommandSent;
use Illuminate\Support\Str;

class AppBlockingService
{
    /**
     * حظر تطبيق
     */
    public function blockApp(BlockAppDTO $dto): BlockedApp
    {
        $blocked = BlockedApp::updateOrCreate(
            [
                'device_id'    => $dto->deviceId,
                'package_name' => $dto->packageName,
            ],
            [
                'app_name'      => $dto->appName,
                'block_type'    => $dto->blockType,
                'reason'        => $dto->reason,
                'blocked_until' => $dto->blockedUntil,
                'is_active'     => true,
                'blocked_at'    => now(),
            ]
        );

        // إرسال أمر للجهاز لتطبيق الحظر فوراً
        $this->sendBlockCommand($dto->deviceId, $dto->packageName, 'block');

        return $blocked->fresh();
    }

    /**
     * إلغاء حظر تطبيق
     */
    public function unblockApp(int $deviceId, string $packageName): bool
    {
        $result = BlockedApp::where('device_id',    $deviceId)
                            ->where('package_name', $packageName)
                            ->update(['is_active' => false]);

        if ($result) {
            // إرسال أمر للجهاز لإلغاء الحظر
            $this->sendBlockCommand($deviceId, $packageName, 'unblock');
        }

        return $result > 0;
    }

    /**
     * جلب التطبيقات المحظورة للجهاز
     */
    public function getBlockedApps(int $deviceId): array
    {
        return BlockedApp::where('device_id', $deviceId)
            ->where('is_active', true)
            ->get()
            ->map(fn (BlockedApp $app) => [
                'package_name'  => $app->package_name,
                'app_name'      => $app->app_name,
                'block_type'    => $app->block_type,
                'blocked_until' => $app->blocked_until?->toIso8601String(),
                'is_blocked'    => $app->isCurrentlyBlocked(),
            ])
            ->toArray();
    }

    /**
     * التحقق إذا كان التطبيق محظوراً (يُستخدم من الجهاز)
     */
    public function isAppBlocked(int $deviceId, string $packageName): bool
    {
        $blocked = BlockedApp::where('device_id',    $deviceId)
                             ->where('package_name', $packageName)
                             ->where('is_active',    true)
                             ->first();

        return $blocked ? $blocked->isCurrentlyBlocked() : false;
    }

    /**
     * إرسال أمر حظر/إلغاء حظر للجهاز عبر Reverb
     */
    private function sendBlockCommand(
        int    $deviceId,
        string $packageName,
        string $action
    ): void {
        try {
            $device = Device::find($deviceId);
            if (!$device || !$device->isOnline()) {
                return;
            }

            $command = RemoteCommand::create([
                'uuid'             => Str::uuid(),
                'device_id'        => $deviceId,
                'command_category' => 'app',
                'command_type'     => $action === 'block' ? 'block_app' : 'unblock_app',
                'command_data'     => ['package_name' => $packageName],
                'status'           => 'pending',
                'priority'         => 'high',
                'created_by'       => auth()->id() ?? 1,
                'expires_at'       => now()->addHour(),
            ]);

            broadcast(new CommandSent($command));
            $command->markAsSent();

        } catch (\Exception) {
            // Log error but don't fail the blocking operation
        }
    }
}
