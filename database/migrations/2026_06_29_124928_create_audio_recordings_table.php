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
        Schema::create('audio_recordings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('uuid', 36)->index('idx_uuid');
            $table->unsignedBigInteger('device_id')->index('idx_device_id');
            $table->string('file_path', 500);
            $table->unsignedBigInteger('file_size');
            $table->unsignedInteger('duration_sec');
            $table->enum('quality', ['low', 'medium', 'high'])->nullable()->default('medium');
            $table->enum('trigger_type', ['manual', 'scheduled', 'alert']);
            $table->string('trigger_reason')->nullable();
            $table->enum('status', ['recording', 'uploaded', 'viewed', 'deleted'])->nullable()->default('recording')->index('idx_status');
            $table->unsignedBigInteger('requested_by')->nullable()->index('requested_by');
            $table->boolean('parent_viewed')->nullable()->default(false);
            $table->timestamp('parent_viewed_at')->nullable();
            $table->boolean('parent_deleted')->nullable()->default(false);
            $table->text('parent_notes')->nullable();
            $table->timestamp('started_at')->useCurrent()->index('idx_started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('uploaded_at')->nullable();

            $table->unique(['uuid'], 'uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audio_recordings');
    }
};
