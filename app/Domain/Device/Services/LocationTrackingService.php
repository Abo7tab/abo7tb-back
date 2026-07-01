<?php

namespace App\Domain\Device\Services;

use App\Domain\Device\DTOs\UpdateLocationDTO;
use App\Domain\Device\Models\Device;
use App\Domain\Device\Models\DeviceLocation;
use App\Events\LocationUpdated;

class LocationTrackingService
{
    /**
     * تسجيل الموقع الجديد + تحديث الجهاز + بث للأب عبر Reverb
     */
    public function updateLocation(Device $device, UpdateLocationDTO $dto): DeviceLocation
    {
        // حفظ الموقع في السجل التاريخي
        $location = DeviceLocation::create([
            'device_id'  => $device->id,
            'latitude'   => $dto->latitude,
            'longitude'  => $dto->longitude,
            'altitude'   => $dto->altitude,
            'accuracy'   => $dto->accuracy,
            'speed'      => $dto->speed,
            'bearing'    => $dto->bearing,
            'address'    => $dto->address,
            'city'       => $dto->city,
            'country'    => $dto->country,
            'provider'   => $dto->provider,
            'recorded_at' => now(),
        ]);

        // تحديث آخر موقع في جدول الجهاز
        $device->updateLocation($dto->latitude, $dto->longitude);

        // بث الموقع للأب عبر Reverb (Real-time)
        broadcast(new LocationUpdated($device, $location))->toOthers();

        return $location;
    }

    /**
     * حساب المسافة بين نقطتين (Haversine formula) — بالمتر
     */
    public function calculateDistance(
        float $lat1, float $lng1,
        float $lat2, float $lng2
    ): float {
        $earthRadius = 6371000; // meters
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
