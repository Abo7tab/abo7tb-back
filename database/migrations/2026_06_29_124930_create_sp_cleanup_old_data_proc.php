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
        DB::unprepared("CREATE DEFINER=`abo7tb`@`%` PROCEDURE `sp_cleanup_old_data`(IN `p_days` INT)
BEGIN
    DECLARE retention_date TIMESTAMP;
    SET retention_date = DATE_SUB(NOW(), INTERVAL p_days DAY);

    DELETE FROM device_locations  WHERE recorded_at < retention_date;
    DELETE FROM browsing_history  WHERE visited_at  < retention_date;
    DELETE FROM call_logs         WHERE called_at   < retention_date;
    DELETE FROM sms_logs          WHERE sent_at     < retention_date;
    DELETE FROM access_audit_log  WHERE created_at  < retention_date;
    DELETE FROM notifications     
        WHERE is_read = TRUE AND created_at < retention_date;
    DELETE FROM screenshots       WHERE captured_at < retention_date;

    SELECT ROW_COUNT() AS deleted_rows;
END");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP PROCEDURE IF EXISTS sp_cleanup_old_data");
    }
};
