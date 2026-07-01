<?php

namespace App\Domain\Communication\Services;

use App\Domain\Communication\Models\Contact;
use App\Domain\Device\Models\Device;

class ContactService
{
    /**
     * مزامنة جهات اتصال الجهاز (batch sync)
     */
    public function syncContacts(Device $device, array $contacts): array
    {
        $synced  = 0;
        $skipped = 0;

        foreach ($contacts as $contactData) {
            if (empty($contactData['phone_number'])) {
                $skipped++;
                continue;
            }

            Contact::updateOrCreate(
                [
                    'device_id'    => $device->id,
                    'phone_number' => $contactData['phone_number'],
                ],
                [
                    'contact_name' => $contactData['contact_name'] ?? 'غير معروف',
                    'contact_type' => $contactData['contact_type'] ?? 'phone',
                    'email'        => $contactData['email'] ?? null,
                    'is_favorite'  => $contactData['is_favorite'] ?? false,
                    'photo_url'    => $contactData['photo_url'] ?? null,
                    'synced_at'    => now(),
                ]
            );
            $synced++;
        }

        return [
            'total'   => count($contacts),
            'synced'  => $synced,
            'skipped' => $skipped,
        ];
    }

    /**
     * قائمة جهات الاتصال
     */
    public function getContacts(int $deviceId, int $perPage = 50, ?string $search = null)
    {
        $query = Contact::where('device_id', $deviceId);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('contact_name', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('contact_name')->paginate($perPage);
    }

    /**
     * حذف جهة اتصال
     */
    public function delete(int $deviceId, int $contactId): bool
    {
        return Contact::where('device_id', $deviceId)
            ->where('id', $contactId)
            ->delete() > 0;
    }

    /**
     * إجمالي جهات الاتصال لجهاز
     */
    public function getCount(int $deviceId): int
    {
        return Contact::where('device_id', $deviceId)->count();
    }
}
