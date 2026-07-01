<?php

namespace App\Application\Http\Controllers\Api\V1\Media;

use App\Application\Http\Controllers\Controller;
use App\Domain\Device\Repositories\DeviceRepositoryInterface;
use App\Domain\Media\Models\Screenshot;
use App\Domain\Media\Services\ScreenshotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ScreenshotController extends Controller
{
    public function __construct(
        protected ScreenshotService         $screenshotService,
        protected DeviceRepositoryInterface $deviceRepo
    ) {}

    /**
     * POST /api/v1/devices/{uuid}/screenshot
     * إرسال أمر لأخذ لقطة شاشة (من الأب)
     */
    public function capture(Request $request, string $uuid): JsonResponse
    {
        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device || $device->user_id !== $request->user()->id) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $command = $this->screenshotService->sendScreenshotCommand(
            deviceId:    $device->id,
            requestedBy: $request->user()->id
        );

        return $this->success(
            ['command_uuid' => $command->uuid],
            'تم إرسال أمر لقطة الشاشة.'
        );
    }

    /**
     * POST /api/v1/devices/{uuid}/screenshot/upload
     * رفع لقطة شاشة (من الجهاز)
     */
    public function upload(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'file'         => 'required|file|mimes:jpg,jpeg,png|max:20480',
            'trigger_type' => 'sometimes|in:manual,scheduled,app_open',
            'trigger_app'  => 'sometimes|nullable|string|max:100',
            'captured_at'  => 'sometimes|date',
            'width'        => 'sometimes|integer',
            'height'       => 'sometimes|integer',
        ]);

        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $screenshot = $this->screenshotService->storeScreenshot(
            deviceId:    $device->id,
            file:        $request->file('file'),
            triggerType: $request->get('trigger_type', 'manual'),
            triggerApp:  $request->get('trigger_app'),
            metadata:    $request->only(['width', 'height', 'captured_at'])
        );

        return $this->success([
            'uuid'      => $screenshot->uuid,
            'file_size' => $screenshot->getFileSizeFormatted(),
        ], 'تم رفع لقطة الشاشة بنجاح.', 201);
    }

    /**
     * GET /api/v1/devices/{uuid}/screenshots
     * قائمة لقطات الشاشة (للأب)
     *
     * Query: ?unviewed_only=1  &app=YouTube  &per_page=20
     */
    public function index(Request $request, string $uuid): JsonResponse
    {
        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device || $device->user_id !== $request->user()->id) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $screenshots = $this->screenshotService->getDeviceScreenshots(
            deviceId:   $device->id,
            perPage:    $request->integer('per_page', 20),
            unviewed:   $request->boolean('unviewed_only'),
            triggerApp: $request->get('app')
        );

        return $this->success([
            'screenshots' => $screenshots->map(fn (Screenshot $s) => [
                'uuid'          => $s->uuid,
                'thumbnail_url' => $s->getThumbnailUrl(),
                'stream_url'    => route('screenshot.stream', $s->uuid),
                'file_size'     => $s->getFileSizeFormatted(),
                'width'         => $s->width,
                'height'        => $s->height,
                'trigger_type'  => $s->trigger_type,
                'trigger_app'   => $s->trigger_app,
                'is_viewed'     => $s->parent_viewed,
                'captured_at'   => $s->captured_at?->toIso8601String(),
            ]),
            'pagination' => [
                'total'        => $screenshots->total(),
                'per_page'     => $screenshots->perPage(),
                'current_page' => $screenshots->currentPage(),
                'last_page'    => $screenshots->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/v1/screenshot/{uuid}/stream
     * عرض لقطة الشاشة (للأب)
     */
    public function stream(string $screenshotUuid)
    {
        $screenshot = Screenshot::where('uuid', $screenshotUuid)->first();

        if (!$screenshot || !Storage::disk('media')->exists($screenshot->file_path)) {
            abort(404, 'لقطة الشاشة غير موجودة.');
        }

        $screenshot->markAsViewed();

        $filePath = Storage::disk('media')->path($screenshot->file_path);

        return response()->file($filePath, [
            'Content-Type'        => 'image/jpeg',
            'Content-Disposition' => 'inline',
        ]);
    }

    /**
     * PATCH /api/v1/screenshot/{uuid}/view
     * تحديد كمشاهَد (للأب)
     */
    public function markViewed(string $screenshotUuid): JsonResponse
    {
        $screenshot = Screenshot::where('uuid', $screenshotUuid)->first();

        if (!$screenshot) {
            return $this->error('لقطة الشاشة غير موجودة.', 404);
        }

        $screenshot->markAsViewed();

        return $this->success(null, 'تم تحديد لقطة الشاشة كمشاهَدة.');
    }

    /**
     * DELETE /api/v1/screenshot/{uuid}
     * حذف لقطة شاشة (للأب)
     */
    public function destroy(Request $request, string $screenshotUuid): JsonResponse
    {
        $screenshot = Screenshot::where('uuid', $screenshotUuid)
            ->whereHas('device', fn ($q) => $q->where('user_id', $request->user()->id))
            ->first();

        if (!$screenshot) {
            return $this->error('لقطة الشاشة غير موجودة.', 404);
        }

        $this->screenshotService->deleteScreenshot($screenshot);

        return $this->success(null, 'تم حذف لقطة الشاشة بنجاح.');
    }
}
