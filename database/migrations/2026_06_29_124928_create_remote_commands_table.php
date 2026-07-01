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
        Schema::create('remote_commands', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('uuid', 36)->index('idx_uuid');
            $table->unsignedBigInteger('device_id')->index('idx_device_id');
            $table->enum('command_category', ['camera', 'microphone', 'screen', 'gallery', 'location', 'system', 'app', 'notification'])->index('idx_command_category');
            $table->string('command_type', 50);
            $table->json('command_data')->nullable();
            $table->enum('status', ['pending', 'sent', 'executing', 'completed', 'failed', 'cancelled'])->nullable()->default('pending')->index('idx_status');
            $table->json('result')->nullable();
            $table->text('error_message')->nullable();
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->nullable()->default('normal');
            $table->unsignedTinyInteger('retry_count')->nullable()->default(0);
            $table->unsignedTinyInteger('max_retries')->nullable()->default(3);
            $table->unsignedBigInteger('created_by')->index('created_by');
            $table->timestamp('created_at')->useCurrent()->index('idx_created_at');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->unique(['uuid'], 'uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remote_commands');
    }
};
