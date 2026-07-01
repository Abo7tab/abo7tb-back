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
        Schema::create('installed_apps', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('device_id')->index('idx_device_id');
            $table->string('app_name', 150);
            $table->string('package_name')->index('idx_package_name');
            $table->string('version_name', 50)->nullable();
            $table->unsignedInteger('version_code')->nullable();
            $table->unsignedBigInteger('app_size')->nullable();
            $table->text('app_icon')->nullable();
            $table->boolean('is_system_app')->nullable()->default(false)->index('idx_is_system');
            $table->boolean('is_enabled')->nullable()->default(true);
            $table->timestamp('install_date')->nullable();
            $table->timestamp('update_date')->nullable();
            $table->timestamp('first_seen')->useCurrent();
            $table->timestamp('last_seen')->useCurrentOnUpdate()->useCurrent();

            $table->unique(['device_id', 'package_name'], 'uq_device_package');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installed_apps');
    }
};
