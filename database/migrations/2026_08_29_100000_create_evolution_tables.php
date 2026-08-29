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
        Schema::create('evolution_paths', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('specie_id');
            $table->string('uid');
            $table->string('imagename');
            $table->boolean('finish')->default(false);
            $table->timestamps();

            $table->foreign('specie_id')->references('id')->on('species');
        });

        Schema::create('evolution_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('evolution_path_id');
            $table->string('uid');
            $table->string('imagename');
            $table->boolean('finish')->default(false);
            $table->timestamps();

            $table->foreign('evolution_path_id')->references('id')->on('evolution_paths')->onDelete('cascade');
        });

        Schema::create('evolution_step_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('evolution_step_id');
            $table->string('key');
            $table->string('value');
            $table->timestamps();

            $table->foreign('evolution_step_id')->references('id')->on('evolution_steps')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evolution_step_details');
        Schema::dropIfExists('evolution_steps');
        Schema::dropIfExists('evolution_paths');
    }
};