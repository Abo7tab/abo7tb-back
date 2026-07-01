<?php

namespace App\Application\Http\Controllers\Api\V1\Dashboard;

use App\Application\Http\Controllers\Controller;
use App\Domain\Dashboard\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    /**
     * GET /api/v1/dashboard/summary
     * الملخص الرئيسي للداشبورد (devices + today stats + alerts + storage)
     */
    public function summary(Request $request): JsonResponse
    {
        return $this->success(
            $this->dashboardService->getSummary($request->user()->id)
        );
    }

    /**
     * GET /api/v1/dashboard/screen-time
     * رسم بياني لوقت الشاشة
     *
     * Query: ?days=7|14|30
     */
    public function screenTime(Request $request): JsonResponse
    {
        $request->validate([
            'days' => 'sometimes|integer|in:7,14,30',
        ]);

        return $this->success(
            $this->dashboardService->getScreenTimeChart(
                userId: $request->user()->id,
                days:   $request->integer('days', 7)
            )
        );
    }

    /**
     * GET /api/v1/dashboard/top-apps
     * أكثر التطبيقات استخداماً
     *
     * Query: ?days=7  &limit=10
     */
    public function topApps(Request $request): JsonResponse
    {
        $request->validate([
            'days'  => 'sometimes|integer|in:7,14,30',
            'limit' => 'sometimes|integer|min:5|max:20',
        ]);

        return $this->success(
            $this->dashboardService->getTopApps(
                userId: $request->user()->id,
                days:   $request->integer('days', 7),
                limit:  $request->integer('limit', 10)
            )
        );
    }

    /**
     * GET /api/v1/dashboard/weekly-report
     * تقرير أسبوعي شامل
     */
    public function weeklyReport(Request $request): JsonResponse
    {
        return $this->success(
            $this->dashboardService->getWeeklyReport($request->user()->id)
        );
    }

    /**
     * DELETE /api/v1/dashboard/cache
     * مسح الـ Cache يدوياً (للتطوير أو عند الطلب)
     */
    public function clearCache(Request $request): JsonResponse
    {
        $this->dashboardService->clearCache($request->user()->id);

        return $this->success(null, 'تم مسح الـ Cache بنجاح.');
    }
}
