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
        Schema::table('screen_locks', function (Blueprint $table) {
            $table->foreign(['device_id'], 'screen_locks_ibfk_1')->references(['id'])->on('devices')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['locked_by'], 'screen_locks_ibfk_2')->references(['id'])->on('users')->onUpdate('restrict')->onDelete('set null');
            $table->foreign(['unlocked_by'], 'screen_locks_ibfk_3')->references(['id'])->on('users')->onUpdate('restrict')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('screen_locks', function (Blueprint $table) {
            $table->dropForeign('screen_locks_ibfk_1');
            $table->dropForeign('screen_locks_ibfk_2');
            $table->dropForeign('screen_locks_ibfk_3');
        });
    }
};
