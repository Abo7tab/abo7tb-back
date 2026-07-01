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
        Schema::create('screenshots', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('uuid', 36)->unique('uuid');
            $table->unsignedBigInteger('device_id')->index('idx_device_id');
            $table->string('file_path', 500);
            $table->string('thumbnail_path', 500)->nullable();
            $table->unsignedInteger('file_size')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->enum('trigger_type', ['manual', 'auto', 'app_open', 'alert'])->nullable()->default('manual');
            $table->string('trigger_app')->nullable();
            $table->boolean('parent_viewed')->nullable()->default(false);
            $table->timestamp('parent_viewed_at')->nullable();
            $table->timestamp('captured_at')->useCurrent()->index('idx_captured_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('screenshots');
    }
};
