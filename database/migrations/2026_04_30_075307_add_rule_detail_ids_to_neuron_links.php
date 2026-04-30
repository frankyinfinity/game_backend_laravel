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
        Schema::table('neuron_links', function (Blueprint $table) {
            if (!Schema::hasColumn('neuron_links', 'rule_chimical_element_detail_id')) {
                $table->unsignedBigInteger('rule_chimical_element_detail_id')->nullable()->after('color');
            }
        });

        Schema::table('element_has_position_neuron_links', function (Blueprint $table) {
            if (!Schema::hasColumn('element_has_position_neuron_links', 'element_has_position_rule_chimical_element_detail_id')) {
                $table->unsignedBigInteger('element_has_position_rule_chimical_element_detail_id')->nullable()->after('color');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('neuron_links', function (Blueprint $table) {
            if (Schema::hasColumn('neuron_links', 'rule_chimical_element_detail_id')) {
                $table->dropColumn('rule_chimical_element_detail_id');
            }
        });

        Schema::table('element_has_position_neuron_links', function (Blueprint $table) {
            if (Schema::hasColumn('element_has_position_neuron_links', 'element_has_position_rule_chimical_element_detail_id')) {
                $table->dropColumn('element_has_position_rule_chimical_element_detail_id');
            }
        });
    }
};
