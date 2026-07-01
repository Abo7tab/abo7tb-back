<?php

namespace App\Domain\Media\Services;

use App\Domain\Media\Models\GalleryItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GalleryService
{
    /**
     * رفع ملف للمعرض — مع كشف التكرار بـ MD5
     */
    public function storeItem(
        int          $deviceId,
        UploadedFile $file,
        array        $metadata = []
    ): GalleryItem {

        $mimeType  = $file->getMimeType();
        $mediaType = $this->detectMediaType($mimeType);
        $extension = $file->getClientOriginalExtension() ?: 'bin';
        $fileName  = Str::uuid() . '.' . $extension;
        $folder    = "gallery/{$deviceId}/{$mediaType}/" . date('Y/m');
        $path      = "{$folder}/{$fileName}";
        $md5       = md5_file($file->getRealPath());

        // منع التكرار
        $existing = GalleryItem::where('device_id', $deviceId)
            ->where('md5_hash', $md5)
            ->first();

        if ($existing) {
            return $existing;
        }

        Storage::disk('media')->putFileAs($folder, $file, $fileName);

        // Thumbnail للصور
        $thumbnailPath = null;
        if ($mediaType === 'photo') {
            $thumbnailPath = $this->createThumbnail($file, $deviceId, $fileName);
        }

        return GalleryItem::create([
            'uuid'           => Str::uuid(),
            'device_id'      => $deviceId,
            'file_name'      => $metadata['file_name'] ?? $file->getClientOriginalName(),
            'file_path'      => $path,
            'thumbnail_path' => $thumbnailPath,
            'file_size'      => $file->getSize(),
            'mime_type'      => $mimeType,
            'media_type'     => $mediaType,
            'source_folder'  => $metadata['source_folder'] ?? null,
            'source_app'     => $metadata['source_app']    ?? null,
            'width'          => $metadata['width']          ?? null,
            'height'         => $metadata['height']         ?? null,
            'duration_sec'   => $metadata['duration_sec']  ?? null,
            'taken_at'       => $metadata['taken_at']       ?? null,
            'latitude'       => $metadata['latitude']       ?? null,
            'longitude'      => $metadata['longitude']      ?? null,
            'md5_hash'       => $md5,
            'sync_status'    => 'synced',
            'first_seen_at'  => now(),
        ]);
    }

    /**
     * مزامنة metadata فقط (بدون رفع ملفات — للـ batch metadata sync)
     *
     * @return array{created: int, skipped: int}
     */
    public function syncMetadata(int $deviceId, array $items): array
    {
        $created = 0;
        $skipped = 0;

        foreach ($items as $item) {
            try {
                $md5 = $item['md5_hash'] ?? null;

                if ($md5) {
                    $exists = GalleryItem::where('device_id', $deviceId)
                        ->where('md5_hash', $md5)
                        ->exists();

                    if ($exists) {
                        $skipped++;
                        continue;
                    }
                }

                GalleryItem::create([
                    'uuid'          => Str::uuid(),
                    'device_id'     => $deviceId,
                    'file_name'     => $item['file_name']      ?? 'unknown',
                    'file_path'     => $item['file_path']      ?? '',
                    'file_size'     => $item['file_size']      ?? 0,
                    'mime_type'     => $item['mime_type']      ?? 'image/jpeg',
                    'media_type'    => $item['media_type']     ?? 'photo',
                    'source_folder' => $item['source_folder']  ?? null,
                    'source_app'    => $item['source_app']     ?? null,
                    'width'         => $item['width']           ?? null,
                    'height'        => $item['height']          ?? null,
                    'duration_sec'  => $item['duration_sec']   ?? null,
                    'taken_at'      => $item['taken_at']        ?? null,
                    'md5_hash'      => $md5,
                    'sync_status'   => 'metadata_only',
                    'first_seen_at' => now(),
                ]);
                $created++;
            } catch (\Exception) {
                $skipped++;
            }
        }

        return ['created' => $created, 'skipped' => $skipped, 'total' => count($items)];
    }

    /**
     * جلب المعرض مع فلاتر (paginated)
     */
    public function getGallery(
        int     $deviceId,
        int     $perPage   = 30,
        ?string $mediaType = null,
        ?string $sourceApp = null,
        ?string $folder    = null,
        bool    $flagged   = false
    ) {
        $query = GalleryItem::where('device_id', $deviceId)
            ->orderByDesc('taken_at');

        if ($mediaType) {
            $query->where('media_type', $mediaType);
        }
        if ($sourceApp) {
            $query->bySource($sourceApp);
        }
        if ($folder) {
            $query->byFolder($folder);
        }
        if ($flagged) {
            $query->flagged();
        }

        return $query->paginate($perPage);
    }

    /**
     * إحصائيات المعرض
     */
    public function getGalleryStats(int $deviceId): array
    {
        $items = GalleryItem::where('device_id', $deviceId)->get();

        return [
            'total_items'   => $items->count(),
            'total_size'    => $this->formatBytes((int) $items->sum('file_size')),
            'photos_count'  => $items->where('media_type', 'photo')->count(),
            'videos_count'  => $items->where('media_type', 'video')->count(),
            'flagged_count' => $items->where('parent_flagged', true)->count(),
            'by_source'     => $items->groupBy('source_app')
                ->map(fn ($g, $app) => [
                    'source_app' => $app ?? 'Unknown',
                    'count'      => $g->count(),
                ])
                ->sortByDesc('count')
                ->values()
                ->toArray(),
            'by_folder' => $items->groupBy('source_folder')
                ->map(fn ($g, $f) => [
                    'folder' => $f ?? 'Root',
                    'count'  => $g->count(),
                ])
                ->values()
                ->toArray(),
        ];
    }

    /**
     * حذف ملف من المعرض
     */
    public function deleteItem(GalleryItem $item): bool
    {
        if (Storage::disk('media')->exists($item->file_path)) {
            Storage::disk('media')->delete($item->file_path);
        }
        if ($item->thumbnail_path && Storage::disk('media')->exists($item->thumbnail_path)) {
            Storage::disk('media')->delete($item->thumbnail_path);
        }

        $item->delete();
        return true;
    }

    // ==================== Private Helpers ====================

    private function detectMediaType(string $mimeType): string
    {
        return match (true) {
            str_starts_with($mimeType, 'image/') => 'photo',
            str_starts_with($mimeType, 'video/') => 'video',
            str_starts_with($mimeType, 'audio/') => 'audio',
            default                               => 'document',
        };
    }

    private function createThumbnail(UploadedFile $file, int $deviceId, string $fileName): string
    {
        $thumbDir  = "thumbnails/{$deviceId}";
        $thumbName = 'thumb_' . $fileName;
        Storage::disk('media')->putFileAs($thumbDir, $file, $thumbName);
        return "{$thumbDir}/{$thumbName}";
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
