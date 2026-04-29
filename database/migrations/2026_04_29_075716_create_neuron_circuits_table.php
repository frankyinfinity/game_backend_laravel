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
        Schema::create('neuron_circuits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('brain_id')->index();
            $table->string('uid')->index();
            $table->string('state')->default('created')->index(); // created, closed
            $table->timestamps();

            $table->foreign('brain_id')->references('id')->on('brains')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('neuron_circuits');
    }
};
