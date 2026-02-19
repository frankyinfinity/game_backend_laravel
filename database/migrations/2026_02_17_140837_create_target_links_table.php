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
        Schema::create('target_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_target_id')->constrained('targets')->onDelete('cascade');
            $table->foreignId('to_target_id')->constrained('targets')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('target_links');
    }
};
