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
            $table->foreignId('element_has_rule_chimical_element_id')->nullable()->after('gene_attack_id')->constrained('rule_chimical_elements')->nullOnDelete();
        });

        Schema::table('element_has_position_neurons', function (Blueprint $table) {
            $table->unsignedBigInteger('element_has_rule_chimical_element_id')->nullable()->after('gene_attack_id');

            $table->foreign('element_has_rule_chimical_element_id', 'ehpn_rule_chimical_element_fk')
                ->references('id')
                ->on('rule_chimical_elements')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('element_has_position_neurons', function (Blueprint $table) {
            $table->dropForeign('ehpn_rule_chimical_element_fk');
            $table->dropColumn('element_has_rule_chimical_element_id');
        });

        Schema::table('neurons', function (Blueprint $table) {
            $table->dropForeign(['element_has_rule_chimical_element_id']);
            $table->dropColumn('element_has_rule_chimical_element_id');
        });
    }
};
