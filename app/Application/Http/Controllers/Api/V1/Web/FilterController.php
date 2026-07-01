<?php

namespace App\Application\Http\Controllers\Api\V1\Web;

use App\Application\Http\Controllers\Controller;
use App\Domain\Device\Repositories\DeviceRepositoryInterface;
use App\Domain\Web\DTOs\BlockWebsiteDTO;
use App\Domain\Web\Models\BlockedWebsite;
use App\Domain\Web\Models\WebsiteCategory;
use App\Domain\Web\Services\WebFilteringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FilterController extends Controller
{
    public function __construct(
        protected WebFilteringService       $filteringService,
        protected DeviceRepositoryInterface $deviceRepo
    ) {}

    /**
     * GET /api/v1/devices/{uuid}/blocked-websites
     * قائمة المواقع المحظورة (للأب والجهاز)
     *
     * Query: ?category=adult|social|...
     */
    public function index(Request $request, string $uuid): JsonResponse
    {
        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $query = BlockedWebsite::where('device_id', $device->id)
            ->where('is_active', true);

        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }

        $blocked = $query->orderBy('domain')->get();

        return $this->success([
            'blocked_websites' => $blocked->map(fn (BlockedWebsite $b) => [
                'id'         => $b->id,
                'domain'     => $b->domain,
                'category'   => $b->category,
                'block_type' => $b->block_type,
                'blocked_at' => $b->blocked_at?->toIso8601String(),
            ]),
            'total' => $blocked->count(),
        ]);
    }

    /**
     * POST /api/v1/devices/{uuid}/blocked-websites
     * حظر موقع (من الأب)
     *
     * Body: { "domain": "tiktok.com", "block_type": "domain", "category": "social" }
     */
    public function block(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'domain'     => 'required|string|max:255',
            'block_type' => 'sometimes|in:domain,keyword,category',
            'category'   => 'sometimes|nullable|string|max:50',
        ]);

        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device || $device->user_id !== $request->user()->id) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $dto     = BlockWebsiteDTO::fromArray($request->validated(), $device->id);
        $blocked = $this->filteringService->blockWebsite($dto);

        return $this->success([
            'id'         => $blocked->id,
            'domain'     => $blocked->domain,
            'category'   => $blocked->category,
            'block_type' => $blocked->block_type,
        ], "تم حظر: {$blocked->domain}");
    }

    /**
     * DELETE /api/v1/devices/{uuid}/blocked-websites/{domain}
     * إلغاء حظر موقع (من الأب)
     */
    public function unblock(
        Request $request,
        string  $uuid,
        string  $domain
    ): JsonResponse {
        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device || $device->user_id !== $request->user()->id) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $result = $this->filteringService->unblockWebsite(
            $device->id,
            urldecode($domain)
        );

        if (!$result) {
            return $this->error('الموقع غير محظور أصلاً.', 404);
        }

        return $this->success(null, 'تم إلغاء الحظر بنجاح.');
    }

    /**
     * POST /api/v1/devices/{uuid}/blocked-websites/category
     * حظر فئة كاملة من المواقع (من الأب)
     *
     * Body: { "category": "adult" }
     */
    public function blockCategory(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'category' => 'required|string|max:50',
        ]);

        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device || $device->user_id !== $request->user()->id) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        try {
            $count = $this->filteringService->blockCategory(
                $device->id,
                $request->category
            );

            return $this->success(
                ['blocked_domains' => $count],
                "تم حظر فئة {$request->category} ({$count} نطاق)."
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * DELETE /api/v1/devices/{uuid}/blocked-websites/category/{category}
     * إلغاء حظر فئة كاملة (من الأب)
     */
    public function unblockCategory(
        Request $request,
        string  $uuid,
        string  $category
    ): JsonResponse {
        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device || $device->user_id !== $request->user()->id) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $count = $this->filteringService->unblockCategory($device->id, $category);

        return $this->success(
            ['unblocked' => $count],
            "تم إلغاء حظر فئة {$category}."
        );
    }

    /**
     * GET /api/v1/website-categories
     * جميع فئات المواقع المتاحة للحظر
     */
    public function categories(): JsonResponse
    {
        $categories = WebsiteCategory::orderBy('category_name')->get();

        return $this->success([
            'categories' => $categories->map(fn (WebsiteCategory $c) => [
                'id'            => $c->id,
                'category_name' => $c->category_name,
                'description'   => $c->description,
                'domains_count' => count($c->domains ?? []),
                'is_default'    => $c->is_default,
            ]),
        ]);
    }
}
