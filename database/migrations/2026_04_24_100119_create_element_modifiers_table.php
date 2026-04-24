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
        Schema::create('element_modifiers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('element_has_position_id');
            $table->unsignedBigInteger('element_has_position_information_id')->nullable();
            $table->unsignedBigInteger('effect_id')->nullable();
            $table->timestamps();

            $table->foreign('element_has_position_id')->references('id')->on('element_has_positions')->onDelete('cascade');
            $table->foreign('element_has_position_information_id', 'em_information_fk')->references('id')->on('element_has_position_information')->onDelete('cascade');
            $table->foreign('effect_id', 'em_effect_fk')->references('id')->on('element_has_position_rule_chimical_element_detail_effects')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('element_modifiers');
    }
};
