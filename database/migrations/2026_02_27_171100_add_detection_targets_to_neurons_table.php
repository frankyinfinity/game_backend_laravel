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
        Schema::table('neurons', function (Blueprint $table) {
            $table->string('target_type')->nullable()->after('radius');
            $table->foreignId('target_element_id')->nullable()->after('target_type')->constrained('elements')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('neurons', function (Blueprint $table) {
            $table->dropForeign(['target_element_id']);
            $table->dropColumn(['target_type', 'target_element_id']);
        });
    }
};
