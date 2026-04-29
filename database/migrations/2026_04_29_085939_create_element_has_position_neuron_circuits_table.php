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
        Schema::create('element_has_position_neuron_circuits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('element_has_position_id')
                  ->constrained('element_has_positions')
                  ->onDelete('cascade')
                  ->name('fk_ehp_neuron_circuits_ehp_id'); // shortened foreign key name to avoid string too long error
            $table->foreignId('start_neuron_id')
                  ->nullable()
                  ->constrained('neurons')
                  ->onDelete('cascade');
            $table->string('uid')->unique();
            $table->string('state')->default('created');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('element_has_position_neuron_circuits');
    }
};
