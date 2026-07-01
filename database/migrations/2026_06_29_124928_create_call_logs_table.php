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
        Schema::create('call_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('device_id')->index('idx_device_id');
            $table->string('phone_number', 20)->index('idx_phone');
            $table->string('contact_name', 150)->nullable();
            $table->enum('call_type', ['incoming', 'outgoing', 'missed', 'rejected', 'blocked']);
            $table->unsignedInteger('duration_sec')->nullable()->default(0);
            $table->boolean('is_unknown')->nullable()->default(false);
            $table->boolean('parent_read')->nullable()->default(false)->index('idx_parent_read');
            $table->timestamp('called_at')->useCurrentOnUpdate()->useCurrent()->index('idx_called_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_logs');
    }
};
