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
        Schema::table('element_has_position_rewards', function (Blueprint $table) {
            $table->renameColumn('effect_id', 'effect');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('element_has_position_rewards', function (Blueprint $table) {
            $table->renameColumn('effect', 'effect_id');
        });
    }
};
