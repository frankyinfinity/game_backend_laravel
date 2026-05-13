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
        Schema::create('birth_region_diffusion_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('birth_region_diffusion_id')->constrained('birth_region_diffusions')->onDelete('cascade');
            $table->json('json_chimical_element')->nullable();
            $table->json('json_complex_chimical_element')->nullable();
            $table->integer('from');
            $table->integer('to');
            $table->timestamps();


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('birth_region_diffusion_details');
    }
};
