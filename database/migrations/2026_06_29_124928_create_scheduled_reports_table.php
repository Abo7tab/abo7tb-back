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
        Schema::create('scheduled_reports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index('idx_user_id');
            $table->unsignedBigInteger('device_id')->nullable()->index('device_id');
            $table->enum('report_type', ['daily', 'weekly', 'monthly']);
            $table->enum('report_format', ['email', 'pdf', 'both'])->nullable()->default('email');
            $table->time('delivery_time')->nullable();
            $table->unsignedTinyInteger('delivery_day')->nullable();
            $table->boolean('is_active')->nullable()->default(true);
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('next_send_at')->nullable()->index('idx_next_send');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_reports');
    }
};
