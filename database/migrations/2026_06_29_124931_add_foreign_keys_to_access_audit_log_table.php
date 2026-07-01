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
        Schema::table('access_audit_log', function (Blueprint $table) {
            $table->foreign(['device_id'], 'access_audit_log_ibfk_1')->references(['id'])->on('devices')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['user_id'], 'access_audit_log_ibfk_2')->references(['id'])->on('users')->onUpdate('restrict')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('access_audit_log', function (Blueprint $table) {
            $table->dropForeign('access_audit_log_ibfk_1');
            $table->dropForeign('access_audit_log_ibfk_2');
        });
    }
};
