<?php

namespace App\Application\Http\Requests\Device;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'altitude'  => 'sometimes|numeric',
            'accuracy'  => 'sometimes|numeric|min:0',
            'speed'     => 'sometimes|numeric|min:0',
            'bearing'   => 'sometimes|numeric|between:0,360',
            'address'   => 'sometimes|string|max:500',
            'city'      => 'sometimes|string|max:100',
            'country'   => 'sometimes|string|max:100',
            'provider'  => 'sometimes|string|max:20',
        ];
    }
}
