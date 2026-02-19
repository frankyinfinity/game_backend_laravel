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
        Schema::create('age_player', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->onDelete('cascade');
            $table->foreignId('age_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->integer('order')->default(1);
            $table->string('state')->default('locked');
            $table->timestamps();
            
            // Ensure unique combination of player_id and age_id
            $table->unique(['player_id', 'age_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('age_player');
    }
};
