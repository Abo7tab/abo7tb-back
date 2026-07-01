<?php

namespace App\Domain\Shared\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Sent to the parent when a suspicious security event occurs on a child device.
 *
 * Supported events:
 *  - too_many_verify_attempts  → someone tried to enter parent password 5 times and failed
 */
class SuspiciousActivityNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly mixed  $device,
        public readonly string $event,
        public readonly array  $details = []
    ) {}

    public function via(object $notifiable): array
    {
        // Use mail if configured, otherwise log only
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $deviceName = $this->device->device_name ?? $this->device->device_model ?? 'جهاز الطفل';
        $childName  = $this->device->child_name ?? 'ابنك/ابنتك';

        $title   = $this->eventTitle();
        $body    = $this->eventBody($childName, $deviceName);
        $ip      = $this->details['ip'] ?? 'غير معروف';
        $time    = now()->format('Y-m-d H:i:s') . ' UTC';

        return (new MailMessage)
            ->subject("🚨 تنبيه أمني - Family Guard - {$deviceName}")
            ->greeting("مرحباً {$notifiable->name}،")
            ->line($body)
            ->line("**الجهاز:** {$deviceName} ({$childName})")
            ->line("**IP:** {$ip}")
            ->line("**الوقت:** {$time}")
            ->line('إذا كنت أنت من يحاول، يرجى التأكد من إدخال كلمة المرور الصحيحة.')
            ->line('إذا لم تكن أنت، يُنصح بتغيير كلمة المرور فوراً.')
            ->salutation('فريق Family Guard 🛡️');
    }

    public function toDatabase(object $notifiable): array
    {
        $deviceName = $this->device->device_name ?? $this->device->device_model ?? 'جهاز';
        $childName  = $this->device->child_name ?? 'الطفل';

        return [
            'type'        => 'security_alert',
            'event'       => $this->event,
            'title'       => $this->eventTitle(),
            'body'        => $this->eventBody($childName, $deviceName),
            'device_uuid' => $this->device->uuid,
            'device_name' => $deviceName,
            'child_name'  => $childName,
            'details'     => $this->details,
        ];
    }

    private function eventTitle(): string
    {
        return match ($this->event) {
            'too_many_verify_attempts' => '🚨 محاولات تحقق فاشلة متعددة',
            default                    => '🚨 نشاط مشبوه',
        };
    }

    private function eventBody(string $childName, string $deviceName): string
    {
        return match ($this->event) {
            'too_many_verify_attempts' =>
                "تم رصد {5} محاولات فاشلة متتالية للتحقق من هويتك على جهاز {$childName} ({$deviceName}). " .
                "تم حظر المحاولات مؤقتاً لمدة 15 دقيقة.",
            default =>
                "تم رصد نشاط مشبوه على جهاز {$childName} ({$deviceName}).",
        };
    }
}
