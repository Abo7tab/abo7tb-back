<?php

namespace App\Domain\Device\Models;

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChildConsent extends Model
{
    protected $table = 'child_consents';

    protected $fillable = [
        'device_id',
        'user_id',
        'child_name',
        'child_age',
        'policy_version',
        'policy_text',
        'consent_status',
        'consent_given_at',
        'consent_ip',
        'consent_device',
        'allow_camera',
        'allow_microphone',
        'allow_gallery',
        'allow_location',
        'allow_call_monitoring',
        'allow_sms_monitoring',
        'allow_app_monitoring',
        'allow_web_monitoring',
        'allow_screen_lock',
        'allow_contacts_sync',
        'show_permanent_notification',
        'show_monitoring_icon',
        'revoked_at',
        'revocation_reason',
    ];

    protected $casts = [
        'consent_given_at'            => 'datetime',
        'revoked_at'                  => 'datetime',
        'child_age'                   => 'integer',
        'allow_camera'                => 'boolean',
        'allow_microphone'            => 'boolean',
        'allow_gallery'               => 'boolean',
        'allow_location'              => 'boolean',
        'allow_call_monitoring'       => 'boolean',
        'allow_sms_monitoring'        => 'boolean',
        'allow_app_monitoring'        => 'boolean',
        'allow_web_monitoring'        => 'boolean',
        'allow_screen_lock'           => 'boolean',
        'allow_contacts_sync'         => 'boolean',
        'show_permanent_notification' => 'boolean',
        'show_monitoring_icon'        => 'boolean',
    ];

    // ==================== Relations ====================

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==================== Helpers ====================

    public function isAccepted(): bool
    {
        return $this->consent_status === 'accepted'
            && $this->consent_given_at !== null;
    }

    public function isRevoked(): bool
    {
        return $this->consent_status === 'revoked';
    }

    public function allows(string $permission): bool
    {
        if (!$this->isAccepted()) {
            return false;
        }

        $field = 'allow_' . $permission;
        return $this->{$field} ?? false;
    }

    public function accept(string $ip, string $deviceInfo, array $permissions = []): bool
    {
        $data = array_merge([
            'consent_status'   => 'accepted',
            'consent_given_at' => now(),
            'consent_ip'       => $ip,
            'consent_device'   => $deviceInfo,
        ], $permissions);

        return $this->update($data);
    }

    public function revoke(string $reason = ''): bool
    {
        return $this->update([
            'consent_status'    => 'revoked',
            'revoked_at'        => now(),
            'revocation_reason' => $reason,
        ]);
    }

    public static function getDefaultPolicyText(): string
    {
        return <<<POLICY
سياسة المراقبة الأبوية - الإصدار 2.0

بموافقتك على هذه السياسة، فأنت توافق على ما يلي:

1. 📍 تتبع الموقع الجغرافي لجهازك
2. 📱 مراقبة التطبيقات المثبتة ووقت استخدامها
3. 🌐 مراقبة سجل تصفح الإنترنت
4. 📞 الوصول إلى سجل المكالمات
5. 💬 الوصول إلى الرسائل النصية
6. 👥 الوصول إلى جهات الاتصال
7. 📸 التقاط صور وتسجيل مقاطع من الكاميرا عند الطلب
8. 🎤 تسجيل صوتي عند الطلب
9. 🖼️ الوصول إلى معرض الصور والفيديوهات
10. 🔒 قفل الشاشة عن بُعد عند الحاجة

حقوقك:
✅ ستظهر أيقونة دائمة تدل على تفعيل المراقبة
✅ يمكنك إلغاء الموافقة في أي وقت
✅ جميع البيانات محمية ومشفرة
✅ البيانات لا تُستخدم لأي غرض آخر

هذه السياسة مخصصة للرقابة الأبوية على الأبناء دون سن 18.
POLICY;
    }
}
