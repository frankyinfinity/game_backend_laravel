<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Enumerazione dei percorsi possibili per ogni brain_schedule.
     * Ogni path = sequenza ordinata di step (neurone + link condizionato preso).
     */
    public function up(): void
    {
        Schema::create('brain_schedule_paths', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('brain_schedule_id')->index();
            $table->unsignedBigInteger('element_has_position_neuron_circuit_id')->index('bsp_ehp_neuron_circuit_id_idx');
            $table->unsignedInteger('steps_count')->default(0);
            $table->string('hash', 64)->index();
            $table->string('closed_reason', 20)->nullable(); // 'end' | 'cycle' | 'truncated'
            $table->timestamps();

            $table->foreign('brain_schedule_id')
                ->references('id')
                ->on('brain_schedules')
                ->onDelete('cascade');

            $table->foreign('element_has_position_neuron_circuit_id', 'fk_bsp_ehp_neuron_circuit')
                ->references('id')
                ->on('element_has_position_neuron_circuits')
                ->onDelete('cascade');

            $table->unique(['brain_schedule_id', 'hash'], 'bsp_schedule_hash_unique');
        });

        Schema::create('brain_schedule_path_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('brain_schedule_path_id')->index('bsps_path_id_idx');
            $table->unsignedInteger('seq');
            $table->unsignedBigInteger('element_has_position_neuron_id')->index('bsps_neuron_id_idx');
            $table->unsignedBigInteger('via_element_has_position_neuron_link_id')->nullable()->index('bsps_via_link_id_idx');
            $table->string('condition')->nullable();
            $table->timestamps();

            $table->foreign('brain_schedule_path_id', 'fk_bsps_path')
                ->references('id')
                ->on('brain_schedule_paths')
                ->onDelete('cascade');

            $table->foreign('element_has_position_neuron_id', 'fk_bsps_neuron')
                ->references('id')
                ->on('element_has_position_neurons')
                ->onDelete('cascade');

            $table->foreign('via_element_has_position_neuron_link_id', 'fk_bsps_via_link')
                ->references('id')
                ->on('element_has_position_neuron_links')
                ->onDelete('cascade');

            $table->unique(['brain_schedule_path_id', 'seq'], 'bsps_path_seq_unique');
        });

        Schema::table('brain_schedules', function (Blueprint $table) {
            $table->unsignedBigInteger('executed_brain_schedule_path_id')->nullable()->index();
            $table->foreign('executed_brain_schedule_path_id')
                ->references('id')
                ->on('brain_schedule_paths')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('brain_schedules', function (Blueprint $table) {
            $table->dropForeign(['executed_brain_schedule_path_id']);
            $table->dropIndex(['executed_brain_schedule_path_id']);
            $table->dropColumn('executed_brain_schedule_path_id');
        });

        Schema::dropIfExists('brain_schedule_path_steps');
        Schema::dropIfExists('brain_schedule_paths');
    }
};
