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
        Schema::create('player_modifiers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('player_id');
            $table->unsignedBigInteger('genome_id')->nullable();
            $table->unsignedBigInteger('effect_id')->nullable();
            $table->timestamps();
            
            $table->foreign('player_id')->references('id')->on('players')->onDelete('cascade');
            $table->foreign('genome_id')->references('id')->on('genomes')->onDelete('cascade');
            $table->foreign('effect_id')->references('id')->on('player_rule_chimical_element_detail_effects')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_modifiers');
    }
};
