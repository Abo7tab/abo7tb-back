<?php

namespace App\Application\Http\Middleware;

use App\Domain\Device\Repositories\DeviceRepositoryInterface;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureDeviceOwnership
 *
 * Applied to every route that contains {uuid}.
 * 1. Finds the device by UUID.
 * 2. Verifies it belongs to the authenticated user.
 * 3. Injects the device into $request->attributes for downstream controllers.
 * 4. Logs unauthorized access attempts to access_audit_log.
 *
 * Controllers can retrieve the pre-loaded device via:
 *   $device = $request->attributes->get('device');
 *   or: $request->get('device');
 */
class EnsureDeviceOwnership
{
    public function __construct(
        protected DeviceRepositoryInterface $deviceRepo
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $uuid = $request->route('uuid');

        if (!$uuid) {
            return response()->json([
                'success' => false,
                'message' => 'Device UUID required',
                'error'   => 'DEVICE_UUID_REQUIRED',
            ], 400);
        }

        $device = $this->deviceRepo->findByUuid($uuid);

        // Device not found
        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'الجهاز غير موجود.',
                'error'   => 'DEVICE_NOT_FOUND',
            ], 404);
        }

        $user = $request->user();

        // Authenticated user does not own this device
        if ($user && (int) $device->user_id !== (int) $user->id) {

            // 1. Notify the real device owner via in-app notification
            try {
                \App\Domain\Notification\Models\Notification::create([
                    'user_id'    => $device->user_id,
                    'device_id'  => $device->id,
                    'title'      => '🚨 محاولة وصول مشبوهة',
                    'message'    => 'محاولة وصول غير مصرح بها على جهازك من حساب آخر.',
                    'type'       => 'security_alert',
                    'priority'   => 'urgent',
                    'is_read'    => false,
                    'created_at' => now(),
                ]);
            } catch (\Throwable $e) {
                Log::warning('Could not create ownership-violation notification: ' . $e->getMessage());
            }

            // 2. Write to audit log
            $this->writeAuditLog($device->id, $user->id, $request);

            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بالوصول لهذا الجهاز.',
                'error'   => 'UNAUTHORIZED_DEVICE',
            ], 403);
        }

        // Inject device into request attributes so controllers can use it
        // without an additional DB query. Accessible via:
        //   $request->attributes->get('device')  — always works
        //   $request->get('device')               — works after merge below
        $request->attributes->set('device', $device);
        $request->attributes->set('resolved_device', $device); // backward compat

        return $next($request);
    }

    private function writeAuditLog(int $deviceId, int $attackerId, Request $request): void
    {
        try {
            \DB::table('access_audit_log')->insert([
                'device_id'   => $deviceId,
                'user_id'     => $attackerId,
                'action_type' => 'unauthorized_access_attempt',
                'entity_type' => 'device',
                'entity_id'   => $deviceId,
                'details'     => json_encode([
                    'route'      => $request->path(),
                    'method'     => $request->method(),
                    'ip'         => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]),
                'ip_address'  => $request->ip(),
                'user_agent'  => $request->userAgent(),
                'created_at'  => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Audit log write failed: ' . $e->getMessage());
        }
    }
}
