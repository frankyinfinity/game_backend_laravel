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
        Schema::create('birth_regions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('birth_planet_id');
            $table->foreign('birth_planet_id')->references('id')->on('birth_planets');
            $table->unsignedBigInteger('birth_climate_id');
            $table->foreign('birth_climate_id')->references('id')->on('birth_climates');
            $table->string('name');
            $table->integer('width');
            $table->integer('height');
            $table->longText('description')->nullable();   
            $table->string('filename')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('birth_regions');
    }
};
