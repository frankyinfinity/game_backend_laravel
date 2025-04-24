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
        Schema::dropIfExists('players');
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->unsignedBigInteger('birth_planet_id');
            $table->foreign('birth_planet_id')->references('id')->on('birth_planets');
            $table->unsignedBigInteger('birth_region_id');
            $table->foreign('birth_region_id')->references('id')->on('birth_regions');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('players');
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->unsignedBigInteger('birth_planet_id');
            $table->foreign('birth_planet_id')->references('id')->on('planets');
            $table->unsignedBigInteger('birth_region_id');
            $table->foreign('birth_region_id')->references('id')->on('regions');
            $table->timestamps();
        });
    }
};
