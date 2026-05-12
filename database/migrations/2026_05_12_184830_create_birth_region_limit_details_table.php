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
        Schema::create('birth_region_limit_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('birth_region_limit_id');
            $table->foreign('birth_region_limit_id')->references('id')->on('birth_region_limits')->onDelete('cascade');
            $table->json('json_chimical_element')->nullable();
            $table->json('json_complex_chimical_element')->nullable();
            $table->integer('limit_value');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('birth_region_limit_details');
    }
};