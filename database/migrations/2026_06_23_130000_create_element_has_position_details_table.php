<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('element_has_position_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('element_has_position_id');
            $table->string('detailable_type');
            $table->unsignedBigInteger('detailable_id');
            $table->timestamps();

            $table->foreign('element_has_position_id', 'ehp_details_ehp_fk')
                ->references('id')->on('element_has_positions')->onDelete('cascade');
            $table->index(['detailable_type', 'detailable_id'], 'ehp_details_detailable_idx');
        });

        Schema::create('element_has_position_detail_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('element_has_position_detail_id');
            $table->string('key');
            $table->string('value');
            $table->timestamps();

            $table->foreign('element_has_position_detail_id', 'ehp_detail_data_detail_fk')
                ->references('id')->on('element_has_position_details')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('element_has_position_detail_data');
        Schema::dropIfExists('element_has_position_details');
    }
};
