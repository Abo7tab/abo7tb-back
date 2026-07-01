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
        Schema::create('screen_locks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('device_id')->index('idx_device_id');
            $table->enum('lock_type', ['black_screen', 'custom_message', 'bedtime', 'study_time', 'punishment', 'emergency'])->index('idx_lock_type');
            $table->string('message_title')->nullable();
            $table->text('message_body')->nullable();
            $table->char('background_color', 7)->nullable()->default('#000000');
            $table->boolean('show_message')->nullable()->default(false);
            $table->boolean('is_active')->nullable()->default(true)->index('idx_is_active');
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->boolean('allow_emergency_calls')->nullable()->default(true);
            $table->boolean('allow_alarm')->nullable()->default(true);
            $table->boolean('allow_music')->nullable()->default(false);
            $table->json('whitelisted_numbers')->nullable();
            $table->unsignedBigInteger('locked_by')->nullable()->index('locked_by');
            $table->unsignedBigInteger('unlocked_by')->nullable()->index('unlocked_by');
            $table->timestamp('locked_at')->useCurrent();
            $table->timestamp('unlocked_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('screen_locks');
    }
};
