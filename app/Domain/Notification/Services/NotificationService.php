<?php

namespace App\Domain\Notification\Services;

use App\Domain\Notification\Models\Notification;

class NotificationService
{
    /**
     * إرسال إشعار وبثّه عبر Reverb
     */
    public function send(
        int     $userId,
        string  $title,
        string  $message,
        string  $type,
        string  $priority = 'medium',
        ?int    $deviceId = null,
        ?string $icon     = null,
        array   $data     = []
    ): Notification {

        $notification = Notification::create([
            'user_id'    => $userId,
            'device_id'  => $deviceId,
            'title'      => $title,
            'message'    => $message,
            'type'       => $type,
            'priority'   => $priority,
            'icon'       => $icon,
            'data'       => $data,
            'is_read'    => false,
            'created_at' => now(),
        ]);

        try {
            broadcast(new \App\Events\NotificationSent($notification));
        } catch (\Exception) {
            // لا توقف العملية
        }

        return $notification;
    }

    // ==================== Factory Methods ====================

    public function notifyNewApp(int $userId, int $deviceId, string $appName, string $packageName): Notification
    {
        return $this->send(
            userId:   $userId,
            title:    '📱 تطبيق جديد',
            message:  "تم تثبيت تطبيق جديد: {$appName}",
            type:     'new_app',
            priority: 'high',
            deviceId: $deviceId,
            icon:     'app',
            data:     ['package_name' => $packageName, 'app_name' => $appName]
        );
    }

    public function notifyLocationAlert(int $userId, int $deviceId, string $childName, string $zoneName, string $action): Notification
    {
        $emoji = $action === 'exit' ? '⚠️' : '✅';
        $verb  = $action === 'exit' ? 'خرج من' : 'دخل';

        return $this->send(
            userId:   $userId,
            title:    "{$emoji} تنبيه موقع",
            message:  "{$childName} {$verb} المنطقة الآمنة: {$zoneName}",
            type:     'location_alert',
            priority: 'urgent',
            deviceId: $deviceId,
            icon:     'location',
            data:     ['zone_name' => $zoneName, 'action' => $action]
        );
    }

    public function notifyTimeLimitReached(int $userId, int $deviceId, string $childName, string $limitType): Notification
    {
        return $this->send(
            userId:   $userId,
            title:    '⏰ انتهى وقت الاستخدام',
            message:  "وصل {$childName} للحد المسموح من وقت الشاشة",
            type:     'time_limit',
            priority: 'high',
            deviceId: $deviceId,
            icon:     'clock',
            data:     ['limit_type' => $limitType]
        );
    }

    public function notifyBlockedWebsite(int $userId, int $deviceId, string $childName, string $domain): Notification
    {
        return $this->send(
            userId:   $userId,
            title:    '🚫 محاولة الوصول لموقع محظور',
            message:  "حاول {$childName} الدخول إلى: {$domain}",
            type:     'blocked_website',
            priority: 'high',
            deviceId: $deviceId,
            icon:     'web',
            data:     ['domain' => $domain]
        );
    }

    public function notifyDeviceOffline(int $userId, int $deviceId, string $childName): Notification
    {
        return $this->send(
            userId:   $userId,
            title:    '📵 الجهاز غير متصل',
            message:  "جهاز {$childName} غير متصل بالإنترنت",
            type:     'device_offline',
            priority: 'medium',
            deviceId: $deviceId,
            icon:     'offline',
            data:     []
        );
    }

    public function notifySuspiciousCall(int $userId, int $deviceId, string $childName, string $phoneNumber): Notification
    {
        return $this->send(
            userId:   $userId,
            title:    '📞 مكالمة من رقم غير معروف',
            message:  "اتصل بـ{$childName} رقم غير موجود في جهات الاتصال",
            type:     'suspicious_call',
            priority: 'high',
            deviceId: $deviceId,
            icon:     'call',
            data:     ['phone_number' => $phoneNumber]
        );
    }

    // ==================== Query Methods ====================

    public function getUserNotifications(int $userId, int $perPage = 20, bool $unreadOnly = false)
    {
        $query = Notification::where('user_id', $userId)
            ->orderByDesc('created_at');

        if ($unreadOnly) {
            $query->unread();
        }

        return $query->paginate($perPage);
    }

    public function markAllAsRead(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);
    }

    public function getUnreadCount(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->count();
    }

    public function deleteOld(int $userId, int $days = 30): int
    {
        return Notification::where('user_id', $userId)
            ->where('is_read', true)
            ->where('created_at', '<', now()->subDays($days))
            ->delete();
    }
}
