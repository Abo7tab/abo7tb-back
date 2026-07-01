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
        Schema::create('time_limits', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('device_id')->index('idx_device_id');
            $table->string('limit_name', 100)->nullable();
            $table->enum('limit_type', ['daily_total', 'app_specific', 'bedtime', 'study_time', 'custom'])->index('idx_limit_type');
            $table->unsignedInteger('max_minutes_per_day')->nullable();
            $table->string('package_name')->nullable();
            $table->unsignedInteger('max_app_minutes')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->json('active_days')->nullable();
            $table->boolean('block_completely')->nullable()->default(false);
            $table->boolean('allow_emergency_calls')->nullable()->default(true);
            $table->boolean('is_active')->nullable()->default(true)->index('idx_is_active');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_limits');
    }
};
