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
        Schema::create('contacts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('device_id')->index('idx_device_id');
            $table->string('contact_name', 150)->nullable();
            $table->string('phone_number', 20)->index('idx_phone');
            $table->string('email')->nullable();
            $table->boolean('is_favorite')->nullable()->default(false);
            $table->text('photo_uri')->nullable();
            $table->unsignedInteger('times_contacted')->nullable()->default(0);
            $table->timestamp('last_contacted')->nullable();
            $table->timestamp('synced_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
