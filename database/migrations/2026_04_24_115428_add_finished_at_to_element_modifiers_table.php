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
        Schema::table('element_modifiers', function (Blueprint $table) {
            $table->timestamp('finished_at')->nullable()->after('effect_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('element_modifiers', function (Blueprint $table) {
            $table->dropColumn('finished_at');
        });
    }
};
