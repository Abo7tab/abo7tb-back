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
        Schema::create('device_gallery', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('uuid', 36)->index('idx_uuid');
            $table->unsignedBigInteger('device_id')->index('idx_device_id');
            $table->string('file_name');
            $table->string('file_path', 500);
            $table->string('thumbnail_path', 500)->nullable();
            $table->unsignedBigInteger('file_size');
            $table->string('mime_type', 100);
            $table->enum('media_type', ['photo', 'video', 'audio', 'document'])->index('idx_media_type');
            $table->string('source_folder')->nullable();
            $table->string('source_app', 150)->nullable()->index('idx_source_app');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('duration_sec')->nullable();
            $table->timestamp('taken_at')->nullable()->index('idx_taken_at');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->boolean('parent_viewed')->nullable()->default(false);
            $table->timestamp('parent_viewed_at')->nullable();
            $table->boolean('parent_flagged')->nullable()->default(false);
            $table->text('flag_reason')->nullable();
            $table->char('md5_hash', 32)->nullable()->index('idx_md5_hash');
            $table->enum('sync_status', ['pending', 'synced', 'failed'])->nullable()->default('pending');
            $table->timestamp('first_seen_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();

            $table->unique(['uuid'], 'uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_gallery');
    }
};
