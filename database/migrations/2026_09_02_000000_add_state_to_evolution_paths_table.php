<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Aggiunge lo stato alla EvolutionPath:
     * 0 = created (path creata), 1 = ready (tutti gli EvolutionStep creati).
     */
    public function up(): void
    {
        Schema::table('evolution_paths', function (Blueprint $table) {
            $table->tinyInteger('state')->default(0)->after('finish');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evolution_paths', function (Blueprint $table) {
            $table->dropColumn('state');
        });
    }
};