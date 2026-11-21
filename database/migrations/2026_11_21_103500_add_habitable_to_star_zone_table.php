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
        Schema::table('star_zone', function (Blueprint $table) {
            $table->boolean('habitable')->default(false)->after('rgb');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('star_zone', function (Blueprint $table) {
            $table->dropColumn('habitable');
        });
    }
};
