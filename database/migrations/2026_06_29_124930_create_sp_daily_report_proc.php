<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::unprepared("CREATE DEFINER=`abo7tb`@`%` PROCEDURE `sp_daily_report`(
    IN `p_device_id` BIGINT, 
    IN `p_date` DATE
)
BEGIN
    -- وقت الشاشة
    SELECT 'screen_time' AS metric,
           COALESCE(total_sec, 0)    AS value_sec,
           COALESCE(unlock_count, 0) AS unlocks
    FROM screen_time
    WHERE device_id = p_device_id 
      AND date = p_date;

    -- أكثر التطبيقات استخداماً
    SELECT package_name, app_name,
           foreground_sec, launch_count
    FROM app_usage
    WHERE device_id  = p_device_id 
      AND usage_date = p_date
    ORDER BY foreground_sec DESC
    LIMIT 10;

    -- إحصائيات المكالمات
    SELECT call_type, 
           COUNT(*)          AS total,
           SUM(duration_sec) AS total_duration
    FROM call_logs
    WHERE device_id = p_device_id 
      AND DATE(called_at) = p_date
    GROUP BY call_type;

    -- عدد المواقع المزارة
    SELECT COUNT(*) AS websites_visited
    FROM browsing_history
    WHERE device_id = p_device_id 
      AND DATE(visited_at) = p_date;
END");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP PROCEDURE IF EXISTS sp_daily_report");
    }
};
