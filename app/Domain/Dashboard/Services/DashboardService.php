<?php

namespace App\Domain\Dashboard\Services;

use App\Domain\App\Models\AppUsage;
use App\Domain\App\Models\ScreenTime;
use App\Domain\Communication\Models\CallLog;
use App\Domain\Communication\Models\SmsLog;
use App\Domain\Device\Models\Device;
use App\Domain\Device\Models\DeviceLocation;
use App\Domain\Media\Models\AudioRecording;
use App\Domain\Media\Models\CameraCapture;
use App\Domain\Media\Models\GalleryItem;
use App\Domain\Notification\Models\Notification;
use App\Domain\Web\Models\BrowsingHistory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    private const CACHE_TTL = 300; // 5 دقائق

    // ==================== Summary ====================

    public function getSummary(int $userId): array
    {
        return Cache::remember("dashboard:summary:{$userId}", self::CACHE_TTL, function () use ($userId) {
            $devices   = Device::where('user_id', $userId)->where('is_active', true)->get();
            $deviceIds = $devices->pluck('id')->toArray();

            if (empty($deviceIds)) {
                return $this->emptySummary();
            }

            return [
                'devices' => [
                    'total'   => $devices->count(),
                    'online'  => $devices->filter(fn ($d) => $d->isOnline())->count(),
                    'offline' => $devices->filter(fn ($d) => !$d->isOnline())->count(),
                    'locked'  => $devices->where('is_locked_by_parent', true)->count(),
                ],
                'today'   => $this->getTodayStats($deviceIds),
                'alerts'  => [
                    'unread_notifications' => Notification::where('user_id', $userId)->where('is_read', false)->count(),
                    'urgent'               => Notification::where('user_id', $userId)->where('is_read', false)
                        ->whereIn('priority', ['urgent', 'high'])->count(),
                ],
                'storage' => $this->getStorageStats($deviceIds),
            ];
        });
    }

    // ==================== Screen Time Chart ====================

    public function getScreenTimeChart(int $userId, int $days = 7): array
    {
        return Cache::remember("dashboard:screentime:{$userId}:{$days}", self::CACHE_TTL, function () use ($userId, $days) {
            $deviceIds = Device::where('user_id', $userId)->where('is_active', true)->pluck('id');

            return ScreenTime::whereIn('device_id', $deviceIds)
                ->whereDate('date', '>=', now()->subDays($days))
                ->select(
                    'date',
                    DB::raw('SUM(total_sec) as total_sec'),
                    DB::raw('SUM(screen_on_sec) as screen_on_sec'),
                    DB::raw('SUM(unlock_count) as unlock_count')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(fn ($d) => [
                    'date'          => $d->date->format('Y-m-d'),
                    'day_name'      => $d->date->format('D'),
                    'total_min'     => (int) round($d->total_sec / 60),
                    'screen_on_min' => (int) round($d->screen_on_sec / 60),
                    'unlock_count'  => (int) $d->unlock_count,
                ])
                ->toArray();
        });
    }

    // ==================== Top Apps ====================

    public function getTopApps(int $userId, int $days = 7, int $limit = 10): array
    {
        return Cache::remember("dashboard:topapps:{$userId}:{$days}:{$limit}", self::CACHE_TTL, function () use ($userId, $days, $limit) {
            $deviceIds = Device::where('user_id', $userId)->where('is_active', true)->pluck('id');

            return AppUsage::whereIn('device_id', $deviceIds)
                ->whereDate('usage_date', '>=', now()->subDays($days))
                ->select(
                    'package_name',
                    'app_name',
                    DB::raw('SUM(foreground_sec) as total_foreground'),
                    DB::raw('SUM(launch_count) as total_launches')
                )
                ->groupBy('package_name', 'app_name')
                ->orderByDesc('total_foreground')
                ->limit($limit)
                ->get()
                ->map(fn ($a) => [
                    'package_name'   => $a->package_name,
                    'app_name'       => $a->app_name ?? $a->package_name,
                    'total_min'      => (int) round($a->total_foreground / 60),
                    'total_launches' => (int) $a->total_launches,
                ])
                ->toArray();
        });
    }

    // ==================== Weekly Report ====================

    public function getWeeklyReport(int $userId): array
    {
        return Cache::remember("dashboard:weekly:{$userId}", 600, function () use ($userId) {
            $deviceIds = Device::where('user_id', $userId)->where('is_active', true)->pluck('id');
            $weekStart = now()->startOfWeek();
            $weekEnd   = now()->endOfWeek();

            return [
                'period' => [
                    'start' => $weekStart->toDateString(),
                    'end'   => $weekEnd->toDateString(),
                ],
                'screen_time' => [
                    'total_hours'    => round(
                        ScreenTime::whereIn('device_id', $deviceIds)->whereBetween('date', [$weekStart, $weekEnd])->sum('total_sec') / 3600,
                        1
                    ),
                    'avg_daily_min'  => (int) round(
                        ScreenTime::whereIn('device_id', $deviceIds)->whereBetween('date', [$weekStart, $weekEnd])->avg('total_sec') / 60
                    ),
                ],
                'communication' => [
                    'total_calls'   => CallLog::whereIn('device_id', $deviceIds)->whereBetween('called_at', [$weekStart, $weekEnd])->count(),
                    'total_sms'     => SmsLog::whereIn('device_id', $deviceIds)->whereBetween('sent_at', [$weekStart, $weekEnd])->count(),
                    'unknown_calls' => CallLog::whereIn('device_id', $deviceIds)->whereBetween('called_at', [$weekStart, $weekEnd])
                        ->where('is_unknown', true)->count(),
                ],
                'web' => [
                    'total_visits' => BrowsingHistory::whereIn('device_id', $deviceIds)->whereBetween('visited_at', [$weekStart, $weekEnd])
                        ->sum('visit_count'),
                    'unique_sites'  => BrowsingHistory::whereIn('device_id', $deviceIds)->whereBetween('visited_at', [$weekStart, $weekEnd])
                        ->distinct('url')->count('url'),
                ],
                'media' => [
                    'photos_taken'    => CameraCapture::whereIn('device_id', $deviceIds)->where('capture_type', 'photo')
                        ->whereBetween('captured_at', [$weekStart, $weekEnd])->count(),
                    'videos_recorded' => CameraCapture::whereIn('device_id', $deviceIds)->where('capture_type', 'video')
                        ->whereBetween('captured_at', [$weekStart, $weekEnd])->count(),
                ],
                'security' => [
                    'apps_blocked'    => \App\Domain\App\Models\BlockedApp::whereIn('device_id', $deviceIds)->where('is_active', true)->count(),
                    'sites_blocked'   => \App\Domain\Web\Models\BlockedWebsite::whereIn('device_id', $deviceIds)->where('is_active', true)->count(),
                    'numbers_blocked' => \App\Domain\Communication\Models\BlockedNumber::whereIn('device_id', $deviceIds)->count(),
                ],
            ];
        });
    }

    // ==================== Cache Invalidation ====================

    public function clearCache(int $userId): void
    {
        Cache::forget("dashboard:summary:{$userId}");
        Cache::forget("dashboard:weekly:{$userId}");
        foreach ([7, 14, 30] as $days) {
            Cache::forget("dashboard:screentime:{$userId}:{$days}");
            Cache::forget("dashboard:topapps:{$userId}:{$days}:10");
        }
    }

    // ==================== Private ====================

    private function getTodayStats(array $deviceIds): array
    {
        $today = today();

        return [
            'screen_time_min' => (int) round(
                ScreenTime::whereIn('device_id', $deviceIds)->whereDate('date', $today)->sum('total_sec') / 60
            ),
            'apps_used'   => AppUsage::whereIn('device_id', $deviceIds)->whereDate('usage_date', $today)->count(),
            'calls'       => CallLog::whereIn('device_id', $deviceIds)->whereDate('called_at', $today)->count(),
            'sms'         => SmsLog::whereIn('device_id', $deviceIds)->whereDate('sent_at', $today)->count(),
            'websites'    => BrowsingHistory::whereIn('device_id', $deviceIds)->whereDate('visited_at', $today)->count(),
            'locations'   => DeviceLocation::whereIn('device_id', $deviceIds)->whereDate('recorded_at', $today)->count(),
        ];
    }

    private function getStorageStats(array $deviceIds): array
    {
        $photos  = CameraCapture::whereIn('device_id', $deviceIds)->where('parent_deleted', false)->sum('file_size');
        $audio   = AudioRecording::whereIn('device_id', $deviceIds)->where('parent_deleted', false)->sum('file_size');
        $gallery = GalleryItem::whereIn('device_id', $deviceIds)->sum('file_size');
        $total   = $photos + $audio + $gallery;

        return [
            'photos_mb'  => round($photos  / 1048576, 2),
            'audio_mb'   => round($audio   / 1048576, 2),
            'gallery_mb' => round($gallery  / 1048576, 2),
            'total_mb'   => round($total   / 1048576, 2),
            'total_gb'   => round($total   / 1073741824, 2),
        ];
    }

    private function emptySummary(): array
    {
        return [
            'devices' => ['total' => 0, 'online' => 0, 'offline' => 0, 'locked' => 0],
            'today'   => ['screen_time_min' => 0, 'apps_used' => 0, 'calls' => 0, 'sms' => 0, 'websites' => 0, 'locations' => 0],
            'alerts'  => ['unread_notifications' => 0, 'urgent' => 0],
            'storage' => ['photos_mb' => 0, 'audio_mb' => 0, 'gallery_mb' => 0, 'total_mb' => 0, 'total_gb' => 0],
        ];
    }
}
