<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ehp_component_brain_neurons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ehp_component_brain_id');
            $table->string('type');
            $table->integer('grid_i');
            $table->integer('grid_j');
            $table->integer('radius')->nullable();
            $table->boolean('stop_before_target')->default(false);
            $table->string('target_type')->nullable();
            $table->unsignedBigInteger('target_element_id')->nullable();
            $table->unsignedBigInteger('chemical_element_id')->nullable();
            $table->unsignedBigInteger('complex_chemical_element_id')->nullable();
            $table->unsignedBigInteger('gene_life_id')->nullable();
            $table->unsignedBigInteger('gene_attack_id')->nullable();
            $table->unsignedBigInteger('element_infomation_id')->nullable();
            $table->unsignedBigInteger('rule_chimical_element_id')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->foreign('ehp_component_brain_id', 'ehp_cb_neurons_brain_fk')
                ->references('id')->on('element_has_position_component_brains')->onDelete('cascade');
            $table->unique(['ehp_component_brain_id', 'grid_i', 'grid_j'], 'ehp_cb_neurons_unique');
        });

        Schema::create('ehp_component_brain_neuron_condition_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ehp_component_brain_neuron_id');
            $table->string('condition');
            $table->integer('sort_order')->default(0);
            $table->string('color')->nullable();
            $table->unsignedBigInteger('rule_detail_id')->nullable();
            $table->timestamps();

            $table->foreign('ehp_component_brain_neuron_id', 'ehp_cb_ncond_neuron_fk')
                ->references('id')->on('ehp_component_brain_neurons')->onDelete('cascade');
        });

        Schema::create('ehp_component_brain_neuron_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('from_neuron_id');
            $table->unsignedBigInteger('to_neuron_id');
            $table->unsignedBigInteger('condition_order_id')->nullable();
            $table->timestamps();

            $table->foreign('from_neuron_id', 'ehp_cb_nlinks_from_fk')
                ->references('id')->on('ehp_component_brain_neurons')->onDelete('cascade');
            $table->foreign('to_neuron_id', 'ehp_cb_nlinks_to_fk')
                ->references('id')->on('ehp_component_brain_neurons')->onDelete('cascade');
            $table->foreign('condition_order_id', 'ehp_cb_nlinks_cond_fk')
                ->references('id')->on('ehp_component_brain_neuron_condition_orders')->onDelete('set null');
        });

        Schema::create('ehp_component_brain_neuron_circuits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ehp_component_brain_id');
            $table->string('uid');
            $table->string('state')->default('created');
            $table->boolean('active')->default(true);
            $table->string('color')->nullable();
            $table->unsignedBigInteger('start_neuron_id')->nullable();
            $table->timestamps();

            $table->foreign('ehp_component_brain_id', 'ehp_cb_circuits_brain_fk')
                ->references('id')->on('element_has_position_component_brains')->onDelete('cascade');
            $table->foreign('start_neuron_id', 'ehp_cb_circuits_start_fk')
                ->references('id')->on('ehp_component_brain_neurons')->onDelete('set null');
        });

        Schema::create('ehp_component_brain_neuron_circuit_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('circuit_id');
            $table->unsignedBigInteger('neuron_id');
            $table->timestamps();

            $table->foreign('circuit_id', 'ehp_cb_cdetails_circuit_fk')
                ->references('id')->on('ehp_component_brain_neuron_circuits')->onDelete('cascade');
            $table->foreign('neuron_id', 'ehp_cb_cdetails_neuron_fk')
                ->references('id')->on('ehp_component_brain_neurons')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ehp_component_brain_neuron_circuit_details');
        Schema::dropIfExists('ehp_component_brain_neuron_circuits');
        Schema::dropIfExists('ehp_component_brain_neuron_links');
        Schema::dropIfExists('ehp_component_brain_neuron_condition_orders');
        Schema::dropIfExists('ehp_component_brain_neurons');
    }
};
