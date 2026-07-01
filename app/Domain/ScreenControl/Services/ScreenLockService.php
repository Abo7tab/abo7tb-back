<?php

namespace App\Domain\ScreenControl\Services;

use App\Domain\Device\Models\Device;
use App\Domain\Device\Models\RemoteCommand;
use App\Domain\Device\Models\ScreenLock;
use App\Events\CommandSent;
use Illuminate\Support\Str;

class ScreenLockService
{
    /**
     * قفل الشاشة فوراً
     */
    public function lockScreen(
        Device   $device,
        string   $lockType        = 'black_screen',
        ?string  $messageTitle    = null,
        ?string  $messageBody     = null,
        string   $backgroundColor = '#000000',
        bool     $showMessage     = false,
        ?int     $durationMinutes = null,
        bool     $allowCalls      = true,
        bool     $allowAlarm      = true,
        bool     $allowMusic      = false,
        array    $whitelistedNums = [],
        int      $lockedBy        = 0
    ): ScreenLock {

        // إلغاء أي قفل نشط
        ScreenLock::where('device_id', $device->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        $endTime = $durationMinutes
            ? now()->addMinutes($durationMinutes)
            : null;

        $lock = ScreenLock::create([
            'device_id'             => $device->id,
            'lock_type'             => $lockType,
            'message_title'         => $messageTitle ?? $this->getDefaultTitle($lockType),
            'message_body'          => $messageBody  ?? $this->getDefaultBody($lockType),
            'background_color'      => $backgroundColor,
            'show_message'          => $showMessage,
            'is_active'             => true,
            'start_time'            => now(),
            'end_time'              => $endTime,
            'allow_emergency_calls' => $allowCalls,
            'allow_alarm'           => $allowAlarm,
            'allow_music'           => $allowMusic,
            'whitelisted_numbers'   => $whitelistedNums,
            'locked_by'             => $lockedBy,
            'locked_at'             => now(),
        ]);

        // تحديث الجهاز
        if (method_exists($device, 'lockScreen')) {
            $device->lockScreen();
        } else {
            $device->update(['is_locked_by_parent' => true]);
        }

        $this->sendLockCommand($device, $lock);

        return $lock;
    }

    /**
     * فتح الشاشة
     */
    public function unlockScreen(Device $device, int $unlockedBy = 0): bool
    {
        ScreenLock::where('device_id', $device->id)
            ->where('is_active', true)
            ->update([
                'is_active'   => false,
                'unlocked_by' => $unlockedBy,
                'unlocked_at' => now(),
            ]);

        if (method_exists($device, 'unlockScreen')) {
            $device->unlockScreen();
        } else {
            $device->update(['is_locked_by_parent' => false]);
        }

        $this->sendUnlockCommand($device);

        return true;
    }

    /**
     * القفل النشط حالياً
     */
    public function getActiveLock(int $deviceId): ?ScreenLock
    {
        $lock = ScreenLock::where('device_id', $deviceId)
            ->where('is_active', true)
            ->latest('locked_at')
            ->first();

        if ($lock && $lock->end_time && $lock->end_time->isPast()) {
            $lock->update(['is_active' => false, 'unlocked_at' => now()]);

            $device = Device::find($deviceId);
            if ($device) {
                $device->update(['is_locked_by_parent' => false]);
                $this->sendUnlockCommand($device);
            }

            return null;
        }

        return $lock;
    }

    /**
     * سجل القفل
     */
    public function getLockHistory(int $deviceId, int $perPage = 20)
    {
        return ScreenLock::where('device_id', $deviceId)
            ->orderByDesc('locked_at')
            ->paginate($perPage);
    }

    // ==================== Private ====================

    private function sendLockCommand(Device $device, ScreenLock $lock): void
    {
        try {
            $commandData = [
                'lock_type'        => $lock->lock_type,
                'message_title'    => $lock->message_title,
                'message_body'     => $lock->message_body,
                'background_color' => $lock->background_color,
                'show_message'     => $lock->show_message,
                'allow_calls'      => $lock->allow_emergency_calls,
                'allow_alarm'      => $lock->allow_alarm,
                'allow_music'      => $lock->allow_music,
                'end_time'         => $lock->end_time?->toIso8601String(),
            ];

            $command = RemoteCommand::create([
                'uuid'             => Str::uuid(),
                'device_id'        => $device->id,
                'command_category' => 'screen',
                'command_type'     => 'lock',
                'command_data'     => $commandData,
                'status'           => 'pending',
                'priority'         => 'urgent',
                'created_by'       => $lock->locked_by ?? 1,
                'expires_at'       => now()->addMinutes(2),
            ]);

            broadcast(new CommandSent($command));
            $command->markAsSent();
        } catch (\Exception) {
            // لا توقف العملية بسبب فشل البث
        }
    }

    private function sendUnlockCommand(Device $device): void
    {
        try {
            $command = RemoteCommand::create([
                'uuid'             => Str::uuid(),
                'device_id'        => $device->id,
                'command_category' => 'screen',
                'command_type'     => 'unlock',
                'command_data'     => [],
                'status'           => 'pending',
                'priority'         => 'urgent',
                'created_by'       => auth()->id() ?? 1,
                'expires_at'       => now()->addMinutes(2),
            ]);

            broadcast(new CommandSent($command));
            $command->markAsSent();
        } catch (\Exception) {
            //
        }
    }

    private function getDefaultTitle(string $lockType): string
    {
        return match ($lockType) {
            'bedtime'    => '⏰ وقت النوم',
            'study_time' => '📚 وقت الدراسة',
            'punishment' => '⛔ الجهاز مقفل',
            'emergency'  => '🚨 طوارئ',
            default      => '🔒 الجهاز مقفل',
        };
    }

    private function getDefaultBody(string $lockType): string
    {
        return match ($lockType) {
            'bedtime'    => 'حان وقت النوم. تصبح على خير! 🌙',
            'study_time' => 'حان وقت المذاكرة. ركّز في دراستك! 📖',
            'punishment' => 'تم قفل جهازك مؤقتاً.',
            'emergency'  => 'تم قفل الجهاز بسبب طوارئ.',
            default      => 'الجهاز مقفل من قِبل وليّ الأمر.',
        };
    }
}
