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
        Schema::table('element_has_position_neuron_circuit_details', function (Blueprint $table) {
            $table->dropForeign(['neuron_id']);
            $table->dropColumn('neuron_id');
            $table->foreignId('element_has_position_neuron_id')
                  ->constrained('element_has_position_neurons')
                  ->onDelete('cascade')
                  ->name('fk_ehp_circuit_details_neuron_id'); // Short name
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('element_has_position_neuron_circuit_details', function (Blueprint $table) {
            $table->dropForeign(['element_has_position_neuron_id']);
            $table->dropColumn('element_has_position_neuron_id');
            $table->foreignId('neuron_id')->constrained('neurons')->onDelete('cascade');
        });
    }
};
