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
        Schema::dropIfExists('family_tile_diffusions');
        Schema::create('family_tile_diffusions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_tile_id')->constrained('family_tiles')->onDelete('cascade');
            $table->foreignId('chimical_element_id')->nullable()->constrained('chimical_elements')->onDelete('cascade');
            $table->foreignId('complex_chimical_element_id')->nullable()->constrained('complex_chimical_elements')->onDelete('cascade');
            $table->integer('from');
            $table->integer('to');
            $table->timestamps();

            $table->unique(['family_tile_id', 'chimical_element_id'], 'ftd_chimical_unique');
            $table->unique(['family_tile_id', 'complex_chimical_element_id'], 'ftd_complex_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('family_tile_diffusions');
    }
};
