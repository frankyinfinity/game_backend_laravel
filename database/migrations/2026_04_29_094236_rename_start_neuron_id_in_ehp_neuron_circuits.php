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
        Schema::table('element_has_position_neuron_circuits', function (Blueprint $table) {
            $table->dropForeign(['start_neuron_id']);
            $table->dropColumn('start_neuron_id');
            $table->foreignId('start_element_has_position_neuron_id')
                  ->nullable()
                  ->constrained('element_has_position_neurons')
                  ->onDelete('cascade')
                  ->name('fk_ehp_neuron_circuits_start_neuron_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('element_has_position_neuron_circuits', function (Blueprint $table) {
            $table->dropForeign(['start_element_has_position_neuron_id']);
            $table->dropColumn('start_element_has_position_neuron_id');
            $table->foreignId('start_neuron_id')->nullable()->constrained('neurons')->onDelete('cascade');
        });
    }
};
