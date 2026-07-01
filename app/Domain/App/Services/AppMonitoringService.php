<?php

namespace App\Domain\App\Services;

use App\Domain\App\DTOs\RecordUsageDTO;
use App\Domain\App\DTOs\SyncAppsDTO;
use App\Domain\App\Models\AppUsage;
use App\Domain\App\Models\InstalledApp;
use App\Domain\App\Models\ScreenTime;

class AppMonitoringService
{
    /**
     * مزامنة التطبيقات المثبتة
     * يستقبل قائمة التطبيقات من الجهاز ويحدث قاعدة البيانات
     *
     * @return array{synced: int, updated: int, errors: int, total: int}
     */
    public function syncInstalledApps(SyncAppsDTO $dto): array
    {
        $synced  = 0;
        $updated = 0;
        $errors  = 0;

        foreach ($dto->appsList as $appData) {
            try {
                $package = $appData['package_name'] ?? null;
                if (!$package) {
                    continue;
                }

                $existing = InstalledApp::where('device_id',    $dto->deviceId)
                                        ->where('package_name', $package)
                                        ->first();

                if ($existing) {
                    $existing->update([
                        'app_name'     => $appData['app_name']     ?? $existing->app_name,
                        'version_name' => $appData['version_name'] ?? $existing->version_name,
                        'version_code' => $appData['version_code'] ?? $existing->version_code,
                        'app_size'     => $appData['app_size']     ?? $existing->app_size,
                        'is_enabled'   => $appData['is_enabled']   ?? true,
                        'update_date'  => $appData['update_date']  ?? null,
                        'last_seen'    => now(),
                    ]);
                    $updated++;
                } else {
                    InstalledApp::create([
                        'device_id'     => $dto->deviceId,
                        'app_name'      => $appData['app_name']      ?? $package,
                        'package_name'  => $package,
                        'version_name'  => $appData['version_name']  ?? null,
                        'version_code'  => $appData['version_code']  ?? null,
                        'app_size'      => $appData['app_size']      ?? null,
                        'app_icon'      => $appData['app_icon']      ?? null,
                        'is_system_app' => $appData['is_system_app'] ?? false,
                        'is_enabled'    => $appData['is_enabled']    ?? true,
                        'install_date'  => $appData['install_date']  ?? null,
                        'update_date'   => $appData['update_date']   ?? null,
                        'first_seen'    => now(),
                        'last_seen'     => now(),
                    ]);
                    $synced++;
                }
            } catch (\Exception $e) {
                $errors++;
            }
        }

        return [
            'synced'  => $synced,
            'updated' => $updated,
            'errors'  => $errors,
            'total'   => count($dto->appsList),
        ];
    }

    /**
     * تسجيل الاستخدام اليومي للتطبيقات
     *
     * @return array{recorded: int, errors: int, date: string}
     */
    public function recordDailyUsage(RecordUsageDTO $dto): array
    {
        $recorded = 0;
        $errors   = 0;

        // حفظ استخدام كل تطبيق
        foreach ($dto->usageData as $usage) {
            try {
                $package = $usage['package_name'] ?? null;
                if (!$package) {
                    continue;
                }

                AppUsage::updateOrCreate(
                    [
                        'device_id'    => $dto->deviceId,
                        'package_name' => $package,
                        'usage_date'   => $dto->date,
                    ],
                    [
                        'app_name'       => $usage['app_name']       ?? $package,
                        'foreground_sec' => (int) ($usage['foreground_sec'] ?? 0),
                        'background_sec' => (int) ($usage['background_sec'] ?? 0),
                        'launch_count'   => (int) ($usage['launch_count']   ?? 0),
                        'data_sent'      => (int) ($usage['data_sent']      ?? 0),
                        'data_received'  => (int) ($usage['data_received']  ?? 0),
                        'last_used_at'   => $usage['last_used_at']   ?? null,
                    ]
                );

                $recorded++;
            } catch (\Exception $e) {
                $errors++;
            }
        }

        // حفظ وقت الشاشة الإجمالي
        ScreenTime::updateOrCreate(
            [
                'device_id' => $dto->deviceId,
                'date'      => $dto->date,
            ],
            [
                'total_sec'       => $dto->totalScreenSec,
                'screen_on_sec'   => $dto->screenOnSec,
                'interactive_sec' => $dto->interactiveSec,
                'unlock_count'    => $dto->unlockCount,
            ]
        );

        return [
            'recorded' => $recorded,
            'errors'   => $errors,
            'date'     => $dto->date,
        ];
    }

    /**
     * إحصائيات استخدام التطبيقات
     *
     * @return array{period: string, total_sec: int, apps: array}
     */
    public function getUsageStats(int $deviceId, string $period = 'today'): array
    {
        $query = AppUsage::where('device_id', $deviceId);

        match ($period) {
            'week'  => $query->whereBetween('usage_date', [now()->startOfWeek(), now()->endOfWeek()]),
            'month' => $query->whereBetween('usage_date', [now()->startOfMonth(), now()->endOfMonth()]),
            default => $query->whereDate('usage_date', today()),
        };

        $usage    = $query->orderByDesc('foreground_sec')->get();
        $totalSec = (int) $usage->sum('foreground_sec');

        return [
            'period'    => $period,
            'total_sec' => $totalSec,
            'apps'      => $usage->map(fn (AppUsage $u) => [
                'package_name'   => $u->package_name,
                'app_name'       => $u->app_name,
                'foreground_sec' => $u->foreground_sec,
                'formatted'      => $u->foreground_formatted,
                'launch_count'   => $u->launch_count,
                'percentage'     => $totalSec > 0
                    ? round(($u->foreground_sec / $totalSec) * 100, 1)
                    : 0,
            ])->toArray(),
        ];
    }
}
