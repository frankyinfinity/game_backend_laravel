<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('element_component_consumption_effects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('element_component_id');
            $table->unsignedBigInteger('gene_id');
            $table->integer('effect')->default(0);
            $table->timestamps();

            $table->foreign('element_component_id', 'elc_consumption_elc_fk')
                ->references('id')->on('element_components')->onDelete('cascade');
            $table->foreign('gene_id', 'elc_consumption_gene_fk')
                ->references('id')->on('genes')->onDelete('cascade');
            $table->unique(['element_component_id', 'gene_id'], 'elc_consumption_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('element_component_consumption_effects');
    }
};
