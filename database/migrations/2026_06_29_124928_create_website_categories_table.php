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
        Schema::create('website_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('category_name', 50)->unique('category_name');
            $table->text('description')->nullable();
            $table->json('domains')->nullable();
            $table->json('keywords')->nullable();
            $table->boolean('is_default')->nullable()->default(false);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_categories');
    }
};
