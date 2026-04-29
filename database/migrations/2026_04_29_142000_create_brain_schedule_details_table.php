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
        Schema::create('brain_schedule_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('brain_schedule_id')->index();
            $table->unsignedBigInteger('element_has_position_neuron_circuit_id')->index('brain_sched_det_ehp_neur_circ_id_idx');
            $table->timestamps();

            $table->foreign('brain_schedule_id')
                ->references('id')
                ->on('brain_schedules')
                ->onDelete('cascade');

            $table->foreign('element_has_position_neuron_circuit_id', 'fk_brain_sched_det_ehp_neur_circ_id')
                ->references('id')
                ->on('element_has_position_neuron_circuits')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brain_schedule_details');
    }
};
