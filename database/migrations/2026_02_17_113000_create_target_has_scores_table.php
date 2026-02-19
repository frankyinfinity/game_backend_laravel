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
        Schema::create('target_has_scores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('target_id');
            $table->unsignedBigInteger('score_id');
            $table->integer('value');
            
            $table->foreign('target_id')->references('id')->on('targets')->onDelete('cascade');
            $table->foreign('score_id')->references('id')->on('scores')->onDelete('cascade');
            
            // Vincolo di unicità per score_id e target_id
            $table->unique(['target_id', 'score_id']);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('target_has_scores');
    }
};
