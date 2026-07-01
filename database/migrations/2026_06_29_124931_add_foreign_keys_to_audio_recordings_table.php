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
        Schema::table('audio_recordings', function (Blueprint $table) {
            $table->foreign(['device_id'], 'audio_recordings_ibfk_1')->references(['id'])->on('devices')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['requested_by'], 'audio_recordings_ibfk_2')->references(['id'])->on('users')->onUpdate('restrict')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audio_recordings', function (Blueprint $table) {
            $table->dropForeign('audio_recordings_ibfk_1');
            $table->dropForeign('audio_recordings_ibfk_2');
        });
    }
};
