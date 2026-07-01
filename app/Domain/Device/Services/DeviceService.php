<?php

namespace App\Domain\Device\Services;

use App\Domain\Device\DTOs\RegisterDeviceDTO;
use App\Domain\Device\Models\ChildConsent;
use App\Domain\Device\Models\Device;
use App\Domain\Device\Repositories\DeviceRepositoryInterface;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DeviceService
{
    public function __construct(
        protected DeviceRepositoryInterface $deviceRepo
    ) {}

    /**
     * تسجيل جهاز جديد + إنشاء سجل الموافقة في نفس الـ Transaction
     */
    public function registerDevice(RegisterDeviceDTO $dto): Device
    {
        // 1. التحقق من حد الأجهزة حسب الخطة
        $this->checkDeviceLimit($dto->userId);

        // 2. التحقق من عدم تكرار الجهاز
        $existing = $this->deviceRepo->findByDeviceId($dto->deviceId);
        if ($existing) {
            // إذا كان الجهاز مسجلاً لنفس الأب، قم بتحديث بياناته وأرجعه
            if ($existing->user_id === $dto->userId) {
                $existing->update([
                    'child_name'   => $dto->childName,
                    'child_age'    => $dto->childAge,
                    'device_name'  => $dto->deviceName,
                    'device_model' => $dto->deviceModel,
                    'app_version'  => $dto->appVersion,
                ]);
                return $existing->load('consent');
            }
            throw new \RuntimeException('هذا الجهاز مسجل لدى حساب آخر.');
        }

        // 3. إنشاء الجهاز + الموافقة في Transaction واحدة
        return DB::transaction(function () use ($dto): Device {

            $device = Device::create([
                'uuid'            => Str::uuid(),
                'user_id'         => $dto->userId,
                'child_name'      => $dto->childName,
                'child_age'       => $dto->childAge,
                'device_name'     => $dto->deviceName,
                'device_id'       => $dto->deviceId,
                'device_model'    => $dto->deviceModel,
                'device_brand'    => $dto->deviceBrand,
                'android_version' => $dto->androidVersion,
                'sdk_version'     => $dto->sdkVersion,
                'imei'            => $dto->imei,
                'serial_number'   => $dto->serialNumber,
                'mac_address'     => $dto->macAddress,
                'app_version'     => $dto->appVersion,
                'registered_at'   => now(),
                // الحالة الافتراضية
                'is_online'           => false,
                'is_charging'         => false,
                'monitoring_enabled'  => true,
                'is_active'           => true,
                'is_locked_by_parent' => false,
            ]);

            ChildConsent::create([
                'device_id'      => $device->id,
                'user_id'        => $dto->userId,
                'child_name'     => $dto->childName,
                'child_age'      => $dto->childAge,
                'policy_version' => '2.0',
                'policy_text'    => ChildConsent::getDefaultPolicyText(),
                'consent_status' => 'pending',
                // كل الصلاحيات false حتى يوافق الطفل
                'allow_camera'                => false,
                'allow_microphone'            => false,
                'allow_gallery'               => false,
                'allow_location'              => false,
                'allow_call_monitoring'       => false,
                'allow_sms_monitoring'        => false,
                'allow_app_monitoring'        => false,
                'allow_web_monitoring'        => false,
                'allow_screen_lock'           => false,
                'allow_contacts_sync'         => false,
                // الشفافية دائماً مفعلة
                'show_permanent_notification' => true,
                'show_monitoring_icon'        => true,
            ]);

            return $device->load('consent');
        });
    }

    /**
     * قبول الموافقة من الطفل
     */
    public function acceptConsent(
        Device $device,
        array  $permissions,
        string $ip,
        string $deviceInfo
    ): ChildConsent {

        $consent = $device->consent;

        if (!$consent) {
            throw new \RuntimeException('لم يتم العثور على سجل الموافقة.');
        }

        if ($consent->isAccepted()) {
            throw new \RuntimeException('تم قبول الموافقة مسبقاً.');
        }

        $allowedPermissions = [
            'allow_camera'          => $permissions['camera']          ?? false,
            'allow_microphone'      => $permissions['microphone']      ?? false,
            'allow_gallery'         => $permissions['gallery']         ?? false,
            'allow_location'        => $permissions['location']        ?? false,
            'allow_call_monitoring' => $permissions['call_monitoring'] ?? false,
            'allow_sms_monitoring'  => $permissions['sms_monitoring']  ?? false,
            'allow_app_monitoring'  => $permissions['app_monitoring']  ?? false,
            'allow_web_monitoring'  => $permissions['web_monitoring']  ?? false,
            'allow_screen_lock'     => $permissions['screen_lock']     ?? false,
            'allow_contacts_sync'   => $permissions['contacts_sync']   ?? false,
        ];

        $consent->accept($ip, $deviceInfo, $allowedPermissions);

        return $consent->fresh();
    }

    /**
     * تحديث بيانات الجهاز اللحظية (من الجهاز)
     */
    public function updateDeviceStatus(Device $device, array $data): bool
    {
        $allowed  = ['battery_level', 'is_charging', 'is_screen_on', 'current_wifi', 'is_online', 'app_version'];
        $filtered = array_intersect_key($data, array_flip($allowed));
        $filtered['last_seen_at'] = now();
        $filtered['is_online']    = true;

        return $this->deviceRepo->updateStatus($device->id, $filtered);
    }

    /**
     * تحديث صلاحيات الجهاز
     */
    public function updatePermissions(Device $device, array $permissions): bool
    {
        return $this->deviceRepo->updatePermissions($device->id, $permissions);
    }

    /**
     * التحقق من حد الأجهزة حسب الخطة
     */
    private function checkDeviceLimit(int $userId): void
    {
        $user  = User::findOrFail($userId);
        $count = $this->deviceRepo->countUserDevices($userId);

        $limits = [
            'free'    => config('parental-control.limits.free_plan_devices', 100),
            'premium' => config('parental-control.limits.premium_plan_devices', 5),
            'family'  => config('parental-control.limits.family_plan_devices', 10),
        ];

        $plan  = $user->subscription_plan ?? 'free';
        $limit = $limits[$plan] ?? 2;

        if ($count >= $limit) {
            throw new \RuntimeException(
                "لقد وصلت للحد الأقصى من الأجهزة ({$limit}) في خطة {$plan}. قم بالترقية للإضافة المزيد."
            );
        }
    }
}
