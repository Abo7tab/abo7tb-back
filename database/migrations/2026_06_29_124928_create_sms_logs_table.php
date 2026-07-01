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
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('device_id')->index('idx_device_id');
            $table->string('phone_number', 20)->index('idx_phone');
            $table->string('contact_name', 150)->nullable();
            $table->enum('message_type', ['sent', 'received', 'draft']);
            $table->text('message_body')->nullable()->fulltext('idx_body');
            $table->boolean('is_unknown')->nullable()->default(false);
            $table->boolean('parent_read')->nullable()->default(false)->index('idx_parent_read');
            $table->timestamp('sent_at')->useCurrentOnUpdate()->useCurrent()->index('idx_sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};
