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
        Schema::create('rule_chimical_elements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chimical_element_id')->nullable();
            $table->unsignedBigInteger('complex_chimical_element_id')->nullable();
            $table->integer('min')->default(0);
            $table->integer('max')->default(0);
            $table->timestamps();

            $table->foreign('chimical_element_id', 'rule_chem_elem_fk')
                ->references('id')
                ->on('chimical_elements')
                ->onDelete('cascade');
            $table->foreign('complex_chimical_element_id', 'rule_complex_chem_elem_fk')
                ->references('id')
                ->on('complex_chimical_elements')
                ->onDelete('cascade');

            $table->rawIndex('(chimical_element_id IS NOT NULL OR complex_chimical_element_id IS NOT NULL)', 'rule_chem_elem_one_required');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rule_chimical_elements');
    }
};
