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
        Schema::create('element_informations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('element_id')->constrained()->onDelete('cascade');
            $table->foreignId('gene_id')->constrained()->onDelete('cascade');
            $table->integer('min_value')->default(0);
            $table->integer('max_value')->default(0);
            $table->timestamps();
            
            $table->unique(['element_id', 'gene_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('element_informations');
    }
};
