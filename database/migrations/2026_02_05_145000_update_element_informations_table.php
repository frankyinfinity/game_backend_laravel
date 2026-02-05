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
        Schema::table('element_informations', function (Blueprint $table) {
            $table->integer('max_from')->after('min_value')->default(0);
            $table->integer('max_to')->after('max_from')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('element_informations', function (Blueprint $table) {
            $table->dropColumn('max_from');
            $table->dropColumn('max_to');
        });
    }
};
