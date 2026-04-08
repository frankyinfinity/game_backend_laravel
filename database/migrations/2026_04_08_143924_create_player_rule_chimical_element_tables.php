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
        Schema::create('player_rule_chimical_elements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('player_id');
            $table->unsignedBigInteger('chimical_element_id')->nullable();
            $table->unsignedBigInteger('complex_chimical_element_id')->nullable();
            $table->integer('min')->default(0);
            $table->integer('max')->default(0);
            $table->string('title')->nullable();
            $table->timestamps();
            
            $table->foreign('player_id', 'prce_player_fk')->references('id')->on('players')->onDelete('cascade');
            $table->index('player_id');
        });

        Schema::create('player_rule_chimical_element_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('player_rule_chimical_element_id');
            $table->integer('min')->default(0);
            $table->integer('max')->default(0);
            $table->string('color', 7)->default('#000000');
            $table->timestamps();
            
            $table->foreign('player_rule_chimical_element_id', 'prced_parent_fk')->references('id')->on('player_rule_chimical_elements')->onDelete('cascade');
        });

        Schema::create('player_rule_chimical_element_detail_effects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('player_rule_chimical_element_detail_id');
            $table->integer('type');
            $table->unsignedBigInteger('gene_id');
            $table->integer('value')->default(0);
            $table->timestamps();
            
            $table->foreign('player_rule_chimical_element_detail_id', 'prceded_parent_fk')->references('id')->on('player_rule_chimical_element_details')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_rule_chimical_element_detail_effects');
        Schema::dropIfExists('player_rule_chimical_element_details');
        Schema::dropIfExists('player_rule_chimical_elements');
    }
};
