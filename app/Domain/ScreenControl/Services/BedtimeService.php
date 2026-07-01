<?php

namespace App\Domain\ScreenControl\Services;

use App\Domain\TimeManagement\Models\TimeLimit;

class BedtimeService
{
    public function __construct(
        protected ScreenLockService $lockService
    ) {}

    /**
     * جدولة وقت النوم (يُحفظ كـ TimeLimit)
     */
    public function scheduleBedtime(
        int    $deviceId,
        string $startTime,
        string $endTime,
        array  $activeDays,
        ?string $message   = null,
        bool   $allowCalls = true,
        bool   $allowAlarm = true,
        int    $createdBy  = 0
    ): array {

        TimeLimit::updateOrCreate(
            [
                'device_id'  => $deviceId,
                'limit_type' => 'bedtime',
            ],
            [
                'limit_name'            => 'وقت النوم',
                'start_time'            => $startTime,
                'end_time'              => $endTime,
                'active_days'           => $activeDays,
                'block_completely'      => true,
                'allow_emergency_calls' => $allowCalls,
                'is_active'             => true,
            ]
        );

        return [
            'scheduled'       => true,
            'start_time'      => $startTime,
            'end_time'        => $endTime,
            'active_days'     => $activeDays,
            'next_occurrence' => $this->calculateNextBedtime($startTime, $activeDays),
        ];
    }

    /**
     * إلغاء جدولة وقت النوم
     */
    public function cancelBedtime(int $deviceId): bool
    {
        return TimeLimit::where('device_id', $deviceId)
            ->where('limit_type', 'bedtime')
            ->update(['is_active' => false]) > 0;
    }

    /**
     * هل الآن وقت النوم لهذا الجهاز؟
     */
    public function isBedtime(int $deviceId): bool
    {
        $bedtime = TimeLimit::where('device_id', $deviceId)
            ->where('limit_type', 'bedtime')
            ->where('is_active', true)
            ->first();

        if (!$bedtime) {
            return false;
        }

        $now        = now();
        $dayOfWeek  = $now->dayOfWeek;
        $activeDays = $bedtime->active_days ?? [];

        if (!in_array($dayOfWeek, $activeDays)) {
            return false;
        }

        $current   = $now->format('H:i');
        $startTime = $bedtime->start_time;
        $endTime   = $bedtime->end_time;

        if ($startTime < $endTime) {
            return $current >= $startTime && $current < $endTime;
        }

        // يتجاوز منتصف الليل
        return $current >= $startTime || $current < $endTime;
    }

    /**
     * الموعد القادم لوقت النوم
     */
    private function calculateNextBedtime(string $startTime, array $activeDays): string
    {
        $now        = now();
        $dayOfWeek  = $now->dayOfWeek;
        $currentTime = $now->format('H:i');

        if (in_array($dayOfWeek, $activeDays) && $currentTime < $startTime) {
            return $now->format('Y-m-d') . ' ' . $startTime;
        }

        for ($i = 1; $i <= 7; $i++) {
            $nextDay = ($dayOfWeek + $i) % 7;
            if (in_array($nextDay, $activeDays)) {
                return $now->copy()->addDays($i)->format('Y-m-d') . ' ' . $startTime;
            }
        }

        return $now->copy()->addDay()->format('Y-m-d') . ' ' . $startTime;
    }
}
