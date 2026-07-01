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
        Schema::create('blocked_numbers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('device_id')->index('idx_device_id');
            $table->string('phone_number', 20);
            $table->string('contact_name', 150)->nullable();
            $table->boolean('block_calls')->nullable()->default(true);
            $table->boolean('block_sms')->nullable()->default(true);
            $table->text('reason')->nullable();
            $table->timestamp('blocked_at')->useCurrent();

            $table->unique(['device_id', 'phone_number'], 'uq_device_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blocked_numbers');
    }
};
