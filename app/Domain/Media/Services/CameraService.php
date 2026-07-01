<?php

namespace App\Domain\Media\Services;

use App\Domain\Device\Models\RemoteCommand;
use App\Domain\Media\Models\CameraCapture;
use App\Events\CommandSent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CameraService
{
    /**
     * استقبال صورة/فيديو من الجهاز وحفظه
     */
    public function storeCapture(
        int          $deviceId,
        UploadedFile $file,
        string       $captureType,
        string       $cameraFacing,
        string       $triggerType   = 'manual',
        ?int         $requestedBy   = null,
        ?string      $triggerReason = null,
        array        $metadata      = []
    ): CameraCapture {

        $folder    = $captureType === 'photo' ? 'camera/photos' : 'camera/videos';
        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        $fileName  = Str::uuid() . '.' . $extension;
        $path      = "{$folder}/{$deviceId}/" . date('Y/m') . "/{$fileName}";

        Storage::disk('media')->putFileAs(dirname($path), $file, basename($path));

        // Thumbnail للصور فقط
        $thumbnailPath = null;
        if ($captureType === 'photo') {
            $thumbnailPath = $this->createThumbnail($file, $deviceId, $fileName);
        }

        return CameraCapture::create([
            'uuid'           => Str::uuid(),
            'device_id'      => $deviceId,
            'capture_type'   => $captureType,
            'camera_facing'  => $cameraFacing,
            'file_path'      => $path,
            'thumbnail_path' => $thumbnailPath,
            'file_size'      => $file->getSize(),
            'mime_type'      => $file->getMimeType(),
            'duration_sec'   => $metadata['duration_sec'] ?? null,
            'width'          => $metadata['width']         ?? null,
            'height'         => $metadata['height']        ?? null,
            'latitude'       => $metadata['latitude']      ?? null,
            'longitude'      => $metadata['longitude']     ?? null,
            'trigger_type'   => $triggerType,
            'trigger_reason' => $triggerReason,
            'status'         => 'uploaded',
            'requested_by'   => $requestedBy,
            'captured_at'    => $metadata['captured_at']   ?? now(),
            'uploaded_at'    => now(),
        ]);
    }

    /**
     * إرسال أمر التقاط صورة
     */
    public function sendTakePhotoCommand(
        int    $deviceId,
        string $cameraFacing = 'back',
        string $quality      = 'high',
        int    $requestedBy  = 0
    ): RemoteCommand {

        return $this->sendCommand($deviceId, 'camera', 'take_photo', [
            'camera'  => $cameraFacing,
            'quality' => $quality,
        ], $requestedBy);
    }

    /**
     * إرسال أمر تسجيل فيديو
     */
    public function sendRecordVideoCommand(
        int    $deviceId,
        string $cameraFacing = 'back',
        int    $duration     = 30,
        bool   $withAudio    = true,
        int    $requestedBy  = 0
    ): RemoteCommand {

        return $this->sendCommand($deviceId, 'camera', 'record_video', [
            'camera'     => $cameraFacing,
            'duration'   => $duration,
            'with_audio' => $withAudio,
        ], $requestedBy);
    }

    /**
     * جلب صور الجهاز
     */
    public function getDevicePhotos(
        int     $deviceId,
        int     $perPage  = 20,
        bool    $unviewed = false,
        ?string $camera   = null
    ) {
        $query = CameraCapture::where('device_id', $deviceId)
            ->notDeleted()
            ->photos()
            ->orderByDesc('captured_at');

        if ($unviewed) {
            $query->unviewed();
        }
        if ($camera) {
            $query->byCamera($camera);
        }

        return $query->paginate($perPage);
    }

    /**
     * جلب فيديوهات الجهاز
     */
    public function getDeviceVideos(int $deviceId, int $perPage = 15)
    {
        return CameraCapture::where('device_id', $deviceId)
            ->notDeleted()
            ->videos()
            ->orderByDesc('captured_at')
            ->paginate($perPage);
    }

    /**
     * حذف ملف وسجله
     */
    public function deleteCapture(CameraCapture $capture): bool
    {
        if (Storage::disk('media')->exists($capture->file_path)) {
            Storage::disk('media')->delete($capture->file_path);
        }
        if ($capture->thumbnail_path && Storage::disk('media')->exists($capture->thumbnail_path)) {
            Storage::disk('media')->delete($capture->thumbnail_path);
        }

        $capture->markAsDeleted();
        return true;
    }

    // ==================== Private Helpers ====================

    private function createThumbnail(UploadedFile $file, int $deviceId, string $fileName): string
    {
        $thumbDir  = "thumbnails/{$deviceId}";
        $thumbName = 'thumb_' . $fileName;
        Storage::disk('media')->putFileAs($thumbDir, $file, $thumbName);
        return "{$thumbDir}/{$thumbName}";
    }

    private function sendCommand(
        int    $deviceId,
        string $category,
        string $type,
        array  $data,
        int    $requestedBy
    ): RemoteCommand {

        $command = RemoteCommand::create([
            'uuid'             => Str::uuid(),
            'device_id'        => $deviceId,
            'command_category' => $category,
            'command_type'     => $type,
            'command_data'     => $data,
            'status'           => 'pending',
            'priority'         => 'high',
            'created_by'       => $requestedBy,
            'expires_at'       => now()->addHour(),
        ]);

        broadcast(new CommandSent($command));
        $command->markAsSent();

        return $command;
    }
}
