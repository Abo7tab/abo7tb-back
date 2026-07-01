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
        Schema::create('screen_time', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('device_id');
            $table->date('date');
            $table->unsignedInteger('total_sec')->nullable()->default(0);
            $table->unsignedInteger('screen_on_sec')->nullable()->default(0);
            $table->unsignedInteger('interactive_sec')->nullable()->default(0);
            $table->unsignedInteger('unlock_count')->nullable()->default(0);

            $table->index(['device_id', 'date'], 'idx_device_date');
            $table->unique(['device_id', 'date'], 'uq_device_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('screen_time');
    }
};
