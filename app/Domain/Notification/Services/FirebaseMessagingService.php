<?php

namespace App\Domain\Notification\Services;

use App\Domain\Notification\Exceptions\InvalidFcmTokenException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FirebaseMessagingService
{
    public function sendToDevice(string $token, array $data, string $priority = 'normal'): array
    {
        $projectId = $this->projectId();

        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                'message' => [
                    'token' => $token,
                    'data' => $this->stringifyData($data),
                    'android' => [
                        'priority' => 'high',
                        'ttl' => '300s',
                    ],
                ],
            ]);

        if ($response->failed()) {
            $errorStatus = $response->json('error.status');
            $errorMessage = $response->json('error.message', $response->body());

            if (in_array($errorStatus, ['NOT_FOUND', 'INVALID_ARGUMENT'], true)
                || str_contains((string) $errorMessage, 'UNREGISTERED')
            ) {
                throw new InvalidFcmTokenException('FCM token is invalid: ' . $errorMessage);
            }

            throw new RuntimeException('FCM send failed: ' . $response->body());
        }

        return $response->json() ?? [];
    }

    private function accessToken(): string
    {
        return Cache::remember('firebase_access_token', now()->addMinutes(50), function () {
            $credentials = $this->credentials();
            $now = time();

            $assertion = $this->base64UrlEncode(json_encode([
                'alg' => 'RS256',
                'typ' => 'JWT',
            ])) . '.' . $this->base64UrlEncode(json_encode([
                'iss' => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => $credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]));

            openssl_sign($assertion, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256);
            $jwt = $assertion . '.' . $this->base64UrlEncode($signature);

            $response = Http::asForm()->post($credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if ($response->failed() || !$response->json('access_token')) {
                throw new RuntimeException('Unable to fetch Firebase access token: ' . $response->body());
            }

            return $response->json('access_token');
        });
    }

    private function credentials(): array
    {
        $path = config('services.firebase.credentials');

        if (!$path) {
            throw new RuntimeException('FIREBASE_CREDENTIALS is not configured.');
        }

        $fullPath = str_starts_with($path, DIRECTORY_SEPARATOR)
            ? $path
            : base_path($path);

        if (!is_file($fullPath)) {
            throw new RuntimeException("Firebase credentials file not found: {$path}");
        }

        $credentials = json_decode(file_get_contents($fullPath), true);

        if (!is_array($credentials) || empty($credentials['client_email']) || empty($credentials['private_key'])) {
            throw new RuntimeException('Firebase credentials file is invalid.');
        }

        return $credentials;
    }

    private function projectId(): string
    {
        $configured = config('services.firebase.project_id');

        if ($configured) {
            return $configured;
        }

        $credentials = $this->credentials();

        if (empty($credentials['project_id'])) {
            throw new RuntimeException('Firebase project id is not configured.');
        }

        return $credentials['project_id'];
    }

    private function stringifyData(array $data): array
    {
        return collect($data)
            ->map(fn ($value) => is_scalar($value) || $value === null ? (string) $value : json_encode($value))
            ->all();
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
