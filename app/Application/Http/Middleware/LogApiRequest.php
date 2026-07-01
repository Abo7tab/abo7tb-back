<?php

namespace App\Application\Http\Middleware;

use App\Domain\Device\Models\Device;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequest
{
    private const SENSITIVE_ACTIONS = [
        'lock', 'unlock', 'delete', 'wipe',
        'camera', 'audio', 'screenshot',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $response  = $next($request);
        $duration  = round((microtime(true) - $startTime) * 1000, 2);

        if ($duration > 500) {
            Log::warning('Slow API Request', [
                'method'   => $request->method(),
                'url'      => $request->fullUrl(),
                'duration' => $duration . 'ms',
                'status'   => $response->getStatusCode(),
                'user_id'  => $request->user()?->id,
                'ip'       => $request->ip(),
            ]);
        }

        $routeName   = $request->route()?->getName() ?? '';
        $isSensitive = collect(self::SENSITIVE_ACTIONS)
            ->contains(fn ($a) => str_contains($routeName, $a));

        if ($isSensitive) {
            $userId   = $request->user()?->id;
            $deviceId = null;

            if ($uuid = $request->route('uuid')) {
                $deviceId = Device::where('uuid', $uuid)->value('id');
            }

            if ($userId) {
                try {
                    DB::table('access_audit_log')->insert([
                        'device_id'   => $deviceId,
                        'user_id'     => $userId,
                        'action_type' => $routeName,
                        'entity_type' => 'api_request',
                        'details'     => json_encode([
                            'method' => $request->method(),
                            'url'    => $request->path(),
                            'status' => $response->getStatusCode(),
                        ]),
                        'ip_address'  => $request->ip(),
                        'user_agent'  => $request->userAgent(),
                        'created_at'  => now(),
                    ]);
                } catch (\Exception) {
                    //
                }
            }
        }

        $response->headers->set('X-Response-Time', $duration . 'ms');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        return $response;
    }
}
