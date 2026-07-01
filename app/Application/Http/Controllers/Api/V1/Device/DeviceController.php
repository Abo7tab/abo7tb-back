<?php

namespace App\Application\Http\Controllers\Api\V1\Device;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\Device\AcceptConsentRequest;
use App\Application\Http\Requests\Device\RegisterDeviceRequest;
use App\Application\Http\Requests\Device\SendCommandRequest;
use App\Application\Http\Requests\Device\UpdateLocationRequest;
use App\Application\Http\Resources\DeviceResource;
use App\Domain\Device\DTOs\RegisterDeviceDTO;
use App\Domain\Device\DTOs\SendCommandDTO;
use App\Domain\Device\DTOs\UpdateLocationDTO;
use App\Domain\Device\Repositories\DeviceRepositoryInterface;
use App\Domain\Device\Services\CommandService;
use App\Domain\Device\Services\DeviceService;
use App\Domain\Device\Services\LocationTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function __construct(
        protected DeviceService             $deviceService,
        protected CommandService            $commandService,
        protected LocationTrackingService   $locationService,
        protected DeviceRepositoryInterface $deviceRepo
    ) {}

    // ==================== للأب ====================

    /**
     * GET /api/v1/devices
     * جلب جميع أجهزة الأب
     */
    public function index(Request $request): JsonResponse
    {
        $devices = $this->deviceRepo->getUserDevices($request->user()->id);

        return $this->success([
            'devices' => DeviceResource::collection($devices),
            'total'   => $devices->count(),
        ]);
    }

    /**
     * POST /api/v1/devices/register
     * تسجيل جهاز جديد
     */
    public function register(RegisterDeviceRequest $request): JsonResponse
    {
        try {
            $dto    = RegisterDeviceDTO::fromArray($request->validated(), $request->user()->id);
            $device = $this->deviceService->registerDevice($dto);

            return $this->success(
                new DeviceResource($device),
                'تم تسجيل الجهاز بنجاح. في انتظار موافقة الطفل.',
                201
            );
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * GET /api/v1/devices/{uuid}
     * تفاصيل جهاز واحد
     */
    public function show(Request $request, string $uuid): JsonResponse
    {
        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device || $device->user_id !== $request->user()->id) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        return $this->success(new DeviceResource($device->load('consent')));
    }

    /**
     * POST /api/v1/devices/{uuid}/command
     * إرسال أمر للجهاز (من الأب)
     */
    public function sendCommand(SendCommandRequest $request, string $uuid): JsonResponse
    {
        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device || $device->user_id !== $request->user()->id) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        try {
            $dto     = SendCommandDTO::fromArray($request->validated(), $device->id, $request->user()->id);
            $command = $this->commandService->sendCommand($dto);

            return $this->success([
                'command_uuid' => $command->uuid,
                'status'       => $command->status,
            ], 'تم إرسال الأمر بنجاح.');
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * DELETE /api/v1/devices/{uuid}
     * حذف جهاز (من الأب)
     */
    public function destroy(Request $request, string $uuid): JsonResponse
    {
        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device || $device->user_id !== $request->user()->id) {
            return $this->error('الجهاز غير موجود أو لا تملك صلاحية حذفه.', 404);
        }

        try {
            $device->delete();
            return $this->success(null, 'تم حذف الجهاز بنجاح.');
        } catch (\Exception $e) {
            return $this->error('حدث خطأ أثناء حذف الجهاز.', 500);
        }
    }

    // ==================== للجهاز (الطفل) ====================

    /**
     * GET /api/v1/devices/{uuid}/location/history
     * جلب سجل المواقع للجهاز (للأب)
     */
    public function locationHistory(Request $request, string $uuid): JsonResponse
    {
        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device || $device->user_id !== $request->user()->id) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $limit = $request->query('limit', 20);
        $locations = \App\Domain\Device\Models\DeviceLocation::where('device_id', $device->id)
            ->orderBy('recorded_at', 'desc')
            ->limit($limit)
            ->get();

        return $this->success($locations);
    }

    /**
     * POST /api/v1/devices/{uuid}/heartbeat
     * تحديث حالة الجهاز (من الموبايل)
     */
    public function heartbeat(Request $request, string $uuid): JsonResponse
    {
        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $this->deviceService->updateDeviceStatus($device, $request->all());

        return $this->success(null, 'تم تحديث حالة الجهاز بنجاح.');
    }

    /**
     * POST /api/v1/devices/{uuid}/consent/accept
     * قبول الموافقة من الجهاز
     */
    public function acceptConsent(AcceptConsentRequest $request, string $uuid): JsonResponse
    {
        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        try {
            $consent = $this->deviceService->acceptConsent(
                $device,
                $request->validated('permissions', []),
                $request->ip(),
                $request->userAgent() ?? 'Unknown'
            );

            return $this->success([
                'consent_status' => $consent->consent_status,
                'given_at'       => $consent->consent_given_at?->toIso8601String(),
            ], 'تم قبول الموافقة بنجاح.');
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * POST /api/v1/devices/{uuid}/location
     * تحديث الموقع (من الجهاز)
     */
    public function updateLocation(UpdateLocationRequest $request, string $uuid): JsonResponse
    {
        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $dto      = UpdateLocationDTO::fromArray($request->validated());
        $location = $this->locationService->updateLocation($device, $dto);

        return $this->success([
            'latitude'    => $location->latitude,
            'longitude'   => $location->longitude,
            'recorded_at' => $location->recorded_at?->toIso8601String(),
        ], 'تم تحديث الموقع.');
    }

    /**
     * GET /api/v1/devices/{uuid}/commands/pending
     * جلب الأوامر المعلقة (من الجهاز)
     */
    public function pendingCommands(string $uuid): JsonResponse
    {
        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $commands = $this->commandService->getPendingCommands($device->id);

        // Decode command_data from string to array (since DB::select returns it as string)
        foreach ($commands as $cmd) {
            if (isset($cmd->command_data) && is_string($cmd->command_data)) {
                $cmd->command_data = json_decode($cmd->command_data, true);
            }
        }

        return $this->success([
            'commands' => $commands,
            'count' => count($commands),
            'polling_interval' => 60,
        ]);
    }

    /**
     * POST /api/v1/devices/{uuid}/push-token
     * تحديث FCM token الخاص بجهاز الطفل.
     */
    public function updateFcmToken(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'fcm_token'    => 'required|string|max:4096',
            'push_enabled' => 'sometimes|boolean',
        ]);

        $device = $request->attributes->get('device')
            ?? $this->deviceRepo->findByUuid($uuid);

        if (!$device) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $device->update([
            'fcm_token'      => $request->string('fcm_token')->toString(),
            'push_enabled'   => $request->boolean('push_enabled', true),
            'fcm_updated_at' => now(),
        ]);

        return $this->success(null, 'تم تحديث FCM token بنجاح.');
    }

    /**
     * PATCH /api/v1/commands/{uuid}/status
     * تحديث حالة الأمر (من الجهاز)
     */
    public function updateCommandStatus(Request $request, string $commandUuid): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:completed,failed,executing',
            'result' => 'sometimes|array',
            'error'  => 'sometimes|string',
        ]);

        $existingCommand = \App\Domain\Device\Models\RemoteCommand::with('device')
            ->where('uuid', $commandUuid)
            ->first();

        if (!$existingCommand || (int) $existingCommand->device?->user_id !== (int) $request->user()->id) {
            return $this->error('الأمر غير موجود أو غير مصرح بتحديثه.', 404);
        }

        $command = $this->commandService->updateCommandStatus(
            $commandUuid,
            $request->status,
            $request->result ?? [],
            $request->error  ?? ''
        );

        return $this->success([
            'command_uuid' => $command->uuid,
            'status'       => $command->status,
        ]);
    }
}
