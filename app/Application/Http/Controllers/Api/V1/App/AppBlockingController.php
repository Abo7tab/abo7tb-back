<?php

namespace App\Application\Http\Controllers\Api\V1\App;

use App\Application\Http\Controllers\Controller;
use App\Domain\App\DTOs\BlockAppDTO;
use App\Domain\App\Models\AppUsage;
use App\Domain\App\Models\BlockedApp;
use App\Domain\App\Models\InstalledApp;
use App\Domain\App\Services\AppBlockingService;
use App\Domain\App\Services\AppMonitoringService;
use App\Domain\Device\Repositories\DeviceRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles app blocking/unblocking FROM the parent's dashboard.
 */
class AppBlockingController extends Controller
{
    public function __construct(
        protected AppBlockingService        $blockingService,
        protected AppMonitoringService      $monitoringService,
        protected DeviceRepositoryInterface $deviceRepo
    ) {}

    /**
     * GET /api/v1/devices/{uuid}/apps
     * جلب جميع التطبيقات مع حالة الحظر (للأب)
     */
    public function index(Request $request, string $uuid): JsonResponse
    {
        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device || $device->user_id !== $request->user()->id) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        // جلب التطبيقات (include_system اختياري)
        $apps = InstalledApp::where('device_id', $device->id)
            ->when(
                !$request->boolean('include_system', false),
                fn ($q) => $q->where('is_system_app', false)
            )
            ->orderBy('app_name')
            ->get();

        // جلب التطبيقات المحظورة كـ keyed collection للأداء
        $blockedApps = BlockedApp::where('device_id', $device->id)
            ->where('is_active', true)
            ->get()
            ->keyBy('package_name');

        // جلب استخدام اليوم
        $todayUsage = AppUsage::where('device_id', $device->id)
            ->whereDate('usage_date', today())
            ->pluck('foreground_sec', 'package_name')
            ->toArray();

        $appsList = $apps->map(function (InstalledApp $app) use ($blockedApps, $todayUsage) {
            $blocked = $blockedApps->get($app->package_name);

            return [
                'app_name'        => $app->app_name,
                'package_name'    => $app->package_name,
                'version_name'    => $app->version_name,
                'app_size'        => $app->app_size_formatted,
                'is_system_app'   => $app->is_system_app,
                'is_enabled'      => $app->is_enabled,
                'install_date'    => $app->install_date?->toDateString(),
                'is_blocked'      => (bool) $blocked,
                'block_type'      => $blocked?->block_type,
                'blocked_until'   => $blocked?->blocked_until?->toIso8601String(),
                'today_usage_sec' => $todayUsage[$app->package_name] ?? 0,
            ];
        });

        return $this->success([
            'apps'  => $appsList,
            'stats' => [
                'total_apps'   => $apps->count(),
                'blocked_apps' => $blockedApps->count(),
            ],
        ]);
    }

    /**
     * POST /api/v1/devices/{uuid}/apps/block
     * حظر تطبيق (من الأب)
     */
    public function block(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'package_name'  => 'required|string|max:255',
            'app_name'      => 'sometimes|string|max:150',
            'block_type'    => 'sometimes|in:permanent,scheduled,time_limited',
            'reason'        => 'sometimes|string|max:500',
            'blocked_until' => 'sometimes|nullable|date|after:now',
        ]);

        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device || $device->user_id !== $request->user()->id) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $dto     = BlockAppDTO::fromArray($request->validated(), $device->id);
        $blocked = $this->blockingService->blockApp($dto);

        return $this->success([
            'package_name'  => $blocked->package_name,
            'app_name'      => $blocked->app_name,
            'block_type'    => $blocked->block_type,
            'blocked_until' => $blocked->blocked_until?->toIso8601String(),
            'is_active'     => $blocked->is_active,
        ], "تم حظر {$blocked->app_name} بنجاح.");
    }

    /**
     * POST /api/v1/devices/{uuid}/apps/unblock/{packageName}
     * إلغاء حظر تطبيق (من الأب)
     */
    public function unblock(
        Request $request,
        string  $uuid,
        string  $packageName
    ): JsonResponse {
        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device || $device->user_id !== $request->user()->id) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $result = $this->blockingService->unblockApp($device->id, $packageName);

        if (!$result) {
            return $this->error('التطبيق غير محظور أصلاً.', 404);
        }

        return $this->success(null, 'تم إلغاء الحظر بنجاح.');
    }

    /**
     * GET /api/v1/devices/{uuid}/apps/usage/stats
     * إحصائيات الاستخدام (للأب)
     *
     * Query: ?period=today|week|month
     */
    public function usageStats(Request $request, string $uuid): JsonResponse
    {
        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device || $device->user_id !== $request->user()->id) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $period = $request->get('period', 'today');
        $stats  = $this->monitoringService->getUsageStats($device->id, $period);

        return $this->success($stats);
    }

    /**
     * GET /api/v1/devices/{uuid}/apps/blocked
     * التطبيقات المحظورة (للجهاز — يجلبها عند البدء)
     */
    public function blockedList(Request $request, string $uuid): JsonResponse
    {
        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $blocked = $this->blockingService->getBlockedApps($device->id);

        return $this->success(['blocked_apps' => $blocked]);
    }
}
