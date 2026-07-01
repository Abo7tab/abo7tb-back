<?php

namespace App\Application\Http\Controllers\Api\V1\Notification;

use App\Application\Http\Controllers\Controller;
use App\Domain\Notification\Models\Notification;
use App\Domain\Notification\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * GET /api/v1/notifications
     * جميع الإشعارات (للأب)
     *
     * Query: ?unread_only=1  &per_page=20  &type=location_alert
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = $this->notificationService->getUserNotifications(
            userId:     $request->user()->id,
            perPage:    $request->integer('per_page', 20),
            unreadOnly: $request->boolean('unread_only')
        );

        $unreadCount = $this->notificationService->getUnreadCount($request->user()->id);

        return $this->success([
            'unread_count' => $unreadCount,
            'items'        => $notifications->map(fn (Notification $n) => [
                'id'         => $n->id,
                'title'      => $n->title,
                'message'    => $n->message,
                'type'       => $n->type,
                'priority'   => $n->priority,
                'icon'       => $n->icon,
                'color'      => $n->getPriorityColor(),
                'data'       => $n->data,
                'is_read'    => $n->is_read,
                'device_id'  => $n->device_id,
                'created_at' => $n->created_at?->toIso8601String(),
            ]),
            'pagination' => [
                'total'        => $notifications->total(),
                'per_page'     => $notifications->perPage(),
                'current_page' => $notifications->currentPage(),
                'last_page'    => $notifications->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/v1/notifications/unread-count
     * عدد الإشعارات غير المقروءة (للـ badge)
     */
    public function unreadCount(Request $request): JsonResponse
    {
        return $this->success([
            'count' => $this->notificationService->getUnreadCount($request->user()->id),
        ]);
    }

    /**
     * PATCH /api/v1/notifications/{id}/read
     * تحديد إشعار واحد كمقروء
     */
    public function markRead(Request $request, int $id): JsonResponse
    {
        $notification = Notification::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$notification) {
            return $this->error('الإشعار غير موجود.', 404);
        }

        $notification->markAsRead();

        return $this->success(null, 'تم تحديد الإشعار كمقروء.');
    }

    /**
     * PATCH /api/v1/notifications/read-all
     * تحديد جميع الإشعارات كمقروءة
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $count = $this->notificationService->markAllAsRead($request->user()->id);

        return $this->success(
            ['marked_count' => $count],
            "تم تحديد {$count} إشعار كمقروء."
        );
    }

    /**
     * DELETE /api/v1/notifications/{id}
     * حذف إشعار واحد
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $deleted = Notification::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->delete();

        if (!$deleted) {
            return $this->error('الإشعار غير موجود.', 404);
        }

        return $this->success(null, 'تم حذف الإشعار.');
    }

    /**
     * DELETE /api/v1/notifications/clear-old
     * حذف الإشعارات القديمة المقروءة (أكثر من X يوم)
     *
     * Query: ?days=30
     */
    public function clearOld(Request $request): JsonResponse
    {
        $days  = $request->integer('days', 30);
        $count = $this->notificationService->deleteOld($request->user()->id, $days);

        return $this->success(
            ['deleted_count' => $count],
            "تم حذف {$count} إشعار قديم."
        );
    }
}
