<?php

namespace App\Application\Http\Controllers\Api\V1\Device;

use App\Application\Http\Controllers\Controller;
use App\Domain\Device\Repositories\DeviceRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use App\Domain\User\Models\User;
use App\Domain\Shared\Notifications\SuspiciousActivityNotification;

/**
 * POST /api/v1/devices/{uuid}/verify-parent
 *
 * Verifies parent identity before allowing sensitive actions on the child device.
 * - Rate limit: 5 attempts per 15 minutes per device
 * - After 5 failed attempts: notifies the parent & logs the event
 * - Issues a short-lived verification token (5 min) on success
 * - Never stores or logs the plain-text password
 */
class ParentVerificationController extends Controller
{
    private const MAX_ATTEMPTS    = 5;
    private const DECAY_MINUTES   = 15;
    private const TOKEN_TTL_SEC   = 300; // 5 minutes

    public function __construct(
        protected DeviceRepositoryInterface $deviceRepo
    ) {}

    public function verify(Request $request, string $uuid): JsonResponse
    {
        // ── 1. Validate input ─────────────────────────────────────────────
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string|min:1',
        ]);

        // ── 2. Find the device ────────────────────────────────────────────
        $device = $this->deviceRepo->findByUuid($uuid);
        if (!$device) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        // ── 3. Rate-limit check ───────────────────────────────────────────
        $cacheKey  = "verify_parent_attempts:{$device->id}";
        $attempts  = (int) Cache::get($cacheKey, 0);

        if ($attempts >= self::MAX_ATTEMPTS) {
            $ttl = Cache::getStore()->get("laravel_cache_{$cacheKey}:ttl") ?? (self::DECAY_MINUTES * 60);
            return response()->json([
                'success'           => false,
                'verified'          => false,
                'message'           => 'Too many failed attempts. Try again in ' . self::DECAY_MINUTES . ' minutes.',
                'retry_after_sec'   => $ttl,
            ], 429);
        }

        // ── 4. Verify credentials ─────────────────────────────────────────
        $parent = User::where('email', $request->email)->first();

        $credentialsValid = $parent
            && Hash::check($request->password, $parent->getAuthPassword())
            && $device->user_id === $parent->id;

        // ── 5. Increment attempt counter ──────────────────────────────────
        if (!$credentialsValid) {
            $newAttempts = $attempts + 1;
            Cache::put($cacheKey, $newAttempts, now()->addMinutes(self::DECAY_MINUTES));

            $remaining = self::MAX_ATTEMPTS - $newAttempts;

            // ── 6. Audit log ──────────────────────────────────────────────
            $this->logAudit($device->id, $parent?->id ?? null, 'verify_parent_failed', $request);

            // ── 7. Notify parent after max attempts ───────────────────────
            if ($remaining <= 0) {
                $owner = User::find($device->user_id);
                if ($owner) {
                    try {
                        $owner->notify(new SuspiciousActivityNotification(
                            device: $device,
                            event:  'too_many_verify_attempts',
                            details: [
                                'ip'         => $request->ip(),
                                'user_agent' => $request->userAgent(),
                            ]
                        ));
                    } catch (\Throwable $e) {
                        Log::warning('Could not send suspicious-activity notification: ' . $e->getMessage());
                    }
                }
            }

            if ($remaining <= 0) {
                return response()->json([
                    'success'          => false,
                    'verified'         => false,
                    'message'          => 'Too many failed attempts. Try again in ' . self::DECAY_MINUTES . ' minutes.',
                    'retry_after_sec'  => self::DECAY_MINUTES * 60,
                ], 429);
            }

            return response()->json([
                'success'           => false,
                'verified'          => false,
                'message'           => 'Invalid email or password.',
                'attempts_remaining' => $remaining,
            ], 401);
        }

        // ── 8. Success — clear counter, issue short-lived token ───────────
        Cache::forget($cacheKey);

        $verificationToken = Str::random(64);
        $tokenCacheKey     = "parent_verified:{$device->id}:{$verificationToken}";
        Cache::put($tokenCacheKey, true, now()->addSeconds(self::TOKEN_TTL_SEC));

        // ── 9. Audit log (success) ────────────────────────────────────────
        $this->logAudit($device->id, $parent->id, 'verify_parent_success', $request);

        return response()->json([
            'success'            => true,
            'verified'           => true,
            'verification_token' => $verificationToken,
            'expires_in'         => self::TOKEN_TTL_SEC,
        ]);
    }

    // ── Helper: write to access_audit_log ────────────────────────────────
    private function logAudit(int $deviceId, ?int $userId, string $action, Request $request): void
    {
        try {
            \DB::table('access_audit_log')->insert([
                'device_id'   => $deviceId,
                'user_id'     => $userId,
                'action_type' => $action,
                'entity_type' => 'device',
                'entity_id'   => $deviceId,
                'details'     => json_encode([
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
