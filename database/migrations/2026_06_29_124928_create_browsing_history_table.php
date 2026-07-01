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
        Schema::create('browsing_history', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('device_id')->index('idx_device_id');
            $table->text('url');
            $table->string('title', 500)->nullable();
            $table->string('browser_name', 100)->nullable();
            $table->unsignedInteger('visit_count')->nullable()->default(1);
            $table->timestamp('visited_at')->useCurrent()->index('idx_visited_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('browsing_history');
    }
};
