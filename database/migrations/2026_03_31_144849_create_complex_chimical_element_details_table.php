<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complex_chimical_element_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('complex_chimical_element_id');
            $table->unsignedBigInteger('chimical_element_id');
            $table->integer('quantity');
            $table->timestamps();

            $table->foreign('complex_chimical_element_id', 'cce_details_cce_id_fk')->references('id')->on('complex_chimical_elements')->onDelete('cascade');
            $table->foreign('chimical_element_id', 'cce_details_ce_id_fk')->references('id')->on('chimical_elements')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complex_chimical_element_details');
    }
};
