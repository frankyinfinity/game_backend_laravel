<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('element_body_zones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('element_body_id');
            $table->string('name');
            $table->string('color')->nullable();
            $table->timestamps();

            $table->foreign('element_body_id')->references('id')->on('element_bodies')->onDelete('cascade');
        });

        Schema::create('element_body_zone_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('element_body_zone_id');
            $table->integer('x');
            $table->integer('y');
            $table->timestamps();

            $table->foreign('element_body_zone_id', 'elb_zone_details_zone_fk')
                ->references('id')->on('element_body_zones')->onDelete('cascade');
        });

        Schema::create('element_body_zone_pixels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('element_body_zone_id');
            $table->integer('x');
            $table->integer('y');
            $table->timestamps();

            $table->foreign('element_body_zone_id', 'elb_zone_pixels_zone_fk')
                ->references('id')->on('element_body_zones')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('element_body_zone_pixels');
        Schema::dropIfExists('element_body_zone_details');
        Schema::dropIfExists('element_body_zones');
    }
};
