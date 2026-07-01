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
        Schema::create('notifications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index('idx_user_id');
            $table->unsignedBigInteger('device_id')->nullable()->index('device_id');
            $table->string('title');
            $table->text('message');
            $table->string('type', 50)->index('idx_type');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->nullable()->default('medium');
            $table->string('icon', 50)->nullable();
            $table->json('data')->nullable();
            $table->boolean('is_read')->nullable()->default(false)->index('idx_is_read');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->useCurrent()->index('idx_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
