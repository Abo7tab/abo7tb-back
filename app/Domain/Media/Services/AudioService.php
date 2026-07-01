<?php

namespace App\Domain\Media\Services;

use App\Domain\Device\Models\RemoteCommand;
use App\Domain\Media\Models\AudioRecording;
use App\Events\CommandSent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AudioService
{
    /**
     * استقبال تسجيل صوتي من الجهاز وحفظه
     */
    public function storeRecording(
        int          $deviceId,
        UploadedFile $file,
        int          $durationSec,
        string       $quality       = 'medium',
        string       $triggerType   = 'manual',
        ?int         $requestedBy   = null,
        ?string      $triggerReason = null
    ): AudioRecording {

        $extension = $file->getClientOriginalExtension() ?: 'm4a';
        $fileName  = Str::uuid() . '.' . $extension;
        $folder    = "audio/{$deviceId}/" . date('Y/m');
        $path      = "{$folder}/{$fileName}";

        Storage::disk('media')->putFileAs($folder, $file, $fileName);

        return AudioRecording::create([
            'uuid'           => Str::uuid(),
            'device_id'      => $deviceId,
            'file_path'      => $path,
            'file_size'      => $file->getSize(),
            'duration_sec'   => $durationSec,
            'quality'        => $quality,
            'trigger_type'   => $triggerType,
            'trigger_reason' => $triggerReason,
            'status'         => 'uploaded',
            'requested_by'   => $requestedBy,
            'started_at'     => now()->subSeconds($durationSec),
            'ended_at'       => now(),
            'uploaded_at'    => now(),
        ]);
    }

    /**
     * إرسال أمر بدء التسجيل عبر Reverb
     */
    public function sendStartRecordingCommand(
        int    $deviceId,
        int    $duration    = 60,
        string $quality     = 'medium',
        int    $requestedBy = 0
    ): RemoteCommand {

        $command = RemoteCommand::create([
            'uuid'             => Str::uuid(),
            'device_id'        => $deviceId,
            'command_category' => 'microphone',
            'command_type'     => 'start_recording',
            'command_data'     => [
                'duration' => $duration,
                'quality'  => $quality,
            ],
            'status'     => 'pending',
            'priority'   => 'high',
            'created_by' => $requestedBy,
            'expires_at' => now()->addHour(),
        ]);

        broadcast(new CommandSent($command));
        $command->markAsSent();

        return $command;
    }

    /**
     * جلب تسجيلات الجهاز (paginated)
     */
    public function getDeviceRecordings(int $deviceId, int $perPage = 20)
    {
        return AudioRecording::where('device_id', $deviceId)
            ->notDeleted()
            ->orderByDesc('started_at')
            ->paginate($perPage);
    }

    /**
     * حذف تسجيل وملفه
     */
    public function deleteRecording(AudioRecording $recording): bool
    {
        if (Storage::disk('media')->exists($recording->file_path)) {
            Storage::disk('media')->delete($recording->file_path);
        }

        $recording->markAsDeleted();
        return true;
    }
}
