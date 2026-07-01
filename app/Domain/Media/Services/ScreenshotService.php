<?php

namespace App\Domain\Media\Services;

use App\Domain\Device\Models\RemoteCommand;
use App\Domain\Media\Models\Screenshot;
use App\Events\CommandSent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ScreenshotService
{
    /**
     * استقبال لقطة شاشة من الجهاز وحفظها
     */
    public function storeScreenshot(
        int          $deviceId,
        UploadedFile $file,
        string       $triggerType = 'manual',
        ?string      $triggerApp  = null,
        array        $metadata    = []
    ): Screenshot {

        $fileName  = Str::uuid() . '.jpg';
        $folder    = "screenshots/{$deviceId}/" . date('Y/m');
        $path      = "{$folder}/{$fileName}";

        Storage::disk('media')->putFileAs($folder, $file, $fileName);

        // Thumbnail
        $thumbDir      = "thumbnails/{$deviceId}";
        $thumbName     = "thumb_{$fileName}";
        $thumbnailPath = "{$thumbDir}/{$thumbName}";
        Storage::disk('media')->putFileAs($thumbDir, $file, $thumbName);

        return Screenshot::create([
            'uuid'           => Str::uuid(),
            'device_id'      => $deviceId,
            'file_path'      => $path,
            'thumbnail_path' => $thumbnailPath,
            'file_size'      => $file->getSize(),
            'width'          => $metadata['width']       ?? null,
            'height'         => $metadata['height']      ?? null,
            'trigger_type'   => $triggerType,
            'trigger_app'    => $triggerApp,
            'captured_at'    => $metadata['captured_at'] ?? now(),
        ]);
    }

    /**
     * إرسال أمر لأخذ لقطة شاشة
     */
    public function sendScreenshotCommand(int $deviceId, int $requestedBy = 0): RemoteCommand
    {
        $command = RemoteCommand::create([
            'uuid'             => Str::uuid(),
            'device_id'        => $deviceId,
            'command_category' => 'screenshot',
            'command_type'     => 'take_screenshot',
            'command_data'     => [],
            'status'           => 'pending',
            'priority'         => 'normal',
            'created_by'       => $requestedBy,
            'expires_at'       => now()->addMinutes(5),
        ]);

        broadcast(new CommandSent($command));
        $command->markAsSent();

        return $command;
    }

    /**
     * جلب لقطات الشاشة (paginated)
     */
    public function getDeviceScreenshots(
        int     $deviceId,
        int     $perPage    = 20,
        bool    $unviewed   = false,
        ?string $triggerApp = null
    ) {
        $query = Screenshot::where('device_id', $deviceId)
            ->orderByDesc('captured_at');

        if ($unviewed) {
            $query->unviewed();
        }
        if ($triggerApp) {
            $query->byApp($triggerApp);
        }

        return $query->paginate($perPage);
    }

    /**
     * حذف لقطة شاشة وملفها
     */
    public function deleteScreenshot(Screenshot $screenshot): bool
    {
        if (Storage::disk('media')->exists($screenshot->file_path)) {
            Storage::disk('media')->delete($screenshot->file_path);
        }
        if ($screenshot->thumbnail_path && Storage::disk('media')->exists($screenshot->thumbnail_path)) {
            Storage::disk('media')->delete($screenshot->thumbnail_path);
        }

        $screenshot->delete();
        return true;
    }
}
