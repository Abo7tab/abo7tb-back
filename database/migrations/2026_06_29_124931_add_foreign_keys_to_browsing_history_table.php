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
        Schema::table('browsing_history', function (Blueprint $table) {
            $table->foreign(['device_id'], 'browsing_history_ibfk_1')->references(['id'])->on('devices')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('browsing_history', function (Blueprint $table) {
            $table->dropForeign('browsing_history_ibfk_1');
        });
    }
};
