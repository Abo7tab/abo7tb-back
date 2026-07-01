<?php

namespace App\Application\Http\Controllers\Api\V1\Communication;

use App\Application\Http\Controllers\Controller;
use App\Domain\Communication\DTOs\SyncCallLogsDTO;
use App\Domain\Communication\Models\CallLog;
use App\Domain\Communication\Services\CallMonitoringService;
use App\Domain\Device\Repositories\DeviceRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CallController extends Controller
{
    public function __construct(
        protected CallMonitoringService     $callService,
        protected DeviceRepositoryInterface $deviceRepo
    ) {}

    /**
     * POST /api/v1/devices/{uuid}/calls/sync
     * مزامنة سجلات المكالمات (من الجهاز)
     *
     * Body:
     * {
     *   "calls": [
     *     {
     *       "phone_number": "+201234567890",
     *       "contact_name": "Ahmed",
     *       "call_type": "incoming|outgoing|missed|rejected",
     *       "duration_sec": 120,
     *       "called_at": "2024-01-15T10:00:00Z"
     *     }
     *   ]
     * }
     */
    public function sync(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'calls'                    => 'required|array|max:1000',
            'calls.*.phone_number'     => 'required|string|max:30',
            'calls.*.contact_name'     => 'sometimes|nullable|string|max:255',
            'calls.*.call_type'        => 'required|in:incoming,outgoing,missed,rejected',
            'calls.*.duration_sec'     => 'sometimes|integer|min:0',
            'calls.*.called_at'        => 'required|date',
            'calls.*.is_blocked_number'=> 'sometimes|boolean',
        ]);

        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $dto    = SyncCallLogsDTO::fromArray($request->validated(), $device->id);
        $result = $this->callService->syncCallLogs($dto);

        return $this->success($result, "تم مزامنة {$result['synced']} مكالمة.");
    }

    /**
     * GET /api/v1/devices/{uuid}/calls
     * سجل المكالمات (للأب)
     *
     * Query: ?type=incoming|outgoing|missed  &period=today|week|month  &per_page=50
     */
    public function index(Request $request, string $uuid): JsonResponse
    {
        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device || $device->user_id !== $request->user()->id) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $query = CallLog::where('device_id', $device->id)
            ->orderByDesc('called_at');

        // فلترة بنوع المكالمة
        if ($request->filled('type')) {
            $query->where('call_type', $request->type);
        }

        // فلترة بالفترة
        match ($request->get('period', 'all')) {
            'today' => $query->whereDate('called_at', today()),
            'week'  => $query->whereBetween('called_at', [now()->startOfWeek(), now()->endOfWeek()]),
            'month' => $query->whereBetween('called_at', [now()->startOfMonth(), now()->endOfMonth()]),
            default => null,
        };

        // فلترة برقم معين
        if ($request->filled('number')) {
            $query->byNumber($request->number);
        }

        $calls = $query->paginate($request->integer('per_page', 50));

        return $this->success([
            'calls' => $calls->map(fn (CallLog $c) => [
                'id'                => $c->id,
                'phone_number'      => $c->phone_number,
                'contact_name'      => $c->contact_name,
                'call_type'         => $c->call_type,
                'duration_sec'      => $c->duration_sec,
                'duration_formatted'=> $c->duration_formatted,
                'is_blocked_number' => $c->is_blocked_number,
                'called_at'         => $c->called_at?->toIso8601String(),
            ]),
            'pagination' => [
                'total'        => $calls->total(),
                'per_page'     => $calls->perPage(),
                'current_page' => $calls->currentPage(),
                'last_page'    => $calls->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/v1/devices/{uuid}/calls/stats
     * إحصائيات المكالمات (للأب)
     *
     * Query: ?period=today|week|month
     */
    public function stats(Request $request, string $uuid): JsonResponse
    {
        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device || $device->user_id !== $request->user()->id) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $stats = $this->callService->getCallStats(
            $device->id,
            $request->get('period', 'today')
        );

        return $this->success($stats);
    }
}
