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
        Schema::create('entity_component_has_genes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entity_component_id');
            $table->unsignedBigInteger('gene_id');
            $table->timestamps();

            $table->foreign('entity_component_id', 'ec_has_genes_ec_fk')->references('id')->on('entity_components')->onDelete('cascade');
            $table->foreign('gene_id', 'ec_has_genes_gene_fk')->references('id')->on('genes')->onDelete('cascade');
        });

        Schema::create('entity_component_has_rule_chimical_elements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entity_component_id');
            $table->unsignedBigInteger('rule_chimical_element_id');
            $table->timestamps();

            $table->foreign('entity_component_id', 'ec_has_rules_ec_fk')->references('id')->on('entity_components')->onDelete('cascade');
            $table->foreign('rule_chimical_element_id', 'ec_has_rules_rule_fk')->references('id')->on('rule_chimical_elements')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entity_component_has_rule_chimical_elements');
        Schema::dropIfExists('entity_component_has_genes');
    }
};
