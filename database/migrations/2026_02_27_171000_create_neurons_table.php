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
        Schema::create('neurons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brain_id')->constrained('brains')->cascadeOnDelete();
            $table->string('type');
            $table->unsignedInteger('grid_i');
            $table->unsignedInteger('grid_j');
            $table->unsignedInteger('radius')->nullable();
            $table->timestamps();

            $table->unique(['brain_id', 'grid_i', 'grid_j']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('neurons');
    }
};

