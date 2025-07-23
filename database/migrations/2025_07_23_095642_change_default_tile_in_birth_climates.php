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
        Schema::table('birth_climates', function (Blueprint $table) {
            $table->dropForeign(['default_tile_id']);
            $table->dropColumn('default_tile_id');
            $table->json('default_tile')->nullable()->after('max_temperature');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('birth_climates', function (Blueprint $table) {
            $table->dropColumn('default_tile');
            $table->unsignedBigInteger('default_tile_id')->nullable()->after('max_temperature');
            $table->foreign('default_tile_id')->references('id')->on('tiles');
        });
    }
};
