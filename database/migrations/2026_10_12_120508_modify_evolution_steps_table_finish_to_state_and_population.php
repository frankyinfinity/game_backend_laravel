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
        Schema::table('evolution_steps', function (Blueprint $table) {
            $table->dropColumn('finish');
            $table->integer('state')->default(0)->after('id');
            $table->integer('population')->default(0)->after('state');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evolution_steps', function (Blueprint $table) {
            $table->dropColumn(['state', 'population']);
            $table->boolean('finish')->default(false);
        });
    }
};
