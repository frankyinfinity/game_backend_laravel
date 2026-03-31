<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generator_chimical_elements', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('chimical_element_id');
            $table->integer('tick_quantity');
            $table->timestamps();

            $table->foreign('chimical_element_id', 'gce_ce_id_fk')->references('id')->on('chimical_elements')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generator_chimical_elements');
    }
};
