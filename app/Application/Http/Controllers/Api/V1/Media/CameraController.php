<?php

namespace App\Application\Http\Controllers\Api\V1\Media;

use App\Application\Http\Controllers\Controller;
use App\Domain\Device\Repositories\DeviceRepositoryInterface;
use App\Domain\Media\Models\CameraCapture;
use App\Domain\Media\Services\CameraService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CameraController extends Controller
{
    public function __construct(
        protected CameraService             $cameraService,
        protected DeviceRepositoryInterface $deviceRepo
    ) {}

    /**
     * POST /api/v1/devices/{uuid}/camera/photo
     * إرسال أمر التقاط صورة (من الأب)
     */
    public function takePhoto(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'camera'  => 'sometimes|in:front,back',
            'quality' => 'sometimes|in:low,medium,high',
        ]);

        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device || $device->user_id !== $request->user()->id) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $command = $this->cameraService->sendTakePhotoCommand(
            deviceId:     $device->id,
            cameraFacing: $request->get('camera', 'back'),
            quality:      $request->get('quality', 'high'),
            requestedBy:  $request->user()->id
        );

        return $this->success([
            'command_uuid' => $command->uuid,
            'status'       => $command->status,
        ], 'تم إرسال أمر التقاط الصورة.');
    }

    /**
     * POST /api/v1/devices/{uuid}/camera/video
     * إرسال أمر تسجيل فيديو (من الأب)
     */
    public function recordVideo(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'camera'     => 'sometimes|in:front,back',
            'duration'   => 'required|integer|min:5|max:120',
            'with_audio' => 'sometimes|boolean',
        ]);

        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device || $device->user_id !== $request->user()->id) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $command = $this->cameraService->sendRecordVideoCommand(
            deviceId:     $device->id,
            cameraFacing: $request->get('camera', 'back'),
            duration:     $request->integer('duration', 30),
            withAudio:    $request->boolean('with_audio', true),
            requestedBy:  $request->user()->id
        );

        return $this->success(
            ['command_uuid' => $command->uuid],
            "تم إرسال أمر التسجيل لمدة {$request->duration} ثانية."
        );
    }

    /**
     * POST /api/v1/devices/{uuid}/camera/upload
     * رفع الصورة/الفيديو (من الجهاز)
     */
    public function upload(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'file'          => 'required|file|max:102400',
            'capture_type'  => 'required|in:photo,video',
            'camera_facing' => 'required|in:front,back',
            'trigger_type'  => 'sometimes|in:manual,scheduled,alert',
            'duration_sec'  => 'sometimes|integer|min:1',
            'latitude'      => 'sometimes|numeric|between:-90,90',
            'longitude'     => 'sometimes|numeric|between:-180,180',
            'captured_at'   => 'sometimes|date',
        ]);

        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $capture = $this->cameraService->storeCapture(
            deviceId:     $device->id,
            file:         $request->file('file'),
            captureType:  $request->capture_type,
            cameraFacing: $request->camera_facing,
            triggerType:  $request->get('trigger_type', 'manual'),
            metadata:     $request->only(['duration_sec', 'width', 'height', 'latitude', 'longitude', 'captured_at'])
        );

        return $this->success([
            'uuid'         => $capture->uuid,
            'capture_type' => $capture->capture_type,
            'file_size'    => $capture->getFileSizeFormatted(),
        ], 'تم رفع الملف بنجاح.', 201);
    }

    /**
     * GET /api/v1/devices/{uuid}/camera/photos
     * جلب صور الكاميرا (للأب)
     *
     * Query: ?unviewed_only=1  &camera=front|back  &per_page=20
     */
    public function photos(Request $request, string $uuid): JsonResponse
    {
        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device || $device->user_id !== $request->user()->id) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $captures = $this->cameraService->getDevicePhotos(
            deviceId: $device->id,
            perPage:  $request->integer('per_page', 20),
            unviewed: $request->boolean('unviewed_only'),
            camera:   $request->get('camera')
        );

        return $this->success([
            'photos'     => $captures->map(fn (CameraCapture $c) => $this->formatCapture($c)),
            'pagination' => [
                'total'        => $captures->total(),
                'per_page'     => $captures->perPage(),
                'current_page' => $captures->currentPage(),
                'last_page'    => $captures->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/v1/devices/{uuid}/camera/videos
     * جلب فيديوهات الكاميرا (للأب)
     */
    public function videos(Request $request, string $uuid): JsonResponse
    {
        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device || $device->user_id !== $request->user()->id) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $captures = $this->cameraService->getDeviceVideos(
            deviceId: $device->id,
            perPage:  $request->integer('per_page', 15)
        );

        return $this->success([
            'videos'     => $captures->map(fn (CameraCapture $c) => $this->formatCapture($c)),
            'pagination' => [
                'total'        => $captures->total(),
                'per_page'     => $captures->perPage(),
                'current_page' => $captures->currentPage(),
                'last_page'    => $captures->lastPage(),
            ],
        ]);
    }

    /**
     * PATCH /api/v1/camera/{uuid}/view
     * تحديد كـ مشاهَد (للأب)
     */
    public function markViewed(string $captureUuid): JsonResponse
    {
        $capture = CameraCapture::where('uuid', $captureUuid)->first();

        if (!$capture) {
            return $this->error('الملف غير موجود.', 404);
        }

        $capture->markAsViewed();

        return $this->success([
            'uuid'            => $capture->uuid,
            'parent_viewed'   => true,
            'parent_viewed_at'=> now()->toIso8601String(),
        ], 'تم تحديد الملف كمشاهَد.');
    }

    /**
     * GET /api/v1/camera/{uuid}/stream
     * بث الملف (للأب) — يدعم HTTP Range للفيديو
     */
    public function stream(string $captureUuid)
    {
        $capture = CameraCapture::where('uuid', $captureUuid)->first();

        if (!$capture || !Storage::disk('media')->exists($capture->file_path)) {
            abort(404, 'الملف غير موجود.');
        }

        $capture->markAsViewed();

        $filePath = Storage::disk('media')->path($capture->file_path);
        $fileSize = Storage::disk('media')->size($capture->file_path);
        $mimeType = $capture->mime_type ?? 'application/octet-stream';

        if (request()->hasHeader('Range')) {
            return $this->streamWithRange($filePath, $fileSize, $mimeType);
        }

        return response()->file($filePath, [
            'Content-Type'        => $mimeType,
            'Content-Disposition' => 'inline',
            'Content-Length'      => $fileSize,
            'Accept-Ranges'       => 'bytes',
        ]);
    }

    /**
     * DELETE /api/v1/camera/{uuid}
     * حذف ملف (للأب)
     */
    public function destroy(Request $request, string $captureUuid): JsonResponse
    {
        $capture = CameraCapture::where('uuid', $captureUuid)
            ->whereHas('device', fn ($q) => $q->where('user_id', $request->user()->id))
            ->first();

        if (!$capture) {
            return $this->error('الملف غير موجود.', 404);
        }

        $this->cameraService->deleteCapture($capture);

        return $this->success(null, 'تم حذف الملف بنجاح.');
    }

    // ==================== Private Helpers ====================

    private function formatCapture(CameraCapture $c): array
    {
        return [
            'uuid'          => $c->uuid,
            'capture_type'  => $c->capture_type,
            'camera_facing' => $c->camera_facing,
            'thumbnail_url' => $c->getThumbnailUrl(),
            'stream_url'    => route('camera.stream', $c->uuid),
            'file_size'     => $c->getFileSizeFormatted(),
            'duration'      => $c->getDurationFormatted(),
            'width'         => $c->width,
            'height'        => $c->height,
            'trigger_type'  => $c->trigger_type,
            'is_viewed'     => $c->parent_viewed,
            'captured_at'   => $c->captured_at?->toIso8601String(),
            'latitude'      => $c->latitude,
            'longitude'     => $c->longitude,
        ];
    }

    private function streamWithRange(string $file, int $fileSize, string $mimeType)
    {
        $range = request()->header('Range');
        preg_match('/bytes=(\d+)-(\d*)/', $range, $matches);

        $start = (int) ($matches[1] ?? 0);
        $end   = isset($matches[2]) && $matches[2] !== ''
            ? (int) $matches[2]
            : $fileSize - 1;

        $chunkSize = $end - $start + 1;
        $fp        = fopen($file, 'rb');
        fseek($fp, $start);

        return response()->stream(
            function () use ($fp, $chunkSize) {
                $remaining = $chunkSize;
                while ($remaining > 0 && !feof($fp)) {
                    $read = min(8192, $remaining);
                    echo fread($fp, $read);
                    $remaining -= $read;
                    flush();
                }
                fclose($fp);
            },
            206,
            [
                'Content-Type'   => $mimeType,
                'Content-Range'  => "bytes {$start}-{$end}/{$fileSize}",
                'Content-Length' => $chunkSize,
                'Accept-Ranges'  => 'bytes',
            ]
        );
    }
}
