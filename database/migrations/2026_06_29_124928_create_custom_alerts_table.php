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
        Schema::create('custom_alerts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('device_id')->index('idx_device_id');
            $table->string('alert_name', 100);
            $table->string('alert_type', 50)->index('idx_alert_type');
            $table->json('conditions');
            $table->json('actions');
            $table->boolean('is_active')->nullable()->default(true)->index('idx_is_active');
            $table->timestamp('last_triggered_at')->nullable();
            $table->unsignedInteger('trigger_count')->nullable()->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_alerts');
    }
};
