<?php

namespace App\Application\Http\Controllers\Api\V1\ScreenControl;

use App\Application\Http\Controllers\Controller;
use App\Domain\Device\Repositories\DeviceRepositoryInterface;
use App\Domain\ScreenControl\Services\BedtimeService;
use App\Domain\ScreenControl\Services\ScreenLockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScreenLockController extends Controller
{
    public function __construct(
        protected ScreenLockService         $lockService,
        protected BedtimeService            $bedtimeService,
        protected DeviceRepositoryInterface $deviceRepo
    ) {}

    /**
     * POST /api/v1/devices/{uuid}/screen/lock
     * قفل الشاشة فوراً (من الأب)
     */
    public function lock(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'lock_type'             => 'required|in:black_screen,custom_message,bedtime,study_time,punishment,emergency',
            'message_title'         => 'sometimes|string|max:255',
            'message_body'          => 'sometimes|string|max:500',
            'background_color'      => 'sometimes|regex:/^#[0-9A-Fa-f]{6}$/',
            'show_message'          => 'sometimes|boolean',
            'duration_minutes'      => 'sometimes|integer|min:1|max:480',
            'allow_emergency_calls' => 'sometimes|boolean',
            'allow_alarm'           => 'sometimes|boolean',
            'allow_music'           => 'sometimes|boolean',
            'whitelisted_numbers'   => 'sometimes|array',
        ]);

        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device || $device->user_id !== $request->user()->id) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $lock = $this->lockService->lockScreen(
            device:          $device,
            lockType:        $request->lock_type,
            messageTitle:    $request->message_title,
            messageBody:     $request->message_body,
            backgroundColor: $request->get('background_color', '#000000'),
            showMessage:     $request->boolean('show_message', false),
            durationMinutes: $request->duration_minutes,
            allowCalls:      $request->boolean('allow_emergency_calls', true),
            allowAlarm:      $request->boolean('allow_alarm', true),
            allowMusic:      $request->boolean('allow_music', false),
            whitelistedNums: $request->get('whitelisted_numbers', []),
            lockedBy:        $request->user()->id
        );

        return $this->success([
            'lock_id'    => $lock->id,
            'lock_type'  => $lock->lock_type,
            'title'      => $lock->message_title,
            'locked_at'  => $lock->locked_at?->toIso8601String(),
            'end_time'   => $lock->end_time?->toIso8601String(),
            'allow_calls'=> $lock->allow_emergency_calls,
        ], "تم قفل الشاشة: {$lock->message_title}");
    }

    /**
     * POST /api/v1/devices/{uuid}/screen/unlock
     * فتح الشاشة (من الأب)
     */
    public function unlock(Request $request, string $uuid): JsonResponse
    {
        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device || $device->user_id !== $request->user()->id) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $this->lockService->unlockScreen($device, $request->user()->id);

        return $this->success(null, 'تم فتح الشاشة بنجاح.');
    }

    /**
     * GET /api/v1/devices/{uuid}/screen/status
     * حالة القفل الحالية (للأب والجهاز)
     */
    public function status(Request $request, string $uuid): JsonResponse
    {
        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $activeLock = $this->lockService->getActiveLock($device->id);
        $isBedtime  = $this->bedtimeService->isBedtime($device->id);

        return $this->success([
            'is_locked'  => $activeLock !== null,
            'is_bedtime' => $isBedtime,
            'lock'       => $activeLock ? [
                'id'          => $activeLock->id,
                'lock_type'   => $activeLock->lock_type,
                'title'       => $activeLock->message_title,
                'body'        => $activeLock->message_body,
                'color'       => $activeLock->background_color,
                'show_message'=> $activeLock->show_message,
                'locked_at'   => $activeLock->locked_at?->toIso8601String(),
                'end_time'    => $activeLock->end_time?->toIso8601String(),
                'allow_calls' => $activeLock->allow_emergency_calls,
                'allow_alarm' => $activeLock->allow_alarm,
                'allow_music' => $activeLock->allow_music,
            ] : null,
        ]);
    }

    /**
     * GET /api/v1/devices/{uuid}/screen/history
     * سجل القفل (للأب)
     */
    public function history(Request $request, string $uuid): JsonResponse
    {
        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device || $device->user_id !== $request->user()->id) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $history = $this->lockService->getLockHistory(
            $device->id,
            $request->integer('per_page', 20)
        );

        return $this->success([
            'history' => $history->map(fn ($l) => [
                'id'           => $l->id,
                'lock_type'    => $l->lock_type,
                'title'        => $l->message_title,
                'is_active'    => $l->is_active,
                'locked_at'    => $l->locked_at?->toIso8601String(),
                'unlocked_at'  => $l->unlocked_at?->toIso8601String(),
                'duration_min' => $l->locked_at && $l->unlocked_at
                    ? $l->locked_at->diffInMinutes($l->unlocked_at)
                    : null,
            ]),
            'pagination' => [
                'total'        => $history->total(),
                'current_page' => $history->currentPage(),
                'last_page'    => $history->lastPage(),
            ],
        ]);
    }

    /**
     * POST /api/v1/devices/{uuid}/screen/bedtime
     * جدولة وقت النوم (من الأب)
     */
    public function setBedtime(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'start_time'            => 'required|date_format:H:i',
            'end_time'              => 'required|date_format:H:i',
            'active_days'           => 'required|array|min:1',
            'active_days.*'         => 'integer|min:0|max:6',
            'message'               => 'sometimes|string|max:255',
            'allow_emergency_calls' => 'sometimes|boolean',
            'allow_alarm'           => 'sometimes|boolean',
        ]);

        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device || $device->user_id !== $request->user()->id) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $result = $this->bedtimeService->scheduleBedtime(
            deviceId:   $device->id,
            startTime:  $request->start_time,
            endTime:    $request->end_time,
            activeDays: $request->active_days,
            message:    $request->message,
            allowCalls: $request->boolean('allow_emergency_calls', true),
            allowAlarm: $request->boolean('allow_alarm', true),
            createdBy:  $request->user()->id
        );

        return $this->success($result, 'تم تعيين وقت النوم بنجاح.');
    }

    /**
     * DELETE /api/v1/devices/{uuid}/screen/bedtime
     * إلغاء جدولة وقت النوم (من الأب)
     */
    public function cancelBedtime(Request $request, string $uuid): JsonResponse
    {
        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device || $device->user_id !== $request->user()->id) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $this->bedtimeService->cancelBedtime($device->id);

        return $this->success(null, 'تم إلغاء جدولة وقت النوم.');
    }
}
