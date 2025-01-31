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
        Schema::create('genomes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entity_id');
            $table->foreign('entity_id')->references('id')->on('entities');
            $table->unsignedBigInteger('gene_id');
            $table->foreign('gene_id')->references('id')->on('genes');
            $table->integer('min');
            $table->integer('max');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('genomes');
    }
};
