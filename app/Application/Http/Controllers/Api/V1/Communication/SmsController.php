<?php

namespace App\Application\Http\Controllers\Api\V1\Communication;

use App\Application\Http\Controllers\Controller;
use App\Domain\Communication\DTOs\BlockNumberDTO;
use App\Domain\Communication\DTOs\SyncSmsLogsDTO;
use App\Domain\Communication\Models\BlockedNumber;
use App\Domain\Communication\Models\SmsLog;
use App\Domain\Communication\Services\SmsMonitoringService;
use App\Domain\Device\Repositories\DeviceRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SmsController extends Controller
{
    public function __construct(
        protected SmsMonitoringService      $smsService,
        protected DeviceRepositoryInterface $deviceRepo
    ) {}

    /**
     * POST /api/v1/devices/{uuid}/sms/sync
     * مزامنة الرسائل النصية (من الجهاز)
     *
     * Body:
     * {
     *   "messages": [
     *     {
     *       "phone_number": "+201234567890",
     *       "contact_name": "Ahmed",
     *       "message_body": "Hello",
     *       "direction": "incoming|outgoing",
     *       "is_read": true,
     *       "sent_at": "2024-01-15T10:00:00Z"
     *     }
     *   ]
     * }
     */
    public function sync(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'messages'                => 'required|array|max:2000',
            'messages.*.phone_number' => 'required|string|max:30',
            'messages.*.contact_name' => 'sometimes|nullable|string|max:255',
            'messages.*.message_body' => 'sometimes|string|max:2000',
            'messages.*.direction'    => 'required|in:incoming,outgoing',
            'messages.*.is_read'      => 'sometimes|boolean',
            'messages.*.sent_at'      => 'required|date',
        ]);

        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $dto    = SyncSmsLogsDTO::fromArray($request->validated(), $device->id);
        $result = $this->smsService->syncSmsLogs($dto);

        return $this->success($result, "تم مزامنة {$result['synced']} رسالة.");
    }

    /**
     * GET /api/v1/devices/{uuid}/sms
     * قائمة الرسائل (للأب)
     *
     * Query: ?direction=incoming|outgoing  &period=today|week|month
     *        &search=keyword  &number=+201234567890  &per_page=50
     */
    public function index(Request $request, string $uuid): JsonResponse
    {
        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device || $device->user_id !== $request->user()->id) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $query = SmsLog::where('device_id', $device->id)
            ->orderByDesc('sent_at');

        if ($request->filled('direction')) {
            $query->where('message_type', $request->direction === 'outgoing' ? 'sent' : 'received');
        }

        match ($request->get('period', 'all')) {
            'today' => $query->whereDate('sent_at', today()),
            'week'  => $query->whereBetween('sent_at', [now()->startOfWeek(), now()->endOfWeek()]),
            'month' => $query->whereBetween('sent_at', [now()->startOfMonth(), now()->endOfMonth()]),
            default => null,
        };

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('number')) {
            $query->byNumber($request->number);
        }

        $messages = $query->paginate($request->integer('per_page', 50));

        return $this->success([
            'messages' => $messages->map(fn (SmsLog $m) => [
                'id'           => $m->id,
                'phone_number' => $m->phone_number,
                'contact_name' => $m->contact_name,
                'preview'      => $m->preview,
                'direction'    => $m->direction,
                'is_read'      => $m->parent_read,
                'sent_at'      => $m->sent_at?->toIso8601String(),
            ]),
            'pagination' => [
                'total'        => $messages->total(),
                'per_page'     => $messages->perPage(),
                'current_page' => $messages->currentPage(),
                'last_page'    => $messages->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/v1/devices/{uuid}/sms/conversation/{number}
     * محادثة كاملة مع رقم معين (للأب)
     */
    public function conversation(Request $request, string $uuid, string $number): JsonResponse
    {
        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device || $device->user_id !== $request->user()->id) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $thread = $this->smsService->getConversation($device->id, urldecode($number));

        return $this->success([
            'phone_number' => urldecode($number),
            'messages'     => $thread,
            'count'        => count($thread),
        ]);
    }

    /**
     * GET /api/v1/devices/{uuid}/sms/stats
     * إحصائيات الرسائل (للأب)
     */
    public function stats(Request $request, string $uuid): JsonResponse
    {
        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device || $device->user_id !== $request->user()->id) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $stats = $this->smsService->getSmsStats(
            $device->id,
            $request->get('period', 'today')
        );

        return $this->success($stats);
    }

    /**
     * POST /api/v1/devices/{uuid}/blocked-numbers
     * حظر رقم هاتف (مكالمات + رسائل)
     */
    public function blockNumber(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'phone_number' => 'required|string|max:30',
            'contact_name' => 'sometimes|nullable|string|max:255',
            'block_calls'  => 'sometimes|boolean',
            'block_sms'    => 'sometimes|boolean',
            'reason'       => 'sometimes|nullable|string|max:500',
        ]);

        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device || $device->user_id !== $request->user()->id) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $blocked = BlockedNumber::updateOrCreate(
            [
                'device_id'    => $device->id,
                'phone_hash'   => BlockedNumber::hashPhone($request->phone_number),
            ],
            [
                'phone_number' => $request->phone_number,
                'contact_name' => $request->contact_name  ?? null,
                'block_calls'  => $request->boolean('block_calls',  true),
                'block_sms'    => $request->boolean('block_sms',    true),
                'reason'       => $request->reason        ?? null,
                'is_active'    => true,
                'blocked_at'   => now(),
            ]
        );

        return $this->success([
            'id'           => $blocked->id,
            'phone_number' => $blocked->phone_number,
            'block_calls'  => $blocked->block_calls,
            'block_sms'    => $blocked->block_sms,
        ], "تم حظر الرقم {$blocked->phone_number}.");
    }

    /**
     * DELETE /api/v1/devices/{uuid}/blocked-numbers/{number}
     * إلغاء حظر رقم هاتف
     */
    public function unblockNumber(Request $request, string $uuid, string $number): JsonResponse
    {
        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device || $device->user_id !== $request->user()->id) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $result = BlockedNumber::where('device_id',    $device->id)
                               ->where('phone_hash', BlockedNumber::hashPhone(urldecode($number)))
                               ->update(['is_active' => false]);

        if (!$result) {
            return $this->error('الرقم غير محظور أصلاً.', 404);
        }

        return $this->success(null, 'تم إلغاء الحظر بنجاح.');
    }

    /**
     * GET /api/v1/devices/{uuid}/blocked-numbers
     * قائمة الأرقام المحظورة (للأب والجهاز)
     */
    public function blockedNumbers(Request $request, string $uuid): JsonResponse
    {
        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $blocked = BlockedNumber::where('device_id', $device->id)
            ->active()
            ->get();

        return $this->success([
            'blocked_numbers' => $blocked->map(fn (BlockedNumber $b) => [
                'id'           => $b->id,
                'phone_number' => $b->phone_number,
                'contact_name' => $b->contact_name,
                'block_calls'  => $b->block_calls,
                'block_sms'    => $b->block_sms,
                'reason'       => $b->reason,
                'blocked_at'   => $b->blocked_at?->toIso8601String(),
            ]),
            'total' => $blocked->count(),
        ]);
    }
}
