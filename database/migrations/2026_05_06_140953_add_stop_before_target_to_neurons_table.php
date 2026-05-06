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
        Schema::table('neurons', function (Blueprint $table) {
            $table->boolean('stop_before_target')->default(true)->after('radius');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('neurons', function (Blueprint $table) {
            $table->dropColumn('stop_before_target');
        });
    }
};
