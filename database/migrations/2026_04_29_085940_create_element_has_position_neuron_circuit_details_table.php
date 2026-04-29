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
        Schema::create('element_has_position_neuron_circuit_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('element_has_position_neuron_circuit_id')
                  ->constrained('element_has_position_neuron_circuits')
                  ->onDelete('cascade')
                  ->name('fk_ehp_circuit_details_circuit_id');
            $table->foreignId('neuron_id')
                  ->constrained('neurons')
                  ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('element_has_position_neuron_circuit_details');
    }
};
