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
        Schema::table('player_rule_chimical_elements', function (Blueprint $table) {
            $table->integer('quantity_tick_degradation')->nullable()->after('default_value');
            $table->float('percentage_degradation')->nullable()->after('quantity_tick_degradation');
            $table->boolean('degradable')->default(false)->after('percentage_degradation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('player_rule_chimical_elements', function (Blueprint $table) {
            //
        });
    }
};
