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
        Schema::table('birth_regions', function (Blueprint $table) {
            $table->boolean('get_coordinate')->default(false)->after('imagename');
        });

        Schema::table('birth_region_details', function (Blueprint $table) {
            $table->json('json_coordinates')->nullable()->after('json_generator');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('birth_region_details', function (Blueprint $table) {
            $table->dropColumn('json_coordinates');
        });

        Schema::table('birth_regions', function (Blueprint $table) {
            $table->dropColumn('get_coordinate');
        });
    }
};
