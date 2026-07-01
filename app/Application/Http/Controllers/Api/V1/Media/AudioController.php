<?php

namespace App\Application\Http\Controllers\Api\V1\Media;

use App\Application\Http\Controllers\Controller;
use App\Domain\Device\Repositories\DeviceRepositoryInterface;
use App\Domain\Media\Models\AudioRecording;
use App\Domain\Media\Services\AudioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AudioController extends Controller
{
    public function __construct(
        protected AudioService              $audioService,
        protected DeviceRepositoryInterface $deviceRepo
    ) {}

    /**
     * POST /api/v1/devices/{uuid}/audio/start
     * إرسال أمر بدء التسجيل (من الأب)
     */
    public function start(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'duration' => 'required|integer|min:5|max:300',
            'quality'  => 'sometimes|in:low,medium,high',
        ]);

        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device || $device->user_id !== $request->user()->id) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $command = $this->audioService->sendStartRecordingCommand(
            deviceId:    $device->id,
            duration:    $request->integer('duration'),
            quality:     $request->get('quality', 'medium'),
            requestedBy: $request->user()->id
        );

        return $this->success(
            ['command_uuid' => $command->uuid],
            "تم إرسال أمر التسجيل لمدة {$request->duration} ثانية."
        );
    }

    /**
     * POST /api/v1/devices/{uuid}/audio/upload
     * رفع تسجيل صوتي (من الجهاز)
     */
    public function upload(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'file'         => 'required|file|mimes:m4a,mp3,ogg,wav,aac,opus|max:51200',
            'duration_sec' => 'required|integer|min:1',
            'quality'      => 'sometimes|in:low,medium,high',
            'trigger_type' => 'sometimes|in:manual,scheduled,alert',
        ]);

        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $recording = $this->audioService->storeRecording(
            deviceId:    $device->id,
            file:        $request->file('file'),
            durationSec: $request->integer('duration_sec'),
            quality:     $request->get('quality', 'medium'),
            triggerType: $request->get('trigger_type', 'manual'),
        );

        return $this->success([
            'uuid'      => $recording->uuid,
            'duration'  => $recording->getDurationFormatted(),
            'file_size' => $recording->getFileSizeFormatted(),
        ], 'تم رفع التسجيل بنجاح.', 201);
    }

    /**
     * GET /api/v1/devices/{uuid}/audio
     * جلب التسجيلات (للأب)
     *
     * Query: ?per_page=20
     */
    public function index(Request $request, string $uuid): JsonResponse
    {
        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device || $device->user_id !== $request->user()->id) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $recordings = $this->audioService->getDeviceRecordings(
            deviceId: $device->id,
            perPage:  $request->integer('per_page', 20)
        );

        return $this->success([
            'recordings' => $recordings->map(fn (AudioRecording $r) => [
                'uuid'        => $r->uuid,
                'duration'    => $r->getDurationFormatted(),
                'file_size'   => $r->getFileSizeFormatted(),
                'quality'     => $r->quality,
                'trigger_type'=> $r->trigger_type,
                'is_viewed'   => $r->parent_viewed,
                'stream_url'  => route('audio.stream', $r->uuid),
                'started_at'  => $r->started_at?->toIso8601String(),
            ]),
            'pagination' => [
                'total'        => $recordings->total(),
                'per_page'     => $recordings->perPage(),
                'current_page' => $recordings->currentPage(),
                'last_page'    => $recordings->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/v1/audio/{uuid}/stream
     * بث التسجيل الصوتي (للأب) — يدعم HTTP Range
     */
    public function stream(string $recordingUuid)
    {
        $recording = AudioRecording::where('uuid', $recordingUuid)->first();

        if (!$recording || !Storage::disk('media')->exists($recording->file_path)) {
            abort(404, 'التسجيل غير موجود.');
        }

        $recording->markAsViewed();

        $filePath = Storage::disk('media')->path($recording->file_path);
        $fileSize = Storage::disk('media')->size($recording->file_path);
        $mimeType = 'audio/mpeg';

        if (request()->hasHeader('Range')) {
            $range = request()->header('Range');
            preg_match('/bytes=(\d+)-(\d*)/', $range, $matches);
            $start     = (int) ($matches[1] ?? 0);
            $end       = isset($matches[2]) && $matches[2] !== '' ? (int) $matches[2] : $fileSize - 1;
            $chunkSize = $end - $start + 1;
            $fp        = fopen($filePath, 'rb');
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

        return response()->file($filePath, [
            'Content-Type'  => $mimeType,
            'Content-Length'=> $fileSize,
            'Accept-Ranges' => 'bytes',
        ]);
    }

    /**
     * PATCH /api/v1/audio/{uuid}/view
     * تحديد كـ مشاهَد (للأب)
     */
    public function markViewed(string $recordingUuid): JsonResponse
    {
        $recording = AudioRecording::where('uuid', $recordingUuid)->first();

        if (!$recording) {
            return $this->error('التسجيل غير موجود.', 404);
        }

        $recording->markAsViewed();

        return $this->success(null, 'تم تحديد التسجيل كمسموع.');
    }

    /**
     * DELETE /api/v1/audio/{uuid}
     * حذف تسجيل (للأب)
     */
    public function destroy(Request $request, string $recordingUuid): JsonResponse
    {
        $recording = AudioRecording::where('uuid', $recordingUuid)
            ->whereHas('device', fn ($q) => $q->where('user_id', $request->user()->id))
            ->first();

        if (!$recording) {
            return $this->error('التسجيل غير موجود.', 404);
        }

        $this->audioService->deleteRecording($recording);

        return $this->success(null, 'تم حذف التسجيل بنجاح.');
    }
}
