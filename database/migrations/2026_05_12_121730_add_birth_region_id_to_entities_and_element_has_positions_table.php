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
        Schema::table('entities', function (Blueprint $table) {
            $table->unsignedBigInteger('birth_region_id')->nullable()->after('specie_id');
            $table->foreign('birth_region_id')->references('id')->on('birth_regions');
        });

        Schema::table('element_has_positions', function (Blueprint $table) {
            $table->unsignedBigInteger('birth_region_id')->nullable()->after('session_id');
            $table->foreign('birth_region_id')->references('id')->on('birth_regions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('element_has_positions', function (Blueprint $table) {
            $table->dropForeign(['birth_region_id']);
            $table->dropColumn('birth_region_id');
        });

        Schema::table('entities', function (Blueprint $table) {
            $table->dropForeign(['birth_region_id']);
            $table->dropColumn('birth_region_id');
        });
    }
};
