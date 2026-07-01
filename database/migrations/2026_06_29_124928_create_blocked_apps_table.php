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
        Schema::create('blocked_apps', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('device_id')->index('idx_device_id');
            $table->string('package_name');
            $table->string('app_name', 150)->nullable();
            $table->enum('block_type', ['permanent', 'scheduled', 'time_limited'])->nullable()->default('permanent');
            $table->text('reason')->nullable();
            $table->timestamp('blocked_until')->nullable();
            $table->boolean('is_active')->nullable()->default(true)->index('idx_is_active');
            $table->timestamp('blocked_at')->useCurrent();

            $table->unique(['device_id', 'package_name'], 'uq_device_package');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blocked_apps');
    }
};
