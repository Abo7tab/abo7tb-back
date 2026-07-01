<?php

namespace App\Application\Http\Controllers\Api\V1\Web;

use App\Application\Http\Controllers\Controller;
use App\Domain\Device\Repositories\DeviceRepositoryInterface;
use App\Domain\Web\DTOs\BatchBrowsingDTO;
use App\Domain\Web\DTOs\RecordBrowsingDTO;
use App\Domain\Web\Models\BrowsingHistory;
use App\Domain\Web\Services\WebFilteringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrowsingController extends Controller
{
    public function __construct(
        protected WebFilteringService       $filteringService,
        protected DeviceRepositoryInterface $deviceRepo
    ) {}

    /**
     * POST /api/v1/devices/{uuid}/browsing
     * تسجيل زيارة واحدة (من الجهاز)
     */
    public function record(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'url'          => 'required|string|max:2048',
            'title'        => 'sometimes|string|max:500',
            'browser_name' => 'sometimes|string|max:100',
        ]);

        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $dto   = RecordBrowsingDTO::fromArray($request->validated(), $device->id);
        $visit = $this->filteringService->recordVisit($dto);

        return $this->success([
            'visit_count' => $visit->visit_count,
            'visited_at'  => $visit->visited_at?->toIso8601String(),
        ], 'تم تسجيل الزيارة.');
    }

    /**
     * POST /api/v1/devices/{uuid}/browsing/batch
     * تسجيل مجموعة زيارات دفعة واحدة (من الجهاز)
     *
     * Body:
     * {
     *   "history": [
     *     { "url": "https://google.com", "title": "Google", "visited_at": "2024-01-15T10:00:00Z" }
     *   ]
     * }
     */
    public function batch(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'history'                => 'required|array|max:500',
            'history.*.url'          => 'required|string|max:2048',
            'history.*.title'        => 'sometimes|string|max:500',
            'history.*.browser_name' => 'sometimes|string|max:100',
            'history.*.visit_count'  => 'sometimes|integer|min:1',
            'history.*.visited_at'   => 'sometimes|date',
        ]);

        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $dto    = BatchBrowsingDTO::fromArray($request->validated(), $device->id);
        $result = $this->filteringService->recordBatch($dto);

        return $this->success($result, "تم تسجيل {$result['recorded']} زيارة.");
    }

    /**
     * GET /api/v1/devices/{uuid}/browsing
     * سجل التصفح (للأب)
     *
     * Query: ?period=today|week|month  OR  ?date=2024-01-15
     *        &search=youtube  &browser=Chrome  &per_page=50
     */
    public function index(Request $request, string $uuid): JsonResponse
    {
        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device || $device->user_id !== $request->user()->id) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $query = BrowsingHistory::where('device_id', $device->id)
            ->orderByDesc('visited_at');

        // فلترة بالتاريخ
        if ($request->has('date')) {
            $query->whereDate('visited_at', $request->date);
        } elseif ($request->has('period')) {
            match ($request->period) {
                'week'  => $query->whereBetween('visited_at', [now()->startOfWeek(), now()->endOfWeek()]),
                'month' => $query->whereBetween('visited_at', [now()->startOfMonth(), now()->endOfMonth()]),
                default => $query->whereDate('visited_at', today()),
            };
        }

        // بحث في URL والعنوان
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // فلترة بالمتصفح
        if ($request->filled('browser')) {
            $query->byBrowser($request->browser);
        }

        $history = $query->paginate($request->integer('per_page', 50));

        return $this->success([
            'history' => $history->map(fn (BrowsingHistory $h) => [
                'id'           => $h->id,
                'url'          => $h->url,
                'domain'       => $h->domain,
                'title'        => $h->title,
                'browser_name' => $h->browser_name,
                'visit_count'  => $h->visit_count,
                'is_https'     => $h->isHttps(),
                'visited_at'   => $h->visited_at?->toIso8601String(),
            ]),
            'pagination' => [
                'total'        => $history->total(),
                'per_page'     => $history->perPage(),
                'current_page' => $history->currentPage(),
                'last_page'    => $history->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/v1/devices/{uuid}/browsing/stats
     * إحصائيات التصفح (للأب)
     *
     * Query: ?period=today|week|month
     */
    public function stats(Request $request, string $uuid): JsonResponse
    {
        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device || $device->user_id !== $request->user()->id) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $stats = $this->filteringService->getBrowsingStats(
            $device->id,
            $request->get('period', 'today')
        );

        return $this->success($stats);
    }

    /**
     * POST /api/v1/devices/{uuid}/browsing/check
     * فحص إذا كان الموقع محظوراً قبل فتحه (من الجهاز)
     *
     * Body: { "url": "https://example.com" }
     */
    public function check(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'url' => 'required|string|max:2048',
        ]);

        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $result = $this->filteringService->isWebsiteBlocked($device->id, $request->url);

        return $this->success($result);
    }
}
