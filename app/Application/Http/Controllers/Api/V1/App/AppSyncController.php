<?php

namespace App\Application\Http\Controllers\Api\V1\App;

use App\Application\Http\Controllers\Controller;
use App\Domain\App\DTOs\RecordUsageDTO;
use App\Domain\App\DTOs\SyncAppsDTO;
use App\Domain\App\Services\AppMonitoringService;
use App\Domain\Device\Repositories\DeviceRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles app sync and usage reporting FROM the child's device.
 */
class AppSyncController extends Controller
{
    public function __construct(
        protected AppMonitoringService      $monitoringService,
        protected DeviceRepositoryInterface $deviceRepo
    ) {}

    /**
     * POST /api/v1/devices/{uuid}/apps/sync
     * مزامنة التطبيقات المثبتة (من الجهاز)
     *
     * Body: { "apps": [{ "package_name", "app_name", "version_name", ... }] }
     */
    public function sync(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'apps'                    => 'required|array',
            'apps.*.package_name'     => 'required|string|max:255',
            'apps.*.app_name'         => 'sometimes|string|max:150',
            'apps.*.version_name'     => 'sometimes|string|max:50',
            'apps.*.version_code'     => 'sometimes|integer',
            'apps.*.app_size'         => 'sometimes|integer',
            'apps.*.is_system_app'    => 'sometimes|boolean',
            'apps.*.is_enabled'       => 'sometimes|boolean',
            'apps.*.install_date'     => 'sometimes|date',
        ]);

        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $dto    = SyncAppsDTO::fromArray($request->validated(), $device->id);
        $result = $this->monitoringService->syncInstalledApps($dto);

        return $this->success($result, "تم مزامنة {$result['synced']} تطبيق جديد و{$result['updated']} محدَّث.");
    }

    /**
     * POST /api/v1/devices/{uuid}/apps/usage
     * تسجيل الاستخدام اليومي (من الجهاز)
     *
     * Body:
     * {
     *   "date": "2024-01-15",
     *   "usage": [{ "package_name", "foreground_sec", "background_sec", ... }],
     *   "total_screen_sec": 14400,
     *   "screen_on_sec": 10800,
     *   "interactive_sec": 7200,
     *   "unlock_count": 25
     * }
     */
    public function usage(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'date'                     => 'required|date|date_format:Y-m-d',
            'usage'                    => 'required|array',
            'usage.*.package_name'     => 'required|string|max:255',
            'usage.*.app_name'         => 'sometimes|string|max:150',
            'usage.*.foreground_sec'   => 'sometimes|integer|min:0',
            'usage.*.background_sec'   => 'sometimes|integer|min:0',
            'usage.*.launch_count'     => 'sometimes|integer|min:0',
            'usage.*.data_sent'        => 'sometimes|integer|min:0',
            'usage.*.data_received'    => 'sometimes|integer|min:0',
            'usage.*.last_used_at'     => 'sometimes|date',
            'total_screen_sec'         => 'sometimes|integer|min:0',
            'screen_on_sec'            => 'sometimes|integer|min:0',
            'interactive_sec'          => 'sometimes|integer|min:0',
            'unlock_count'             => 'sometimes|integer|min:0',
        ]);

        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $dto    = RecordUsageDTO::fromArray($request->validated(), $device->id);
        $result = $this->monitoringService->recordDailyUsage($dto);

        return $this->success($result, "تم تسجيل استخدام {$result['recorded']} تطبيق.");
    }
}
