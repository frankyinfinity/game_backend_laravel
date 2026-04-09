<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rule_chimical_element_details', function (Blueprint $table) {
            $table->integer('min')->change();
            $table->integer('max')->change();
        });

        Schema::table('rule_chimical_elements', function (Blueprint $table) {
            $table->integer('min')->change();
            $table->integer('max')->change();
        });

        Schema::table('player_rule_chimical_element_details', function (Blueprint $table) {
            $table->integer('min')->change();
            $table->integer('max')->change();
        });

        Schema::table('player_rule_chimical_elements', function (Blueprint $table) {
            $table->integer('min')->change();
            $table->integer('max')->change();
        });
    }

    public function down(): void
    {
        Schema::table('rule_chimical_element_details', function (Blueprint $table) {
            $table->integer('min')->default(0)->change();
            $table->integer('max')->default(0)->change();
        });

        Schema::table('rule_chimical_elements', function (Blueprint $table) {
            $table->integer('min')->default(0)->change();
            $table->integer('max')->default(0)->change();
        });

        Schema::table('player_rule_chimical_element_details', function (Blueprint $table) {
            $table->integer('min')->default(0)->change();
            $table->integer('max')->default(0)->change();
        });

        Schema::table('player_rule_chimical_elements', function (Blueprint $table) {
            $table->integer('min')->default(0)->change();
            $table->integer('max')->default(0)->change();
        });
    }
};
