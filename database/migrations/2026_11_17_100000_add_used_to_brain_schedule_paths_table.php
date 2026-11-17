<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Flag 'used': il container element lo setta a true (API brain_flow/path_used)
     * quando il flusso del brain attraversa un neurone END appartenente a quel
     * path — indica il percorso effettivamente eseguito.
     */
    public function up(): void
    {
        Schema::table('brain_schedule_paths', function (Blueprint $table) {
            $table->boolean('used')->default(false)->index();
        });
    }

    public function down(): void
    {
        Schema::table('brain_schedule_paths', function (Blueprint $table) {
            $table->dropIndex(['used']);
            $table->dropColumn('used');
        });
    }
};
