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
        Schema::table('child_consents', function (Blueprint $table) {
            $table->foreign(['device_id'], 'child_consents_ibfk_1')->references(['id'])->on('devices')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['user_id'], 'child_consents_ibfk_2')->references(['id'])->on('users')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('child_consents', function (Blueprint $table) {
            $table->dropForeign('child_consents_ibfk_1');
            $table->dropForeign('child_consents_ibfk_2');
        });
    }
};
