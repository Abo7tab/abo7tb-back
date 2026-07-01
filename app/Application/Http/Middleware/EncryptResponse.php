<?php

namespace App\Application\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Response;

class EncryptResponse
{
    /**
     * الحقول الحساسة التي تُشفَّر في الاستجابة عند طلبها من الجهاز
     */
    private const SENSITIVE_FIELDS = [
        'message_body',
        'phone_number',
        'imei',
        'serial_number',
        'mac_address',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // تشفير فقط لو الطلب قادم من الجهاز (يحمل X-Device-Token)
        if (!$request->hasHeader('X-Device-Token')) {
            return $response;
        }

        if (
            $response->getStatusCode() === 200 &&
            str_contains($response->headers->get('Content-Type', ''), 'json')
        ) {
            $data = json_decode($response->getContent(), true);

            if (is_array($data)) {
                $response->setContent(json_encode($this->encryptSensitiveFields($data)));
            }
        }

        return $response;
    }

    private function encryptSensitiveFields(array $data): array
    {
        array_walk_recursive($data, function (&$value, $key) {
            if (
                in_array($key, self::SENSITIVE_FIELDS, true) &&
                is_string($value) &&
                !empty($value)
            ) {
                $value = Crypt::encryptString($value);
            }
        });

        return $data;
    }
}
