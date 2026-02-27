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
        Schema::create('neuron_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_neuron_id')->constrained('neurons')->cascadeOnDelete();
            $table->foreignId('to_neuron_id')->constrained('neurons')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['from_neuron_id', 'to_neuron_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('neuron_links');
    }
};

