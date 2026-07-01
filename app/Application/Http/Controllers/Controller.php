<?php

namespace App\Application\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;

/**
 * Base controller for all API controllers.
 */
abstract class Controller extends BaseController
{
    /**
     * Return a standardized success response.
     */
    protected function success(mixed $data = null, string $message = '', int $code = 200): \Illuminate\Http\JsonResponse
    {
        $payload = ['success' => true];

        if ($message) {
            $payload['message'] = $message;
        }

        if ($data !== null) {
            $payload['data'] = $data;
        }

        return response()->json($payload, $code);
    }

    /**
     * Return a standardized error response.
     */
    protected function error(string $message, int $code = 422, mixed $errors = null): \Illuminate\Http\JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $code);
    }
}
