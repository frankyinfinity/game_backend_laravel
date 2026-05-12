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
        Schema::create('family_tile_limits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('family_tile_id');
            $table->foreign('family_tile_id')->references('id')->on('family_tiles')->onDelete('cascade');
            $table->unsignedBigInteger('chimical_element_id')->nullable();
            $table->foreign('chimical_element_id')->references('id')->on('chimical_elements')->onDelete('cascade');
            $table->unsignedBigInteger('complex_chimical_element_id')->nullable();
            $table->foreign('complex_chimical_element_id')->references('id')->on('complex_chimical_elements')->onDelete('cascade');
            $table->integer('limit_value');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('family_tile_limits');
    }
};
