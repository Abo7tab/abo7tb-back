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
        DB::statement("CREATE VIEW `v_latest_locations` AS select `dl`.`id` AS `id`,`dl`.`device_id` AS `device_id`,`dl`.`latitude` AS `latitude`,`dl`.`longitude` AS `longitude`,`dl`.`altitude` AS `altitude`,`dl`.`accuracy` AS `accuracy`,`dl`.`speed` AS `speed`,`dl`.`bearing` AS `bearing`,`dl`.`address` AS `address`,`dl`.`city` AS `city`,`dl`.`country` AS `country`,`dl`.`provider` AS `provider`,`dl`.`recorded_at` AS `recorded_at`,`d`.`child_name` AS `child_name`,`d`.`device_name` AS `device_name` from ((`abo7tb_parental_control`.`device_locations` `dl` join (select `abo7tb_parental_control`.`device_locations`.`device_id` AS `device_id`,max(`abo7tb_parental_control`.`device_locations`.`recorded_at`) AS `latest` from `abo7tb_parental_control`.`device_locations` group by `abo7tb_parental_control`.`device_locations`.`device_id`) `mx` on(`dl`.`device_id` = `mx`.`device_id` and `dl`.`recorded_at` = `mx`.`latest`)) join `abo7tb_parental_control`.`devices` `d` on(`dl`.`device_id` = `d`.`id`))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `v_latest_locations`");
    }
};
