<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('birth_region_detail_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('birth_region_detail_id');
            $table->foreign('birth_region_detail_id')->references('id')->on('birth_region_details')->onDelete('cascade');
            $table->json('json_chimical_element')->nullable();
            $table->json('json_complex_chimical_element')->nullable();
            $table->integer('quantity');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('birth_region_detail_data');
    }
};
