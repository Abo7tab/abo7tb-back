<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('uuid', 36)->index('idx_uuid');
            $table->unsignedBigInteger('user_id')->index('idx_user_id');
            $table->string('child_name', 100);
            $table->unsignedTinyInteger('child_age');
            $table->string('device_name', 100);
            $table->string('device_model', 100)->nullable();
            $table->string('device_brand', 50)->nullable();
            $table->string('android_version', 20)->nullable();
            $table->unsignedInteger('sdk_version')->nullable();
            $table->string('device_id')->unique('device_id');
            $table->string('imei', 50)->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->string('mac_address', 50)->nullable();
            $table->string('app_version', 20)->nullable();
            $table->unsignedTinyInteger('battery_level')->nullable()->default(0);
            $table->boolean('is_charging')->nullable()->default(false);
            $table->boolean('is_screen_on')->nullable()->default(true);
            $table->string('current_wifi', 100)->nullable();
            $table->boolean('is_online')->nullable()->default(false)->index('idx_is_online');
            $table->timestamp('last_seen_at')->nullable()->index('idx_last_seen');
            $table->decimal('last_location_lat', 10, 8)->nullable();
            $table->decimal('last_location_lng', 11, 8)->nullable();
            $table->timestamp('last_location_at')->nullable();
            $table->boolean('perm_camera')->nullable()->default(false);
            $table->boolean('perm_microphone')->nullable()->default(false);
            $table->boolean('perm_storage')->nullable()->default(false);
            $table->boolean('perm_location')->nullable()->default(false);
            $table->boolean('perm_contacts')->nullable()->default(false);
            $table->boolean('perm_call_log')->nullable()->default(false);
            $table->boolean('perm_sms')->nullable()->default(false);
            $table->boolean('perm_overlay')->nullable()->default(false);
            $table->boolean('perm_usage_stats')->nullable()->default(false);
            $table->boolean('perm_accessibility')->nullable()->default(false);
            $table->boolean('perm_device_admin')->nullable()->default(false);
            $table->boolean('monitoring_enabled')->nullable()->default(true);
            $table->boolean('is_active')->nullable()->default(true);
            $table->boolean('is_locked_by_parent')->nullable()->default(false);
            $table->timestamp('registered_at')->useCurrent();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();

            $table->index(['device_id'], 'idx_device_id');
            $table->unique(['uuid'], 'uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
