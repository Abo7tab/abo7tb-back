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
        DB::statement("CREATE VIEW `v_devices_overview` AS select `d`.`id` AS `id`,`d`.`uuid` AS `uuid`,`d`.`child_name` AS `child_name`,`d`.`child_age` AS `child_age`,`d`.`device_name` AS `device_name`,`d`.`device_model` AS `device_model`,`d`.`is_online` AS `is_online`,`d`.`is_locked_by_parent` AS `is_locked_by_parent`,`d`.`battery_level` AS `battery_level`,`d`.`is_charging` AS `is_charging`,`d`.`last_seen_at` AS `last_seen_at`,`d`.`last_location_lat` AS `last_location_lat`,`d`.`last_location_lng` AS `last_location_lng`,`u`.`name` AS `parent_name`,`u`.`email` AS `parent_email`,`cc`.`consent_status` AS `consent_status`,(select count(0) from `abo7tb_parental_control`.`installed_apps` `ia` where `ia`.`device_id` = `d`.`id`) AS `total_apps`,(select count(0) from `abo7tb_parental_control`.`blocked_apps` `ba` where `ba`.`device_id` = `d`.`id` and `ba`.`is_active` = 1) AS `blocked_apps`,(select coalesce(sum(`au`.`foreground_sec`),0) from `abo7tb_parental_control`.`app_usage` `au` where `au`.`device_id` = `d`.`id` and `au`.`usage_date` = curdate()) AS `today_usage_sec`,(select count(0) from `abo7tb_parental_control`.`notifications` `n` where `n`.`device_id` = `d`.`id` and `n`.`is_read` = 0) AS `unread_notifications` from ((`abo7tb_parental_control`.`devices` `d` join `abo7tb_parental_control`.`users` `u` on(`d`.`user_id` = `u`.`id`)) left join `abo7tb_parental_control`.`child_consents` `cc` on(`cc`.`device_id` = `d`.`id` and `cc`.`consent_status` = 'accepted'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `v_devices_overview`");
    }
};
