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
        Schema::table('tiles', function (Blueprint $table) {
            $table->unsignedBigInteger('family_tile_id')->nullable()->after('name');
            $table->foreign('family_tile_id')->references('id')->on('family_tiles');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tiles', function (Blueprint $table) {
            $table->dropForeign(['family_tile_id']);
            $table->dropColumn('family_tile_id');
        });
    }
};
