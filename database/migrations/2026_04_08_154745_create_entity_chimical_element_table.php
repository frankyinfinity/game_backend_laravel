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
        Schema::create('entity_chimical_elements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entity_id');
            $table->unsignedBigInteger('player_rule_chimical_element_id');
            $table->integer('value')->default(0);
            $table->timestamps();
            
            $table->foreign('entity_id')->references('id')->on('entities')->onDelete('cascade');
            $table->foreign('player_rule_chimical_element_id')->references('id')->on('player_rule_chimical_elements')->onDelete('cascade');
            $table->index('entity_id');
            $table->index('player_rule_chimical_element_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entity_chimical_elements');
    }
};
