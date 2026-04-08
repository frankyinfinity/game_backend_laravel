<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rule_chimical_element_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rule_chimical_element_id');
            $table->integer('min')->default(0);
            $table->integer('max')->default(0);
            $table->string('color', 7)->default('#000000');
            $table->timestamps();

            $table->foreign('rule_chimical_element_id', 'rule_chem_elem_det_fk')
                ->references('id')
                ->on('rule_chimical_elements')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rule_chimical_element_details');
    }
};