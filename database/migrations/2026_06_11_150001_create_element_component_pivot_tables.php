<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('element_component_has_genes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('element_component_id');
            $table->unsignedBigInteger('gene_id');
            $table->integer('value')->nullable();
            $table->timestamps();
            $table->foreign('element_component_id', 'elc_has_genes_elc_fk')->references('id')->on('element_components')->onDelete('cascade');
            $table->foreign('gene_id', 'elc_has_genes_gene_fk')->references('id')->on('genes')->onDelete('cascade');
        });

        Schema::create('element_component_has_rule_chimical_elements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('element_component_id');
            $table->unsignedBigInteger('rule_chimical_element_id');
            $table->timestamps();
            $table->foreign('element_component_id', 'elc_has_rules_elc_fk')->references('id')->on('element_components')->onDelete('cascade');
            $table->foreign('rule_chimical_element_id', 'elc_has_rules_rule_fk')->references('id')->on('rule_chimical_elements')->onDelete('cascade');
        });
    }
    public function down(): void {
        Schema::dropIfExists('element_component_has_rule_chimical_elements');
        Schema::dropIfExists('element_component_has_genes');
    }
};
