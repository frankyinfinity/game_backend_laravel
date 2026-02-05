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
            $table->foreignId('element_has_position_id')->constrained();
            $table->foreignId('gene_id')->constrained();
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
