<?php

namespace App\Application\Http\Requests\Device;

use Illuminate\Foundation\Http\FormRequest;

class RegisterDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'child_name'      => 'required|string|max:100',
            'child_age'       => 'required|integer|min:1|max:17',
            'device_name'     => 'required|string|max:100',
            'device_id'       => 'required|string|max:255',
            'device_model'    => 'sometimes|string|max:100',
            'device_brand'    => 'sometimes|string|max:50',
            'android_version' => 'sometimes|string|max:20',
            'sdk_version'     => 'sometimes|integer',
            'imei'            => 'sometimes|string|max:50',
            'serial_number'   => 'sometimes|string|max:100',
            'mac_address'     => 'sometimes|string|max:50',
            'app_version'     => 'sometimes|string|max:20',
        ];
    }

    public function messages(): array
    {
        return [
            'child_name.required'  => 'اسم الطفل مطلوب',
            'child_age.required'   => 'عمر الطفل مطلوب',
            'child_age.max'        => 'العمر يجب أن يكون أقل من 18 سنة',
            'device_name.required' => 'اسم الجهاز مطلوب',
            'device_id.required'   => 'معرف الجهاز مطلوب',
        ];
    }
}
