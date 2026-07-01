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
        DB::unprepared("CREATE DEFINER=`abo7tb`@`%` PROCEDURE `sp_get_pending_commands`(IN `p_device_id` BIGINT)
BEGIN
    SELECT id, uuid, command_category, command_type,
           command_data, priority, created_at
    FROM remote_commands
    WHERE device_id = p_device_id
      AND status    = 'pending'
      AND (expires_at IS NULL OR expires_at > NOW())
    ORDER BY
        CASE priority
            WHEN 'urgent' THEN 1
            WHEN 'high'   THEN 2
            WHEN 'normal' THEN 3
            WHEN 'low'    THEN 4
        END,
        created_at ASC;
END");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP PROCEDURE IF EXISTS sp_get_pending_commands");
    }
};
