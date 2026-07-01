<?php

use App\Application\Http\Controllers\Api\V1\Auth\AuthController;
use App\Application\Http\Controllers\Api\V1\Device\DeviceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes  (prefix: api/v1 — configured in bootstrap/app.php)
|--------------------------------------------------------------------------
*/

// ── Health check (public) ────────────────────────────────────────────────
Route::get('/health', fn () => response()->json([
    'status'  => 'ok',
    'version' => 'v1',
    'time'    => now()->toIso8601String(),
]));

// ── Auth routes (public) ─────────────────────────────────────────────────
Route::prefix('auth')->name('auth.')->middleware('throttle:100,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login',    [AuthController::class, 'login'])   ->name('login');
});

// ── Authenticated routes (Sanctum) ───────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Current authenticated user
    Route::get('/user', fn (Request $request) => response()->json([
        'success' => true,
        'data'    => $request->user(),
    ]));

    // ── Auth (protected) ─────────────────────────────────────────────────
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('/logout',  [AuthController::class, 'logout']) ->name('logout');
        Route::get('/me',       [AuthController::class, 'me'])     ->name('me');
        Route::post('/refresh', [AuthController::class, 'refresh'])->name('refresh');
    });

    // ── Devices (للأب) ───────────────────────────────────────────────────
    Route::prefix('devices')->name('devices.')->middleware('device.ownership')->group(function () {
        // List all parent's devices (ownership middleware handles index correctly)
        Route::get('/',          [DeviceController::class, 'index'])   ->name('index')->withoutMiddleware('device.ownership');
        // Register a new device
        Route::post('/register', [DeviceController::class, 'register'])->name('register')->withoutMiddleware('device.ownership');
        // Show device details
        Route::get('/{uuid}',    [DeviceController::class, 'show'])    ->name('show');
        // Send a command to the device
        Route::post('/{uuid}/command', [DeviceController::class, 'sendCommand'])->name('command');
        // Get device location history
        Route::get('/{uuid}/location/history', [DeviceController::class, 'locationHistory'])->name('location.history');
        // Safe zones (mock)
        Route::get('/{uuid}/safe-zones', function() { return response()->json(['success' => true, 'data' => []]); });
        // Verify parent identity before sensitive actions (rate-limited: 5 attempts / 15 min)
        Route::post('/{uuid}/verify-parent', [\App\Application\Http\Controllers\Api\V1\Device\ParentVerificationController::class, 'verify'])->name('verify-parent')->middleware('throttle:5,15');
        // Update device FCM token
        Route::post('/{uuid}/push-token', [DeviceController::class, 'updateFcmToken'])->name('push-token')->middleware('throttle:30,1');
        // Delete a device
        Route::delete('/{uuid}', [DeviceController::class, 'destroy']) ->name('destroy');
    });

    // ── App Blocking (Parent Dashboard) ──────────────────────────────────
    Route::prefix('devices')->name('devices.')->middleware('device.ownership')->group(function () {
        Route::get('/{uuid}/apps',                         [\App\Application\Http\Controllers\Api\V1\App\AppBlockingController::class, 'index'])      ->name('apps.index');
        Route::post('/{uuid}/apps/block',                  [\App\Application\Http\Controllers\Api\V1\App\AppBlockingController::class, 'block'])      ->name('apps.block');
        Route::post('/{uuid}/apps/unblock/{packageName}',  [\App\Application\Http\Controllers\Api\V1\App\AppBlockingController::class, 'unblock'])    ->name('apps.unblock');
        Route::get('/{uuid}/apps/usage/stats',             [\App\Application\Http\Controllers\Api\V1\App\AppBlockingController::class, 'usageStats']) ->name('apps.usage.stats');
        Route::get('/{uuid}/apps/blocked',                 [\App\Application\Http\Controllers\Api\V1\App\AppBlockingController::class, 'blockedList'])->name('apps.blocked');
    });

    // ── Web Filtering — Browsing (Parent Dashboard) ───────────────────────
    Route::prefix('devices')->middleware('device.ownership')->group(function () {
        Route::get('/{uuid}/browsing',          [\App\Application\Http\Controllers\Api\V1\Web\BrowsingController::class, 'index'])->name('devices.browsing.index');
        Route::get('/{uuid}/browsing/stats',    [\App\Application\Http\Controllers\Api\V1\Web\BrowsingController::class, 'stats'])->name('devices.browsing.stats');
    });

    // ── Web Filtering — Website Blocking (Parent Dashboard) ──────────────
    Route::prefix('devices')->middleware('device.ownership')->group(function () {
        Route::get('/{uuid}/blocked-websites',                        [\App\Application\Http\Controllers\Api\V1\Web\FilterController::class, 'index'])          ->name('devices.websites.index');
        Route::post('/{uuid}/blocked-websites',                       [\App\Application\Http\Controllers\Api\V1\Web\FilterController::class, 'block'])          ->name('devices.websites.block');
        Route::delete('/{uuid}/blocked-websites/{domain}',            [\App\Application\Http\Controllers\Api\V1\Web\FilterController::class, 'unblock'])        ->name('devices.websites.unblock');
        Route::post('/{uuid}/blocked-websites/category',              [\App\Application\Http\Controllers\Api\V1\Web\FilterController::class, 'blockCategory'])  ->name('devices.websites.block-category');
        Route::delete('/{uuid}/blocked-websites/category/{category}', [\App\Application\Http\Controllers\Api\V1\Web\FilterController::class, 'unblockCategory'])->name('devices.websites.unblock-category');
    });

    // ── Website Categories (global) ───────────────────────────────────────
    Route::get('/website-categories', [\App\Application\Http\Controllers\Api\V1\Web\FilterController::class, 'categories'])->name('websites.categories');

    // ══════════════════════════════════════════════════════════════════════
    // Phase 6: Communication Domain
    // ══════════════════════════════════════════════════════════════════════

    // ── Contacts ──────────────────────────────────────────────────────────
    Route::prefix('devices')->middleware('device.ownership')->group(function () {
        Route::get('/{uuid}/contacts',       [\App\Application\Http\Controllers\Api\V1\Communication\ContactController::class, 'index'])->name('devices.contacts.index');
    });

    // ── Call Logs ─────────────────────────────────────────────────────────
    Route::prefix('devices')->middleware('device.ownership')->group(function () {
        Route::get('/{uuid}/calls',        [\App\Application\Http\Controllers\Api\V1\Communication\CallController::class, 'index'])->name('devices.calls.index');
        Route::get('/{uuid}/calls/stats',  [\App\Application\Http\Controllers\Api\V1\Communication\CallController::class, 'stats'])->name('devices.calls.stats');
    });

    // ── SMS Logs ──────────────────────────────────────────────────────────
    Route::prefix('devices')->middleware('device.ownership')->group(function () {
        Route::get('/{uuid}/sms',                               [\App\Application\Http\Controllers\Api\V1\Communication\SmsController::class, 'index'])         ->name('devices.sms.index');
        Route::get('/{uuid}/sms/stats',                         [\App\Application\Http\Controllers\Api\V1\Communication\SmsController::class, 'stats'])         ->name('devices.sms.stats');
        Route::get('/{uuid}/sms/conversation/{number}',         [\App\Application\Http\Controllers\Api\V1\Communication\SmsController::class, 'conversation'])  ->name('devices.sms.conversation');
        Route::get('/{uuid}/blocked-numbers',                   [\App\Application\Http\Controllers\Api\V1\Communication\SmsController::class, 'blockedNumbers'])->name('devices.numbers.index');
        Route::post('/{uuid}/blocked-numbers',                  [\App\Application\Http\Controllers\Api\V1\Communication\SmsController::class, 'blockNumber'])   ->name('devices.numbers.block');
        Route::delete('/{uuid}/blocked-numbers/{number}',       [\App\Application\Http\Controllers\Api\V1\Communication\SmsController::class, 'unblockNumber'])->name('devices.numbers.unblock');
    });

    // ══════════════════════════════════════════════════════════════════════
    // Phase 7: Media Domain
    // ══════════════════════════════════════════════════════════════════════

    // ── Camera (Parent ← Server) ────────────────────────
    Route::prefix('devices')->middleware('device.ownership')->group(function () {
        Route::post('/{uuid}/camera/photo',   [\App\Application\Http\Controllers\Api\V1\Media\CameraController::class, 'takePhoto'])   ->name('devices.camera.photo');
        Route::post('/{uuid}/camera/video',   [\App\Application\Http\Controllers\Api\V1\Media\CameraController::class, 'recordVideo']) ->name('devices.camera.video');
        Route::get('/{uuid}/camera/photos',   [\App\Application\Http\Controllers\Api\V1\Media\CameraController::class, 'photos'])      ->name('devices.camera.photos');
        Route::get('/{uuid}/camera/videos',   [\App\Application\Http\Controllers\Api\V1\Media\CameraController::class, 'videos'])      ->name('devices.camera.videos');
    });

    // Camera single-resource routes (by capture uuid)
    Route::prefix('camera')->group(function () {
        Route::get('/{uuid}/stream',     [\App\Application\Http\Controllers\Api\V1\Media\CameraController::class, 'stream'])     ->name('camera.stream')->withoutMiddleware('auth:sanctum');
        Route::patch('/{uuid}/view',     [\App\Application\Http\Controllers\Api\V1\Media\CameraController::class, 'markViewed'])->name('camera.view');
        Route::delete('/{uuid}',         [\App\Application\Http\Controllers\Api\V1\Media\CameraController::class, 'destroy'])   ->name('camera.destroy');
    });

    // ── Audio (Parent ← Server) ─────────────────────────
    Route::prefix('devices')->middleware('device.ownership')->group(function () {
        Route::post('/{uuid}/audio/start',   [\App\Application\Http\Controllers\Api\V1\Media\AudioController::class, 'start'])  ->name('devices.audio.start');
        Route::get('/{uuid}/audio',          [\App\Application\Http\Controllers\Api\V1\Media\AudioController::class, 'index'])  ->name('devices.audio.index');
    });

    // Audio single-resource routes (by recording uuid)
    Route::prefix('audio')->group(function () {
        Route::get('/{uuid}/stream',     [\App\Application\Http\Controllers\Api\V1\Media\AudioController::class, 'stream'])     ->name('audio.stream')->withoutMiddleware('auth:sanctum');
        Route::patch('/{uuid}/view',     [\App\Application\Http\Controllers\Api\V1\Media\AudioController::class, 'markViewed'])->name('audio.view');
        Route::delete('/{uuid}',         [\App\Application\Http\Controllers\Api\V1\Media\AudioController::class, 'destroy'])   ->name('audio.destroy');
    });

    // ── Gallery (Parent ← Server) ───────────────────────
    Route::prefix('devices')->middleware('device.ownership')->group(function () {
        Route::get('/{uuid}/gallery',         [\App\Application\Http\Controllers\Api\V1\Media\GalleryController::class, 'index'])        ->name('devices.gallery.index');
        Route::get('/{uuid}/gallery/stats',   [\App\Application\Http\Controllers\Api\V1\Media\GalleryController::class, 'stats'])        ->name('devices.gallery.stats');
    });

    // Gallery single-resource routes (by item uuid)
    Route::prefix('gallery')->group(function () {
        Route::patch('/{uuid}/flag',     [\App\Application\Http\Controllers\Api\V1\Media\GalleryController::class, 'flag'])       ->name('gallery.flag');
        Route::patch('/{uuid}/view',     [\App\Application\Http\Controllers\Api\V1\Media\GalleryController::class, 'markViewed'])->name('gallery.view');
        Route::delete('/{uuid}',         [\App\Application\Http\Controllers\Api\V1\Media\GalleryController::class, 'destroy'])   ->name('gallery.destroy');
    });

    // ── Screenshots (Parent ← Server) ───────────────────
    Route::prefix('devices')->middleware('device.ownership')->group(function () {
        Route::post('/{uuid}/screenshot',        [\App\Application\Http\Controllers\Api\V1\Media\ScreenshotController::class, 'capture']) ->name('devices.screenshot.capture');
        Route::get('/{uuid}/screenshots',        [\App\Application\Http\Controllers\Api\V1\Media\ScreenshotController::class, 'index'])   ->name('devices.screenshot.index');
    });

    // Screenshot single-resource routes (by screenshot uuid)
    Route::prefix('screenshot')->group(function () {
        Route::get('/{uuid}/stream',     [\App\Application\Http\Controllers\Api\V1\Media\ScreenshotController::class, 'stream'])     ->name('screenshot.stream')->withoutMiddleware('auth:sanctum');
        Route::patch('/{uuid}/view',     [\App\Application\Http\Controllers\Api\V1\Media\ScreenshotController::class, 'markViewed'])->name('screenshot.view');
        Route::delete('/{uuid}',         [\App\Application\Http\Controllers\Api\V1\Media\ScreenshotController::class, 'destroy'])   ->name('screenshot.destroy');
    });

    // ══════════════════════════════════════════════════════════════════════
    // Phase 8: ScreenControl Domain
    // ══════════════════════════════════════════════════════════════════════
    Route::prefix('devices')->middleware('device.ownership')->group(function () {
        Route::post('/{uuid}/screen/lock',      [\App\Application\Http\Controllers\Api\V1\ScreenControl\ScreenLockController::class, 'lock'])          ->name('devices.screen.lock');
        Route::post('/{uuid}/screen/unlock',    [\App\Application\Http\Controllers\Api\V1\ScreenControl\ScreenLockController::class, 'unlock'])        ->name('devices.screen.unlock');
        Route::get('/{uuid}/screen/status',     [\App\Application\Http\Controllers\Api\V1\ScreenControl\ScreenLockController::class, 'status'])        ->name('devices.screen.status');
        Route::get('/{uuid}/screen/history',    [\App\Application\Http\Controllers\Api\V1\ScreenControl\ScreenLockController::class, 'history'])       ->name('devices.screen.history');
        Route::post('/{uuid}/screen/bedtime',   [\App\Application\Http\Controllers\Api\V1\ScreenControl\ScreenLockController::class, 'setBedtime'])    ->name('devices.screen.bedtime.set');
        Route::delete('/{uuid}/screen/bedtime', [\App\Application\Http\Controllers\Api\V1\ScreenControl\ScreenLockController::class, 'cancelBedtime'])->name('devices.screen.bedtime.cancel');
    });

    // ══════════════════════════════════════════════════════════════════════
    // Phase 9: TimeManagement Domain
    // ══════════════════════════════════════════════════════════════════════
    Route::prefix('devices')->middleware('device.ownership')->group(function () {
        Route::get('/{uuid}/time-limits',              [\App\Application\Http\Controllers\Api\V1\TimeManagement\TimeLimitController::class, 'index'])   ->name('devices.timelimits.index');
        Route::post('/{uuid}/time-limits',             [\App\Application\Http\Controllers\Api\V1\TimeManagement\TimeLimitController::class, 'store'])   ->name('devices.timelimits.store');
        Route::get('/{uuid}/time-limits/check',        [\App\Application\Http\Controllers\Api\V1\TimeManagement\TimeLimitController::class, 'check'])   ->name('devices.timelimits.check');
        Route::get('/{uuid}/time-limits/app/{package}',[\App\Application\Http\Controllers\Api\V1\TimeManagement\TimeLimitController::class, 'checkApp'])->name('devices.timelimits.checkApp');
    });

    Route::prefix('time-limits')->group(function () {
        Route::put('/{id}',    [\App\Application\Http\Controllers\Api\V1\TimeManagement\TimeLimitController::class, 'update']) ->name('timelimits.update');
        Route::delete('/{id}', [\App\Application\Http\Controllers\Api\V1\TimeManagement\TimeLimitController::class, 'destroy'])->name('timelimits.destroy');
    });

    // ══════════════════════════════════════════════════════════════════════
    // Phase 10: Notification Domain
    // ══════════════════════════════════════════════════════════════════════
    Route::prefix('notifications')->group(function () {
        Route::get('/',              [\App\Application\Http\Controllers\Api\V1\Notification\NotificationController::class, 'index'])      ->name('notifications.index');
        Route::get('/unread-count',  [\App\Application\Http\Controllers\Api\V1\Notification\NotificationController::class, 'unreadCount'])->name('notifications.unread');
        Route::patch('/read-all',    [\App\Application\Http\Controllers\Api\V1\Notification\NotificationController::class, 'markAllRead'])->name('notifications.readAll');
        Route::delete('/clear-old',  [\App\Application\Http\Controllers\Api\V1\Notification\NotificationController::class, 'clearOld'])  ->name('notifications.clearOld');
        Route::patch('/{id}/read',   [\App\Application\Http\Controllers\Api\V1\Notification\NotificationController::class, 'markRead'])  ->name('notifications.read');
        Route::delete('/{id}',       [\App\Application\Http\Controllers\Api\V1\Notification\NotificationController::class, 'destroy'])   ->name('notifications.destroy');
    });

    // ══════════════════════════════════════════════════════════════════════
    // Phase 11: Dashboard & Analytics
    // ══════════════════════════════════════════════════════════════════════
    Route::prefix('dashboard')->group(function () {
        Route::get('/summary',       [\App\Application\Http\Controllers\Api\V1\Dashboard\DashboardController::class, 'summary'])      ->name('dashboard.summary');
        Route::get('/screen-time',   [\App\Application\Http\Controllers\Api\V1\Dashboard\DashboardController::class, 'screenTime'])   ->name('dashboard.screen-time');
        Route::get('/top-apps',      [\App\Application\Http\Controllers\Api\V1\Dashboard\DashboardController::class, 'topApps'])      ->name('dashboard.top-apps');
        Route::get('/weekly-report', [\App\Application\Http\Controllers\Api\V1\Dashboard\DashboardController::class, 'weeklyReport'])->name('dashboard.weekly-report');
        Route::delete('/cache',      [\App\Application\Http\Controllers\Api\V1\Dashboard\DashboardController::class, 'clearCache'])   ->name('dashboard.cache.clear');
    });
});

// ── Device endpoints (للجهاز / الطفل - بدون Sanctum كامل) ──────────
Route::prefix('devices')->middleware(['auth:sanctum', 'device.ownership'])->group(function () {
    // Heartbeat status update from device
    Route::post('/{uuid}/heartbeat', [DeviceController::class, 'heartbeat'])->name('devices.heartbeat')->middleware('throttle:30,1');
    // Accept consent from the child's device
    Route::post('/{uuid}/consent/accept', [DeviceController::class, 'acceptConsent'])->name('devices.consent.accept');
    // Update device location
    Route::post('/{uuid}/location',        [DeviceController::class, 'updateLocation'])->name('devices.location.update');
    // Get pending commands
    Route::get('/{uuid}/commands/pending', [DeviceController::class, 'pendingCommands'])->name('devices.commands.pending')->middleware('throttle:120,1');

    // ── App Monitoring (Child → Server) ──────────────────────────────
    Route::post('/{uuid}/apps/sync',  [\App\Application\Http\Controllers\Api\V1\App\AppSyncController::class, 'sync']) ->name('devices.apps.sync');
    Route::post('/{uuid}/apps/usage', [\App\Application\Http\Controllers\Api\V1\App\AppSyncController::class, 'usage'])->name('devices.apps.usage');
});

// ── Web Filtering — Browsing (Device ← → Server) ─────────────────────
Route::prefix('devices')->middleware(['auth:sanctum', 'device.ownership'])->group(function () {
    Route::post('/{uuid}/browsing',         [\App\Application\Http\Controllers\Api\V1\Web\BrowsingController::class, 'record'])->name('devices.browsing.record');
    Route::post('/{uuid}/browsing/batch',   [\App\Application\Http\Controllers\Api\V1\Web\BrowsingController::class, 'batch']) ->name('devices.browsing.batch');
    Route::post('/{uuid}/browsing/check',   [\App\Application\Http\Controllers\Api\V1\Web\BrowsingController::class, 'check']) ->name('devices.browsing.check');
});

// ── Communication Sync (Device → Server) ─────────────────────────────
Route::prefix('devices')->middleware(['auth:sanctum', 'device.ownership'])->group(function () {
    Route::post('/{uuid}/contacts/sync', [\App\Application\Http\Controllers\Api\V1\Communication\ContactController::class, 'sync']) ->name('devices.contacts.sync')->middleware('throttle:10,60');
    Route::post('/{uuid}/calls/sync',  [\App\Application\Http\Controllers\Api\V1\Communication\CallController::class, 'sync']) ->name('devices.calls.sync')->middleware('throttle:30,60');
    Route::post('/{uuid}/sms/sync', [\App\Application\Http\Controllers\Api\V1\Communication\SmsController::class, 'sync']) ->name('devices.sms.sync')->middleware('throttle:30,60');
});

// ── Media Sync (Device → Server) ─────────────────────────────
Route::prefix('devices')->middleware(['auth:sanctum', 'device.ownership'])->group(function () {
    Route::post('/{uuid}/camera/upload',  [\App\Application\Http\Controllers\Api\V1\Media\CameraController::class, 'upload'])      ->name('devices.camera.upload')->middleware('throttle:30,1');
    Route::post('/{uuid}/audio/upload',  [\App\Application\Http\Controllers\Api\V1\Media\AudioController::class, 'upload']) ->name('devices.audio.upload')->middleware('throttle:20,1');
    Route::post('/{uuid}/gallery/upload', [\App\Application\Http\Controllers\Api\V1\Media\GalleryController::class, 'upload'])       ->name('devices.gallery.upload');
    Route::post('/{uuid}/gallery/sync',   [\App\Application\Http\Controllers\Api\V1\Media\GalleryController::class, 'syncMetadata']) ->name('devices.gallery.sync');
    Route::post('/{uuid}/screenshot/upload', [\App\Application\Http\Controllers\Api\V1\Media\ScreenshotController::class, 'upload'])  ->name('devices.screenshot.upload')->middleware('throttle:60,1');
});

// ── Commands (تحديث الحالة من الجهاز) ────────────────────────────────
Route::patch('/commands/{uuid}/status', [DeviceController::class, 'updateCommandStatus'])
    ->middleware(['auth:sanctum', 'throttle:120,1'])
    ->name('commands.status');
