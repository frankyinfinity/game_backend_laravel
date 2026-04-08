<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rule_chimical_element_detail_effects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rule_chimical_element_detail_id');
            $table->integer('type');
            $table->unsignedBigInteger('gene_id');
            $table->integer('value')->default(0);
            $table->timestamps();

            $table->foreign('rule_chimical_element_detail_id', 'rule_chem_elem_det_eff_det_fk')
                ->references('id')
                ->on('rule_chimical_element_details')
                ->onDelete('cascade');
                
            $table->foreign('gene_id', 'rule_chem_elem_det_eff_gene_fk')
                ->references('id')
                ->on('genes')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rule_chimical_element_detail_effects');
    }
};
