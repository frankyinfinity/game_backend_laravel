<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rule_chimical_elements', function (Blueprint $table) {
            $table->string('default_value', 50)->nullable()->after('max');
        });

        Schema::table('player_rule_chimical_elements', function (Blueprint $table) {
            $table->string('default_value', 50)->nullable()->after('max');
        });
    }

    public function down(): void
    {
        Schema::table('rule_chimical_elements', function (Blueprint $table) {
            $table->dropColumn('default_value');
        });

        Schema::table('player_rule_chimical_elements', function (Blueprint $table) {
            $table->dropColumn('default_value');
        });
    }
};