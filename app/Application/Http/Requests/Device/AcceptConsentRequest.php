<?php

namespace App\Application\Http\Requests\Device;

use Illuminate\Foundation\Http\FormRequest;

class AcceptConsentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permissions'                 => 'required|array',
            'permissions.camera'          => 'boolean',
            'permissions.microphone'      => 'boolean',
            'permissions.gallery'         => 'boolean',
            'permissions.location'        => 'boolean',
            'permissions.call_monitoring' => 'boolean',
            'permissions.sms_monitoring'  => 'boolean',
            'permissions.app_monitoring'  => 'boolean',
            'permissions.web_monitoring'  => 'boolean',
            'permissions.screen_lock'     => 'boolean',
            'permissions.contacts_sync'   => 'boolean',
        ];
    }
}
