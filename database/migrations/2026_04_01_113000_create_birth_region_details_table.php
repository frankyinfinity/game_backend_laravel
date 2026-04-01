<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('birth_region_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('birth_region_id');
            $table->foreign('birth_region_id')->references('id')->on('birth_regions')->onDelete('cascade');
            $table->integer('tile_i');
            $table->integer('tile_j');
            $table->json('json_tile')->nullable();
            $table->json('json_generator')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('birth_region_details');
    }
};
