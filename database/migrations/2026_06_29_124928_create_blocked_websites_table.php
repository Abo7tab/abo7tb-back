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
        Schema::create('blocked_websites', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('device_id')->index('idx_device_id');
            $table->string('domain')->index('idx_domain');
            $table->string('category', 50)->nullable()->index('idx_category');
            $table->enum('block_type', ['domain', 'keyword', 'category'])->nullable()->default('domain');
            $table->boolean('is_active')->nullable()->default(true);
            $table->timestamp('blocked_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blocked_websites');
    }
};
