<?php

namespace App\Application\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Domain\Shared\Enums\DeviceStatus;
use Symfony\Component\HttpFoundation\Response;

class CheckDeviceStatus
{
    /**
     * Handle an incoming request.
     * Rejects requests if the device is suspended or inactive.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $device = $request->route('device');

        if ($device) {
            $status = DeviceStatus::from($device->status);

            if ($status === DeviceStatus::SUSPENDED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Device is suspended.',
                ], Response::HTTP_FORBIDDEN);
            }
        }

        return $next($request);
    }
}
