<?php

namespace App\Application\Http\Middleware;

use App\Domain\Device\Repositories\DeviceRepositoryInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyConsent
{
    public function __construct(
        protected DeviceRepositoryInterface $deviceRepo
    ) {}

    /**
     * usage: ->middleware('verify.consent:camera')
     */
    public function handle(Request $request, Closure $next, string $permission = 'monitoring'): Response
    {
        $uuid = $request->route('uuid');

        if (!$uuid) {
            return $next($request);
        }

        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device || !$device->hasActiveConsent()) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم قبول موافقة الطفل بعد',
                'error'   => 'CONSENT_REQUIRED',
            ], 403);
        }

        $consent = $device->consent;

        if ($consent && !$consent->allows($permission)) {
            return response()->json([
                'success' => false,
                'message' => "الطفل لم يوافق على صلاحية: {$permission}",
                'error'   => 'PERMISSION_NOT_GRANTED',
            ], 403);
        }

        return $next($request);
    }
}
