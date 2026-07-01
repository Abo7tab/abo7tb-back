<?php

namespace App\Jobs;

use App\Domain\Device\Models\Device;
use App\Domain\Notification\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckDeviceStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function handle(NotificationService $notifService): void
    {
        // أجهزة كانت أونلاين وانقطعت منذ أكثر من 10 دقائق
        $offlineDevices = Device::where('is_online', true)
            ->where('last_seen_at', '<', now()->subMinutes(10))
            ->get();

        foreach ($offlineDevices as $device) {
            $device->update(['is_online' => false]);

            $notifService->notifyDeviceOffline(
                userId:    $device->user_id,
                deviceId:  $device->id,
                childName: $device->child_name
            );
        }

        // أجهزة كانت أوفلاين وعادت خلال آخر 5 دقائق
        Device::where('is_online', false)
            ->where('last_seen_at', '>', now()->subMinutes(5))
            ->update(['is_online' => true]);
    }
}
