<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('element_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('element_id');
            $table->string('detailable_type');
            $table->unsignedBigInteger('detailable_id');
            $table->timestamps();

            $table->foreign('element_id')->references('id')->on('elements')->onDelete('cascade');
            $table->index(['detailable_type', 'detailable_id']);
        });

        Schema::create('element_detail_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('element_detail_id');
            $table->string('key');
            $table->string('value');
            $table->timestamps();

            $table->foreign('element_detail_id')->references('id')->on('element_details')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('element_detail_data');
        Schema::dropIfExists('element_details');
    }
};
