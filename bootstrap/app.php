<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api:      __DIR__ . '/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
        health:   '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        // Sanctum stateful auth
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        // Custom Middleware Aliases
        $middleware->alias([
            'device.ownership' => \App\Application\Http\Middleware\EnsureDeviceOwnership::class,
            'device.status'    => \App\Application\Http\Middleware\CheckDeviceStatus::class,
            'api.version'      => \App\Application\Http\Middleware\ValidateApiVersion::class,
            'verify.consent'   => \App\Application\Http\Middleware\VerifyConsent::class,
            'encrypt.response' => \App\Application\Http\Middleware\EncryptResponse::class,
            'api.log'          => \App\Application\Http\Middleware\LogApiRequest::class,
        ]);

        // إضافة LogApiRequest لمجموعة الـ api (يُطبَّق على كل الطلبات)
        $middleware->appendToGroup('api', [
            \App\Application\Http\Middleware\LogApiRequest::class,
        ]);

        // Rate Limiting: 60 طلب/دقيقة للـ API عموماً
        $middleware->throttleApi('60,1');
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // 401 Unauthenticated
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح - يرجى تسجيل الدخول',
                'error'   => 'UNAUTHENTICATED',
            ], 401);
        });

        // 422 Validation
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صالحة',
                'errors'  => $e->errors(),
            ], 422);
        });

        // 404 Not Found
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return response()->json([
                'success' => false,
                'message' => 'المورد غير موجود',
                'error'   => 'NOT_FOUND',
            ], 404);
        });

        // Business Logic Exceptions
        $exceptions->render(function (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error'   => 'BUSINESS_LOGIC_ERROR',
            ], 400);
        });

        // 500 Internal — يخفي التفاصيل في production
        $exceptions->render(function (\Throwable $e) {
            if (app()->environment('production')) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ داخلي',
                    'error'   => 'INTERNAL_ERROR',
                ], 500);
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ], 500);
        });

    })->create();
