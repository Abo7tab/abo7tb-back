<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

// كل دقيقة: فحص الأوامر المعلقة وإلغاء المنتهية صلاحيتها
Schedule::job(new \App\Jobs\ProcessPendingCommands)->everyMinute();

// كل 5 دقائق: فحص حالة اتصال الأجهزة
Schedule::job(new \App\Jobs\CheckDeviceStatus)->everyFiveMinutes();

// كل 15 دقيقة: تطبيق حدود وقت الشاشة
Schedule::job(new \App\Jobs\EnforceTimeLimits)->everyFifteenMinutes();

// يومياً 2:00 صباحاً: تنظيف البيانات القديمة
Schedule::job(new \App\Jobs\CleanupOldData)->dailyAt('02:00');

// يومياً 3:00 صباحاً: تنظيف البيانات القديمة باستخدام stored procedure
Schedule::call(function () {
    DB::statement('CALL sp_cleanup_old_data(?)', [90]);
})->dailyAt('03:00')->name('cleanup-old-data-procedure');

// كل ساعة: تنظيف الميديا المجدولة لو procedure موجودة على قاعدة البيانات
Schedule::call(function () {
    $exists = DB::table('information_schema.ROUTINES')
        ->where('ROUTINE_SCHEMA', DB::getDatabaseName())
        ->where('ROUTINE_TYPE', 'PROCEDURE')
        ->where('ROUTINE_NAME', 'sp_cleanup_scheduled_media')
        ->exists();

    if ($exists) {
        DB::statement('CALL sp_cleanup_scheduled_media()');
    }
})->hourly()->name('cleanup-scheduled-media');

// كل دقيقة: تحديث حالة الأجهزة التي لم ترسل heartbeat
Schedule::call(function () {
    DB::table('devices')
        ->where('last_seen_at', '<', now()->subMinutes(5))
        ->where('is_online', 1)
        ->update(['is_online' => 0]);
})->everyMinute()->name('update-offline-devices');

// كل 5 دقائق: إلغاء الأوامر المنتهية
Schedule::call(function () {
    DB::table('remote_commands')
        ->whereIn('status', ['pending', 'sent'])
        ->where('expires_at', '<', now())
        ->update(['status' => 'cancelled']);
})->everyFiveMinutes()->name('cancel-expired-commands');

// يومياً 21:00 مساءً: إرسال التقرير اليومي للآباء
Schedule::command('report:daily')->dailyAt('21:00');
