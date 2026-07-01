<?php

namespace App\Jobs;

use App\Domain\Device\Models\Device;
use App\Domain\ScreenControl\Services\ScreenLockService;
use App\Domain\TimeManagement\Services\TimeLimitService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EnforceTimeLimits implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function handle(
        TimeLimitService  $timeLimitService,
        ScreenLockService $lockService
    ): void {
        $devices = Device::where('is_active', true)
            ->where('monitoring_enabled', true)
            ->where('is_locked_by_parent', false) // لا تقفل جهاز مقفول أصلاً
            ->get();

        foreach ($devices as $device) {
            $dailyCheck = $timeLimitService->checkDailyLimit($device->id);

            if (($dailyCheck['exceeded'] ?? false)) {
                $lockService->lockScreen(
                    device:       $device,
                    lockType:     'custom_message',
                    messageTitle: '⏰ انتهى وقت الشاشة',
                    messageBody:  'وصلت للحد اليومي المسموح. حاول بكرة!',
                    showMessage:  true,
                    lockedBy:     0 // System
                );
            }
        }
    }
}
