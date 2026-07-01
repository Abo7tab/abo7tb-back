<?php

namespace App\Application\Http\Requests\Device;

use Illuminate\Foundation\Http\FormRequest;

class SendCommandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'command_category' => 'required|in:camera,microphone,screen,gallery,location,system,app,notification,calls,sms,contacts',
            'command_type'     => 'required|string|max:50',
            'command_data'     => 'sometimes|array',
            'priority'         => 'sometimes|in:low,normal,high,urgent',
        ];
    }
}
