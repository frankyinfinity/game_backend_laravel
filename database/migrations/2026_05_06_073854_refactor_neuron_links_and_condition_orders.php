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
        // 1. neuron_condition_orders
        Schema::table('neuron_condition_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('rule_chimical_element_detail_id')->nullable()->after('color');
            $table->foreign('rule_chimical_element_detail_id', 'nco_rule_detail_fk')
                  ->references('id')->on('rule_chimical_element_details')
                  ->onDelete('set null');
        });

        // 2. neuron_links
        Schema::table('neuron_links', function (Blueprint $table) {
            $table->unsignedBigInteger('neuron_condition_order_id')->nullable()->after('to_neuron_id');
            $table->foreign('neuron_condition_order_id', 'nl_condition_order_fk')
                  ->references('id')->on('neuron_condition_orders')
                  ->onDelete('cascade');
            
            // Note: We'll drop 'condition' and 'rule_chimical_element_detail_id' in a separate step or here
            $table->dropColumn(['condition', 'rule_chimical_element_detail_id']);
        });

        // 3. element_has_position_neuron_condition_orders
        Schema::table('element_has_position_neuron_condition_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('element_has_position_rule_chimical_element_detail_id')->nullable()->after('color');
            $table->foreign('element_has_position_rule_chimical_element_detail_id', 'ehpnco_rule_detail_fk')
                  ->references('id')->on('element_has_position_rule_chimical_element_details')
                  ->onDelete('set null');
        });

        // 4. element_has_position_neuron_links
        Schema::table('element_has_position_neuron_links', function (Blueprint $table) {
            $table->unsignedBigInteger('element_has_position_neuron_condition_order_id')->nullable()->after('to_element_has_position_neuron_id');
            $table->foreign('element_has_position_neuron_condition_order_id', 'ehpnl_condition_order_fk')
                  ->references('id')->on('element_has_position_neuron_condition_orders')
                  ->onDelete('cascade');

            $table->dropColumn(['condition', 'element_has_position_rule_chimical_element_detail_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('element_has_position_neuron_links', function (Blueprint $table) {
            $table->dropForeign('ehpnl_condition_order_fk');
            $table->dropColumn('element_has_position_neuron_condition_order_id');
            $table->string('condition')->nullable();
            $table->unsignedBigInteger('element_has_position_rule_chimical_element_detail_id')->nullable();
        });

        Schema::table('element_has_position_neuron_condition_orders', function (Blueprint $table) {
            $table->dropForeign('ehpnco_rule_detail_fk');
            $table->dropColumn('element_has_position_rule_chimical_element_detail_id');
        });

        Schema::table('neuron_links', function (Blueprint $table) {
            $table->dropForeign('nl_condition_order_fk');
            $table->dropColumn('neuron_condition_order_id');
            $table->string('condition')->nullable();
            $table->unsignedBigInteger('rule_chimical_element_detail_id')->nullable();
        });

        Schema::table('neuron_condition_orders', function (Blueprint $table) {
            $table->dropForeign('nco_rule_detail_fk');
            $table->dropColumn('rule_chimical_element_detail_id');
        });
    }
};
