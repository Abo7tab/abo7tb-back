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
        Schema::create('child_consents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('device_id')->index('idx_device_id');
            $table->unsignedBigInteger('user_id')->index('user_id');
            $table->string('child_name', 100);
            $table->unsignedTinyInteger('child_age');
            $table->string('policy_version', 10)->nullable()->default('2.0');
            $table->text('policy_text');
            $table->enum('consent_status', ['pending', 'accepted', 'revoked'])->nullable()->default('pending')->index('idx_consent_status');
            $table->timestamp('consent_given_at')->nullable();
            $table->string('consent_ip', 45)->nullable();
            $table->text('consent_device')->nullable();
            $table->boolean('allow_camera')->nullable()->default(false);
            $table->boolean('allow_microphone')->nullable()->default(false);
            $table->boolean('allow_gallery')->nullable()->default(false);
            $table->boolean('allow_location')->nullable()->default(false);
            $table->boolean('allow_call_monitoring')->nullable()->default(false);
            $table->boolean('allow_sms_monitoring')->nullable()->default(false);
            $table->boolean('allow_app_monitoring')->nullable()->default(false);
            $table->boolean('allow_web_monitoring')->nullable()->default(false);
            $table->boolean('allow_screen_lock')->nullable()->default(false);
            $table->boolean('allow_contacts_sync')->nullable()->default(false);
            $table->boolean('show_permanent_notification')->nullable()->default(true);
            $table->boolean('show_monitoring_icon')->nullable()->default(true);
            $table->timestamp('revoked_at')->nullable();
            $table->text('revocation_reason')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('child_consents');
    }
};
