<?php

namespace App\Application\Http\Controllers\Api\V1\Media;

use App\Application\Http\Controllers\Controller;
use App\Domain\Device\Repositories\DeviceRepositoryInterface;
use App\Domain\Media\Models\GalleryItem;
use App\Domain\Media\Services\GalleryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    public function __construct(
        protected GalleryService            $galleryService,
        protected DeviceRepositoryInterface $deviceRepo
    ) {}

    /**
     * POST /api/v1/devices/{uuid}/gallery/upload
     * رفع ملف واحد للمعرض (من الجهاز)
     */
    public function upload(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'file'          => 'required|file|max:204800',
            'file_name'     => 'sometimes|string|max:255',
            'source_folder' => 'sometimes|string|max:255',
            'source_app'    => 'sometimes|string|max:100',
            'taken_at'      => 'sometimes|date',
            'latitude'      => 'sometimes|numeric|between:-90,90',
            'longitude'     => 'sometimes|numeric|between:-180,180',
            'width'         => 'sometimes|integer',
            'height'        => 'sometimes|integer',
            'duration_sec'  => 'sometimes|integer|min:1',
        ]);

        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $item = $this->galleryService->storeItem(
            deviceId: $device->id,
            file:     $request->file('file'),
            metadata: $request->only([
                'file_name', 'source_folder', 'source_app',
                'taken_at', 'latitude', 'longitude', 'width', 'height', 'duration_sec',
            ])
        );

        return $this->success([
            'uuid'       => $item->uuid,
            'media_type' => $item->media_type,
            'file_size'  => $item->getFileSizeFormatted(),
        ], 'تم رفع الملف بنجاح.', 201);
    }

    /**
     * POST /api/v1/devices/{uuid}/gallery/sync
     * مزامنة metadata بدون رفع ملفات (من الجهاز)
     *
     * Body:
     * {
     *   "items": [
     *     { "file_name": "IMG_001.jpg", "media_type": "photo", "md5_hash": "...", "file_size": 1024000 }
     *   ]
     * }
     */
    public function syncMetadata(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'items'                => 'required|array|max:1000',
            'items.*.file_name'    => 'required|string|max:255',
            'items.*.media_type'   => 'required|in:photo,video,audio,document',
            'items.*.file_size'    => 'sometimes|integer|min:0',
            'items.*.md5_hash'     => 'sometimes|string|max:32',
            'items.*.source_folder'=> 'sometimes|string|max:255',
            'items.*.source_app'   => 'sometimes|string|max:100',
            'items.*.taken_at'     => 'sometimes|date',
        ]);

        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $result = $this->galleryService->syncMetadata($device->id, $request->items);

        return $this->success($result, "تم مزامنة {$result['created']} ملف.");
    }

    /**
     * GET /api/v1/devices/{uuid}/gallery
     * عرض المعرض (للأب)
     *
     * Query: ?type=photo|video  &app=WhatsApp  &folder=DCIM  &flagged=1  &per_page=30
     */
    public function index(Request $request, string $uuid): JsonResponse
    {
        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device || $device->user_id !== $request->user()->id) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $gallery = $this->galleryService->getGallery(
            deviceId:  $device->id,
            perPage:   $request->integer('per_page', 30),
            mediaType: $request->get('type'),
            sourceApp: $request->get('app'),
            folder:    $request->get('folder'),
            flagged:   $request->boolean('flagged')
        );

        return $this->success([
            'items' => $gallery->map(fn (GalleryItem $g) => [
                'uuid'          => $g->uuid,
                'file_name'     => $g->file_name,
                'media_type'    => $g->media_type,
                'thumbnail_url' => $g->getThumbnailUrl(),
                'file_url'      => $g->getFileUrl(),
                'file_size'     => $g->getFileSizeFormatted(),
                'duration'      => $g->getDurationFormatted(),
                'source_app'    => $g->source_app,
                'source_folder' => $g->source_folder,
                'is_viewed'     => $g->parent_viewed,
                'is_flagged'    => $g->parent_flagged,
                'flag_reason'   => $g->flag_reason,
                'taken_at'      => $g->taken_at?->toIso8601String(),
            ]),
            'pagination' => [
                'total'        => $gallery->total(),
                'per_page'     => $gallery->perPage(),
                'current_page' => $gallery->currentPage(),
                'last_page'    => $gallery->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/v1/devices/{uuid}/gallery/stats
     * إحصائيات المعرض (للأب)
     */
    public function stats(Request $request, string $uuid): JsonResponse
    {
        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device || $device->user_id !== $request->user()->id) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        return $this->success($this->galleryService->getGalleryStats($device->id));
    }

    /**
     * PATCH /api/v1/gallery/{uuid}/flag
     * تعليم صورة/فيديو كمشبوه (من الأب)
     *
     * Body: { "reason": "inappropriate content" }
     */
    public function flag(Request $request, string $itemUuid): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $item = GalleryItem::where('uuid', $itemUuid)
            ->whereHas('device', fn ($q) => $q->where('user_id', $request->user()->id))
            ->first();

        if (!$item) {
            return $this->error('الملف غير موجود.', 404);
        }

        $item->flag($request->reason);

        return $this->success(null, 'تم تعليم الملف كمشبوه.');
    }

    /**
     * PATCH /api/v1/gallery/{uuid}/view
     * تحديد كمشاهَد (للأب)
     */
    public function markViewed(string $itemUuid): JsonResponse
    {
        $item = GalleryItem::where('uuid', $itemUuid)->first();

        if (!$item) {
            return $this->error('الملف غير موجود.', 404);
        }

        $item->markAsViewed();

        return $this->success(null, 'تم تحديد الملف كمشاهَد.');
    }

    /**
     * DELETE /api/v1/gallery/{uuid}
     * حذف ملف من المعرض (للأب)
     */
    public function destroy(Request $request, string $itemUuid): JsonResponse
    {
        $item = GalleryItem::where('uuid', $itemUuid)
            ->whereHas('device', fn ($q) => $q->where('user_id', $request->user()->id))
            ->first();

        if (!$item) {
            return $this->error('الملف غير موجود.', 404);
        }

        $this->galleryService->deleteItem($item);

        return $this->success(null, 'تم حذف الملف بنجاح.');
    }
}
