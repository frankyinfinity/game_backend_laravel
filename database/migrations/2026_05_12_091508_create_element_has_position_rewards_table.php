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
        Schema::create('element_has_position_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('element_has_position_id')
                ->constrained('element_has_positions')
                ->onDelete('cascade')
                ->name('ehp_reward_pos_fk');
            $table->foreignId('gene_id')->constrained()->onDelete('cascade');
            $table->integer('effect_id'); // Value from element_has_genes.effect
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('element_has_position_rewards');
    }
};
