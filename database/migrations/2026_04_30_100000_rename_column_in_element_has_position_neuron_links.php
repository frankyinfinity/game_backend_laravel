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
        Schema::table('element_has_position_neuron_links', function (Blueprint $table) {
            $table->renameColumn('elemenet_has_position_rule_chimical_element_detail_id', 'element_has_position_rule_chimical_element_detail_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('element_has_position_neuron_links', function (Blueprint $table) {
            $table->renameColumn('element_has_position_rule_chimical_element_detail_id', 'elemenet_has_position_rule_chimical_element_detail_id');
        });
    }
};