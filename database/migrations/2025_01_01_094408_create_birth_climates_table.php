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
        Schema::create('birth_climates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('started');
            $table->integer('min_temperature');
            $table->integer('max_temperature');
            $table->unsignedBigInteger('default_tile_id');
            $table->foreign('default_tile_id')->references('id')->on('tiles');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('birth_climates');
    }
};
