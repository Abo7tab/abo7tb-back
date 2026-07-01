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
        Schema::create('system_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->string('setting_key', 100)->unique('setting_key');
            $table->text('setting_value')->nullable();
            $table->enum('setting_type', ['string', 'integer', 'boolean', 'json'])->nullable()->default('string');
            $table->text('description')->nullable();
            $table->boolean('is_public')->nullable()->default(false);
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
