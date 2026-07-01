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
        DB::statement("CREATE VIEW `v_online_devices` AS select `d`.`id` AS `id`,`d`.`uuid` AS `uuid`,`d`.`user_id` AS `user_id`,`d`.`child_name` AS `child_name`,`d`.`child_age` AS `child_age`,`d`.`device_name` AS `device_name`,`d`.`device_model` AS `device_model`,`d`.`device_brand` AS `device_brand`,`d`.`android_version` AS `android_version`,`d`.`sdk_version` AS `sdk_version`,`d`.`device_id` AS `device_id`,`d`.`imei` AS `imei`,`d`.`serial_number` AS `serial_number`,`d`.`mac_address` AS `mac_address`,`d`.`app_version` AS `app_version`,`d`.`battery_level` AS `battery_level`,`d`.`is_charging` AS `is_charging`,`d`.`is_screen_on` AS `is_screen_on`,`d`.`current_wifi` AS `current_wifi`,`d`.`is_online` AS `is_online`,`d`.`last_seen_at` AS `last_seen_at`,`d`.`last_location_lat` AS `last_location_lat`,`d`.`last_location_lng` AS `last_location_lng`,`d`.`last_location_at` AS `last_location_at`,`d`.`perm_camera` AS `perm_camera`,`d`.`perm_microphone` AS `perm_microphone`,`d`.`perm_storage` AS `perm_storage`,`d`.`perm_location` AS `perm_location`,`d`.`perm_contacts` AS `perm_contacts`,`d`.`perm_call_log` AS `perm_call_log`,`d`.`perm_sms` AS `perm_sms`,`d`.`perm_overlay` AS `perm_overlay`,`d`.`perm_usage_stats` AS `perm_usage_stats`,`d`.`perm_accessibility` AS `perm_accessibility`,`d`.`perm_device_admin` AS `perm_device_admin`,`d`.`monitoring_enabled` AS `monitoring_enabled`,`d`.`is_active` AS `is_active`,`d`.`is_locked_by_parent` AS `is_locked_by_parent`,`d`.`registered_at` AS `registered_at`,`d`.`created_at` AS `created_at`,`d`.`updated_at` AS `updated_at`,`u`.`name` AS `parent_name` from (`abo7tb_parental_control`.`devices` `d` join `abo7tb_parental_control`.`users` `u` on(`d`.`user_id` = `u`.`id`)) where `d`.`is_online` = 1 or timestampdiff(MINUTE,`d`.`last_seen_at`,current_timestamp()) < 5");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `v_online_devices`");
    }
};
