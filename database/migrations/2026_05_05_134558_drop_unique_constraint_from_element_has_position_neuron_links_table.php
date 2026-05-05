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
            $table->dropForeign('ehpnl_from_fk');
            $table->dropForeign('ehpnl_to_fk');
            $table->dropUnique('ehp_neuron_links_unique_pair');
            $table->foreign('from_element_has_position_neuron_id', 'ehpnl_from_fk')
                ->references('id')
                ->on('element_has_position_neurons')
                ->cascadeOnDelete();
            $table->foreign('to_element_has_position_neuron_id', 'ehpnl_to_fk')
                ->references('id')
                ->on('element_has_position_neurons')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('element_has_position_neuron_links', function (Blueprint $table) {
            $table->dropForeign('ehpnl_from_fk');
            $table->dropForeign('ehpnl_to_fk');
            $table->unique([
                'from_element_has_position_neuron_id',
                'to_element_has_position_neuron_id',
            ], 'ehp_neuron_links_unique_pair');
            $table->foreign('from_element_has_position_neuron_id', 'ehpnl_from_fk')
                ->references('id')
                ->on('element_has_position_neurons')
                ->cascadeOnDelete();
            $table->foreign('to_element_has_position_neuron_id', 'ehpnl_to_fk')
                ->references('id')
                ->on('element_has_position_neurons')
                ->cascadeOnDelete();
        });
    }
};
