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
        Schema::create('element_has_genes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('element_id')->constrained()->onDelete('cascade');
            $table->foreignId('gene_id')->constrained()->onDelete('cascade');
            $table->integer('effect')->default(0); // Positivo o negativo
            
            $table->unique(['element_id', 'gene_id']); // Un gene può avere un solo effetto per elemento
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('element_has_genes');
    }
};
