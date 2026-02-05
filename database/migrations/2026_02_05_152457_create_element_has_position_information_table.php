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
        Schema::create('element_has_position_information', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('element_has_position_id');
            $table->foreign('element_has_position_id')->references('id')->on('element_has_positions');
            $table->unsignedBigInteger('gene_id');
            $table->foreign('gene_id')->references('id')->on('genes');
            $table->integer('min')->default(0);
            $table->integer('max')->default(0);
            $table->integer('value')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('element_has_position_information');
    }
};
