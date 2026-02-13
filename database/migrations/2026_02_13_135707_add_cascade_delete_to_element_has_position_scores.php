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
        Schema::table('element_has_position_scores', function (Blueprint $table) {
             // Drop the existing foreign key
            $table->dropForeign(['element_has_position_id']);
            // Re-add with cascade delete
            $table->foreign('element_has_position_id')
                  ->references('id')
                  ->on('element_has_positions')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('element_has_position_scores', function (Blueprint $table) {
            $table->dropForeign(['element_has_position_id']);
            $table->foreign('element_has_position_id')
                  ->references('id')
                  ->on('element_has_positions');
        });
    }
};
