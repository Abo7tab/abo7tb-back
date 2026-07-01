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
        Schema::create('camera_captures', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('uuid', 36)->index('idx_uuid');
            $table->unsignedBigInteger('device_id')->index('idx_device_id');
            $table->enum('capture_type', ['photo', 'video'])->index('idx_capture_type');
            $table->enum('camera_facing', ['front', 'back']);
            $table->string('file_path', 500);
            $table->string('thumbnail_path', 500)->nullable();
            $table->unsignedBigInteger('file_size');
            $table->string('mime_type', 100);
            $table->unsignedInteger('duration_sec')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->enum('trigger_type', ['manual', 'scheduled', 'alert']);
            $table->string('trigger_reason')->nullable();
            $table->enum('status', ['pending', 'uploaded', 'viewed', 'deleted'])->nullable()->default('pending')->index('idx_status');
            $table->unsignedBigInteger('requested_by')->nullable()->index('requested_by');
            $table->boolean('parent_viewed')->nullable()->default(false);
            $table->timestamp('parent_viewed_at')->nullable();
            $table->boolean('parent_deleted')->nullable()->default(false);
            $table->text('parent_notes')->nullable();
            $table->timestamp('captured_at')->useCurrent()->index('idx_captured_at');
            $table->timestamp('uploaded_at')->nullable();

            $table->unique(['uuid'], 'uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('camera_captures');
    }
};
