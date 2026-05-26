<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix morph type format to use backslashes for polymorphic relationships
        DB::table('entity_anchors')
            ->where('anchorable_type', 'AppModelsEntityBody')
            ->update(['anchorable_type' => 'App\Models\EntityBody']);

        DB::table('entity_anchors')
            ->where('anchorable_type', 'AppModelsEntityComponent')
            ->update(['anchorable_type' => 'App\Models\EntityComponent']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to old format without backslashes
        DB::table('entity_anchors')
            ->where('anchorable_type', 'App\Models\EntityBody')
            ->update(['anchorable_type' => 'AppModelsEntityBody']);

        DB::table('entity_anchors')
            ->where('anchorable_type', 'App\Models\EntityComponent')
            ->update(['anchorable_type' => 'AppModelsEntityComponent']);
    }
};
