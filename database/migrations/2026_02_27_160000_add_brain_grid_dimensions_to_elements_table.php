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
        Schema::table('elements', function (Blueprint $table) {
            $table->unsignedInteger('brain_grid_width')->default(5)->after('characteristic');
            $table->unsignedInteger('brain_grid_height')->default(5)->after('brain_grid_width');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('elements', function (Blueprint $table) {
            $table->dropColumn(['brain_grid_width', 'brain_grid_height']);
        });
    }
};
