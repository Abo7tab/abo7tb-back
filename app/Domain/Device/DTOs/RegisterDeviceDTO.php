<?php

namespace App\Domain\Device\DTOs;

final readonly class RegisterDeviceDTO
{
    public function __construct(
        public int     $userId,
        public string  $childName,
        public int     $childAge,
        public string  $deviceName,
        public string  $deviceId,
        public ?string $deviceModel    = null,
        public ?string $deviceBrand    = null,
        public ?string $androidVersion = null,
        public ?int    $sdkVersion     = null,
        public ?string $imei           = null,
        public ?string $serialNumber   = null,
        public ?string $macAddress     = null,
        public ?string $appVersion     = null,
    ) {}

    public static function fromArray(array $data, int $userId): self
    {
        return new self(
            userId:         $userId,
            childName:      $data['child_name'],
            childAge:       (int) $data['child_age'],
            deviceName:     $data['device_name'],
            deviceId:       $data['device_id'],
            deviceModel:    $data['device_model']    ?? null,
            deviceBrand:    $data['device_brand']    ?? null,
            androidVersion: $data['android_version'] ?? null,
            sdkVersion:     isset($data['sdk_version']) ? (int) $data['sdk_version'] : null,
            imei:           $data['imei']            ?? null,
            serialNumber:   $data['serial_number']   ?? null,
            macAddress:     $data['mac_address']     ?? null,
            appVersion:     $data['app_version']     ?? null,
        );
    }
}
