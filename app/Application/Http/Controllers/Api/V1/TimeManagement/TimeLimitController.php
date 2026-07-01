<?php

namespace App\Application\Http\Controllers\Api\V1\TimeManagement;

use App\Application\Http\Controllers\Controller;
use App\Domain\Device\Repositories\DeviceRepositoryInterface;
use App\Domain\TimeManagement\Models\TimeLimit;
use App\Domain\TimeManagement\Services\TimeLimitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimeLimitController extends Controller
{
    public function __construct(
        protected TimeLimitService          $timeLimitService,
        protected DeviceRepositoryInterface $deviceRepo
    ) {}

    /**
     * GET /api/v1/devices/{uuid}/time-limits
     * جميع القيود الزمنية للجهاز (للأب)
     */
    public function index(Request $request, string $uuid): JsonResponse
    {
        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device || $device->user_id !== $request->user()->id) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $limits = $this->timeLimitService->getDeviceLimits($device->id);

        return $this->success($limits);
    }

    /**
     * POST /api/v1/devices/{uuid}/time-limits
     * إنشاء قيد زمني جديد
     */
    public function store(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'limit_type'            => 'required|in:daily_total,app_specific,bedtime,study_time,custom',
            'limit_name'            => 'sometimes|string|max:100',
            'max_minutes_per_day'   => 'required_if:limit_type,daily_total|integer|min:1|max:1440',
            'package_name'          => 'required_if:limit_type,app_specific|string|max:255',
            'max_app_minutes'       => 'required_if:limit_type,app_specific|integer|min:1|max:1440',
            'start_time'            => 'sometimes|nullable|date_format:H:i',
            'end_time'              => 'sometimes|nullable|date_format:H:i',
            'active_days'           => 'sometimes|array',
            'active_days.*'         => 'integer|min:0|max:6',
            'block_completely'      => 'sometimes|boolean',
            'allow_emergency_calls' => 'sometimes|boolean',
        ]);

        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device || $device->user_id !== $request->user()->id) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $limit = $this->timeLimitService->createLimit(
            deviceId:  $device->id,
            limitType: $request->limit_type,
            data:      $request->validated()
        );

        return $this->success($this->formatLimit($limit), 'تم إنشاء القيد بنجاح.', 201);
    }

    /**
     * PUT /api/v1/time-limits/{id}
     * تحديث قيد زمني
     */
    public function update(Request $request, int $limitId): JsonResponse
    {
        $request->validate([
            'limit_name'          => 'sometimes|string|max:100',
            'max_minutes_per_day' => 'sometimes|integer|min:1|max:1440',
            'max_app_minutes'     => 'sometimes|integer|min:1|max:1440',
            'start_time'          => 'sometimes|nullable|date_format:H:i',
            'end_time'            => 'sometimes|nullable|date_format:H:i',
            'active_days'         => 'sometimes|array',
            'active_days.*'       => 'integer|min:0|max:6',
            'block_completely'    => 'sometimes|boolean',
            'is_active'           => 'sometimes|boolean',
        ]);

        $updated = $this->timeLimitService->updateLimit($limitId, $request->validated());

        return $this->success(
            ['updated' => $updated],
            $updated ? 'تم التحديث بنجاح.' : 'لم يتم التحديث.'
        );
    }

    /**
     * DELETE /api/v1/time-limits/{id}
     * حذف قيد زمني
     */
    public function destroy(int $limitId): JsonResponse
    {
        $deleted = $this->timeLimitService->deleteLimit($limitId);

        return $this->success(
            ['deleted' => $deleted],
            $deleted ? 'تم الحذف.' : 'لم يتم الحذف.'
        );
    }

    /**
     * GET /api/v1/devices/{uuid}/time-limits/check
     * فحص حالة القيود الآن (للجهاز + الأب)
     */
    public function check(Request $request, string $uuid): JsonResponse
    {
        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $dailyCheck   = $this->timeLimitService->checkDailyLimit($device->id);
        $activeLimits = $this->timeLimitService->getActiveLimitsNow($device->id);
        $isBlocked    = !empty($activeLimits) &&
            collect($activeLimits)->contains('block_completely', true);

        return $this->success([
            'daily_limit' => $dailyCheck,
            'active_now'  => $activeLimits,
            'is_blocked'  => $isBlocked,
        ]);
    }

    /**
     * GET /api/v1/devices/{uuid}/time-limits/app/{package}
     * فحص حد تطبيق معين (للجهاز)
     *
     * {package} = package name URL-encoded (e.g. com.whatsapp → com.whatsapp)
     */
    public function checkApp(Request $request, string $uuid, string $package): JsonResponse
    {
        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $result = $this->timeLimitService->checkAppLimit(
            $device->id,
            urldecode($package)
        );

        return $this->success($result);
    }

    // ==================== Private Helpers ====================

    private function formatLimit(TimeLimit $limit): array
    {
        return [
            'id'                  => $limit->id,
            'limit_name'          => $limit->limit_name,
            'limit_type'          => $limit->limit_type,
            'max_minutes_per_day' => $limit->max_minutes_per_day,
            'package_name'        => $limit->package_name,
            'max_app_minutes'     => $limit->max_app_minutes,
            'start_time'          => $limit->start_time,
            'end_time'            => $limit->end_time,
            'active_days'         => $limit->active_days,
            'block_completely'    => $limit->block_completely,
            'allow_emergency'     => $limit->allow_emergency_calls,
            'is_active'           => $limit->is_active,
            'is_active_now'       => $limit->isActiveNow(),
        ];
    }
}
