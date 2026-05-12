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
        Schema::table('birth_region_limits', function (Blueprint $table) {
            $table->unsignedBigInteger('family_tile_id')->nullable()->after('birth_region_id');
            $table->foreign('family_tile_id')->references('id')->on('family_tiles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('birth_region_limits', function (Blueprint $table) {
            $table->dropForeign(['family_tile_id']);
            $table->dropColumn('family_tile_id');
        });
    }
};