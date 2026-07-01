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
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('uuid', 36)->index('idx_uuid');
            $table->string('name', 100);
            $table->string('email')->unique('email');
            $table->string('password_hash');
            $table->string('phone', 20)->nullable();
            $table->string('profile_image')->nullable();
            $table->enum('subscription_plan', ['free', 'premium', 'family'])->nullable()->default('free');
            $table->date('subscription_expires_at')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->boolean('is_active')->nullable()->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();

            $table->index(['email'], 'idx_email');
            $table->unique(['uuid'], 'uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
