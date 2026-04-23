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
        Schema::create('element_has_position_rule_chimical_elements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('element_has_position_id');
            $table->unsignedBigInteger('chimical_element_id')->nullable();
            $table->unsignedBigInteger('complex_chimical_element_id')->nullable();
            $table->integer('min')->default(0);
            $table->integer('max')->default(0);
            $table->string('title')->nullable();
            $table->integer('default_value')->default(0);
            $table->integer('quantity_tick_degradation')->default(0);
            $table->float('percentage_degradation')->default(0);
            $table->boolean('degradable')->default(false);
            $table->timestamps();

            $table->foreign('element_has_position_id', 'ehprce_ehp_fk')->references('id')->on('element_has_positions')->onDelete('cascade');
            $table->index('element_has_position_id', 'ehprce_ehp_idx');
        });

        Schema::create('element_has_position_rule_chimical_element_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('element_has_position_rule_chimical_element_id');
            $table->integer('min')->default(0);
            $table->integer('max')->default(0);
            $table->string('color', 7)->default('#000000');
            $table->timestamps();

            $table->foreign('element_has_position_rule_chimical_element_id', 'ehprced_parent_fk')
                ->references('id')
                ->on('element_has_position_rule_chimical_elements')
                ->onDelete('cascade');
        });

        Schema::create('element_has_position_rule_chimical_element_detail_effects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('element_has_position_rule_chimical_element_detail_id');
            $table->integer('type');
            $table->unsignedBigInteger('gene_id');
            $table->integer('value')->default(0);
            $table->integer('duration')->default(0);
            $table->timestamps();

            $table->foreign('element_has_position_rule_chimical_element_detail_id', 'ehprceded_parent_fk')
                ->references('id')
                ->on('element_has_position_rule_chimical_element_details')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('element_has_position_rule_chimical_element_detail_effects');
        Schema::dropIfExists('element_has_position_rule_chimical_element_details');
        Schema::dropIfExists('element_has_position_rule_chimical_elements');
    }
};
