<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('player_rule_chimical_element_detail_effects', function (Blueprint $table) {
            $table->integer('duration')->nullable()->after('value');
        });
    }

    public function down(): void
    {
        Schema::table('player_rule_chimical_element_detail_effects', function (Blueprint $table) {
            $table->dropColumn('duration');
        });
    }
};