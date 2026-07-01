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
        Schema::create('app_usage', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('device_id');
            $table->string('package_name')->index('idx_package');
            $table->string('app_name', 150)->nullable();
            $table->date('usage_date');
            $table->unsignedInteger('foreground_sec')->nullable()->default(0);
            $table->unsignedInteger('background_sec')->nullable()->default(0);
            $table->unsignedInteger('launch_count')->nullable()->default(0);
            $table->unsignedBigInteger('data_sent')->nullable()->default(0);
            $table->unsignedBigInteger('data_received')->nullable()->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();

            $table->index(['device_id', 'usage_date'], 'idx_device_date');
            $table->unique(['device_id', 'package_name', 'usage_date'], 'uq_daily_usage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_usage');
    }
};
