<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_get_pending_commands');

        DB::unprepared(<<<'SQL'
CREATE PROCEDURE sp_get_pending_commands(IN p_device_id BIGINT)
BEGIN
    DECLARE v_picked_at TIMESTAMP;

    SET v_picked_at = NOW();

    START TRANSACTION;

    UPDATE remote_commands
    SET status = 'sent',
        sent_at = v_picked_at,
        delivery_method = 'polling'
    WHERE device_id = p_device_id
      AND status = 'pending'
      AND (expires_at IS NULL OR expires_at > NOW())
    ORDER BY
        CASE priority
            WHEN 'urgent' THEN 1
            WHEN 'high' THEN 2
            WHEN 'normal' THEN 3
            WHEN 'low' THEN 4
            ELSE 5
        END,
        created_at ASC
    LIMIT 50;

    SELECT id, uuid, command_category, command_type,
           command_data, priority, created_at, expires_at
    FROM remote_commands
    WHERE device_id = p_device_id
      AND status = 'sent'
      AND delivery_method = 'polling'
      AND sent_at = v_picked_at
    ORDER BY
        CASE priority
            WHEN 'urgent' THEN 1
            WHEN 'high' THEN 2
            WHEN 'normal' THEN 3
            WHEN 'low' THEN 4
            ELSE 5
        END,
        created_at ASC;

    COMMIT;
END
SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_get_pending_commands');
    }
};
