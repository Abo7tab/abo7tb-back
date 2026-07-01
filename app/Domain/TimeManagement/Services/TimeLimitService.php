<?php

namespace App\Domain\TimeManagement\Services;

use App\Domain\App\Models\AppUsage;
use App\Domain\App\Models\ScreenTime;
use App\Domain\TimeManagement\Models\TimeLimit;

class TimeLimitService
{
    /**
     * إنشاء أو تحديث قيد وقت
     */
    public function createLimit(int $deviceId, string $limitType, array $data): TimeLimit
    {
        // الأنواع التي يجب أن تكون فريدة per device
        $unique = ['bedtime', 'study_time', 'daily_total'];

        if (in_array($limitType, $unique)) {
            return TimeLimit::updateOrCreate(
                [
                    'device_id'    => $deviceId,
                    'limit_type'   => $limitType,
                    'package_name' => $data['package_name'] ?? null,
                ],
                array_merge($data, [
                    'device_id'  => $deviceId,
                    'limit_type' => $limitType,
                    'is_active'  => true,
                ])
            );
        }

        return TimeLimit::create(array_merge($data, [
            'device_id'  => $deviceId,
            'limit_type' => $limitType,
            'is_active'  => true,
        ]));
    }

    /**
     * تحديث قيد
     */
    public function updateLimit(int $limitId, array $data): bool
    {
        return TimeLimit::where('id', $limitId)->update($data) > 0;
    }

    /**
     * حذف قيد
     */
    public function deleteLimit(int $limitId): bool
    {
        return TimeLimit::where('id', $limitId)->delete() > 0;
    }

    /**
     * جميع القيود لجهاز مقسّمة بالنوع
     */
    public function getDeviceLimits(int $deviceId): array
    {
        $limits = TimeLimit::where('device_id', $deviceId)->active()->get();

        return [
            'daily_total' => $limits->firstWhere('limit_type', 'daily_total'),
            'bedtime'     => $limits->firstWhere('limit_type', 'bedtime'),
            'study_time'  => $limits->firstWhere('limit_type', 'study_time'),
            'app_limits'  => $limits->where('limit_type', 'app_specific')->values(),
            'custom'      => $limits->where('limit_type', 'custom')->values(),
        ];
    }

    /**
     * التحقق من تجاوز الحد اليومي الكلي
     *
     * @return array{has_limit: bool, max_minutes?: int, used_minutes?: int, remaining_min?: int, exceeded?: bool, percentage?: int}
     */
    public function checkDailyLimit(int $deviceId): array
    {
        $limit = TimeLimit::where('device_id', $deviceId)
            ->where('limit_type', 'daily_total')
            ->active()
            ->first();

        if (!$limit) {
            return ['has_limit' => false];
        }

        $todayUsage  = ScreenTime::where('device_id', $deviceId)
            ->whereDate('date', today())
            ->first();

        $usedMinutes = $todayUsage ? (int) floor($todayUsage->total_sec / 60) : 0;
        $maxMinutes  = $limit->max_minutes_per_day;
        $remaining   = max(0, $maxMinutes - $usedMinutes);
        $exceeded    = $usedMinutes >= $maxMinutes;

        return [
            'has_limit'     => true,
            'max_minutes'   => $maxMinutes,
            'used_minutes'  => $usedMinutes,
            'remaining_min' => $remaining,
            'exceeded'      => $exceeded,
            'percentage'    => $maxMinutes > 0
                ? min(100, (int) round(($usedMinutes / $maxMinutes) * 100))
                : 0,
        ];
    }

    /**
     * التحقق من تجاوز حد تطبيق معين
     *
     * @return array{has_limit: bool, package_name?: string, max_minutes?: int, used_minutes?: int, remaining_min?: int, exceeded?: bool}
     */
    public function checkAppLimit(int $deviceId, string $packageName): array
    {
        $limit = TimeLimit::where('device_id', $deviceId)
            ->where('limit_type', 'app_specific')
            ->where('package_name', $packageName)
            ->active()
            ->first();

        if (!$limit) {
            return ['has_limit' => false];
        }

        $todayUsage = AppUsage::where('device_id', $deviceId)
            ->where('package_name', $packageName)
            ->whereDate('usage_date', today())
            ->first();

        $usedMinutes = $todayUsage ? (int) floor($todayUsage->foreground_sec / 60) : 0;
        $maxMinutes  = $limit->max_app_minutes;

        return [
            'has_limit'     => true,
            'package_name'  => $packageName,
            'max_minutes'   => $maxMinutes,
            'used_minutes'  => $usedMinutes,
            'remaining_min' => max(0, $maxMinutes - $usedMinutes),
            'exceeded'      => $usedMinutes >= $maxMinutes,
        ];
    }

    /**
     * القيود النشطة الآن (للجهاز)
     */
    public function getActiveLimitsNow(int $deviceId): array
    {
        $limits = TimeLimit::where('device_id', $deviceId)->active()->get();

        return $limits
            ->filter(fn (TimeLimit $l) => $l->isActiveNow())
            ->map(fn (TimeLimit $l) => [
                'id'               => $l->id,
                'limit_type'       => $l->limit_type,
                'limit_name'       => $l->limit_name,
                'package_name'     => $l->package_name,
                'block_completely' => $l->block_completely,
                'allow_emergency'  => $l->allow_emergency_calls,
            ])
            ->values()
            ->toArray();
    }
}
