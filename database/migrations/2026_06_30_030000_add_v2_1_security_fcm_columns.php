<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->text('fcm_token')->nullable()->after('is_locked_by_parent');
            $table->boolean('push_enabled')->default(true)->after('fcm_token');
            $table->timestamp('fcm_updated_at')->nullable()->after('push_enabled');
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('remote_commands', function (Blueprint $table) {
            $table->string('delivery_method', 20)->nullable()->after('sent_at');
        });

        $this->dropIndexIfExists('sms_logs', 'idx_phone');
        $this->dropIndexIfExists('call_logs', 'idx_phone');
        $this->dropIndexIfExists('contacts', 'idx_phone');
        $this->dropIndexIfExists('blocked_numbers', 'uq_device_number');

        DB::statement('ALTER TABLE sms_logs MODIFY phone_number TEXT NOT NULL, MODIFY contact_name TEXT NULL');
        DB::statement('ALTER TABLE call_logs MODIFY phone_number TEXT NOT NULL, MODIFY contact_name TEXT NULL');
        DB::statement('ALTER TABLE contacts MODIFY phone_number TEXT NOT NULL, MODIFY contact_name TEXT NULL, MODIFY email TEXT NULL');
        DB::statement('ALTER TABLE blocked_numbers MODIFY phone_number TEXT NOT NULL, MODIFY contact_name TEXT NULL');
        DB::statement('ALTER TABLE browsing_history MODIFY title TEXT NULL');
        DB::statement('ALTER TABLE device_locations MODIFY city TEXT NULL, MODIFY country TEXT NULL');

        Schema::table('sms_logs', function (Blueprint $table) {
            $table->char('phone_hash', 64)->nullable()->after('phone_number');
            $table->index('phone_hash', 'idx_sms_phone_hash');
        });

        Schema::table('call_logs', function (Blueprint $table) {
            $table->char('phone_hash', 64)->nullable()->after('phone_number');
            $table->index('phone_hash', 'idx_calls_phone_hash');
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->char('phone_hash', 64)->nullable()->after('phone_number');
            $table->index('phone_hash', 'idx_contacts_phone_hash');
        });

        Schema::table('blocked_numbers', function (Blueprint $table) {
            $table->char('phone_hash', 64)->nullable()->after('phone_number');
            $table->unique(['device_id', 'phone_hash'], 'uq_device_phone_hash');
        });
    }

    public function down(): void
    {
        Schema::table('blocked_numbers', function (Blueprint $table) {
            $table->dropUnique('uq_device_phone_hash');
            $table->dropColumn('phone_hash');
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropIndex('idx_contacts_phone_hash');
            $table->dropColumn('phone_hash');
        });

        Schema::table('call_logs', function (Blueprint $table) {
            $table->dropIndex('idx_calls_phone_hash');
            $table->dropColumn('phone_hash');
        });

        Schema::table('sms_logs', function (Blueprint $table) {
            $table->dropIndex('idx_sms_phone_hash');
            $table->dropColumn('phone_hash');
        });

        Schema::table('remote_commands', function (Blueprint $table) {
            $table->dropColumn('delivery_method');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('devices', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['fcm_token', 'push_enabled', 'fcm_updated_at']);
        });
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        try {
            DB::statement("ALTER TABLE {$table} DROP INDEX {$index}");
        } catch (Throwable) {
            // Index may not exist in fresh or manually adjusted databases.
        }
    }
};
