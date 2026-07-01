<?php

namespace App\Application\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Domain\Device\Models\Device
 */
class DeviceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'uuid'         => $this->uuid,
            'child_name'   => $this->child_name,
            'child_age'    => $this->child_age,
            'device_name'  => $this->device_name,
            'device_model' => $this->device_model,
            'device_brand' => $this->device_brand,

            'status' => [
                'is_online'     => $this->isOnline(),
                'is_locked'     => $this->is_locked_by_parent,
                'battery_level' => $this->battery_level,
                'is_charging'   => $this->is_charging,
                'is_screen_on'  => $this->is_screen_on,
                'current_wifi'  => $this->current_wifi,
                'last_seen_at'  => $this->last_seen_at?->toIso8601String(),
            ],

            'last_location' => $this->last_location_lat ? [
                'latitude'   => $this->last_location_lat,
                'longitude'  => $this->last_location_lng,
                'updated_at' => $this->last_location_at?->toIso8601String(),
            ] : null,

            'consent' => $this->whenLoaded('consent', fn () => [
                'status'      => $this->consent->consent_status,
                'given_at'    => $this->consent->consent_given_at?->toIso8601String(),
                'is_accepted' => $this->consent->isAccepted(),
                'permissions' => [
                    'camera'          => $this->consent->allow_camera,
                    'microphone'      => $this->consent->allow_microphone,
                    'gallery'         => $this->consent->allow_gallery,
                    'location'        => $this->consent->allow_location,
                    'call_monitoring' => $this->consent->allow_call_monitoring,
                    'sms_monitoring'  => $this->consent->allow_sms_monitoring,
                    'app_monitoring'  => $this->consent->allow_app_monitoring,
                    'web_monitoring'  => $this->consent->allow_web_monitoring,
                    'screen_lock'     => $this->consent->allow_screen_lock,
                    'contacts_sync'   => $this->consent->allow_contacts_sync,
                ],
                'transparency' => [
                    'show_notification' => $this->consent->show_permanent_notification,
                    'show_icon'         => $this->consent->show_monitoring_icon,
                ],
            ]),

            'permissions' => [
                'camera'        => $this->perm_camera,
                'microphone'    => $this->perm_microphone,
                'storage'       => $this->perm_storage,
                'location'      => $this->perm_location,
                'contacts'      => $this->perm_contacts,
                'call_log'      => $this->perm_call_log,
                'sms'           => $this->perm_sms,
                'overlay'       => $this->perm_overlay,
                'usage_stats'   => $this->perm_usage_stats,
                'accessibility' => $this->perm_accessibility,
                'device_admin'  => $this->perm_device_admin,
            ],

            'monitoring_enabled' => $this->monitoring_enabled,
            'is_active'          => $this->is_active,
            'android_version'    => $this->android_version,
            'app_version'        => $this->app_version,
            'registered_at'      => $this->registered_at?->toIso8601String(),
        ];
    }
}
