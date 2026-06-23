<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('element_components', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('element_type_component_id')->nullable();
            $table->foreign('element_type_component_id')->references('id')->on('element_type_components')->onDelete('set null');
            $table->string('name');
            $table->string('image')->nullable();
            $table->integer('state')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('element_components');
    }
};
