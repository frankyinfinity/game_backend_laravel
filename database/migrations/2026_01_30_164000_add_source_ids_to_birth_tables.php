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
        Schema::table('birth_planets', function (Blueprint $table) {
            $table->unsignedBigInteger('planet_id')->nullable()->after('id');
        });

        Schema::table('birth_regions', function (Blueprint $table) {
            $table->unsignedBigInteger('region_id')->nullable()->after('id');
        });

        Schema::table('birth_climates', function (Blueprint $table) {
            $table->unsignedBigInteger('climate_id')->nullable()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('birth_planets', function (Blueprint $table) {
            $table->dropColumn('planet_id');
        });

        Schema::table('birth_regions', function (Blueprint $table) {
            $table->dropColumn('region_id');
        });

        Schema::table('birth_climates', function (Blueprint $table) {
            $table->dropColumn('climate_id');
        });
    }
};
